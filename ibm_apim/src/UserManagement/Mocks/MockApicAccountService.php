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

namespace Drupal\ibm_apim\UserManagement\Mocks;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\State\State;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface;
use Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface;
use Drupal\ibm_apim\UserManagement\ApicAccountInterface;
use Drupal\user\UserInterface;
use Throwable;

/**
 * Mock of the ApicAccountService service.
 *
 * This mock doesn't call out to the management node so that we can run our
 * tests against a standalone portal appliance.
 */
class MockApicAccountService implements ApicAccountInterface {

  /**
   * Temp store for session data.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected PrivateTempStore $sessionStore;

  /**
   * Management server.
   *
   * @var \Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface
   */
  protected ManagementServerInterface $mgmtServer;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface
   */
  protected ApicUserStorageInterface $userStorage;

  /**
   * @var \Drupal\Core\State\State
   */
  protected State $state;

  /**
   * @var string
   */
  protected string $provider = 'auth_apic';

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected Messenger $messenger;

  /**
   * MockApicAccountService constructor.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   * @param \Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface $mgmtInterface
   * @param \Drupal\Core\State\State $state
   * @param \Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface $user_storage
   * @param \Drupal\Core\Messenger\Messenger $messenger
   */
  public function __construct(PrivateTempStoreFactory $tempStoreFactory,
                              ManagementServerInterface $mgmtInterface,
                              State $state,
                              ApicUserStorageInterface $user_storage,
                              Messenger $messenger) {
    $this->sessionStore = $tempStoreFactory->get('ibm_apim');
    $this->mgmtServer = $mgmtInterface;
    $this->state = $state;
    $this->userStorage = $user_storage;
    $this->messenger = $messenger;
  }


  /**
   * @inheritDoc
   * @throws \Exception
   */
  public function registerApicUser(ApicUser $user): ?EntityInterface {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $user->getUsername());
    }
    $returnValue = NULL;
    try {

      // The code inside this if statement isn't valid in the unit test environment where we have no Drupal instance
      if (!isset($GLOBALS['__PHPUNIT_BOOTSTRAP']) && \Drupal::hasContainer()) {
        $returnValue = $this->loadUserFromDatabase($user);
      }
      if ($returnValue === NULL) {
        $account = $this->userStorage->register($user);
        $returnValue = $account;
      }
    } catch (Throwable $e) {
      throw $e;
    }

    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    return $returnValue;
  }

  /**
   * @inheritDoc
   */
  public function updateApicAccount(ApicUser $user): ?ApicUser  {
     $this->updateLocalAccount($user);
     return $user;
  }

  /**
   * @inheritDoc
   */
  public function updateLocalAccount(ApicUser $user) {

    $dbUser = $this->loadUserFromDatabase($user);
    if ($dbUser !== NULL) {
      $dbUser->set('first_name', $user->getFirstname());
      $dbUser->set('last_name', $user->getLastname());
      $dbUser->set('mail', $user->getMail());
      $dbUser->save();
    }

    $this->messenger->addStatus('MOCKED SERVICE:: Your account has been updated.');
    return $dbUser;
  }


  /**
   * @inheritDoc
   */
  public function updateLocalAccountRoles(ApicUser $user, array $roles): bool {

    $dbUser = $this->loadUserFromDatabase($user);
    if ($dbUser !== NULL){
      // Splat all of the old roles
      $existingRoles = $dbUser->getRoles();
      foreach ($existingRoles as $role) {
        $dbUser->removeRole($role);
      }

      // Add all of the new roles
      unset($roles['authenticated']);          // This isn't a 'proper' role so remove it
      foreach ($roles as $role) {
        if ($role !== 'authenticated') {
          $dbUser->addRole($role);
        }
      }
    }

    return TRUE;
  }

  public function setDefaultLanguage($user): void {
    \Drupal::logger('ibm_apim_mocks')->warning('MockApicAccountService::setDefaultLanguage not implemented');
  }

  /**
   * @inheritDoc
   */
  public function createOrUpdateLocalAccount(ApicUser $user): ?UserInterface {
    \Drupal::logger('ibm_apim_mocks')->warning('MockApicAccountService::createOrUpdateLocalAccount not implemented');
    return null;
  }

  /**
   * @param \Drupal\ibm_apim\ApicType\ApicUser $user
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   */
  private function loadUserFromDatabase(ApicUser $user): ?EntityInterface {
    return $this->userStorage->load($user);
  }

}
