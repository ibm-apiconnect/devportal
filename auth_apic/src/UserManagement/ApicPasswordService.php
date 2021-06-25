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
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface;
use Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface;
use Drupal\user\Entity\User;
use Psr\Log\LoggerInterface;

class ApicPasswordService implements ApicPasswordInterface {

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface
   */
  private ManagementServerInterface $mgmtServer;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  private Messenger $messenger;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  private LoggerInterface $logger;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface
   */
  private ApicUserStorageInterface $apicUserStorage;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private EntityStorageInterface $drupalUserStorage;

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(ManagementServerInterface $mgmt_server,
                              Messenger $messenger,
                              LoggerInterface $logger,
                              ApicUserStorageInterface $user_storage,
                              EntityTypeManagerInterface $entity_type_manager) {
    $this->mgmtServer = $mgmt_server;
    $this->messenger = $messenger;
    $this->logger = $logger;
    $this->apicUserStorage = $user_storage;
    $this->drupalUserStorage = $entity_type_manager->getStorage('user');
  }

  /**
   * @inheritDoc
   */
  public function resetPassword(JWTToken $obj, $password): int {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $mgmtResponse = $this->mgmtServer->resetPassword($obj, $password);
    $code = (int) $mgmtResponse->getCode();

    if ($code !== 204) {
      $this->logger->notice('Error resetting password.');
      $this->logger->error('Reset password response: @result', ['@result' => serialize($mgmtResponse)]);
      $this->messenger->addError(t('Error resetting password. Contact the system administrator.'));
      // If we have more information then provide it to the user as well.
      if ($mgmtResponse->getErrors() !== NULL) {
        $this->messenger->addError(t('Error detail:'));
        // Show the errors that the server has returned.
        foreach ($mgmtResponse->getErrors() as $error) {
          $this->messenger->addError('  ' . $error);
        }
      }

    }
    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $code);
    }
    return $code;
  }


  /**
   * @inheritdoc
   */
  public function changePassword(User $user, $old_password, $new_password): bool {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $this->logger->notice('changePassword called for @username', ['@username' => $user->get('name')->value]);
    $mgmtResponse = $this->mgmtServer->changePassword($old_password, $new_password);
    if ((int) $mgmtResponse->getCode() === 204) {
      $this->logger->notice('Password changed successfully.');
      $returnValue = TRUE;
    }
    else {
      $this->logger->error('Password change failure.');
      $returnValue = FALSE;
    }

    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    }
    return $returnValue;
  }


  /**
   * @inheritDoc
   */
  public function lookupUpAccount(string $lookup, string $registry_url = NULL): ?EntityInterface {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $lookup);
    }

    // identify whether this is admin (uid=1)
    $admin_account = $this->drupalUserStorage->load(1);
    if ($admin_account !== NULL && ($lookup === $admin_account->getAccountName() || $lookup === $admin_account->get('mail')
          ->getValue()[0]['value'])) {
      $this->logger->notice('lookUpAccount: identified user as admin account');
      $account = $admin_account;
    }
    else {
      // Not admin, so we need to see if this user is known to us..
      // Try to load by email.
      $account = $this->apicUserStorage->loadUserByEmailAddress($lookup);
      if ($account === NULL) {
        // No success, try to load by name + registry
        $lookup_user = new ApicUser();
        $lookup_user->setUsername($lookup);
        if ($registry_url !== NULL) {
          $lookup_user->setApicUserRegistryUrl($registry_url);
        }
        $account = $this->apicUserStorage->load($lookup_user);
      }
    }

    if (\function_exists('ibm_apim_exit_trace')) {
      $msg = $account !== NULL ? $account->id() : NULL;
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $msg);
    }
    return $account;
  }

}
