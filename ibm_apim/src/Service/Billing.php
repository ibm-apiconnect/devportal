<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2019
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Service;

use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * Functionality for handling billing objects
 */
class Billing {

  private $state;

  private $logger;

  public function __construct(StateInterface $state, LoggerInterface $logger) {
    $this->state = $state;
    $this->logger = $logger;
  }

  /**
   * get all the billing objects
   *
   * @return NULL|array null if an error occurs otherwise an array of the billing objects.
   */
  public function getAll(): ?array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $bills = $this->state->get('ibm_apim.billing_objects');

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $bills);
    return $bills;
  }

  /**
   * get a specific billing object by url
   *
   * @param $key
   *
   * @return null|array
   */
  public function get($key): ?array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $key);

    $bill = NULL;
    if (isset($key)) {
      // clear caches if config different to previous requests
      $current_data = $this->state->get('ibm_apim.billing_objects');

      if (isset($current_data[$key])) {
        $bill = $current_data[$key];
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $bill);
    return $bill;
  }

  /**
   * Update all billing objects
   *
   * @param $data array of billing objects keyed on url
   */
  public function updateAll($data): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $data);

    if (isset($data)) {
      $billings = [];
      foreach ($data as $bill) {
        $billings[$bill['url']] = $bill;
      }
      $this->state->set('ibm_apim.billing_objects', $billings);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Update a specific billing object
   *
   * @param $key
   * @param $data
   */
  public function update($key, $data): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $key);

    if (isset($key, $data)) {
      $current_data = $this->state->get('ibm_apim.billing_objects');

      if (!is_array($current_data)) {
        $current_data = [];
      }
      $current_data[$key] = $data;
      $this->state->set('ibm_apim.billing_objects', $current_data);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Delete a specific billing object
   *
   * @param $key (url)
   */
  public function delete($key): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $key);

    if (isset($key)) {
      $current_data = $this->state->get('ibm_apim.billing_objects');

      if (isset($current_data)) {
        $new_data = [];
        foreach ($current_data as $url => $value) {
          if ($url !== $key) {
            $new_data[$url] = $value;
          }
        }
        $this->state->set('ibm_apim.billing_objects', $new_data);
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Delete all current billing objects
   */
  public function deleteAll(): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $this->state->set('ibm_apim.billing_objects', []);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}
