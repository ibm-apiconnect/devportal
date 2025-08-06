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
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface;
use Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface;
use Psr\Log\LoggerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
class ApicUserDeleteService implements ApicUserDeleteInterface {

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface
   */
  private ManagementServerInterface $mgmtServer;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface
   */
  private ApicUserStorageInterface $userStorage;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  private LoggerInterface $logger;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private AccountProxyInterface $currentUser;

 /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  public function __construct(ManagementServerInterface $mgmt_server,
                              ApicUserStorageInterface $user_storage,
                              LoggerInterface $logger,
                              AccountProxyInterface $current_user,
                              EntityTypeManagerInterface $entity_type_manager
                              ) {
    $this->mgmtServer = $mgmt_server;
    $this->userStorage = $user_storage;
    $this->logger = $logger;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
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

    if ($mgmtResponse !== NULL && (int) $mgmtResponse->getCode() === 200) { // DELETE /me should return 200 with me resource
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
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $userManagerResponse->success());
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

    if ($id === NULL) {
      $this->logger->error('Unable to delete local account.');
    } else if ($this->entityTypeManager->getStorage('user')->load($id) === NULL) {
      //The user was deleted when the consumer org was deleted
      $response = TRUE;
    } else {
      //DELETE USER AVATAR
      $avatar = \Drupal::service('user.data')->get('avatar_kit', $id, 'avatar_name');
      if ($avatar) {
        $directory = 'public://avatar_kit/ak_letter';
        $path = $directory . '/' . $avatar . '.png';
        $file_system = \Drupal::service('file_system');
        $file_system->delete($path);
      }
      $this->logger->notice('Deleting user - id = @id', ['@id' => $id]);
      // DO NOT DELETE THE ADMIN USER!
      $performBatch = FALSE;
      if ((int) $id > 1) {
        user_cancel([], $id, 'user_cancel_reassign');
        $performBatch = TRUE;
      }
      if ($performBatch && !isset($GLOBALS['__PHPUNIT_ISOLATION_BLACKLIST']) && \Drupal::hasContainer()) {
       $this->logger->notice('Processing batch delete of users...');
        $batch = &batch_get();
        $batch['progressive'] = FALSE;
        batch_process();
      }
      $response = TRUE;
    }

    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $response);
    }
    return $response;
  }

}
