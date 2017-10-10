<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2015, 2016
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

include_once DRUPAL_ROOT . '/sites/all/modules/ibm_apim/application/application.install';
function application_drush_command() {

  $items['application-cleanse-drush-command'] = array(
    'description' => 'Clears the application entries back to a clean state.',
    'aliases' => array('cleanse_applications')
  );
  $items['application-updateapp'] = array(
    'description' => 'Updates an application',
    'aliases' => array('uapp'),
    'arguments' => array(
      'event' => 'The event type',
      'app' => 'The JSON application payload'
    )
  );
  $items['application-createapp'] = array(
    'description' => 'Creates an application',
    'aliases' => array('capp'),
    'arguments' => array(
      'event' => 'The event type',
      'app' => 'The JSON application payload'
    )
  );
  $items['application-deleteapp'] = array(
    'description' => 'Deletes an application',
    'aliases' => array('dapp'),
    'arguments' => array(
      'event' => 'The event type',
      'id' => 'The application id'
    )
  );
  $items['application-createsub'] = array(
    'description' => 'Creates a subscription',
    'aliases' => array('csub'),
    'arguments' => array(
      'event' => 'The event type',
      'app' => 'The JSON subscription payload'
    )
  );
  $items['application-updatesub'] = array(
    'description' => 'Updates a subscription',
    'aliases' => array('usub'),
    'arguments' => array(
      'event' => 'The event type',
      'app' => 'The JSON subscription payload'
    )
  );
  $items['application-deletesub'] = array(
    'description' => 'Deletes a subscription',
    'aliases' => array('dsub'),
    'arguments' => array(
      'event' => 'The event type',
      'id' => 'The subscription id'
    )
  );
  $items['application-massupdate'] = array(
    'description' => 'Mass updates a list of Applications',
    'aliases' => array('mapp'),
    'arguments' => array(
      'apps' => 'The JSON array of Applications',
      'event' => 'The event type'
    )
  );
  $items['application-sub-massupdate'] = array(
    'description' => 'Mass updates a list of Subscriptions',
    'aliases' => array('msub'),
    'arguments' => array(
      'subs' => 'The JSON array of Subscriptions',
      'event' => 'The event type'
    )
  );
  $items['application-tidy'] = array(
    'description' => 'Tidies the list of Applications to ensure consistent with APIm',
    'aliases' => array('tapp'),
    'arguments' => array(
      'appids' => 'The JSON array of app IDs'
    )
  );
  $items['application-sub-tidy'] = array(
    'description' => 'Tidies the list of Subscriptions to ensure consistent with APIm',
    'aliases' => array('tsub'),
    'arguments' => array(
      'appids' => 'The JSON array of app IDs, with list of subs per appid'
    )
  );

  return $items;
}

function drush_application_cleanse_drush_command() {
  $query = new EntityFieldQuery();
  $query->entityCondition('entity_type', 'node')
    ->entityCondition('bundle', 'application');
  $results = $query->execute();
  if (isset($results['node'])) {
    foreach ($results['node'] as $item) {
      node_delete($item->nid);
    }
  }
  drupal_set_message(t('All Application entries deleted.'), 'success');
}

function drush_application_createOrUpdateapp($func, $event, $app) {
  if (isset($app)) {
    if (is_string($app)) {
      $app = json_decode($app, TRUE);
    }

    $ref = $app['id'];
    $createdOrUpdated = application_createOrUpdateApplication($app, $event);
    if ($createdOrUpdated) {
      drupal_set_message(t('Drush @func created Application @app', array(
        '@func' => $func,
        '@app' => $ref
      )), 'success');
    }
    else {
      drupal_set_message(t('Drush @func updated existing Application @app', array(
        '@func' => $func,
        '@app' => $ref
      )), 'success');
    }
    if (module_exists('views')) {
      views_invalidate_cache();
    }
  }
  else {
    drupal_set_message(t('Drush @func No Application provided', array('@func' => $func)), 'error');
  }
}

function drush_application_createapp($event, $app) {
  drush_application_createOrUpdateapp('CreateApplication', $event, $app);
}

function drush_application_updateapp($event, $app) {
  drush_application_createOrUpdateapp('UpdateApplication', $event, $app);
}

function drush_application_deleteapp($event, $id) {
  global $user;
  // in case moderation is on we need cron to run as admin
  // save the current user so we can switch back at the end
  $original_user = $user;
  drupal_save_session(FALSE);
  $user = user_load(1);
  if (isset($id)) {
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'node')
      ->entityCondition('bundle', 'application')
      ->fieldCondition('application_apiid', 'value', $id);

    $results = $query->execute();

    if (isset($results['node'])) {
      $first = array_shift($results['node']);
      $nid = $first->nid;
      node_delete($nid);
      drupal_set_message(t('Drush DeleteApplication deleted Application @app', array('@app' => $id)), 'success');
    }
    else {
      drupal_set_message(t('Drush DeleteApplication could not find Application @app', array('@app' => $id)), 'warning');
    }
  }
  else {
    drupal_set_message(t('Drush DeleteApplication No Application id provided'), 'error');
  }
  // switch back to original user
  $user = $original_user;
  drupal_save_session(TRUE);
}

