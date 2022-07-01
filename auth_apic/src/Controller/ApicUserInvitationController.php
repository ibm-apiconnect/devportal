<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2022
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\auth_apic\Controller;

use Drupal\auth_apic\Service\Interfaces\TokenParserInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface;
use Drupal\ibm_apim\Service\SiteConfig;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ApicUserInvitationController extends ControllerBase
{

  // session store for invitation token will use a short lifetime - 15 minutes
  public const INVITATION_COOKIE_TIMEOUT = 900;

  /**
   * @var \Drupal\auth_apic\Service\Interfaces\TokenParserInterface
   */
  protected TokenParserInterface $jwtParser;

  /**
   * @var \Drupal\ibm_apim\Service\SiteConfig
   */
  protected SiteConfig $siteConfig;

  /**
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected PrivateTempStore $sessionStore;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface
   */
  protected ApicUserStorageInterface $userStorage;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected Config $system_site_config;

  /**
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * ApicUserInvitationController constructor.
   *
   * @param \Drupal\auth_apic\Service\Interfaces\TokenParserInterface $tokenParser
   * @param \Drupal\ibm_apim\Service\SiteConfig $site_config
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   * @param \Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface $user_storage
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   * @param \Drupal\Core\Config\Config $system_site_config
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   */
  public function __construct(TokenParserInterface $tokenParser,
                              SiteConfig $site_config,
                              PrivateTempStoreFactory $tempStoreFactory,
                              ApicUserStorageInterface $user_storage,
                              LoggerInterface $logger,
                              AccountProxyInterface $current_user,
                              ModuleHandlerInterface $moduleHandler,
                              Config $system_site_config,
                              MessengerInterface $messenger) {
    $this->jwtParser = $tokenParser;
    $this->siteConfig = $site_config;
    $this->sessionStore = $tempStoreFactory->get('auth_apic_storage', self::INVITATION_COOKIE_TIMEOUT);
    $this->userStorage = $user_storage;
    $this->logger = $logger;
    $this->currentUser = $current_user;
    $this->moduleHandler = $moduleHandler;
    $this->system_site_config = $system_site_config;
    $this->messenger = $messenger;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return \Drupal\auth_apic\Controller\ApicUserInvitationController|static
   */
  public static function create(ContainerInterface $container): ApicUserInvitationController {
    /** @noinspection PhpParamsInspection */
    return new static(
      $container->get('auth_apic.jwtparser'),
      $container->get('ibm_apim.site_config'),
      $container->get('tempstore.private'),
      $container->get('ibm_apim.user_storage'),
      $container->get('logger.channel.auth_apic'),
      $container->get('current_user'),
      $container->get('module_handler'),
      $container->get('config.factory')->get('system.site'),
      $container->get('messenger')
    );
  }

  /**
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function process(): ?RedirectResponse {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $invitationToken = \Drupal::request()->query->get('activation');
    if (empty($invitationToken)) {
      $this->messenger->addError(t('Missing invitation token. Unable to proceed.'));
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, 'invitation error: no token.');
      return $this->redirect('<front>');
    }

    $jwt = $this->jwtParser->parse($invitationToken);
    if ($jwt === NULL || $jwt->getUrl() === NULL) {
      $this->messenger->addError(t('Invalid invitation token. Contact the system administrator for assistance'));
      $this->logger->notice('Invalid invitation Token: %token', ['%token' => $jwt]);
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, 'invitation error: no url to activate in token.');
      return $this->redirect('<front>');
    }

    // if the user is logged in then we can't proceed with the accepting of any invitation.
    if ($this->currentUser->isAuthenticated()) {
      $logout_link = Link::fromTextAndUrl(t('log out'), Url::fromRoute('user.logout'))->toString();
      $this->messenger->addError(t('Unable to complete the invitation process as you are logged in. Please @logout and click on the invitation link again to complete the invitation process.', ['@logout' => $logout_link]));
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, 'authenticated->request to logout');
      return $this->redirect('<front>');
    }

    // The idea here is that we already have user registration and sign-in forms
    // and we want to re-use those rather than creating essentially duplicates
    // for this slightly different flow. So we set a session variable here to signal
    // that we are on this invited user flow so that the reused sign-in and create
    // account forms know to behave differently.
    $this->sessionStore->set('invitation_object', $jwt);

    // check the user email address and attempt to find a matching local account
    $invited_email = $jwt->getPayload()['email'];
    $existing_account = $this->userStorage->loadUserByEmailAddress($invited_email);

    // redirect based on whether we think this user has an account or needs to register
    if (isset($existing_account)) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, 'invitation of existing user');
      return $this->redirect('user.login', ['token' => $invitationToken]);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, 'invitation of new user');
    return $this->redirect('user.register', ['token' => $invitationToken]);

  }

}
