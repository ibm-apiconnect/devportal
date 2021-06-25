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

namespace Drupal\ibm_apim\Service\Mocks;

use Drupal\Core\State\StateInterface;
use Drupal\ibm_apim\Service\Interfaces\PermissionsServiceInterface;
use Psr\Log\LoggerInterface;

/**
 * Mock functionality for handling permissions objects
 */
class MockPermissionsService implements PermissionsServiceInterface {

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  private StateInterface $state;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  private LoggerInterface $logger;

  /**
   * @var array
   */
  private array $permissionsMap = [];

  /**
   * @throws \JsonException
   */
  public function __construct(StateInterface $state, LoggerInterface $logger) {
    $this->state = $state;
    $this->logger = $logger;

    $this->updateAll(json_decode(file_get_contents(drupal_get_path('module', 'ibm_apim') . '/src/Service/Mocks/MockData/permissions.json'), TRUE, 512, JSON_THROW_ON_ERROR));
  }

  /**
   * @inheritDoc
   */
  public function getAll(): array {
    if ($this->permissionsMap === null) {
      $this->permissionsMap = [];
    }

    return $this->permissionsMap;
  }

  /**
   * @inheritDoc
   */
  public function get($key): ?array {
    $perm = NULL;
    if (isset($key)) {
      $current_data = $this->getAll();

      if (isset($current_data[$key])) {
        $perm = $current_data[$key];
      }
    }

    return $perm;
  }

  /**
   * @inheritDoc
   */
  public function updateAll($data): void {
    if (isset($data)) {
      $permissions = [];
      foreach ($data as $perm) {
        $permissions[$perm['url']] = $perm;
      }
      $this->permissionsMap = $permissions;
    }
  }

  /**
   * @inheritDoc
   */
  public function update($key, $data): void {
    if (isset($key, $data)) {
      $this->permissionsMap[$key] = $data;
    }
  }

  /**
   * @inheritDoc
   */
  public function delete($key): void {
    if (isset($key)) {
      $new_data = [];
      foreach ($this->permissionsMap as $url => $value) {
        if ($url !== $key) {
          $new_data[$url] = $value;
        }
      }
      $this->permissionsMap = $new_data;
    }
  }

  /**
   * @inheritDoc
   */
  public function deleteAll(): void {
    $this->permissionsMap = [];
  }
}
