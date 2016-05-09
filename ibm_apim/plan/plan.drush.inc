<?php
include_once DRUPAL_ROOT . '/sites/all/modules/ibm_apim/plan/plan.install';
function plan_drush_command() {

  $items['plan-cleanse-drush-command'] = array(
    'description' => 'Clears the plan entries back to a clean state.',
    'aliases' => array('cleanse_plans'),
  );

  return $items;
}

function drush_plan_cleanse_drush_command() {
  _plan_delete_node_content('plan');
  _plan_delete_custom_fields();
  field_purge_batch(500);
  drupal_set_message(t('All Plan entries deleted.'), 'success');
}