function drush_application_createOrUpdatesub($func, $event, $subinput) {
  global $user;
  // in case moderation is on we need cron to run as admin
  // save the current user so we can switch back at the end
  $original_user = $user;
  drupal_save_session(FALSE);
  $user = user_load(1);
  if (isset($subinput)) {
    if (is_string($subinput)) {
      $subinput = json_decode($subinput, TRUE);
    }
    $ref = $subinput['id'];
    $createdOrUpdated = application_createOrUpdateSubscription($subinput, $event);
    if ($createdOrUpdated) {
      drupal_set_message(t('Drush @func created Subscription @sub', array(
        '@func' => $func,
        '@sub' => $ref
      )), 'success');
    }
    else {
      drupal_set_message(t('Drush @func updated existing Subscription @sub', array(
        '@func' => $func,
        '@sub' => $ref
      )), 'success');
    }
  }
  else {
    drupal_set_message(t('Drush @func No Subscription provided', array('@func' => $func)), 'error');
  }
  // switch back to original user
  $user = $original_user;
  drupal_save_session(TRUE);
}

function drush_application_createsub($event, $subinput) {
  drush_application_createOrUpdatesub('CreateSubscription', $event, $subinput);
}

function drush_application_updatesub($event, $subinput) {
  drush_application_createOrUpdatesub('UpdateSubscription', $event, $subinput);
}

function drush_application_deletesub($event, $appid, $subid) {
  global $user;
  // in case moderation is on we need cron to run as admin
  // save the current user so we can switch back at the end
  $original_user = $user;
  drupal_save_session(FALSE);
  $user = user_load(1);
  if (isset($appid) && isset($subid)) {
    application_remove_subscription_id($appid, $subid);
    drupal_set_message(t('Subscription deleted.'), 'success');
  }
  else {
    drupal_set_message(t('No subscription provided'), 'error');
  }
  // switch back to original user
  $user = $original_user;
  drupal_save_session(TRUE);
}

function drush_application_massupdate($apps, $event = 'bgsync') {
  $apps = json_decode($apps, TRUE);
  if (!empty($apps)) {
    foreach ($apps as $app) {
      drush_application_createOrUpdateapp('MassUpdate', $event, $app);
    }
  }
}

function drush_application_sub_massupdate($subs, $event = 'bgsync') {
  $subs = json_decode($subs, TRUE);
  if (!empty($subs)) {
    foreach ($subs as $sub) {
      drush_application_createOrUpdatesub('MassUpdate', $event, $sub);
    }
  }
}

function drush_application_tidy($appids) {
  $appids = json_decode($appids, TRUE);
  if (!empty($appids)) {
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'node')
      ->entityCondition('bundle', 'application')
      ->fieldCondition('application_apiid', 'value', $appids, 'NOT IN');
    $results = $query->execute();
    if (isset($results['node'])) {
      foreach ($results['node'] as $item) {
        node_delete($item->nid);
      }
    }
  }
}

function drush_application_sub_tidy($appids) {
   $appids = json_decode($appids, TRUE);
   if (!empty($appids)) {

    // Any applications that exist in the database that were not mentioned in the list of apps=>subscriptions
    // must have no subscriptions at all. Insert empty arrays to record this
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'node')->entityCondition('bundle', 'application')->fieldCondition('application_apiid', 'value', array_keys($appids), 'NOT IN');
    $results = $query->execute();

    if (isset($results['node'])) {
      foreach($results['node'] as $resultNode){
        $nid = $resultNode->nid;
        $node = node_load($nid);
        $appids[$node->application_apiid[$node->language][0]['value']] = [];
      }
    }

    foreach ($appids as $appid => $appsub) {
      $query = new EntityFieldQuery();
      $query->entityCondition('entity_type', 'node')->entityCondition('bundle', 'application')->fieldCondition('application_apiid', 'value', $appid);
      $results = $query->execute();

      if (isset($results['node'])) {
        foreach ($results['node'] as $resultNode) {
          $nid = $resultNode->nid;
          $node = node_load($nid);
          if (isset($node)) {
            if (isset($node->application_subscriptions[$node->language][0]) && is_array($node->application_subscriptions[$node->language][0])) {
              $node->application_subscriptions[$node->language][0]['value'] = serialize($appsub);
            }
            else {
              $node->application_subscriptions[$node->language][] = array(
                'value' => serialize($appsub),
                'format' => 'plain_text'
              );
            }
            node_save($node);
          }
        }
      }
    }
  }
}