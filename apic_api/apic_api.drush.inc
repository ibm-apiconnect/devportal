<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

use Drupal\node\Entity\Node;
use Drupal\apic_api\Api;
use Drupal\Core\Session\UserSession;

/**
 * @return mixed
 */
function apic_api_drush_command() {

  $items['apic-api-cleanse-drush-command'] = array(
    'description' => 'Clears the API entries back to a clean state.',
    'aliases' => array('cleanse_apis')
  );
  $items['apic-api-update'] = array(
    'description' => 'Updates an API',
    'aliases' => array('uapi'),
    'arguments' => array(
      'event' => 'The event type',
      'api' => 'The JSON API payload'
    )
  );
  $items['apic-api-create'] = array(
    'description' => 'Creates an API',
    'aliases' => array('capi'),
    'arguments' => array(
      'event' => 'The event type',
      'api' => 'The JSON API payload'
    )
  );
  $items['apic-api-delete'] = array(
    'description' => 'Deletes an API',
    'aliases' => array('dapi'),
    'arguments' => array(
      'event' => 'The event type',
      'id' => 'The API ID'
    )
  );
  $items['apic-api-massupdate'] = array(
    'description' => 'Mass updates a list of APIs',
    'aliases' => array('mapi'),
    'arguments' => array(
      'apis' => 'The JSON array of APIs',
      'event' => 'The event type'
    )
  );
  $items['apic-api-tidy'] = array(
    'description' => 'Tidies the list of APIs to ensure consistent with APIm',
    'aliases' => array('tapi'),
    'arguments' => array(
      'apirefs' => 'The JSON array of API references'
    )
  );


  return $items;
}

function drush_apic_api_cleanse_drush_command() {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = Drupal::service('account_switcher');
  $original_user = \Drupal::currentUser();
  if ($original_user->id() != 1) {
    $accountSwitcher->switchTo(new UserSession(array('uid' => 1)));
  }
  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(array('type' => 'api'));

  foreach ($nodes as $node) {
    $node->delete();
  }
  if (isset($original_user) && $original_user->id() != 1) {
    $accountSwitcher->switchBack();
  }
  drush_log(dt('All API entries deleted.'), 'success');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $func
 * @param $event
 * @param $api
 */
function drush_apic_api_createOrUpdate($api, $event, $func) {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  if (isset($api)) {
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = Drupal::service('account_switcher');
    $original_user = \Drupal::currentUser();
    if ($original_user->id() != 1) {
      $accountSwitcher->switchTo(new UserSession(array('uid' => 1)));
    }
    if (is_string($api)) {
      $api = json_decode($api, TRUE);
    }
    if (isset($api['consumer_api']['definitions']) && empty($api['consumer_api']['definitions'])) {
      unset($api['consumer_api']['definitions']);
    }
    $ref = $api['consumer_api']['info']['x-ibm-name'] . ':' . $api['consumer_api']['info']['version'];

    $portalApi = new Api();
    $createdOrUpdated = $portalApi->createOrUpdate($api, $event);
    if ($createdOrUpdated) {
      drush_log(dt('Drush @func created API @api', array(
        '@func' => $func,
        '@api' => $ref
      )), 'success');
    }
    else {
      drush_log(dt('Drush @func updated existing API @api', array(
        '@func' => $func,
        '@api' => $ref
      )), 'success');
    }
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('views') && $func != 'MassUpdate') {
      views_invalidate_cache();
    }
    if (isset($original_user) && $original_user->id() != 1) {
      $accountSwitcher->switchBack();
    }
  }
  else {
    drush_log(dt('Drush @func No API provided', array('@func' => $func)), 'error');
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $event
 * @param $api
 */
function drush_apic_api_create($api, $event = 'api_create') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  drush_apic_api_createOrUpdate($api, $event, 'CreateAPI');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $event
 * @param $api
 */
function drush_apic_api_update($api, $event = 'api_update') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  drush_apic_api_createOrUpdate($api, $event, 'UpdateAPI');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $event
 * @param $api
 */
function drush_apic_api_delete($api, $event = 'api_delete') {
  ibm_apim_entry_trace(__FUNCTION__);
  if (isset($api)) {
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = Drupal::service('account_switcher');
    $original_user = \Drupal::currentUser();
    if ($original_user->id() != 1) {
      $accountSwitcher->switchTo(new UserSession(array('uid' => 1)));
    }

    if(is_string($api)) {

      $api = json_decode($api, TRUE);
    }
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'api');
    $query->condition('status', 1);
    $query->condition('apic_url', $api['url']);

    $nids = $query->execute();
    if (isset($nids) && !empty($nids)) {
      $nid = array_shift($nids);
      $apiNode = Node::load($nid);

      //Check this api is not still referenced by a product
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'product');
      $query->condition('product_apis', $apiNode->apic_ref->value, 'CONTAINS');

      $results = $query->execute();
      if (isset($results) && !empty($results)) {
        $product_ids = array();
        if (is_array($results) && count($results) > 0) {
          foreach ($results as $prod_nid) {
            $product = Node::load($prod_nid);
            $product_id = $product->apic_ref->value;
            array_push($product_ids, $product_id);
          }
        }
        if (is_array($product_ids) && count($product_ids) > 0) {
          $prodids = implode(" ", $product_ids);
        }
        else {
          $prodids = implode(" ", $results);
        }
        drush_log(dt('Drush DeleteAPI NOT deleting API @api as it is referenced by the following product(s) @prods', array(
          '@api' => $apiNode->id,
          '@prods' => $prodids
        )), 'warning');
      }
      else {
        Api::deleteNode($nid, $event);
        drush_log(dt('Drush DeleteAPI deleted API @api', array('@api' => $api['url'])), 'success');
        $moduleHandler = \Drupal::service('module_handler');
        if ($moduleHandler->moduleExists('views')) {
          views_invalidate_cache();
        }
      }
    }
    else {
      drush_log(dt('Drush DeleteAPI could not find API @api', array('@api' => $api['url'])), 'warning');
    }
    if (isset($original_user) && $original_user->id() != 1) {
      $accountSwitcher->switchBack();
    }
  }
  else {
    drush_log(dt('Drush DeleteAPI No API provided'), 'error');
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

function drush_apic_api_massupdate($apis = array(), $event = 'api_massupdate') {
  ibm_apim_entry_trace(__FUNCTION__, count($apis));

  if (is_string($apis)) {
    $apis = json_decode($apis, TRUE);
  }

  if (!empty($apis)) {
    foreach ($apis as $api) {
      drush_apic_api_createOrUpdate($api, $event, 'MassUpdate');
    }
  }
  $moduleHandler = \Drupal::service('module_handler');
  if ($moduleHandler->moduleExists('views')) {
    views_invalidate_cache();
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

function drush_apic_api_tidy($apirefs = array()) {
  ibm_apim_entry_trace(__FUNCTION__, count($apirefs));
  $apirefs = json_decode($apirefs, TRUE);
  if (!empty($apirefs)) {
    $nids = array();
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'api')
      ->condition('apic_ref', $apirefs, 'NOT IN');
    $results = $query->execute();
    if (isset($results)) {
      foreach ($results as $item) {
        $nids[] = $item;
      }
    }

    foreach ($nids as $nid) {
      Api::deleteNode($nid, 'MassUpdate');
    }
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}
