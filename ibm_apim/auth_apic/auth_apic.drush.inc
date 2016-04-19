<?php

function auth_apic_drush_command() {

  $items['auth-apic-cleanse-users-drush-command'] = array(
    'description' => 'Clears the user entries back to a clean state.',
    'aliases' => array('cleanse_auth_apic_users'),
  );

  return $items;
}

function drush_auth_apic_cleanse_users_drush_command() {
  $query = new EntityFieldQuery;
  $query->entityCondition('entity_type', 'user')
    ->propertyCondition('uid', array(0, 1), 'NOT IN');
  $results = $query->execute();

  $uids = array();

  if (is_array($results) && is_array($results["user"])) {
    foreach ($results["user"] as $result) {
      if (is_object($result)) {
        $uids[] = $result->uid;
      }
    }
  }

  user_delete_multiple($uids);
  drupal_set_message(t('All non-admin user entries deleted.'), 'success');
}
