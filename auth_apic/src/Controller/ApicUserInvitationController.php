<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2019
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\auth_apic\Controller;

use Drupal\auth_apic\Service\Interfaces\TokenParserInterface;
use Drupal\auth_apic\Service\Interfaces\UserManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\ibm_apim\Service\SiteConfig;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\session_based_temp_store\SessionBasedTempStoreFactory;

class ApicUserInvitationController extends ControllerBase {

  // session store for invitation token will use a short lifetime - 15 minutes
  const INVITATION_COOKIE_TIMEOUT = 900;

  protected $jwtParser;

  protected $siteConfig;

  protected $sessionStore;

  protected $userManager;

  protected $logger;

  public function __construct(TokenParserInterface $tokenParser,
                              SiteConfig $site_config,
                              SessionBasedTempStoreFactory $tempStoreFactory,
                              UserManagerInterface $user_manager,
                              LoggerInterface $logger) {
    $this->jwtParser = $tokenParser;
    $this->siteConfig = $site_config;
    $this->sessionStore = $tempStoreFactory->get('auth_apic_invitation_token', self::INVITATION_COOKIE_TIMEOUT);
    $this->userManager = $user_manager;
    $this->logger = $logger;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('auth_apic.jwtparser'),
      $container->get('ibm_apim.site_config'),
      $container->get('session_based_temp_store'),
      $container->get('auth_apic.usermanager'),
      $container->get('logger.channel.auth_apic')
    );
  }

  public function process(): ?\Symfony\Component\HttpFoundation\RedirectResponse {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $invitationToken = \Drupal::request()->query->get('activation');
    if (empty($invitationToken)) {
      drupal_set_message(t('Missing invitation token. Unable to proceed.'), 'error');
      return $this->redirect('<front>');
    }

    $jwt = $this->jwtParser->parse($invitationToken);
    if ($jwt === NULL || $jwt->getUrl() === NULL) {
      drupal_set_message(t('Invalid invitation token. Contact the system administrator for assistance'), 'error');
      $this->logger->notice('Invalid invitation Token: %token', ['%token' => $jwt]);
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      return $this->redirect('<front>');
    }

    // The idea here is that we already have user registration and sign-in forms
    // and we want to re-use those rather than creating essentially duplicates
    // for this slightly different flow. So we set a session variable here to signal
    // that we are on this invited user flow so that the reused sign-in and create
    // account forms know to behave differently.
    if ($jwt) {
      $this->sessionStore->set('invitation_object', $jwt);

      // check the user email address and attempt to find a matching local account
      $invited_email = $jwt->getPayload()['email'];
      $existing_account = $this->userManager->findUserInDatabase($invited_email);

      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      // redirect based on whether we think this user has an account or needs to register
      if (isset($existing_account) && $existing_account !== NULL) {
        return $this->redirect('user.login');
      }
      else {
        return $this->redirect('user.register');
      }
    }
    else {
      $contact_link = \Drupal::l(t('contact'), \Drupal\Core\Url::fromRoute('contact.site_page'));
      drupal_set_message(t('Unable to proceed with invitation process. @contact_link the site administrator.', ['@contact_link' => $contact_link]));
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      return $this->redirect('<front>');
    }


  }
}
