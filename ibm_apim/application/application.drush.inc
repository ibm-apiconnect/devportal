<?php
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
      'app' => 'The JSON application payload'
    )
  );
  $items['application-createapp'] = array(
    'description' => 'Creates an application',
    'aliases' => array('capp'),
    'arguments' => array(
      'app' => 'The JSON application payload'
    )
  );
  $items['application-deleteapp'] = array(
    'description' => 'Deletes an application',
    'aliases' => array('dapp'),
    'arguments' => array(
      'id' => 'The application id'
    )
  );
  $items['application-createsub'] = array(
    'description' => 'Creates a subscription',
    'aliases' => array('csub'),
    'arguments' => array(
      'app' => 'The JSON subscription payload'
    )
  );
  $items['application-updatesub'] = array(
    'description' => 'Updates a subscription',
    'aliases' => array('usub'),
    'arguments' => array(
      'app' => 'The JSON subscription payload'
    )
  );
  $items['application-deletesub'] = array(
    'description' => 'Deletes a subscription',
    'aliases' => array('dsub'),
    'arguments' => array(
      'id' => 'The subscription id'
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

function drush_application_createOrUpdateapp($func, $app) {
  if (isset($app)) {
    $app = json_decode($app, TRUE);
    $ref = $app['id'];
    $createdOrUpdated = application_createOrUpdateApplication($app);
    if ($createdOrUpdated) {
      drupal_set_message(t('Drush @func created Application @app', array('@func' => $func, '@app' => $ref)), 'success');
    }
    else {
      drupal_set_message(t('Drush @func updated existing Application @app', array(
        '@func' => $func,
        '@app' => $ref
      )), 'success');
    }
  }
  else {
    drupal_set_message(t('Drush @func No Application provided', array('@func' => $func)), 'error');
  }
}

function drush_application_createapp($app) {
  drush_application_createOrUpdateapp('CreateApplication', $app);
}

function drush_application_updateapp($app) {
  drush_application_createOrUpdateapp('UpdateApplication', $app);
}

function drush_application_deleteapp($id) {
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
}

function drush_application_createOrUpdatesub($func, $subinput) {
  if (isset($subinput)) {
    $subinput = json_decode($subinput, TRUE);
    $ref = $subinput['id'];
    $createdOrUpdated = application_createOrUpdateSubscription($subinput);
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
}

function drush_application_createsub($subinput) {
  drush_application_createOrUpdatesub('CreateSubscription', $subinput);
}

function drush_application_updatesub($subinput) {
  drush_application_createOrUpdatesub('UpdateSubscription', $subinput);
}

function drush_application_deletesub($appid, $subid) {
  if (isset($appid) && isset($subid)) {
    application_remove_subscription_id($appid, $subid);
    drupal_set_message(t('Subscription deleted.'), 'success');
  }
  else {
    drupal_set_message(t('No subscription provided'), 'error');
  }
}
