<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2024
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\auth_apic\UserManagement;


use Drupal\auth_apic\UserManagerResponse;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface;
use Psr\Log\LoggerInterface;
use Drupal\ibm_apim\Service\SiteConfig;

class NonUserManagedSignUp implements SignUpInterface {

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface
   */
  private ManagementServerInterface $mgmtServer;

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
                              LoggerInterface $logger,
                              SiteConfig $site_config) {
    $this->mgmtServer = $mgmt_interface;
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

    $mgmtResponse = $this->mgmtServer->getAuth($user);

    $userManagerResponse = new UserManagerResponse();

    if ($mgmtResponse && ($mgmtResponse->getCode() === 200 || $mgmtResponse->getCode() === 201)) {
      // For !user_managed registries there is no real sign up process so this is just an authentication check.
      // If we have a response then all is good, report this and inform the user to sign in.
      $userManagerResponse->setSuccess(TRUE);
      $this->logger->notice('non user managed sign-up processed for @username', [
        '@username' => $user->getUsername(),
      ]);

      if ($this->siteConfig->isAccountapprovalsEnabled()) {
        $userManagerResponse->setMessage(t('Your account was created successfully and is pending approval. You will receive an email with further instructions.'));
      } else {
        $userManagerResponse->setMessage(t('Your account was created successfully. You may now sign in.'));
      }
    }
    else {
      $userManagerResponse->setSuccess(FALSE);
      $this->logger->error('error during sign-up process, no token retrieved.');

      $userManagerResponse->setMessage(t('There was an error creating your account. Please contact the system administrator.'));
    }
    $userManagerResponse->setRedirect('<front>');

    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $userManagerResponse);
    }
    return $userManagerResponse;
  }

}
