<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2022
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_event_log\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ibm_apim\Service\EventLogService;

/**
 * APIC Stripe monetization settings form.
 */
class ConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'ibm_event_log_config_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['ibm_event_log.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('ibm_event_log.settings');

    $retentionDays = (int) $config->get('retention_days');

    // set a default if its not set
    if ($retentionDays === NULL || $retentionDays <= 0) {
      $retentionDays = EventLogService::DEFAULT_RETENTION;
    }
    elseif ($retentionDays < EventLogService::MIN_RETENTION) {
      $retentionDays = EventLogService::MIN_RETENTION;
    }
    elseif ($retentionDays > EventLogService::MAX_RETENTION) {
      $retentionDays = EventLogService::MAX_RETENTION;
    }

    $form['intro'] = [
      '#markup' => t('IBM Notifications Event Log Settings'),
      '#weight' => -20,
    ];

    $form['retention'] = [
      '#type' => 'fieldset',
      '#title' => t('Retention'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $form['retention']['retention_days'] = [
      '#type' => 'number',
      '#title' => t('Retention Days'),
      '#description' => t('Provide the number of days to keep events for.'),
      '#min' => EventLogService::MIN_RETENTION,
      '#max' => EventLogService::MAX_RETENTION,
      '#step' => 1,
      '#default_value' => $retentionDays,
      '#required' => TRUE,
      '#weight' => 40,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Set the submitted configuration settings
    $retentionDays = (int) $form_state->getValue('retention_days');
    if ($retentionDays === NULL) {
      $retentionDays = EventLogService::DEFAULT_RETENTION;
    }
    elseif ($retentionDays < EventLogService::MIN_RETENTION) {
      $retentionDays = EventLogService::MIN_RETENTION;
    }
    elseif ($retentionDays > EventLogService::MAX_RETENTION) {
      $retentionDays = EventLogService::MAX_RETENTION;
    }

    $this->config('ibm_event_log.settings')
      ->set('retention_days', $retentionDays)
      ->save();

    parent::submitForm($form, $form_state);
  }

}
