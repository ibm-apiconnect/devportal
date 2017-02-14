<?php

/**
 * @file
 * Provides a Rules integration for APIC application events.
 */

/**
 * Implements hook_rules_event_info().
 *
 * Register rules events used by this module.
 *
 * @return array
 */
function application_rules_event_info() {
  $variables = array(
    'application_id' => array(
      'type' => 'text',
      'label' => t('The application ID'),
    ),
    'application_nid' => array(
      'type' => 'integer',
      'label' => t('The application node ID'),
    ),
    'application_name' => array(
      'type' => 'text',
      'label' => t('The application name'),
    ),
    'application_description' => array(
      'type' => 'text',
      'label' => t('The application description'),
    ),
    'application_credentials' => array(
      'type' => 'text',
      'label' => t('The application credentials'),
    ),
    'application_oauth_redirect_uri' => array(
      'type' => 'text',
      'label' => t('The OAuth redirect URI'),
    ),
    'application_devorg_id' => array(
      'type' => 'text',
      'label' => t('The ID of the owning developer organization'),
    ),
    'application_devorg_name' => array(
      'type' => 'text',
      'label' => t('The name of the owning developer organization'),
    ),
    'application_devorg_owner' => array(
      'type' => 'text',
      'label' => t('The owner of the owning developer organization'),
    ),
    'application_devorg_members' => array(
      'type' => 'text',
      'label' => t('The members of the owning developer organization'),
    )
  );

  $plan_variables = $variables + array(
      'plan_ref' => array(
        'type' => 'text',
        'label' => t('The plan reference'),
      ),
      'product_name' => array(
        'type' => 'text',
        'label' => t('The product name'),
      ),
      'product_version' => array(
        'type' => 'text',
        'label' => t('The product version'),
      ),
      'plan_name' => array(
        'type' => 'text',
        'label' => t('The plan name'),
      )
    );

  $items = array(
    'application_create' => array(
      'label' => t('After creating a new application'),
      'group' => t('Application'),
      'module' => 'application',
      'variables' => $variables,
    ),
    'application_update' => array(
      'label' => t('After updating an existing application'),
      'group' => t('Application'),
      'module' => 'application',
      'variables' => $variables,
    ),
    'application_delete' => array(
      'label' => t('After deleting an application'),
      'group' => t('Application'),
      'module' => 'application',
      'variables' => $variables,
    ),
    'application_clientid_reset' => array(
      'label' => t('After resetting the application client ID'),
      'group' => t('Application'),
      'module' => 'application',
      'variables' => $variables + array(
          'application_new_credentials' => array(
            'type' => 'text',
            'label' => t('The new credentials'),
          ),
        ),
    ),
    'application_clientsecret_reset' => array(
      'label' => t('After resetting the application client secret'),
      'group' => t('Application'),
      'module' => 'application',
      'variables' => $variables + array(
          'application_new_credentials' => array(
            'type' => 'text',
            'label' => t('The new credentials'),
          ),
        ),
    ),
    'application_subscribe' => array(
      'label' => t('After subscribing to a plan'),
      'group' => t('Application'),
      'module' => 'application',
      'variables' => $plan_variables + array(
          'plan_requires_approval' => array(
            'type' => 'boolean',
            'label' => t('Whether the plan requires approval'),
          )
        ),
    ),
    'application_unsubscribe' => array(
      'label' => t('After unsubscribing from a plan'),
      'group' => t('Application'),
      'module' => 'application',
      'variables' => $plan_variables,
    ),
    'application_cred_create' => array(
      'label' => t('After saving new application credentials'),
      'group' => t('Application'),
      'module' => 'application',
      'variables' => $variables + array(
          'application_new_credentials' => array(
            'type' => 'text',
            'label' => t('The new credentials'),
          ),
        ),
    ),
    'application_cred_update' => array(
      'label' => t('After updating existing application credentials'),
      'group' => t('Application'),
      'module' => 'application',
      'variables' => $variables + array(
          'application_new_credentials' => array(
            'type' => 'text',
            'label' => t('The new credentials'),
          ),
        ),
    ),
    'application_cred_delete' => array(
      'label' => t('After deleting application credentials'),
      'group' => t('Application'),
      'module' => 'application',
      'variables' => $variables,
    ),
  );
  return $items;
}