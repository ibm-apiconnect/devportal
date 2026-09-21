<?php


namespace Drupal\ibm_apim\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * APIC billing monetization settings form.
 *
 * Class BillingConfigForm
 *
 * @package Drupal\ibm_apim\Form
 */
class AnalyticsConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'ibm_apim_analytics_config';
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
    $dashboard = $config->get('analytics_dashboard');
    if (empty($dashboard)) {
      $dashboard = [
        'total_calls',
        'total_errors',
        'avg_response',
        'num_calls',
        'status_codes',
        'top_products',
        'top_apis',
        'num_throttled',
        'num_errors',
        'call_table',
        'top_api_paths',
        'apis_with_fastest_response_time',
        'apis_with_slowest_response_time'
      ];
    }

    $form['dashboard'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Metrics and charts'),
      '#options' => array(
        'total_calls' => t('Total calls'),
        'total_errors' => t('Total errors'),
        'avg_response' => t('Average response time'),
        'num_calls' => t('Number of API calls'),
        'status_codes' => t('Status codes'),
        'top_products' => t('Top products over time'),
        'top_apis' => t('Top APIs over time'),
        'num_throttled' => t('Number of throttled API calls'),
        'num_errors' => t('Number of errors'),
        'call_table' => t('API call history'),
        'top_api_paths' => t('Top API paths by calls'),
        'apis_with_slowest_response_time' => t('APIs with slowest response time'),
        'apis_with_fastest_response_time' => t('APIs with fastest response time')
      ),
      '#default_value' => $dashboard,
    );

    $form['#attached']['library'][] = 'ibm_apim/analytics_config';
    $form['#attached']['drupalSettings']['analytics']['adminform']['module_path'] = base_path() . \Drupal::service('extension.list.module')->getPath('ibm_apim');

    return parent::buildForm($form, $form_state);
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $dashboard = array_keys(array_filter($form_state->getValue('dashboard')));
    if (empty($dashboard)) {
      $settings_link = Link::fromTextAndUrl(t('Developer Portal settings'), Url::fromRoute('ibm_apim.settings'));
      $form_state->setErrorByName('dashboard', $this->t('The analytics dashboard cannot be empty. Consumer analytics can be disabled in the @settings_link.', ['@settings_link' => $settings_link->toString()]));
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, 'calling parent validateForm');
    return parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Set the submitted configuration settings
    $dashboard = array_keys(array_filter($form_state->getValue('dashboard')));
    
    // Sanitize dashboard values if needed
    $sanitized_dashboard = array_map(function($item) {
      // Only apply htmlspecialchars if the value would be changed
      if ($item !== htmlspecialchars($item, ENT_QUOTES)) {
        return htmlspecialchars($item, ENT_QUOTES);
      }
      return $item;
    }, $dashboard);
    
    $this->config('ibm_apim.settings')
      ->set('analytics_dashboard', $sanitized_dashboard)
      ->save();
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'application');
    $nids = $query->accessCheck(false)->execute();
    $tags = array_values(array_map(fn ($id) => "node:$id", $nids));
    $tags[] = 'consumeranalytics';
    \Drupal::service('cache_tags.invalidator')->invalidateTags($tags);
    parent::submitForm($form, $form_state);
  }
}
