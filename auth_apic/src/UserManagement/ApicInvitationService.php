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
use Drupal\auth_apic\UserManagerResponse;
use Drupal\ibm_apim\UserManagement\ApicAccountInterface;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface;
use Psr\Log\LoggerInterface;

class ApicInvitationService implements ApicInvitationInterface {

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface
   */
  private $mgmtServer;

  /**
   * @var \Drupal\ibm_apim\UserManagement\ApicAccountInterface
   */
  private $accountService;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  public function __construct(ManagementServerInterface $mgmt_server,
                              ApicAccountInterface $account_service,
                              LoggerInterface $logger) {
    $this->mgmtServer = $mgmt_server;
    $this->accountService = $account_service;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function registerInvitedUser(JWTToken $token, ApicUser $invitedUser = NULL): UserManagerResponse {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $userMgrResponse = new UserManagerResponse();

    if ($invitedUser !== NULL) {
      $invitationResponse = $this->mgmtServer->orgInvitationsRegister($token, $invitedUser);

      if ((int) $invitationResponse->getCode() === 201) {
        $invitedUser->setState('enabled');
        $this->accountService->registerApicUser($invitedUser);

        $this->logger->notice('invitation processed for @username', [
          '@username' => $invitedUser->getUsername(),
        ]);

        $userMgrResponse->setMessage(t('Invitation process complete. Please login to continue.'));
        $userMgrResponse->setSuccess(TRUE);
        $userMgrResponse->setRedirect('<front>');
      }
      else {

        $this->logger->error('Error during account registration: @error', ['@error' => $invitationResponse->getErrors()[0]]);

        $userMgrResponse->setMessage(t('Error during account registration: @error', ['@error' => $invitationResponse->getErrors()[0]]));
        $userMgrResponse->setSuccess(FALSE);
      }
    }
    else {
      $userMgrResponse = new UserManagerResponse();
      $this->logger->error('Error during account registration: invitedUser was null');

      $userMgrResponse->setMessage(t('Error during account registration: invitedUser was null'));
      $userMgrResponse->setSuccess(FALSE);
      $userMgrResponse->setRedirect('<front>');
    }

    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $userMgrResponse);
    }
    return $userMgrResponse;

  }

  /**
   * {@inheritdoc}
   */
  public function acceptInvite(JWTToken $token, ApicUser $acceptingUser): UserManagerResponse {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $invitationResponse = $this->mgmtServer->acceptInvite($token, $acceptingUser, $acceptingUser->getOrganization());
    $userMgrResponse = new UserManagerResponse();

    if ((int) $invitationResponse->getCode() === 201) {

      $this->logger->notice('invitation processed for @username', [
        '@username' => $acceptingUser->getUsername(),
      ]);

      $userMgrResponse->setMessage(t('Invitation process complete. Please login to continue.'));
      $userMgrResponse->setSuccess(TRUE);
      $userMgrResponse->setRedirect('<front>');
    }
    else {
      $this->logger->error('Error during acceptInvite:  @error', ['@error' => $invitationResponse->getErrors()[0]]);

      $userMgrResponse->setMessage(t('Error while accepting invitation: @error', ['@error' => $invitationResponse->getErrors()[0]]));
      $userMgrResponse->setSuccess(FALSE);
      $userMgrResponse->setRedirect('<front>');
    }

    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $userMgrResponse);
    }
    return $userMgrResponse;
  }

}
