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

use Drupal\auth_apic\JWTToken;
use Drupal\auth_apic\UserManagerResponse;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface;
use Drupal\ibm_apim\UserManagement\ApicAccountInterface;
use Psr\Log\LoggerInterface;

class ApicInvitationService implements ApicInvitationInterface {

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
      if (!isset($invitationResponse)) {

        $this->logger->error('Error during account registration: Failed to validate token @tokenUrl', ['@tokenUrl' => $token->getUrl()]);

        $userMgrResponse->setMessage(t('Error during account registration. Please contact your system administrator'));
        $userMgrResponse->setSuccess(FALSE);
      } else if ((int) $invitationResponse->getCode() === 201) {
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
    $userMgrResponse = new UserManagerResponse();

    $acceptingOrg = $acceptingUser->getOrganization();
    $isMemberInvitation = strpos($token->getUrl(), '/member-invitations/');
    if ($isMemberInvitation || $acceptingOrg !== NULL) {
      $invitationResponse = $this->mgmtServer->acceptInvite($token, $acceptingUser, $acceptingUser->getOrganization());

      if ($invitationResponse !== NULL && (int) $invitationResponse->getCode() === 201) {

        $this->logger->notice('invitation processed for @username', [
          '@username' => $acceptingUser->getUsername(),
        ]);
        if ($isMemberInvitation) {
          $userMgrResponse->setMessage(t('Invitation process complete.'));
        } else {
          $userMgrResponse->setMessage(t('Invitation process complete. Please login to continue.'));
        }
        $userMgrResponse->setSuccess(TRUE);
      }
      else {
        $this->logger->error('Error during acceptInvite:  @error', ['@error' => $invitationResponse->getErrors()[0]]);

        $userMgrResponse->setMessage(t('Error while accepting invitation: @error', ['@error' => $invitationResponse->getErrors()[0]]));
        $userMgrResponse->setSuccess(FALSE);
      }
    } else {
      $this->logger->error('Error during acceptInvite:  @error', ['@error' => 'The user does not have a consumer organization']);

      $userMgrResponse->setMessage(t('Error while accepting invitation: @error', ['@error' => 'The user does not have a consumer organization']));
      $userMgrResponse->setSuccess(FALSE);
    }
    $userMgrResponse->setRedirect('<front>');

    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $userMgrResponse);
    }
    return $userMgrResponse;
  }

}
