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
 * APIC settings form.
 *
 * Class DevelForm
 *
 * @package Drupal\ibm_apim\Form
 */
class DevelForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormID(): string {
    return 'ibm_apim_devel_settings';
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

    $form['intro'] = [
      '#markup' => t('IBM API Developer Portal Development Settings'),
      '#weight' => -20,
    ];

    $isInCloud = (boolean) \Drupal::service('ibm_apim.site_config')->isInCloud();
    if ($isInCloud !== TRUE) {
      $form['debug'] = [
        '#type' => 'fieldset',
        '#title' => t('Debug'),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
      ];

      $form['debug']['entry_exit_trace'] = [
        '#type' => 'checkbox',
        '#title' => t('Enable method entry / exit trace'),
        '#default_value' => $config->get('entry_exit_trace'),
        '#weight' => 10,
        '#description' => t('WARNING: Not to be used on production servers. It greatly increases the amount of debug information in the logs.'),
      ];
      $form['debug']['apim_rest_trace'] = [
        '#type' => 'checkbox',
        '#title' => t('Enable API Manager REST interface debug'),
        '#default_value' => $config->get('apim_rest_trace'),
        '#weight' => 15,
        '#description' => t('WARNING: Not to be used on production servers. It greatly increases the amount of debug information in the logs.'),
      ];
      $form['debug']['webhook_debug'] = [
        '#type' => 'checkbox',
        '#title' => t('Enable webhook payload debug'),
        '#default_value' => $config->get('webhook_debug'),
        '#weight' => 15,
        '#description' => t('WARNING: Not to be used on production servers. It stores all webhook payloads in the database for debug. This does not scale well!'),
      ];
      $form['debug']['acl_debug'] = [
        '#type' => 'checkbox',
        '#title' => t('Enable node access control debug'),
        '#default_value' => $config->get('acl_debug'),
        '#weight' => 20,
        '#description' => t('WARNING: Not to be used on production servers. It greatly increases the amount of debug information in the logs.'),
      ];
      $insecure = \Drupal::state()->get('ibm_apim.insecure');
      $form['debug']['insecure'] = [
        '#type' => 'checkbox',
        '#title' => t('Insecure mode (disable Consumer API certificate validation)'),
        '#default_value' => $insecure,
        '#weight' => 20,
        '#description' => t('WARNING: Not to be used on production servers. It disables certificate validation when calling the APIM Consumer API, this leaves you vulnerable to \'Man in the middle\' attacks.'),
      ];
    }
    else {
      $form['cloud'] = [
        '#markup' => t('These development options are not available when running in IBM Cloud.'),
        '#weight' => -50,
      ];
    }
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $isInCloud = (boolean) \Drupal::service('ibm_apim.site_config')->isInCloud();
    if ($isInCloud !== TRUE) {
      // Set the submitted configuration setting
      $this->config('ibm_apim.settings')
        ->set('entry_exit_trace', (bool) $form_state->getValue('entry_exit_trace'))
        ->set('apim_rest_trace', (bool) $form_state->getValue('apim_rest_trace'))
        ->set('webhook_debug', (bool) $form_state->getValue('webhook_debug'))
        ->set('acl_debug', (bool) $form_state->getValue('acl_debug'))
        ->save();
      \Drupal::state()->set('ibm_apim.insecure', (bool) $form_state->getValue('insecure'));
    }
    if ((bool) $form_state->getValue('apim_rest_trace') === FALSE) {
      \Drupal::state()->set('ibm_apim.rest_requests', null);

    }
    if ((bool) $form_state->getValue('webhook_debug') === FALSE) {
      \Drupal::state()->set('ibm_apim.webhook_payloads', null);
      \Drupal::state()->set('ibm_apim.snapshot_webhook_payloads', null);
    }
    parent::submitForm($form, $form_state);
  }

}
