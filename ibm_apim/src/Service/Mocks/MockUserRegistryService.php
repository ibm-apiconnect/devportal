<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Service\Mocks;

use Drupal\Core\State\StateInterface;

use Drupal\ibm_apim\Service\UserRegistryService;
use Psr\Log\LoggerInterface;
use Drupal\ibm_apim\ApicType\UserRegistry;

/**
 * Mock functionality for handling user registries
 */
class MockUserRegistryService extends UserRegistryService {

  public function __construct(StateInterface $state, LoggerInterface $logger) {

    parent::__construct($state, $logger);
  }

  /**
   * get a specific user_registry by url
   *
   * @param $key
   * @return null|array
   */
  public function get($key) {

    $registry = parent::get($key);

    if (!$registry) {
      $registry = $this->createMockRegistry();
    }

    $this->logger->debug("MockUserRegistryService::get($key) returning " . $registry->getName());

    return $registry;
  }

  public function updateAll($data) {
    $this->logger->debug("MockUserRegistryService::updateAll() with " . serialize($data));
    parent::updateAll($data);
  }

  /**
   * @return \Drupal\ibm_apim\ApicType\UserRegistry
   */
  private function createMockRegistry(): \Drupal\ibm_apim\ApicType\UserRegistry {
    $registry = new UserRegistry();
    $registry->setTitle("Mock user registry");
    $registry->setName("Mock user registry");
    $registry->setUserManaged(TRUE);
    $registry->setRegistryType('lur');
    $registry->setUserRegistryManaged(FALSE);
    $registry->setUrl('/mock/user/registry');
    $registry->setIdentityProviders([]);
    $registry->setOnboarding(TRUE);
    $registry->setCaseSensitive(TRUE);
    return $registry;
  }



}
