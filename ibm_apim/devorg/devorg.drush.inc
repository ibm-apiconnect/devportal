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
      'product' => 'The JSON devorg payload'
    )
  );
  $items['devorg-createorg'] = array(
    'description' => 'Creates a devorg',
    'aliases' => array('corg'),
    'arguments' => array(
      'event' => 'The event type',
      'product' => 'The JSON devorg payload'
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

  return $items;
}

function drush_devorg_cleanse_drush_command() {
  $query = new EntityFieldQuery();
  $query->entityCondition('entity_type', 'node')
    ->entityCondition('bundle', 'devorg');
  $results = $query->execute();
  if (isset($results['node'])) {
    foreach ($results['node'] as $item) {
      node_delete($item->nid);
    }
  }
  drupal_set_message(t('All Developer Organization entries deleted.'), 'success');
}

function drush_devorg_createOrUpdateOrg($func, $event, $devorg) {
	if (isset($devorg)) {
		$devorg = json_decode($devorg, TRUE);
		$ref = $devorg['id'];
      $createdOrUpdated = devorg_createOrUpdateDevorg($devorg, $event);
		if ($createdOrUpdated) {
			drupal_set_message(t('Drush @func created Org @org', array( '@func' => $func, '@org' => $ref)), 'success');
		} else {
			drupal_set_message(t('Drush @func updated existing Org @org', array( '@func' => $func, '@org' => $ref)), 'success');
		}
	}
	else {
		drupal_set_message(t('Drush @func No Org provided', array( '@func' => $func)), 'error');
	}
}

function drush_devorg_createorg($event, $devorg) {
  drush_devorg_createOrUpdateOrg("createOrg", $event, $devorg);
}

function drush_devorg_updateorg($event, $devorg) {
  drush_devorg_createOrUpdateOrg("updateOrg", $event, $devorg);
}

function drush_devorg_deleteorg($event, $id) {
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
  } else {
      drupal_set_message(t('Drush DeleteOrg could not find Org @org', array('@org' => $id)), 'warning');
  }
}
