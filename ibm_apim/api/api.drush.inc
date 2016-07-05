<?php
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
      'api' => 'The JSON API payload'
    )
  );
  $items['api-createapi'] = array(
    'description' => 'Creates an API',
    'aliases' => array('capi'),
    'arguments' => array(
      'api' => 'The JSON API payload'
    )
  );
  $items['api-deleteapi'] = array(
    'description' => 'Deletes an API',
    'aliases' => array('dapi'),
    'arguments' => array(
      'id' => 'The API ID'
    )
  );

  return $items;
}

function drush_api_cleanse_drush_command() {
  $query = new EntityFieldQuery();
  $query->entityCondition('entity_type', 'node')
    ->entityCondition('bundle', 'api');
  $results = $query->execute();
  if (isset($results['node'])) {
    foreach ($results['node'] as $item) {
      node_delete($item->nid);
    }
  }
  drupal_set_message(t('All API entries deleted.'), 'success');
}

function drush_api_createOrUpdateApi($func, $api) {
  if (isset($api)) {
    $api = json_decode($api, TRUE);
    if (isset($api['document']['definitions']) && empty($api['document']['definitions'])) {
      unset($api['document']['definitions']);
    }
    $ref = $api['document']['info']['x-ibm-name'] . ':' . $api['document']['info']['version'];
    $createdOrUpdated = api_createOrUpdateAPI($api);
    if ($createdOrUpdated) {
      drupal_set_message(t('Drush @func created API @api', array('@func' => $func, '@api' => $ref)), 'success');
    }
    else {
      drupal_set_message(t('Drush @func updated existing API @api', array('@func' => $func, '@api' => $ref)), 'success');
    }
  }
  else {
    drupal_set_message(t('Drush @func No API provided', array('@func' => $func)), 'error');
  }
}

function drush_api_createapi($api) {
  drush_api_createOrUpdateApi('CreateAPI', $api);
}

function drush_api_updateapi($api) {
  drush_api_createOrUpdateApi('UpdateAPI', $api);
}

function drush_api_deleteapi($id) {
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
        api_deleteAPINode($nid);
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
}

