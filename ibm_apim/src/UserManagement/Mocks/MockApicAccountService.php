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
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\State\State;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface;
use Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface;
use Drupal\ibm_apim\UserManagement\ApicAccountInterface;
use Drupal\user\UserInterface;

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
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $sessionStore;

  /**
   * Management server.
   *
   * @var \Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface
   */
  protected $mgmtServer;

  protected $userStorage;

  protected $state;

  protected $provider = 'auth_apic';

  protected $messenger;

  /**
   * MockApicAccountService constructor.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $tempStoreFactory
   * @param \Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface $mgmtInterface
   * @param \Drupal\Core\State\State $state
   * @param \Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface $user_storage
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
   */
  public function registerApicUser(ApicUser $apicUser): ?EntityInterface {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $apicUser->getUsername());
    }
    $returnValue = NULL;
    try {

      // The code inside this if statement isn't valid in the unit test environment where we have no Drupal instance
      if (!isset($GLOBALS['__PHPUNIT_BOOTSTRAP']) && \Drupal::hasContainer()) {
        $returnValue = $this->loadUserFromDatabase($apicUser);
      }
      if ($returnValue === NULL) {
        $account = $this->userStorage->register($apicUser);
        $returnValue = $account;
      }
    } catch (\Exception $e) {
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
  public function updateLocalAccount(ApicUser $user): ?UserInterface {

    $dbuser = $this->loadUserFromDatabase($user);
    if ($dbuser !== NULL) {
      $dbuser->set('first_name', $user->getFirstname());
      $dbuser->set('last_name', $user->getLastname());
      $dbuser->set('mail', $user->getMail());
      $dbuser->save();
    }

    $this->messenger->addStatus('MOCKED SERVICE:: Your account has been updated.');
    return $dbuser;
  }


  /**
   * @inheritDoc
   */
  public function updateLocalAccountRoles(ApicUser $user, $roles): bool {

    $dbuser = $this->loadUserFromDatabase($user);
    // Splat all of the old roles
    $existingRoles = $dbuser->getRoles();
    foreach ($existingRoles as $role) {
      $dbuser->removeRole($role);
    }

    // Add all of the new roles
    unset($roles['authenticated']);          // This isn't a 'proper' role so remove it
    foreach ($roles as $role) {
      if ($role !== 'authenticated') {
        $dbuser->addRole($role);
      }
    }

    return TRUE;
  }

  public function saveCustomFields($apicUser, $user, $form_state, $view_mode): void {
    \Drupal::logger('ibm_apim_mocks')->error('MockApicAccountService::saveCustomFields not implemented');
  }

  public function setDefaultLanguage($user): void {
    \Drupal::logger('ibm_apim_mocks')->warning('MockApicAccountService::setDefaultLanguage not implemented');
  }

  /**
   * @inheritDoc
   */
  public function createOrUpdateLocalAccount(ApicUser $user): ?UserInterface {
    \Drupal::logger('ibm_apim_mocks')->warning('MockApicAccountService::createOrUpdateLocalAccount not implemented');
  }

  /**
   * @param \Drupal\ibm_apim\ApicType\ApicUser $user
   */
  private function loadUserFromDatabase(ApicUser $user) {
    $user = $this->userStorage->load($user);
    return $user;
  }

}
