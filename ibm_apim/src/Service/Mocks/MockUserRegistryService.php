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

namespace Drupal\ibm_apim\Service\Mocks;

use Drupal\ibm_apim\ApicType\UserRegistry;
use Drupal\ibm_apim\Service\UserRegistryService;

/**
 * Mock functionality for handling user registries
 */
class MockUserRegistryService extends UserRegistryService {

  /**
   * get a specific user_registry by url
   *
   * @param $key
   *
   * @return null|\Drupal\ibm_apim\ApicType\UserRegistry
   */
  public function get($key): ?UserRegistry {

    $registry = parent::get($key);

    if (!$registry) {
      $registry = $this->createMockRegistry();
    }

    $this->logger->debug('MockUserRegistryService::get(%key) returning %registryName' ,['%key' => $key, '%registryName' => $registry->getName()]);

    return $registry;
  }

  public function updateAll($data): bool {
    $this->logger->debug('MockUserRegistryService::updateAll() with %data', ['%data' => serialize($data)]);
    return parent::updateAll($data);
  }

  /**
   * @return \Drupal\ibm_apim\ApicType\UserRegistry
   */
  private function createMockRegistry(): UserRegistry {
    $registry = new UserRegistry();
    $registry->setTitle('Mock user registry');
    $registry->setName('Mock user registry');
    $registry->setUserManaged(TRUE);
    $registry->setRegistryType('lur');
    $registry->setUserRegistryManaged(FALSE);
    $registry->setUrl('/mock/user/registry');
    $registry->setIdentityProviders([["name" => "trueRealm"]]);
    $registry->setOnboarding(TRUE);
    $registry->setCaseSensitive(TRUE);
    return $registry;
  }


}
