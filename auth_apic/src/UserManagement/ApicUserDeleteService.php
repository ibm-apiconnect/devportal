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

namespace Drupal\auth_apic\UserManagement;

use Drupal\auth_apic\UserManagerResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface;
use Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface;
use Psr\Log\LoggerInterface;

class ApicUserDeleteService implements ApicUserDeleteInterface {

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface
   */
  private $mgmtServer;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface
   */
  private $userStorage;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $currentUser;

  public function __construct(ManagementServerInterface $mgmt_server,
                       ApicUserStorageInterface $user_storage,
                       LoggerInterface $logger,
                       AccountProxyInterface $current_user) {
    $this->mgmtServer = $mgmt_server;
    $this->userStorage = $user_storage;
    $this->logger = $logger;
    $this->currentUser = $current_user;
  }

  /**
   * @inheritDoc
   */
  public function deleteUser(): UserManagerResponse {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    $mgmtResponse = $this->mgmtServer->deleteMe();
    $userManagerResponse = new UserManagerResponse();

    if ((int) $mgmtResponse->getCode() === 200) { // DELETE /me should return 200 with me resource
      // we have successfully deleted in apim, now to clean things up locally (drupal account)

      $this->logger->notice('Account deleted in apim by @username', [
        '@username' => $this->currentUser->getAccountName(),
      ]);

      $this->deleteLocalAccount();

      $userManagerResponse->setSuccess(TRUE);

    }
    else {
      $this->logger->error('Error deleting user account in apim');
      $userManagerResponse->setSuccess(FALSE);
    }

    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $userManagerResponse !== NULL ? $userManagerResponse->success() : NULL);
    }
    return $userManagerResponse;
  }

  /**
   * @inheritDoc
   */
  public function deleteLocalAccount(ApicUser $user = NULL): bool {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $response = FALSE;
    $id = NULL;
    if ($user !== NULL) {
      $account = $this->userStorage->load($user);

      if ($account !== NULL) {
        $id = $account->id();
      }
      else {
        $this->logger->error('Unable to load user account to be deleted.');
      }
    }
    else {
      $id = $this->currentUser->id();
    }

    if ($id !== NULL) {
      $this->logger->notice('Deleting user - id = @id', ['@id'=> $id]);
      user_cancel([], $id, 'user_cancel_reassign');
      $response = TRUE;
    }
    else {
      $this->logger->error('Unable to delete local account.');
    }

    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $response);
    }
    return $response;
  }
}
