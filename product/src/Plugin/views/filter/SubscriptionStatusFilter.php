<?php

namespace Drupal\product\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\Core\Database\Database;

/**
 * Filters products if they already have a subscription or not across the consumer org
 *
 * @ViewsFilter("subscription_status_filter")
 */
class SubscriptionStatusFilter extends InOperator implements ContainerFactoryPluginInterface {

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
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);
  }


  public function defaultExposeOptions() {
    parent::defaultExposeOptions();
    $this->options['expose']['sort_values'] = 1;
  }

  public function buildExposeForm(&$form, FormStateInterface $form_state) {
    parent::buildExposeForm($form, $form_state);
  }

  /**
   * No operator options, it's just IN
   *
   * @return array
   */
  public function operatorOptions($wich = 'in') {
    return [];
  }


  public function getValueOptions() {
    if (isset($this->valueOptions)) {
      return $this->valueOptions;
    }

    $this->valueOptions = [
      'Subscribed To',
      'Not Subscribed To'
    ];
  }


  /**
   * Only show the value form in the exposed form if authenticated and not admin user
   *
   * {@inheritdoc}
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [];

    if (\Drupal::currentUser()->isAnonymous() || (int) \Drupal::currentUser()->id() === 1) {
      $form_state->set('exposed', FALSE);
      $this->options['exposed'] = FALSE;
    }

    if ($form_state->get('exposed')) {
      parent::valueForm($form, $form_state);
    }

  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $this->ensureMyTable();

    $value = isset($this->value) ? (bool) $this->value[0] : FALSE;
    $operator = $value ? 'NOT IN' : 'IN';

    $org = \Drupal::service('ibm_apim.user_utils')->getCurrentConsumerOrg();
    if (!empty($org['url'])) {
      $productUrls = Database::getConnection()
      ->query("SELECT DISTINCT product_url FROM {apic_app_application_subs} WHERE consumerorg_url = :consumer_org_url", [':consumer_org_url' => $org['url']])->fetchCol();
      if (empty($productUrls)) {
        // Set to none if no productUrls are subscribed too so we can return an empty list.
        $productUrls = [ 'none' ];
      }
      $this->query->addWhere($this->options['group'], "$this->tableAlias.apic_url_value", $productUrls, $operator);
    }
  }

}
