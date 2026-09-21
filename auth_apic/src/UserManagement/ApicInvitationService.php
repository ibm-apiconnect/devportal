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
use Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface;
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

  /**
   * @var \Drupal\ibm_apim\Service\ApicUserStorageInterface
   */
  private ApicUserStorageInterface $userStorage;

  public function __construct(ManagementServerInterface $mgmt_server,
                              ApicAccountInterface $account_service,
                              LoggerInterface $logger,
                              ApicUserStorageInterface $user_storage) {
    $this->mgmtServer = $mgmt_server;
    $this->accountService = $account_service;
    $this->logger = $logger;
    $this->userStorage = $user_storage;
  }

  /**
   * {@inheritdoc}
   */
  public function registerInvitedUser(JWTToken $token, ?ApicUser $invitedUser = NULL): UserManagerResponse {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $userMgrResponse = new UserManagerResponse();

    if ($invitedUser !== NULL) {
      $existingAccount = $this->userStorage->load($invitedUser);

      if ($existingAccount !== NULL) {
        $this->logger->notice('Re-invited user @username attempted registration but account exists', [
          '@username' => $invitedUser->getUsername(),
        ]);

        $userMgrResponse->setMessage(t('Unable to complete registration. If you have an existing account, try logging in instead.'));
        $userMgrResponse->setSuccess(FALSE);
        $userMgrResponse->setRedirect('user.login');
      }
      else {
        $invitationResponse = $this->mgmtServer->orgInvitationsRegister($token, $invitedUser);

        if (!isset($invitationResponse)) {
          $this->logger->error('Error during account registration: Failed to validate token @tokenUrl', ['@tokenUrl' => $token->getUrl()]);
          $userMgrResponse->setMessage(t('Error during account registration. Please contact your system administrator'));
          $userMgrResponse->setSuccess(FALSE);
        }
        else if ((int) $invitationResponse->getCode() === 201) {
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
          $errors = $invitationResponse->getErrors();
          $errorMessage = is_array($errors) && !empty($errors[0]) ? $errors[0] : t('Unknown registration error');

          $this->logger->error('Error during account registration: @error', ['@error' => $errorMessage]);

          $userMgrResponse->setMessage(t('Error during account registration: @error', ['@error' => $errorMessage]));
          $userMgrResponse->setSuccess(FALSE);
        }
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
    $isMemberInvitation = strpos($token->getUrl(), '/member-invitations/') !== FALSE;
    $isRegistrationInvitation = strpos($token->getUrl(), '/member-invitations/') === FALSE;
    if ($isMemberInvitation || $acceptingOrg !== NULL || $isRegistrationInvitation) {
      $invitationResponse = $this->mgmtServer->acceptInvite($token, $acceptingUser, $acceptingUser->getOrganization());

      if ($invitationResponse !== NULL && (int) $invitationResponse->getCode() === 201) {

        // Check if this is being called from registration form (non-user-managed registry)
        $existingAccount = $this->userStorage->load($acceptingUser);
        $calledFromRegistration = ($acceptingOrg === NULL && !$isMemberInvitation);
        
        if ($calledFromRegistration && $existingAccount !== NULL) {
          // Account already exists - redirect to login without disclosing account existence
          $this->logger->notice('Re-invited user @username attempted registration but account exists', [
            '@username' => $acceptingUser->getUsername(),
          ]);
          
          $userMgrResponse->setMessage(t('Unable to complete registration. If you have an existing account, try logging in instead.'));
          $userMgrResponse->setSuccess(FALSE);
          $userMgrResponse->setRedirect('user.login');
        }
        else {
          $orgUrl = NULL;
          $responseData = $invitationResponse->getData();

          // Try to get org_url from APIM response
          if (isset($responseData['org_url'])) {
            $orgUrl = $responseData['org_url'];
          }
          // Fallback: Extract org ID from invitation token URL
          elseif ($isMemberInvitation) {
            $tokenUrl = $token->getUrl();
            if (preg_match('#/orgs/([^/]+)/member-invitations/#', $tokenUrl, $matches)) {
              $orgUrl = '/consumer-api/orgs/' . $matches[1];
            }
          }

          // Add org to user's Drupal account if we have an org URL
          if ($orgUrl !== NULL && $existingAccount !== NULL) {
            // Convert APIM format to Drupal format
            $orgUrl = str_replace('/consumer-api/orgs/', '/consumer-orgs/', $orgUrl);
            \Drupal::service('ibm_apim.user_utils')->addConsumerOrgToUser($orgUrl, $existingAccount);

            $this->logger->notice('Added org @org to user @user during invitation acceptance', [
              '@user' => $acceptingUser->getUsername(),
              '@org' => $orgUrl,
            ]);
          }

          $this->logger->notice('invitation processed for @username', [
            '@username' => $acceptingUser->getUsername(),
          ]);
          if ($isMemberInvitation) {
            $userMgrResponse->setMessage(t('Invitation process complete.'));
          }
          else {
            $userMgrResponse->setMessage(t('Invitation process complete. Please login to continue.'));
          }
          $userMgrResponse->setSuccess(TRUE);
        }
      }
      else {
        $errors = isset($invitationResponse) ? $invitationResponse->getErrors() : NULL;
        $errorMessage = is_array($errors) && !empty($errors[0]) ? $errors[0] : t('Unknown invitation acceptance error');

        $this->logger->error('Error during acceptInvite:  @error', ['@error' => $errorMessage]);

        $userMgrResponse->setMessage(t('Error while accepting invitation: @error', ['@error' => $errorMessage]));
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