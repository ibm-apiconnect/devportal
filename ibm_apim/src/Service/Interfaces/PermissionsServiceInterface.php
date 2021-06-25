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

namespace Drupal\ibm_apim\Service\Interfaces;

use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * Functionality for handling permissions objects
 */
interface PermissionsServiceInterface {

  public function __construct(StateInterface $state, LoggerInterface $logger);

  /**
   * get all the permissions objects
   *
   * @return array an array of the permissions objects.
   */
  public function getAll(): array;

  /**
   * get a specific permissions object by url
   *
   * @param string $key
   *
   * @return null|array
   */
  public function get(string $key): ?array;

  /**
   * Update all permissions objects
   *
   * @param array $data array of permissions objects keyed on url
   */
  public function updateAll(array $data): void;

  /**
   * Update a specific permissions object
   *
   * @param string $key
   * @param array $data
   */
  public function update(string $key, array $data): void;

  /**
   * Delete a specific permissions object
   *
   * @param string $key (url)
   */
  public function delete(string $key): void;

  /**
   * Delete all current permissions objects
   */
  public function deleteAll(): void;

}
