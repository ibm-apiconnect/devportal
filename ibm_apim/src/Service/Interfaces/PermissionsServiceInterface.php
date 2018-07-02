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
   * @return NULL if an error occurs otherwise an array of the permissions objects.
   */
  public function getAll();

  /**
   * get a specific permissions object by url
   *
   * @param $key
   * @return null|array
   */
  public function get($key);

  /**
   * Update all permissions objects
   *
   * @param $data array of permissions objects keyed on url
   */
  public function updateAll($data);

  /**
   * Update a specific permissions object
   *
   * @param $key
   * @param $data
   */
  public function update($key, $data);

  /**
   * Delete a specific permissions object
   *
   * @param $key (url)
   */
  public function delete($key);

  /**
   * Delete all current permissions objects
   */
  public function deleteAll();

}
