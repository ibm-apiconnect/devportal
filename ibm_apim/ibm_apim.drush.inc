<?php

function ibm_apim_drush_command() {

  $items['ibm_apim-updatecatalog'] = array(
    'description' => 'Updates the catalog',
    'aliases' => array('ucat'),
    'arguments' => array(
      'catalog' => 'The JSON catalog payload',
    )
  );
  $items['ibm_apim-updateconfig'] = array(
    'description' => 'Updates the config',
    'aliases' => array('ucon'),
    'arguments' => array(
      'config' => 'The JSON config payload',
    )
  );
  $items['ibm_apim-bgsync'] = array(
    'description' => 'Runs background sync',
    'aliases' => array('bgsync')
  );

  return $items;
}

function drush_ibm_apim_bgsync() {
  ibm_apim_cron();
  drupal_set_message(t('Background sync run.'), 'success');
}

function drush_ibm_apim_updatecatalog($catalog) {
  ibm_apim_update_catalog($catalog);
  drupal_set_message(t('Catalog updated.'), 'success');
}

function drush_ibm_apim_updateconfig($config) {
  ibm_apim_update_config($config);
  drupal_set_message(t('Config updated.'), 'success');
}
