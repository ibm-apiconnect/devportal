<?php

namespace Drupal\ibm_apim\Service\Mocks;

use Drupal\ibm_apim\ApicType\UserRegistry;
use Drupal\ibm_apim\Service\UserRegistryService;
use Drupal\ibm_apim\External\SerializationUtility;

/**
 * Mock functionality for handling user registries
 */
class MockUserRegistryService extends UserRegistryService {

  /**
   * @inheritDoc
   */
  public function get(string $key): ?UserRegistry {

    $registry = parent::get($key);

    if (!$registry) {
      $registry = $this->createMockRegistry();
    }

    $this->logger->debug('MockUserRegistryService::get(%key) returning %registryName', [
      '%key' => $key,
      '%registryName' => $registry->getName(),
    ]);

    return $registry;
  }

  /**
   * @inheritDoc
   */
  public function updateAll(array $data): bool {
    $this->logger->debug('MockUserRegistryService::updateAll() with %data', ['%data' => SerializationUtility::encodeData($data)]);
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
