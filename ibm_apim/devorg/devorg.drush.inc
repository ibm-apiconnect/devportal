<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2016
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

include_once DRUPAL_ROOT . '/sites/all/modules/ibm_apim/devorg/devorg.install';
function devorg_drush_command() {

  $items['devorg-cleanse-drush-command'] = array(
    'description' => 'Clears the devorg entries back to a clean state.',
    'aliases' => array('cleanse_devorgs')
  );
  $items['devorg-updateorg'] = array(
    'description' => 'Updates a devorg',
    'aliases' => array('uorg'),
    'arguments' => array(
      'event' => 'The event type',
      'devorg' => 'The JSON devorg payload'
    )
  );
  $items['devorg-createorg'] = array(
    'description' => 'Creates a devorg',
    'aliases' => array('corg'),
    'arguments' => array(
      'event' => 'The event type',
      'devorg' => 'The JSON devorg payload'
    )
  );
  $items['devorg-deleteorg'] = array(
    'description' => 'Deletes a devorg',
    'aliases' => array('dorg'),
    'arguments' => array(
      'event' => 'The event type',
      'id' => 'The devorg ID'
    )
  );
  $items['devorg-massupdate'] = array(
    'description' => 'Mass updates a list of Devorgs',
    'aliases' => array('morg'),
    'arguments' => array(
      'devorgs' => 'The JSON array of Devorgs',
      'event' => 'The event type'
    )
  );
  $items['devorg-tidy'] = array(
    'description' => 'Tidies the list of Devorgs to ensure consistent with APIm',
    'aliases' => array('torg'),
    'arguments' => array(
      'devorgids' => 'The JSON array of devorg IDs'
    )
  );

  return $items;
}

function drush_devorg_cleanse_drush_command() {
  global $user;
  // in case moderation is on we need cron to run as admin
  // save the current user so we can switch back at the end
  $original_user = $user;
  drupal_save_session(FALSE);
  $user = user_load(1);
  $query = new EntityFieldQuery();
  $query->entityCondition('entity_type', 'node')
    ->entityCondition('bundle', 'devorg');
  $results = $query->execute();
  if (isset($results['node'])) {
    foreach ($results['node'] as $item) {
      node_delete($item->nid);
    }
  }
  // switch back to original user
  $user = $original_user;
  drupal_save_session(TRUE);
  drupal_set_message(t('All Developer Organization entries deleted.'), 'success');
}

function drush_devorg_createOrUpdateOrg($func, $event, $devorg) {
  global $user;
  // in case moderation is on we need cron to run as admin
  // save the current user so we can switch back at the end
  $original_user = $user;
  drupal_save_session(FALSE);
  $user = user_load(1);
  if (isset($devorg)) {
    if (is_string($devorg)) {
      $devorg = json_decode($devorg, TRUE);
    }
    $ref = $devorg['id'];
    $createdOrUpdated = devorg_createOrUpdateDevorg($devorg, $event);
    if ($createdOrUpdated) {
      drupal_set_message(t('Drush @func created Org @org', array(
        '@func' => $func,
        '@org' => $ref
      )), 'success');
    }
    else {
      drupal_set_message(t('Drush @func updated existing Org @org', array(
        '@func' => $func,
        '@org' => $ref
      )), 'success');
    }
    if (module_exists('views')) {
      views_invalidate_cache();
    }
  }
  else {
    drupal_set_message(t('Drush @func No Org provided', array('@func' => $func)), 'error');
  }
  // switch back to original user
  $user = $original_user;
  drupal_save_session(TRUE);
}

function drush_devorg_createorg($event, $devorg) {
  drush_devorg_createOrUpdateOrg("createOrg", $event, $devorg);
}

function drush_devorg_updateorg($event, $devorg) {
  drush_devorg_createOrUpdateOrg("updateOrg", $event, $devorg);
}

function drush_devorg_deleteorg($event, $id) {
  global $user;
  // in case moderation is on we need cron to run as admin
  // save the current user so we can switch back at the end
  $original_user = $user;
  drupal_save_session(FALSE);
  $user = user_load(1);
  $query = new EntityFieldQuery();
  $query->entityCondition('entity_type', 'node')
    ->entityCondition('bundle', 'devorg')
    ->fieldCondition('devorg_id', 'value', $id);
  $results = $query->execute();
  if (isset($results['node'])) {
    $first = array_shift($results['node']);
    $nid = $first->nid;
    node_delete($nid);
    drupal_set_message(t('Drush DeleteOrg deleted Org @org', array('@org' => $id)), 'success');
  }
  else {
    drupal_set_message(t('Drush DeleteOrg could not find Org @org', array('@org' => $id)), 'warning');
  }
  // switch back to original user
  $user = $original_user;
  drupal_save_session(TRUE);
}

function drush_devorg_massupdate($devorgs, $event = 'bgsync') {
  $devorgs = json_decode($devorgs, TRUE);
  if (!empty($devorgs)) {
    foreach ($devorgs as $devorg) {
      drush_devorg_createOrUpdateOrg('MassUpdate', $event, $devorg);
    }
  }
}

function drush_devorg_tidy($devorgids) {
  $devorgids = json_decode($devorgids, TRUE);
  if (!empty($devorgids)) {
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'node')
      ->entityCondition('bundle', 'devorg')
      ->fieldCondition('devorg_id', 'value', $devorgids, 'NOT IN');
    $results = $query->execute();
    if (isset($results['node'])) {
      foreach ($results['node'] as $item) {
        node_delete($item->nid);
      }
    }
  }
}
