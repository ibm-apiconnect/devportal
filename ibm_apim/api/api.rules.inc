<?php

/**
 * @file
 * Provides a Rules integration for APIC API events.
 */

/**
 * Implement hook_rules_action_info().
 */
function api_rules_action_info() {
  return array(
    'api_rules_action_email_subscribers_owner' => array(
      'label' => t('Email API subscription owners'),
      'group' => t('API'),
      'parameter' => array(
        'api_nid' => array(
          'type' => 'integer',
          'label' => t('API NID'),
          'description' => t('Enter the node ID of the API'),
        ),
        'subject' => array(
          'type' => 'text',
          'label' => t('Subject'),
          'description' => t("The mail's subject."),
          'translatable' => TRUE,
        ),
        'message' => array(
          'type' => 'text',
          'label' => t('Message'),
          'description' => t("The mail's message body."),
          'translatable' => TRUE,
        ),
        'from' => array(
          'type' => 'text',
          'label' => t('From'),
          'description' => t("The mail's from address. Leave it empty to use the site-wide configured address."),
          'optional' => TRUE,
        ),
        'language' => array(
          'type' => 'token',
          'label' => t('Language'),
          'description' => t('If specified, the language used for getting the mail message and subject.'),
          'options list' => 'entity_metadata_language_list',
          'optional' => TRUE,
          'default value' => LANGUAGE_NONE,
          'default mode' => 'selector',
        )
      ),
      'access callback' => 'rules_system_integration_access'
    ),
    'api_rules_action_email_subscribers_members' => array(
      'label' => t('Email API subscription members'),
      'group' => t('API'),
      'parameter' => array(
        'api_nid' => array(
          'type' => 'integer',
          'label' => t('API NID'),
          'description' => t('Enter the node ID of the API'),
        ),
        'subject' => array(
          'type' => 'text',
          'label' => t('Subject'),
          'description' => t("The mail's subject."),
          'translatable' => TRUE,
        ),
        'message' => array(
          'type' => 'text',
          'label' => t('Message'),
          'description' => t("The mail's message body."),
          'translatable' => TRUE,
        ),
        'from' => array(
          'type' => 'text',
          'label' => t('From'),
          'description' => t("The mail's from address. Leave it empty to use the site-wide configured address."),
          'optional' => TRUE,
        ),
        'language' => array(
          'type' => 'token',
          'label' => t('Language'),
          'description' => t('If specified, the language used for getting the mail message and subject.'),
          'options list' => 'entity_metadata_language_list',
          'optional' => TRUE,
          'default value' => LANGUAGE_NONE,
          'default mode' => 'selector',
        )
      ),
      'access callback' => 'rules_system_integration_access'
    )
  );
}

// This callback emails the subscribers by using the parameters provided through rules UI
function api_rules_action_email_subscribers_owner($api_nid, $subject, $message, $from = NULL, $langcode, $settings, RulesState $state, RulesPlugin $element) {
  $to_list = api_get_subscribing_owners($api_nid);
  $from = !empty($from) ? str_replace(array("\r", "\n"), '', $from) : NULL;
  $params = array(
    'subject' => $subject,
    'message' => $message,
    'langcode' => $langcode,
  );
  // Set a unique key for this mail.
  $name = isset($element->root()->name) ? $element->root()->name : 'unnamed';
  $key = 'api_rules_action_email_subscribers_owner_' . $name . '_' . $element->elementId();
  $languages = language_list();
  $language = isset($languages[$langcode]) ? $languages[$langcode] : language_default();

  foreach ($to_list as $to) {
    $message = drupal_mail('rules', $key, $to, $language, $params, $from);
  }
  watchdog('rules', 'Sent email to owners subscribing to API %api', array('%api' => $api_nid));
}

// This callback emails the subscribers by using the parameters provided through rules UI
function api_rules_action_email_subscribers_members($api_nid, $subject, $message, $from = NULL, $langcode, $settings, RulesState $state, RulesPlugin $element) {
  $to_list = api_get_subscribing_members($api_nid);
  $from = !empty($from) ? str_replace(array("\r", "\n"), '', $from) : NULL;
  $params = array(
    'subject' => $subject,
    'message' => $message,
    'langcode' => $langcode,
  );
  // Set a unique key for this mail.
  $name = isset($element->root()->name) ? $element->root()->name : 'unnamed';
  $key = 'api_rules_action_email_subscribers_members_' . $name . '_' . $element->elementId();
  $languages = language_list();
  $language = isset($languages[$langcode]) ? $languages[$langcode] : language_default();

  foreach ($to_list as $to) {
    $message = drupal_mail('rules', $key, $to, $language, $params, $from);
  }
  watchdog('rules', 'Sent email to members subscribing to API %api', array('%api' => $api_nid));
}