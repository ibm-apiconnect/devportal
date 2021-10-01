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

use Drupal\auth_apic\UserManagerResponse;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface;
use Drupal\ibm_apim\UserManagement\ApicAccountInterface;
use Psr\Log\LoggerInterface;
use Drupal\ibm_apim\Service\SiteConfig;

class UserManagedSignUp implements SignUpInterface {

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface
   */
  private ManagementServerInterface $mgmtServer;

  /**
   * @var \Drupal\ibm_apim\UserManagement\ApicAccountInterface
   */
  private ApicAccountInterface $accountService;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  private LoggerInterface $logger;

  /**
   * @var \Drupal\ibm_apim\Service\SiteConfig
   */
  private SiteConfig $siteConfig;

  /**
   * @inheritDoc
   */
  public function __construct(ManagementServerInterface $mgmt_interface,
                              ApicAccountInterface $account_service,
                              LoggerInterface $logger,
                              SiteConfig $site_config) {
    $this->mgmtServer = $mgmt_interface;
    $this->accountService = $account_service;
    $this->logger = $logger;
    $this->siteConfig = $site_config;
  }


  /**
   * @inheritDoc
   */
  public function signUp(ApicUser $user): UserManagerResponse {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    $mgmtResponse = $this->mgmtServer->postSignUp($user);
    $userManagerResponse = new UserManagerResponse();

    if ($mgmtResponse !== NULL && ((int) $mgmtResponse->getCode() === 201 || (int) $mgmtResponse->getCode() === 204)) {
      // user will need to accept invitation so invite as pending.
      if ($this->siteConfig->isAccountapprovalsEnabled()) {
        $user->setState('pending_approval');
      } else {
        $user->setState('pending');
      }
      $register_response = $this->accountService->registerApicUser($user);

      if ($register_response !== NULL) {
        $userManagerResponse->setSuccess(TRUE);

        $this->logger->notice('sign-up processed for @username', [
          '@username' => $user->getUsername(),
        ]);
        if ($this->siteConfig->isAccountapprovalsEnabled()) {
          $userManagerResponse->setMessage(t('Your account was created successfully and is pending approval. You will receive an email with further instructions.'));
        } else {
          $userManagerResponse->setMessage(t('Your account was created successfully. You will receive an email with activation instructions.'));
        }
      }
      else {
        $this->logger->error('error registering drupal account for @username', ['@username' => $user->getUsername()]);
        $userManagerResponse->setSuccess(FALSE);
        $userManagerResponse->setMessage(t('There was an error registering your account. Please contact your system administrator.'));
      }
    }
    else {
      $this->logger->error('unexpected management server response on sign up for @username', ['@username' => $user->getUsername()]);
      $userManagerResponse->setSuccess(FALSE);
      $userManagerResponse->setMessage(t('There was a problem registering your new account. Please contact your system administrator.'));
    }
    // regardless of success redirect to <front>
    $userManagerResponse->setRedirect('<front>');

    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $userManagerResponse);
    }
    return $userManagerResponse;
  }

}
