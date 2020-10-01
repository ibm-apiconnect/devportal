<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2020
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Service;

use Drupal\Core\State\StateInterface;
use Drupal\locale\SourceString;
use Psr\Log\LoggerInterface;

/**
 * Functionality for handling payment method schema objects
 */
class PaymentMethodSchema {

  private $state;

  private $logger;

  public function __construct(StateInterface $state, LoggerInterface $logger) {
    $this->state = $state;
    $this->logger = $logger;
  }

  /**
   * get all the payment method schema objects
   *
   * @return array an array of the payment method schema objects.
   */
  public function getAll(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $integrations = $this->state->get('ibm_apim.payment_method_schemas');
    if ($integrations === NULL || empty($integrations)) {
      $integrations = [];
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $integrations);
    return $integrations;
  }

  /**
   * get a specific payment method schema object by url
   *
   * @param $key
   *
   * @return null|array
   */
  public function get($key): ?array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $key);

    $integration = NULL;
    if (isset($key)) {
      // clear caches if config different to previous requests
      $current_data = $this->state->get('ibm_apim.payment_method_schemas');

      if (isset($current_data[$key])) {
        $integration = $current_data[$key];
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $integration);
    return $integration;
  }

  /**
   * get a specific payment method schema object by name
   *
   * @param $name
   *
   * @return null|array
   */
  public function getByName($name): ?array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $name);

    $targetIntegration = NULL;
    if (isset($name)) {
      $current_data = $this->state->get('ibm_apim.payment_method_schemas');

      foreach ($current_data as $integration) {
        if ($integration['name'] === $name) {
          $targetIntegration = $integration;
        }
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $targetIntegration);
    return $targetIntegration;
  }

  /**
   * Update all payment method schema objects
   *
   * @param $data array of payment method schema objects keyed on url
   */
  public function updateAll($data): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $data);

    if (isset($data)) {
      $paymentMethodSchemas = [];
      foreach ($data as $integration) {
        $paymentMethodSchemas[$integration['url']] = $integration;
        $this->storeTranslations($integration);
      }
      $this->state->set('ibm_apim.payment_method_schemas', $paymentMethodSchemas);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Update a specific payment method schema object
   *
   * @param $key
   * @param $data
   */
  public function update($key, $data): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $key);

    if (isset($key, $data)) {
      $current_data = $this->state->get('ibm_apim.payment_method_schemas');

      if (!is_array($current_data)) {
        $current_data = [];
      }
      $current_data[$key] = $data;
      $this->storeTranslations($data);
      $this->state->set('ibm_apim.payment_method_schemas', $current_data);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Delete a specific payment method schema object
   *
   * @param $key (url)
   */
  public function delete($key): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $key);

    if (isset($key)) {
      $current_data = $this->state->get('ibm_apim.payment_method_schemas');

      if (isset($current_data)) {
        $new_data = [];
        foreach ($current_data as $url => $value) {
          if ($url !== $key) {
            $new_data[$url] = $value;
          }
        }
        $this->state->set('ibm_apim.payment_method_schemas', $new_data);
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Delete all current payment method schema objects
   */
  public function deleteAll(): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $this->state->set('ibm_apim.payment_method_schemas', []);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * @param $integration
   *
   */
  public function storeTranslations($integration): void {
    if (isset($integration['x-ibm-languages'])) {
      $localeStorage = \Drupal::service('locale.storage');
      $utils = \Drupal::service('ibm_apim.utils');
      try {
        foreach ($integration['x-ibm-languages'] as $key => $translations) {
          $string = $localeStorage->findString(['source' => $integration[$key]]);
          if (is_null($string)) {
            $string = new SourceString();
            $string->setString($integration[$key]);
            $string->setStorage($localeStorage);
            $string->save();
          }
          foreach ($translations as $langcode => $translation) {
            $langcode = $utils->convert_lang_name_to_drupal($langcode);
            $localeStorage->createTranslation([
              'lid' => $string->lid,
              'language' => $langcode,
              'translation' => $translation,
            ])->save();
          }
        }
      } catch (\Drupal\locale\StringStorageException $e) {
        // quietly just ignore the translation errors
      }
    }
  }
}
