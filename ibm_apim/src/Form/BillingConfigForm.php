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

namespace Drupal\ibm_apim\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * APIC billing monetization settings form.
 *
 * Class BillingConfigForm
 *
 * @package Drupal\ibm_apim\Form
 */
class BillingConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'ibm_apim_billing_config_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['ibm_apim.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('ibm_apim.settings');
    $currentLang = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $localeStorage = \Drupal::service('locale.storage');

    $form['intro'] = [
      '#markup' => t('IBM API Developer Portal Billing Integration Settings'),
      '#weight' => -20,
    ];

    $form['providers'] = [
      '#type' => 'fieldset',
      '#title' => t('Billing Provider Module Mapping'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $billingProviders = \Drupal::service('ibm_apim.billing')->getAll();
    $existingMapping = unserialize($config->get('billing_providers'), ['allowed_classes' => FALSE]);
    // get a list of modules implementing our hook so we can give the customer a choice of which module to use
    $moduleData = \Drupal::service('extension.list.module')->reset()->getList();
    $hook_modules = \Drupal::moduleHandler()->getImplementations('consumerorg_payment_method_create_alter');
    $options = [];
    foreach ($hook_modules as $moduleName) {
      // if we can get the module display name then use it
      if (isset($moduleData[$moduleName])) {
        // get translation if available
        $translatedModuleTitle = $localeStorage->findTranslation([
          'source' => $moduleData[$moduleName]->info['name'],
          'language' => $currentLang,
        ]);
        if ($translatedModuleTitle !== NULL && $translatedModuleTitle->translation !== NULL) {
          $moduleTitle = $translatedModuleTitle->translation;
        }
        else {
          $moduleTitle = $moduleData[$moduleName]->info['name'];
        }
        $options[$moduleName] = $moduleTitle . ' (' . $moduleName . ')';
      }
      else {
        $options[$moduleName] = $moduleName;
      }
    }
    if (isset($billingProviders) && !empty($billingProviders)) {
      $currentWeight = 40;
      foreach ($billingProviders as $billingUrl => $billingProvider) {
        $billingProvider = \Drupal::service('ibm_apim.billing')->decrypt($billingUrl);
        if (isset($billingProvider['name'], $existingMapping[$billingProvider['name']])) {
          $default_value = $existingMapping[$billingProvider['name']];
        }
        else {
          $default_value = 'ibm_create_payment_method';
        }

        $form['providers'][$billingProvider['name']] = [
          '#type' => 'select',
          '#title' => $billingProvider['title'],
          '#description' => t('Provide the module to use to create payment methods for this billing provider.'),
          '#required' => TRUE,
          '#weight' => $currentWeight,
          '#default_value' => $default_value,
        ];
        $form['providers'][$billingProvider['name']]['#options'] = $options;

        $currentWeight++;
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Set the submitted configuration settings
    $billingProviders = \Drupal::service('ibm_apim.billing')->getAll();
    $newMapping = [];
    if (isset($billingProviders) && !empty($billingProviders)) {
      foreach ($billingProviders as $billingUrl => $billingProvider) {
        $billingProvider = \Drupal::service('ibm_apim.billing')->decrypt($billingUrl);
        $value = $form_state->getValue($billingProvider['name']) ?? 'ibm_create_payment_method';
        $newMapping[$billingProvider['name']] = $value;
      }
    }
    $this->config('ibm_apim.settings')
      ->set('billing_providers', serialize($newMapping))
      ->save();

    parent::submitForm($form, $form_state);

    // clear all caches
    drupal_flush_all_caches();
  }

}
