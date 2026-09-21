<?php


namespace Drupal\product\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\InOperator;

/**
 * Filters products by API type (REST, SOAP, GraphQL, AsyncAPI, etc.).
 *
 * This filter allows users to filter products based on the types of APIs
 * they contain. A product will be shown if it contains at least one API
 * of the selected type(s).
 *
 * @ViewsFilter("api_type_filter")
 */
class ApiTypeFilter extends InOperator {

  protected $valueFormType = 'select';

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['expose']['contains']['sort_values'] = ['default' => 0];
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultExposeOptions() {
    parent::defaultExposeOptions();
    $this->options['expose']['sort_values'] = 1;
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    if (isset($this->valueOptions)) {
      return $this->valueOptions;
    }

    $this->valueOptions = [];

    // Get allowed values from the api_protocol field definition
    try {
      $field_storage = \Drupal::entityTypeManager()
        ->getStorage('field_storage_config')
        ->load('node.api_protocol');
      
      if ($field_storage) {
        $allowed_values = $field_storage->getSetting('allowed_values');
        
        if (is_array($allowed_values)) {
          foreach ($allowed_values as $allowed_value) {
            if (is_array($allowed_value) && isset($allowed_value['value']) && isset($allowed_value['label'])) {
              $this->valueOptions[$allowed_value['value']] = $this->t($allowed_value['label']);
            }
          }
        }
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('product')->error('Error loading API protocol field: @message', ['@message' => $e->getMessage()]);
    }

    // Fallback to hardcoded values if nothing was loaded
    if (empty($this->valueOptions)) {
      $this->valueOptions = [
        'rest' => $this->t('REST'),
        'wsdl' => $this->t('SOAP'),
        'graphql' => $this->t('GraphQL'),
        'asyncapi' => $this->t('AsyncAPI'),
        'kafka' => $this->t('Kafka'),
        'mq' => $this->t('MQ'),
        'oauth' => $this->t('OAuth Provider'),
      ];
    }

    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [];

    // Check if filter is configured as exposed and form state confirms it
    if (($this->options['exposed'] ?? FALSE) && $form_state->get('exposed')) {
      parent::valueForm($form, $form_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // When not exposed, don't filter
    if (!($this->options['exposed'] ?? FALSE)) {
      return;
    }

    // If no value is selected, show all
    if (empty($this->value)) {
      return;
    }

    $this->ensureMyTable();

    // Build a subquery to find product NIDs that have APIs with the selected protocol types
    $subquery = \Drupal::database()->select('node__product_api_nids', 'pan');
    $subquery->addField('pan', 'entity_id');
    $subquery->join('node__api_protocol', 'ap', 'pan.product_api_nids_value = ap.entity_id');
    $subquery->condition('ap.api_protocol_value', $this->value, 'IN');
    $subquery->distinct();

    // Add WHERE condition to filter products
    $this->query->addWhere(
      $this->options['group'],
      "$this->tableAlias.nid",
      $subquery,
      'IN'
    );
  }

}
