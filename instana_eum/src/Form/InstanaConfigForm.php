<?php

namespace Drupal\instana_eum\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/* +**************** {COPYRIGHT-TOP} ********************
 * Licensed Materials - Property of IBM
 *
 * (C) Copyright IBM Corporation 2018, 2024
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 * **************** {COPYRIGHT-END} *********************
 */

/**
 * Instana settings form.
 */
class InstanaConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'instana_eum_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['instana_eum.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $config = $this->config('instana_eum.settings');

    $form['intro'] = [
      '#markup' => t('Configures Instana End User Monitoring (EUM) beacon module. Please see the <a href="https://www.instana.com/docs/website_monitoring/" target="_blank">official documentation</a> for further details.'),
      '#weight' => -30,
    ];

    $form['api_key'] = [
      '#type' => 'password',
      '#default_value' => $config->get('api_key') ?? '',
      '#title' => t('API Key'),
      '#description' => t("Enter the API key for your Instana server."),
      '#required' => TRUE,
      '#maxlen' => 255,
      '#attributes' => ['value' => $config->get('api_key')],
    ];

    $form['reporting_url'] = [
      '#type' => 'textfield',
      '#title' => t('Reporting URL'),
      '#description' => t("Enter the Instana server reporting URL."),
      '#default_value' => $config->get('reporting_url') ?? '',
      '#required' => TRUE,
      '#maxlen' => 255,
    ];

    $form['track_pages'] = [
      '#type' => 'checkbox',
      '#title' => t('Track individual pages'),
      '#description' => t("Isolate specific pages and analyze their performance to find pages with the most traffic, or the slowest response times."),
      '#default_value' => $config->get('track_pages') ?? FALSE,
    ];

    $form['track_admin'] = [
      '#type' => 'checkbox',
      '#title' => t('Admin page tracking'),
      '#description' => t("Include monitoring of pages with /admin in the url."),
      '#default_value' => $config->get('track_admin') ?? FALSE,
    ];

    $advancedDescription = '
    <span>' . t('You can use this to set additional EUM settings.') . '</span>
    <a href="https://www.instana.com/docs/website_monitoring/api/" target="_blank">' . t('See documentation here') . '</a>.
    <p>' . t('Examples:') . '</p>
    <pre>ineum(\'meta\', \'version\', \'1.42.3\');</pre>
    <pre>ineum(\'ignoreUrls\', [/.*\/api\/data.*/]);</pre>';

    $form['advanced_settings'] = [
      '#type' => 'textarea',
      '#title' => t('Advanced Settings'),
      '#description' => $advancedDescription,
      '#default_value' => $config->get('advanced_settings') ?? '',
      '#required' => FALSE,
    ];

    return parent::buildForm($form, $form_state);

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

    $this->config('instana_eum.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->set('reporting_url', $form_state->getValue('reporting_url'))
      ->set('track_pages', $form_state->getValue('track_pages'))
      ->set('track_admin', $form_state->getValue('track_admin'))
      ->set('advanced_settings', $form_state->getValue('advanced_settings'))
      ->save();

    parent::submitForm($form, $form_state);

  }

}
