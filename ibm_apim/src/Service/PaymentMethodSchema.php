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
use Drupal\locale\SourceString;
use Psr\Log\LoggerInterface;

/**
 * Functionality for handling payment method schema objects
 */
class PaymentMethodSchema {

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  private StateInterface $state;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  private LoggerInterface $logger;

  /**
   * PaymentMethodSchema constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   * @param \Psr\Log\LoggerInterface $logger
   */
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
   * @param string $key
   *
   * @return null|array
   */
  public function get(string $key): ?array {
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
   * @param string $name
   *
   * @return null|array
   */
  public function getByName(string $name): ?array {
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
   * get a specific payment method schema object by ID
   *
   * @param string $id
   *
   * @return null|array
   */
  public function getById(string $id): ?array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $id);

    $targetIntegration = NULL;
    if (isset($id)) {
      $current_data = $this->state->get('ibm_apim.payment_method_schemas');

      foreach ($current_data as $integration) {
        if ($integration['id'] === $id) {
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
   * @param array $data array of payment method schema objects keyed on url
   */
  public function updateAll(array $data): void {
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
   * @param string $key
   * @param array $data
   */
  public function update(string $key, array $data): void {
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
   * @param string $key (url)
   */
  public function delete(string $key): void {
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
   * @param array $integration
   *
   */
  public function storeTranslations(array $integration): void {
    if (isset($integration['integration']['configuration_schema'])) {
      $localeStorage = \Drupal::service('locale.storage');
      $utils = \Drupal::service('ibm_apim.utils');
      try {
        foreach ($integration['integration']['configuration_schema'] as $key => $field) {
          if (isset($field['x-ibm-languages'])) {
            $string = $localeStorage->findString(['source' => $field['x-ibm-label']]);
            if (is_null($string)) {
              $string = new SourceString();
              $string->setString($field['x-ibm-label']);
              $string->setStorage($localeStorage);
              $string->save();
            }
            foreach ($field['x-ibm-languages']['x-ibm-label'] as $langcode => $translation) {
              $langcode = $utils->convert_lang_name_to_drupal($langcode);
              $localeStorage->createTranslation([
                'lid' => $string->lid,
                'language' => $langcode,
                'translation' => $translation,
              ])->save();
            }
          }
        }
      } catch (\Drupal\locale\StringStorageException $e) {
        // quietly just ignore the translation errors
      }
    }
  }

  /**
   * @param array $form
   * @param array $integration
   */
  public function addConfigurationSchemaFields(array &$form, array $integration): void {

    // fallback to local form fields
    $currentLang = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $localeStorage = \Drupal::service('locale.storage');

    // OAI3 format to field type map
    $formatMap = [
      'date' => 'date',
      'date-time' => 'datetime',
      'double' => 'number',
      'email' => 'email',
      'float' => 'number',
      'int32' => 'number',
      'int64' => 'number',
      'number' => 'number',
      'password' => 'password',
      'url' => 'url',
      'text' => 'textarea',
    ];
    if (isset($integration['integration']['configuration_schema'])) {
      // loop over the fields and add them to the form
      foreach ($integration['integration']['configuration_schema'] as $key => $field) {
        if ($key !== 'required') {
          $fieldTitle = $field['x-ibm-label'] ?? $key;
          // have to look up translation manually since not allowed to do t() with variables
          // the form should already be cached per language so if you change language this should be re-evaluated
          $translatedFieldTitle = $localeStorage->findTranslation(['source' => $fieldTitle, 'language' => $currentLang]);
          if ($translatedFieldTitle !== NULL && $translatedFieldTitle->translation !== NULL) {
            $fieldTitle = $translatedFieldTitle->translation;
          }
          if (isset($field['x-ibm-description'])) {
            $fieldDescr = $field['x-ibm-label'];
            $translatedFieldDescr = $localeStorage->findTranslation(['source' => $fieldDescr, 'language' => $currentLang]);
            if ($translatedFieldDescr !== NULL && $translatedFieldDescr->translation !== NULL) {
              $fieldDescr = $translatedFieldDescr->translation;
            }
          }

          if (isset($field['type']) && (!array_key_exists('readOnly', $field) || $field['readOnly'] === FALSE)) {

            //Hidden fields
            if ((array_key_exists('x-ibm-display', $field) && $field['x-ibm-display'] === FALSE) ||
              (array_key_exists('x-ibm-display-form', $field) && $field['x-ibm-display-form'] === FALSE)) {
              $form[$key] = [
                '#type' => 'hidden',
                '#title' => $fieldTitle,
              ];
            }
            // ENUM support
            elseif (isset($field['enum'])) {
              $form[$key] = [
                '#type' => 'select',
                '#title' => $fieldTitle,
              ];
              $options = [];
              foreach ($field['enum'] as $enumValue) {
                $options[$enumValue] = $enumValue;
              }
              $form[$key]['#options'] = $options;
            }
            // Strings
            elseif ($field['type'] === 'string') {
              $form[$key] = [
                '#type' => 'textfield',
                '#title' => $fieldTitle,
              ];
              // if format is set then use a more specific field type
              if (isset($field['format'], $formatMap[$field['format']])) {
                $form[$key]['#type'] = $formatMap[$field['format']];
              }
              if (isset($field['format']) && $field['format'] === 'text') {
                $form[$key]['#wysiwyg'] = FALSE;
              }
            }
            // booleans
            elseif ($field['type'] === 'boolean') {
              $form[$key] = [
                '#type' => 'checkbox',
                '#title' => $fieldTitle,
              ];
            }
            // if there happens to be a default then honour it
            if (isset($field['default'])) {
              $form[$key]['#default_value'] = $field['default'];
            }

            // if the field is in the required array then mark it as such
            if (isset($integration['integration']['configuration_schema']['required']) && in_array($key, $integration['integration']['configuration_schema']['required'], TRUE)) {
              $form[$key]['#required'] = TRUE;
            }
            if (isset($fieldDescr)) {
              $form[$key]['#description'] = $fieldDescr;
            }
          }
        }
      }
    }
  }

}
