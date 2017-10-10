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

include_once DRUPAL_ROOT . '/sites/all/modules/ibm_apim/api/api.install';
function api_drush_command() {

  $items['api-cleanse-drush-command'] = array(
    'description' => 'Clears the API entries back to a clean state.',
    'aliases' => array('cleanse_apis')
  );
  $items['api-updateapi'] = array(
    'description' => 'Updates an API',
    'aliases' => array('uapi'),
    'arguments' => array(
      'event' => 'The event type',
      'api' => 'The JSON API payload'
    )
  );
  $items['api-createapi'] = array(
    'description' => 'Creates an API',
    'aliases' => array('capi'),
    'arguments' => array(
      'event' => 'The event type',
      'api' => 'The JSON API payload'
    )
  );
  $items['api-deleteapi'] = array(
    'description' => 'Deletes an API',
    'aliases' => array('dapi'),
    'arguments' => array(
      'event' => 'The event type',
      'id' => 'The API ID'
    )
  );
  $items['api-massupdate'] = array(
    'description' => 'Mass updates a list of APIs',
    'aliases' => array('mapi'),
    'arguments' => array(
      'apis' => 'The JSON array of APIs',
      'event' => 'The event type'
    )
  );
  $items['api-tidy'] = array(
    'description' => 'Tidies the list of APIs to ensure consistent with APIm',
    'aliases' => array('tapi'),
    'arguments' => array(
      'apirefs' => 'The JSON array of API references'
    )
  );

  return $items;
}

function drush_api_cleanse_drush_command() {
  global $user;
  // in case moderation is on we need cron to run as admin
  // save the current user so we can switch back at the end
  $original_user = $user;
  drupal_save_session(FALSE);
  $user = user_load(1);
  $query = new EntityFieldQuery();
  $query->entityCondition('entity_type', 'node')
    ->entityCondition('bundle', 'api');
  $results = $query->execute();
  if (isset($results['node'])) {
    foreach ($results['node'] as $item) {
      node_delete($item->nid);
    }
  }
  // switch back to original user
  $user = $original_user;
  drupal_save_session(TRUE);
  drupal_set_message(t('All API entries deleted.'), 'success');
}

function drush_api_createOrUpdateApi($func, $event, $api) {
  global $user;
  // in case moderation is on we need cron to run as admin
  // save the current user so we can switch back at the end
  $original_user = $user;
  drupal_save_session(FALSE);
  $user = user_load(1);
  if (isset($api)) {
    if (is_string($api)) {
      $api = json_decode($api, TRUE);
    }
    if (isset($api['document']['definitions']) && empty($api['document']['definitions'])) {
      unset($api['document']['definitions']);
    }
    $ref = $api['document']['info']['x-ibm-name'] . ':' . $api['document']['info']['version'];
    $createdOrUpdated = api_createOrUpdateAPI($api, $event);
    if ($createdOrUpdated) {
      drupal_set_message(t('Drush @func created API @api', array(
        '@func' => $func,
        '@api' => $ref
      )), 'success');
    }
    else {
      drupal_set_message(t('Drush @func updated existing API @api', array(
        '@func' => $func,
        '@api' => $ref
      )), 'success');
    }
    if (module_exists('views')) {
      views_invalidate_cache();
    }
  }
  else {
    drupal_set_message(t('Drush @func No API provided', array('@func' => $func)), 'error');
  }
  // switch back to original user
  $user = $original_user;
  drupal_save_session(TRUE);
}

function drush_api_createapi($event, $api) {
  drush_api_createOrUpdateApi('CreateAPI', $event, $api);
}

function drush_api_updateapi($event, $api) {
  drush_api_createOrUpdateApi('UpdateAPI', $event, $api);
}

function drush_api_deleteapi($event, $id) {
  global $user;
  // in case moderation is on we need cron to run as admin
  // save the current user so we can switch back at the end
  $original_user = $user;
  drupal_save_session(FALSE);
  $user = user_load(1);
  if (isset($id)) {
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'node')
      ->entityCondition('bundle', 'api')
      ->fieldCondition('api_apiid', 'value', $id);
    $results = $query->execute();

    if (isset($results['node'])) {
      $first = array_shift($results['node']);
      $nid = $first->nid;
      $api = node_load($nid);

      //Check this api is not still referenced by a product
      $query = new EntityFieldQuery();
      $query->entityCondition('entity_type', 'node')
        ->entityCondition('bundle', 'product')
        ->fieldCondition('product_apis', 'value', $api->api_ref[$api->language][0]['value'], 'CONTAINS');
      $results = $query->execute();
      if (isset($results['node'])) {
        $prod_nids = array_keys($results['node']);
        $product_ids = array();
        if (is_array($prod_nids) && count($prod_nids) > 0) {
          foreach ($prod_nids as $prod_nid) {
            $product = node_load($prod_nid);
            $product_id = $product->product_ref[$product->language][0]['value'];
            array_push($product_ids, $product_id);
          }
        }
        if (is_array($product_ids) && count($product_ids) > 0) {
          $prodids = implode(" ", $product_ids);
        }
        else {
          $prodids = implode(" ", $prod_nids);
        }
        drupal_set_message(t('Drush DeleteAPI NOT deleting API @api as it is referenced by the following product(s) @prods', array(
          '@api' => $id,
          '@prods' => $prodids
        )), 'warning');
      }
      else {
        api_deleteAPINode($nid, $event);
        drupal_set_message(t('Drush DeleteAPI deleted API @api', array('@api' => $id)), 'success');
      }
    }
    else {
      drupal_set_message(t('Drush DeleteAPI could not find API @api', array('@api' => $id)), 'warning');
    }
  }
  else {
    drupal_set_message(t('Drush DeleteAPI No API provided'), 'error');
  }
  // switch back to original user
  $user = $original_user;
  drupal_save_session(TRUE);
}

function drush_api_massupdate($apis, $event = 'bgsync') {
  if (!empty($apis)) {
    $apis = json_decode($apis, TRUE);
    foreach ($apis as $api) {
      drush_api_createOrUpdateApi('MassUpdate', $event, $api);
    }
  }
}

function drush_api_tidy($apirefs) {
  $apirefs = json_decode($apirefs, TRUE);
  if (!empty($apirefs)) {
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'node')
      ->entityCondition('bundle', 'api')
    ->fieldCondition('api_ref', 'value', $apirefs, 'NOT IN');
    $results = $query->execute();
    if (isset($results['node'])) {
      foreach ($results['node'] as $item) {
        node_delete($item->nid);
      }
    }
  }
}