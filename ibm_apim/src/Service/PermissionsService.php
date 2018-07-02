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

namespace Drupal\ibm_apim\Service;

use Drupal\Core\State\StateInterface;
use Drupal\ibm_apim\Service\Interfaces\PermissionsServiceInterface;
use Psr\Log\LoggerInterface;

/**
 * Functionality for handling permissions objects
 */
class PermissionsService implements PermissionsServiceInterface {

  private $state;
  private $logger;

  public function __construct(StateInterface $state, LoggerInterface $logger) {
    $this->state = $state;
    $this->logger = $logger;
  }

  /**
   * get all the permissions objects
   *
   * @return NULL if an error occurs otherwise an array of the permissions objects.
   */
  public function getAll() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $perms = $this->state->get('ibm_apim.permissions_objects');

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $perms);
    return $perms;
  }

  /**
   * get a specific permissions object by url
   *
   * @param $key
   * @return null|array
   */
  public function get($key) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $key);

    $perm = NULL;
    if (isset($key)) {
      // clear caches if config different to previous requests
      $current_data = $this->state->get('ibm_apim.permissions_objects');

      if (isset($current_data) && isset($current_data[$key])) {
        $perm = $current_data[$key];
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $perm);
    return $perm;
  }

  /**
   * Update all permissions objects
   *
   * @param $data array of permissions objects keyed on url
   */
  public function updateAll($data) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $data);

    if (isset($data)) {
      $permissions = array();
      foreach ($data as $perm) {
        $permissions[$perm['url']] = $perm;
      }
      $this->state->set('ibm_apim.permissions_objects', $permissions);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Update a specific permissions object
   *
   * @param $key
   * @param $data
   */
  public function update($key, $data) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $key);

    if (isset($key) && isset($data)) {
      $current_data = $this->state->get('ibm_apim.permissions_objects');

      if (!is_array($current_data)) {
        $current_data = array();
      }
      $current_data[$key] = $data;
      $this->state->set('ibm_apim.permissions_objects', $current_data);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Delete a specific permissions object
   *
   * @param $key (url)
   */
  public function delete($key) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $key);

    if (isset($key)) {
      $current_data = $this->state->get('ibm_apim.permissions_objects');

      if (isset($current_data)) {
        $new_data = array();
        foreach ($current_data as $url => $value) {
          if ($url != $key) {
            $new_data[$url] = $value;
          }
        }
        $this->state->set('ibm_apim.permissions_objects', $new_data);
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Delete all current permissions objects
   */
  public function deleteAll() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $this->state->set('ibm_apim.permissions_objects', array());

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}
