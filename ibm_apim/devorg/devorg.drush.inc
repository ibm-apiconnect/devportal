<?php
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
      'product' => 'The JSON devorg payload'
    )
  );
  $items['devorg-createorg'] = array(
    'description' => 'Creates a devorg',
    'aliases' => array('corg'),
    'arguments' => array(
      'product' => 'The JSON devorg payload'
    )
  );
  $items['devorg-deleteorg'] = array(
    'description' => 'Deletes a devorg',
    'aliases' => array('dorg'),
    'arguments' => array(
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

function drush_devorg_createOrUpdateOrg($func,$devorg) {
	if (isset($devorg)) {
		$devorg = json_decode($devorg, TRUE);
		$ref = $devorg['id'];
		$createdOrUpdated = devorg_createOrUpdateDevorg($devorg);
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

function drush_devorg_createorg($devorg) {
  drush_devorg_createOrUpdateOrg("createOrg",$devorg);
}

function drush_devorg_updateorg($devorg) {
  drush_devorg_createOrUpdateOrg("updateOrg",$devorg);
}

function drush_devorg_deleteorg($id) {
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
