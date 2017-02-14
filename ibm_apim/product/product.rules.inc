<?php

/**
 * @file
 * Provides a Rules integration for APIC product events.
 */

/**
 * Implements hook_rules_event_info().
 *
 * Register rules events used by this module.
 *
 * @return array
 */
function product_rules_event_info() {
  $variables = array(
    'product_id' => array(
      'type' => 'text',
      'label' => t('The product ID'),
    ),
    'product_nid' => array(
      'type' => 'integer',
      'label' => t('The product ID'),
    ),
    'product_name' => array(
      'type' => 'text',
      'label' => t('The product name'),
    ),
    'product_version' => array(
      'type' => 'text',
      'label' => t('The product version'),
    ),
    'product_ref' => array(
      'type' => 'text',
      'label' => t('The product reference'),
    ),
    'product_description' => array(
      'type' => 'text',
      'label' => t('The product description'),
    ),
    'product_contact_name' => array(
      'type' => 'text',
      'label' => t('The contact name for the product'),
    ),
    'product_contact_email' => array(
      'type' => 'text',
      'label' => t('The contact email for the product'),
    ),
    'product_contact_url' => array(
      'type' => 'text',
      'label' => t('The contact URL for the product'),
    ),
  );

  $items = array(
    'product_publish' => array(
      'label' => t('After publishing a new product'),
      'group' => t('Product'),
      'module' => 'product',
      'variables' => $variables,
    ),
    'product_update' => array(
      'label' => t('After updating the visibility of a product'),
      'group' => t('Product'),
      'module' => 'product',
      'variables' => $variables,
    ),
    'product_replace' => array(
      'label' => t('After replacing an existing product'),
      'group' => t('Product'),
      'module' => 'product',
      'variables' => $variables,
    ),
    'product_supersede' => array(
      'label' => t('After superseding an existing product'),
      'group' => t('Product'),
      'module' => 'product',
      'variables' => $variables,
    ),
    'product_restage' => array(
      'label' => t('After restaging a product'),
      'group' => t('Product'),
      'module' => 'product',
      'variables' => $variables,
    ),
    'product_deprecate' => array(
      'label' => t('After deprecating a product'),
      'group' => t('Product'),
      'module' => 'product',
      'variables' => $variables,
    ),
    'product_retire' => array(
      'label' => t('After retiring a product'),
      'group' => t('Product'),
      'module' => 'product',
      'variables' => $variables,
    ),
    'product_remove' => array(
      'label' => t('After removing a product'),
      'group' => t('Product'),
      'module' => 'product',
      'variables' => $variables,
    )
  );
  return $items;
}

/**
 * Implement hook_rules_action_info().
 */
function product_rules_action_info() {
  return array(
    'product_rules_action_email_subscribers_owner' => array(
      'label' => t('Email product subscription owners'),
      'group' => t('Product'),
      'parameter' => array(
        'product_nid' => array(
          'type' => 'integer',
          'label' => t('Product NID'),
          'description' => t('Enter the node ID of the product'),
        ),
        'plan_name' => array(
          'type' => 'text',
          'label' => t('Plan name'),
          'description' => t('Enter the name of the plan or leave empty to include all plans in the product'),
          'optional' => TRUE,
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
    'product_rules_action_email_subscribers_members' => array(
      'label' => t('Email product subscription members'),
      'group' => t('Product'),
      'parameter' => array(
        'product_nid' => array(
          'type' => 'integer',
          'label' => t('Product NID'),
          'description' => t('Enter the node ID of the product'),
        ),
        'plan_name' => array(
          'type' => 'text',
          'label' => t('Plan name'),
          'description' => t('Enter the name of the plan or leave empty to include all plans in the product'),
          'optional' => TRUE
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
function product_rules_action_email_subscribers_owner($product_nid, $plan_name, $subject, $message, $from = NULL, $langcode, $settings, RulesState $state, RulesPlugin $element) {
  $product = node_load($product_nid);
  $plan_ref = '';
  if (isset($plan_name)) {
    $plans = unserialize($product->product_plans[$product->language][0]['value']);
    if (in_array($plan_name, array_keys($plans))) {
      $plan_ref = ':' . $plan_name;
    }
  }
  $to_list = product_get_subscribing_owners($product->product_ref[$product->language][0]['value'] . $plan_ref);
  $from = !empty($from) ? str_replace(array("\r", "\n"), '', $from) : NULL;
  $params = array(
    'subject' => $subject,
    'message' => $message,
    'langcode' => $langcode,
  );
  // Set a unique key for this mail.
  $name = isset($element->root()->name) ? $element->root()->name : 'unnamed';
  $key = 'product_rules_action_email_subscribers_owner_' . $name . '_' . $element->elementId();
  $languages = language_list();
  $language = isset($languages[$langcode]) ? $languages[$langcode] : language_default();

  foreach ($to_list as $to) {
    $message = drupal_mail('rules', $key, $to, $language, $params, $from);
  }
  watchdog('rules', 'Sent email to owners subscribing to product %product', array('%product' => $product_nid));
}

// This callback emails the subscribers by using the parameters provided through rules UI
function product_rules_action_email_subscribers_members($product_nid, $plan_name, $subject, $message, $from = NULL, $langcode, $settings, RulesState $state, RulesPlugin $element) {
  $product = node_load($product_nid);
  $plan_ref = '';
  if (isset($plan_name)) {
    $plans = unserialize($product->product_plans[$product->language][0]['value']);
    if (in_array($plan_name, array_keys($plans))) {
      $plan_ref = ':' . $plan_name;
    }
  }
  $to_list = product_get_subscribing_members($product->product_ref[$product->language][0]['value'] . $plan_ref);
  $from = !empty($from) ? str_replace(array("\r", "\n"), '', $from) : NULL;
  $params = array(
    'subject' => $subject,
    'message' => $message,
    'langcode' => $langcode,
  );
  // Set a unique key for this mail.
  $name = isset($element->root()->name) ? $element->root()->name : 'unnamed';
  $key = 'product_rules_action_email_subscribers_members_' . $name . '_' . $element->elementId();
  $languages = language_list();
  $language = isset($languages[$langcode]) ? $languages[$langcode] : language_default();

  foreach ($to_list as $to) {
    $message = drupal_mail('rules', $key, $to, $language, $params, $from);
  }
  watchdog('rules', 'Sent email to members subscribing to product %product', array('%product' => $product_nid));
}