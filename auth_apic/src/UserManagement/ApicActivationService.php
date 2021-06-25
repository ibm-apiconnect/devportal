<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2021
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\auth_apic\UserManagement;


use Drupal\auth_apic\JWTToken;
use Drupal\Core\Config\Config;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Link;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface;
use Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface;
use Psr\Log\LoggerInterface;

class ApicActivationService implements ApicActivationInterface {

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface
   */
  private ManagementServerInterface $mgmtServer;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface
   */
  private ApicUserStorageInterface $userStorage;

  /**
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  private LinkGeneratorInterface $linkGenerator;

  /**
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  private MessengerInterface $messenger;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  private LoggerInterface $logger;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected Config $system_site_config;

  public function __construct(ManagementServerInterface $mgmt_server,
                       ApicUserStorageInterface $user_storage,
                       LinkGeneratorInterface $link_generator,
                       MessengerInterface $messenger,
                       LoggerInterface $logger,
                       ModuleHandlerInterface $moduleHandler) {
    $this->mgmtServer = $mgmt_server;
    $this->userStorage = $user_storage;
    $this->linkGenerator = $link_generator;
    $this->messenger = $messenger;
    $this->logger = $logger;
    $this->moduleHandler = $moduleHandler;
  }

  public function activate(JWTToken $jwt): bool {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    $result = FALSE;

    $mgmt_response = $this->mgmtServer->activateFromJWT($jwt);
    if (!isset($GLOBALS['__PHPUNIT_BOOTSTRAP']) && \Drupal::hasContainer()) {
      if ($this->moduleHandler->moduleExists('contact')) {
        $contact_link = Link::fromTextAndUrl(t('Contact the site administrator.'), Url::fromRoute('contact.site_page'));
      }
      else {
        $contact_link = Link::fromTextAndUrl(t('Contact the site administrator.'), Url::fromUri('mailto:' . \Drupal::config('system.site')
            ->get('mail')));
      }
    }
    else {
      $contact_link = Link::fromTextAndUrl(t('Contact the site administrator.'), Url::fromRoute('ibm_apim.support'));
    }
    $sign_in_link = $this->linkGenerator->generate(t('Sign In'), Url::fromRoute('user.login'));

    if ($mgmt_response->getCode() === 401) {
      $this->messenger->addError(t('There was an error while processing your activation. Has this activation link already been used?'));
      $this->logger->error('Error while processing user activation. Received response code \'@code\' from backend. 
        Message from backend was \'@message\'.', ['@code' => $mgmt_response->getCode(), '@message' => $mgmt_response->getErrors()[0]]);
    }
    elseif ($mgmt_response->getCode() !== 204) {
      $this->messenger->addError(t('There was an error while processing your activation. @contact_link', ['@contact_link' => $contact_link]));
      $this->logger->error('Error while processing user activation. Received response code \'@code\' from backend. 
        Message from backend was \'@message\'.', ['@code' => $mgmt_response->getCode(), '@message' => $mgmt_response->getErrors()[0]]);
    }
    else {
      // No problems from apim, so all is good.
      // If we have an account then we can activate it in our local database, otherwise this will be created on login.
      $user_mail = $jwt->getPayload()['email'];
      $account = $this->userStorage->loadUserByEmailAddress($user_mail);

      if (!$account) {
        $this->logger->warning('Processing user activation. Could not find account in our database for @mail, continuing as we will act on APIM data.',
          ['@mail' => $user_mail]);
      }
      else {
        // update the apic_state field to show this user is enabled. activate() sets the drupal status field.
        $account->set('apic_state', 'enabled');
        $account->activate();
        $account->save();
      }

      // direct the user to sign in!
      $this->messenger->addMessage(t('Your account has been activated. You can now @signin.', ['@signin' => $sign_in_link]));
      $result = TRUE;
    }
    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $result);
    }
    return $result;
  }

}
