<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2024, 2026
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\consumerorg\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\consumerorg\ConsumerOrgMemberInviteInterface;

/**
 * Filters by member/invitation type.
 *
 * @ViewsFilter("member_type_filter")
 */
class MemberTypeFilter extends InOperator {

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

    $this->valueOptions = [
      ConsumerOrgMemberInviteInterface::TYPE_MEMBER => $this->t('Member'),
      ConsumerOrgMemberInviteInterface::TYPE_INVITATION => $this->t('Invitation'),
    ];

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

    $this->ensureMyTable();

    // If no value is selected, show all
    if (empty($this->value)) {
      return;
    }

    $this->query->addWhere($this->options['group'], "$this->tableAlias.type", $this->value, 'IN');
  }

}
