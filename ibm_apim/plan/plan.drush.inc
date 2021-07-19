<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2015, 2021
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

include_once DRUPAL_ROOT . '/sites/all/modules/ibm_apim/plan/plan.install';
function plan_drush_command() {

  $items['plan-cleanse-drush-command'] = array(
    'description' => 'Clears the plan entries back to a clean state.',
    'aliases' => array('cleanse_plans'),
  );

  return $items;
}

function drush_plan_cleanse_drush_command() {
  $query = new EntityFieldQuery();
  $query->entityCondition('entity_type', 'node')
    ->entityCondition('bundle', 'plan');
  $results = $query->execute();
  if (isset($results['node'])) {
    foreach ($results['node'] as $item) {
      node_delete($item->nid);
    }
  }
  drupal_set_message(t('All Plan entries deleted.'), 'success');
}
