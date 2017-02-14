<?php

/**
 * @file
 * Provides a Rules integration for APIC developer organization events.
 */

/**
 * Implements hook_rules_event_info().
 *
 * Register rules events used by this module.
 *
 * @return array
 */
function devorg_rules_event_info() {
  $variables = array(
    'devorg_id' => array(
      'type' => 'text',
      'label' => t('The developer organization ID'),
    ),
    'devorg_nid' => array(
      'type' => 'integer',
      'label' => t('The developer organization node ID'),
    ),
    'devorg_name' => array(
      'type' => 'text',
      'label' => t('The developer organization name'),
    ),
    'devorg_owner_email' => array(
      'type' => 'text',
      'label' => t('The developer organization owner email address'),
    ),
    'devorg_member_emails' => array(
      'type' => 'text',
      'label' => t('The developer organization member email addresses'),
    )
  );

  $items = array(
    'devorg_create' => array(
      'label' => t('After creating a new developer organization'),
      'group' => t('Developer organization'),
      'module' => 'devorg',
      'variables' => $variables,
    )
  );
  return $items;
}