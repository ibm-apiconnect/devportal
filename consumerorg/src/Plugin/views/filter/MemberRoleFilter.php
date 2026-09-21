<?php


namespace Drupal\consumerorg\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\StringFilter;

/**
 * Filters by member role.
 *
 * @ViewsFilter("member_role_filter")
 */
class MemberRoleFilter extends StringFilter {

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
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [];

    // Check if filter is configured as exposed and form state confirms it
    if (($this->options['exposed'] ?? FALSE) && $form_state->get('exposed')) {
      // Simple text input for role URL filtering
      $form['value'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Role URL'),
        '#default_value' => $this->value,
        '#placeholder' => $this->t('Enter role URL to filter'),
      ];
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

    $this->ensureMyTable();

    // If no value is selected, show all
    if (empty($this->value)) {
      return;
    }

    // Filter by role URL in the JSON array
    $this->query->addWhere($this->options['group'], "$this->tableAlias.role_urls", '%' . $this->connection->escapeLike($this->value) . '%', 'LIKE');
  }

}
