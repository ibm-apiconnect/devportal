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
use Psr\Log\LoggerInterface;

/**
 * Functionality for handling groups
 */
class Group {

  private $state;
  private $logger;

  public function __construct(StateInterface $state, LoggerInterface $logger) {
    $this->state = $state;
    $this->logger = $logger;
  }

  /**
   * get all the groups
   *
   * @return NULL if an error occurs otherwise an array of the groups.
   */
  public function getAll() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $groups = $this->state->get('ibm_apim.groups');

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $groups);
    return $groups;
  }

  /**
   * get a specific group by url
   *
   * @param $key
   * @return null|array
   */
  public function get($key) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $key);

    $group = NULL;
    if (isset($key)) {
      // clear caches if config different to previous requests
      $current_data = $this->state->get('ibm_apim.groups');

      if (isset($current_data) && isset($current_data[$key])) {
        $group = $current_data[$key];
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $group);
    return $group;
  }

  /**
   * Update all groups
   *
   * @param $data array of groups keyed on url
   */
  public function updateAll($data) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $data);

    if (isset($data)) {
      $groups = array();
      foreach ($data as $group) {
        $groups[$group['url']] = $group;
      }
      $this->state->set('ibm_apim.groups', $groups);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Update a specific group
   *
   * @param $key
   * @param $data
   */
  public function update($key, $data) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $key);

    if (isset($key) && isset($data)) {
      $current_data = $this->state->get('ibm_apim.groups');

      if (!is_array($current_data)) {
        $current_data = array();
      }
      $current_data[$key] = $data;
      $this->state->set('ibm_apim.groups', $current_data);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Delete a specific group
   *
   * @param $key (group url)
   */
  public function delete($key) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $key);

    if (isset($key)) {
      $current_data = $this->state->get('ibm_apim.groups');

      if (isset($current_data)) {
        $new_data = array();
        foreach ($current_data as $url => $value) {
          if ($url != $key) {
            $new_data[$url] = $value;
          }
        }
        $this->state->set('ibm_apim.groups', $new_data);
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Delete all current groups
   */
  public function deleteAll() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $this->state->set('ibm_apim.groups', array());

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}
