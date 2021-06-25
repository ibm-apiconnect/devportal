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

namespace Drupal\ibm_apim\Service;

use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;

/**
 * Functionality for handling billing objects
 */
class Billing {

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  private StateInterface $state;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  private LoggerInterface $logger;

  public function __construct(StateInterface $state, LoggerInterface $logger) {
    $this->state = $state;
    $this->logger = $logger;
  }

  /**
   * return true if there is at least one billing object
   *
   * @return bool
   */
  public function isEnabled(): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $enabled = FALSE;
    $bills = $this->state->get('ibm_apim.billing_objects');
    if ($bills !== NULL && !empty($bills) && count($bills) > 0) {
      $enabled = TRUE;
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $enabled);
    return $enabled;
  }

  /**
   * get all the billing objects
   *
   * @return array an array of the billing objects.
   */
  public function getAll(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $bills = $this->state->get('ibm_apim.billing_objects');
    if ($bills === NULL || empty($bills)) {
      $bills = [];
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $bills);
    return $bills;
  }

  /**
   * get a specific billing object by url
   *
   * @param string $key
   *
   * @return null|array|string
   */
  public function get(string $key) {
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
   * @param array $data array of billing objects keyed on url
   */
  public function updateAll(array $data): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $data);

    if (isset($data)) {
      $billings = [];
      $moduleHandler = \Drupal::service('module_handler');
      if ($moduleHandler->moduleExists('encrypt')) {
        $ibmApimConfig = \Drupal::config('ibm_apim.settings');
        $encryptionProfileName = $ibmApimConfig->get('payment_method_encryption_profile');
        $encryptionProfile = \Drupal\encrypt\Entity\EncryptionProfile::load($encryptionProfileName);
        $encryptionService = \Drupal::service('encryption');
      }
      foreach ($data as $bill) {
        $bill_url = $bill['billing_url'];
        if (isset($encryptionService, $encryptionProfile) && $moduleHandler->moduleExists('encrypt')) {
          $bill = $encryptionService->encrypt(serialize($bill), $encryptionProfile);
        }
        $billings[$bill_url] = $bill;
      }
      $this->state->set('ibm_apim.billing_objects', $billings);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Update a specific billing object
   *
   * @param string $key
   * @param array $data
   */
  public function update(string $key, array $data): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $key);

    if (isset($key, $data)) {
      $current_data = $this->state->get('ibm_apim.billing_objects');

      if (!is_array($current_data)) {
        $current_data = [];
      }
      $moduleHandler = \Drupal::service('module_handler');
      if ($moduleHandler->moduleExists('encrypt')) {
        $ibmApimConfig = \Drupal::config('ibm_apim.settings');
        $encryptionProfileName = $ibmApimConfig->get('payment_method_encryption_profile');
        $encryptionProfile = \Drupal\encrypt\Entity\EncryptionProfile::load($encryptionProfileName);
        $encryptionService = \Drupal::service('encryption');
        $data = $encryptionService->encrypt(serialize($data), $encryptionProfile);
      }
      $current_data[$key] = $data;
      $this->state->set('ibm_apim.billing_objects', $current_data);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Delete a specific billing object
   *
   * @param string $key (url)
   */
  public function delete(string $key): void {
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

  /**
   * Return a decrypted version of a given billing object
   *
   * @param string $key - billing URL
   *
   * @return array|null
   */
  public function decrypt(string $key): ?array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $key);
    $data = NULL;
    if (isset($key)) {
      $data = $this->get($key);

      $moduleHandler = \Drupal::service('module_handler');
      if ($moduleHandler->moduleExists('encrypt')) {
        $ibmApimConfig = \Drupal::config('ibm_apim.settings');
        $encryptionProfileName = $ibmApimConfig->get('payment_method_encryption_profile');
        $encryptionProfile = \Drupal\encrypt\Entity\EncryptionProfile::load($encryptionProfileName);
        $encryptionService = \Drupal::service('encryption');
        $data = unserialize($encryptionService->decrypt($data, $encryptionProfile), ['allowed_classes' => FALSE]);
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $data;
  }
}
