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
 * Functionality for handling vendor extensions
 */
class VendorExtension {

  private $state;
  private $logger;

  public function __construct(StateInterface $state, LoggerInterface $logger) {
    $this->state = $state;
    $this->logger = $logger;
  }

  /**
   * get all the vendor extensions
   *
   * @return NULL if an error occurs otherwise an array of the extensions.
   */
  public function getAll() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $extensions = $this->state->get('ibm_apim.vendor_extensions');

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $extensions);
    return $extensions;
  }

  /**
   * get a specific vendor extension by name
   *
   * @param $key
   * @return null|array
   */
  public function get($key) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $key);

    $extension = NULL;
    if (isset($key)) {
      // clear caches if config different to previous requests
      $current_data = $this->state->get('ibm_apim.vendor_extensions');

      if (isset($current_data) && isset($current_data[$key])) {
        $extension = $current_data[$key];
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $extension);
    return $extension;
  }

  /**
   * Update all vendor extensions
   *
   * @param $data array of vendor extensions keyed on name
   */
  public function updateAll($data) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $data);

    if (isset($data)) {
      $exts = array();
      foreach ($data as $ext) {
        $exts[$ext['name']] = $ext;
      }
      $this->state->set('ibm_apim.vendor_extensions', $exts);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Update a specific vendor extension
   *
   * @param $key
   * @param $data
   */
  public function update($key, $data) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $key);

    if (isset($key) && isset($data)) {
      $current_data = $this->state->get('ibm_apim.vendor_extensions');

      if (!is_array($current_data)) {
        $current_data = array();
      }
      $current_data[$key] = $data;
      $this->state->set('ibm_apim.vendor_extensions', $current_data);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Delete a specific vendor extension
   *
   * @param $key (vendor extension name)
   */
  public function delete($key) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $key);

    if (isset($key)) {
      $current_data = $this->state->get('ibm_apim.vendor_extensions');

      if (isset($current_data)) {
        $new_data = array();
        foreach ($current_data as $name => $value) {
          if ($name != $key) {
            $new_data[$name] = $value;
          }
        }
        $this->state->set('ibm_apim.vendor_extensions', $new_data);
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Delete all current vendor extensions
   */
  public function deleteAll() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $this->state->set('ibm_apim.vendor_extensions', array());

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}
