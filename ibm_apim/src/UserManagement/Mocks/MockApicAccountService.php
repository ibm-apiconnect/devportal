<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2020
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\UserManagement\Mocks;

use Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface;
use Drupal\ibm_apim\UserManagement\ApicAccountInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\State\State;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface;
use Drupal\user\Entity\User;
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
                              ApicUserStorageInterface $user_storage) {
    $this->sessionStore = $tempStoreFactory->get('ibm_apim');
    $this->mgmtServer = $mgmtInterface;
    $this->state = $state;
    $this->userStorage = $user_storage;
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

        // Check if the account already exists before creating it
        // This supports the ibmsocial_login case where users are created in drupal before
        // we register them with the mgmt appliance (this is out of our control)
        $ids = \Drupal::entityQuery('user')->execute();
        $users = User::loadMultiple($ids);

        foreach ($users as $user) {
          if ($user->getUsername() === $apicUser->getUsername()) {
            $returnValue = $user;
          }
        }
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
  public function updateApicAccount(ApicUser $user): bool {
     $this->updateLocalAccount($user);
     return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function updateLocalAccount(ApicUser $user): ?UserInterface {
    // Update the user directly in drupal db
    $ids = \Drupal::entityQuery('user')->execute();
    $users = User::loadMultiple($ids);

    // TODO: this is risky at the moment, as we can have multiple users with the same username - we need to extend to check on
    // TODO: registry_url as well.
    foreach ($users as $dbuser) {
      if ($dbuser->getUsername() === $user->getUsername()) {
        $dbuser->set('first_name', $user->getFirstname());
        $dbuser->set('last_name', $user->getLastname());
        $dbuser->set('mail', $user->getMail());
        $dbuser->save();
        break;
      }
    }

    drupal_set_message('MOCKED SERVICE:: Your account has been updated.');
    return NULL;
  }


  /**
   * @inheritDoc
   */
  public function updateLocalAccountRoles(ApicUser $user, $roles): bool {
    // Update the user directly in drupal db
    $ids = \Drupal::entityQuery('user')->execute();
    $users = User::loadMultiple($ids);

    foreach ($users as $dbuser) {
      if ($dbuser->getUsername() === $user->getUsername()) {
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
      }
    }
    return TRUE;
  }

  public function saveCustomFields($user, $form_state, $view_mode): void {
    \Drupal::logger('ibm_apim_mocks')->error('MockApicAccountService::saveCustomFields not implemented');
  }

  public function setDefaultLanguage($user): void {
    \Drupal::logger('ibm_apim_mocks')->error('MockApicAccountService::setDefaultLanguage not implemented');
  }

  /**
   * @inheritDoc
   */
  public function createOrUpdateLocalAccount(ApicUser $user): ?UserInterface {
    \Drupal::logger('ibm_apim_mocks')->error('MockApicAccountService::createOrUpdateLocalAccount not implemented');
  }


}
