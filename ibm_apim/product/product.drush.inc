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

include_once DRUPAL_ROOT . '/sites/all/modules/ibm_apim/product/product.install';
function product_drush_command() {

  $items['product-cleanse-drush-command'] = array(
    'description' => 'Clears the product entries back to a clean state.',
    'aliases' => array('cleanse_products')
  );
  $items['product-updateproduct'] = array(
    'description' => 'Updates a product',
    'aliases' => array('uprod'),
    'arguments' => array(
      'event' => 'The event type',
      'product' => 'The JSON product payload'
    )
  );
  $items['product-createproduct'] = array(
    'description' => 'Creates a product',
    'aliases' => array('cprod'),
    'arguments' => array(
      'event' => 'The event type',
      'product' => 'The JSON product payload'
    )
  );
  $items['product-deleteproduct'] = array(
    'description' => 'Deletes a product',
    'aliases' => array('dprod'),
    'arguments' => array(
      'event' => 'The event type',
      'name' => 'The product name:version'
    )
  );
  $items['product-massupdate'] = array(
    'description' => 'Mass updates a list of Products',
    'aliases' => array('mprod'),
    'arguments' => array(
      'products' => 'The JSON array of Products',
      'event' => 'The event type'
    )
  );
  $items['product-tidy'] = array(
    'description' => 'Tidies the list of Products to ensure consistent with APIm',
    'aliases' => array('tprod'),
    'arguments' => array(
      'prodrefs' => 'The JSON array of Product references'
    )
  );

  return $items;
}

function drush_product_cleanse_drush_command() {
  global $user;
  // in case moderation is on we need cron to run as admin
  // save the current user so we can switch back at the end
  $original_user = $user;
  drupal_save_session(FALSE);
  $user = user_load(1);
  $query = new EntityFieldQuery();
  $query->entityCondition('entity_type', 'node')
    ->entityCondition('bundle', 'product');
  $results = $query->execute();
  if (isset($results['node'])) {
    foreach ($results['node'] as $item) {
      node_delete($item->nid);
    }
  }
  // switch back to original user
  $user = $original_user;
  drupal_save_session(TRUE);
  drupal_set_message(t('All Product entries deleted.'), 'success');
}

function drush_product_createOrUpdateProduct($func, $event, $product) {
  global $user;
  // in case moderation is on we need cron to run as admin
  // save the current user so we can switch back at the end
  $original_user = $user;
  drupal_save_session(FALSE);
  $user = user_load(1);
  if (isset($product)) {
    if (is_string($product)) {
      $product = json_decode($product, TRUE);
    }
    $ref = $product['document']['info']['name'] . ':' . $product['document']['info']['version'];

    if (strtolower($product['state']) == "retired") {
      drush_product_deleteproduct('retired', $ref);
    }
    else {
      $createdOrUpdated = product_createOrUpdateProduct($product, $event);

      if ($createdOrUpdated) {
        drupal_set_message(t('Drush @func created Product @prod', array(
          '@func' => $func,
          '@prod' => $ref
        )), 'success');
      }
      else {
        drupal_set_message(t('Drush @func updated existing Product @prod', array(
          '@func' => $func,
          '@prod' => $ref
        )), 'success');
      }
    }
    if (module_exists('views')) {
      views_invalidate_cache();
    }
  }
  else {
    drupal_set_message(t('Drush @func No Product provided', array('@func' => $func)), 'error');
  }
  // switch back to original user
  $user = $original_user;
  drupal_save_session(TRUE);
}

function drush_product_createproduct($event, $product) {
  drush_product_createOrUpdateProduct('CreateProduct', $event, $product);
}

function drush_product_updateproduct($event, $product) {
  drush_product_createOrUpdateProduct('UpdateProduct', $event, $product);
}

function drush_product_deleteproduct($event, $ref) {
  global $user;
  // in case moderation is on we need cron to run as admin
  // save the current user so we can switch back at the end
  $original_user = $user;
  drupal_save_session(FALSE);
  $user = user_load(1);
  if (isset($ref)) {
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'node')
      ->entityCondition('bundle', 'product')
      ->fieldCondition('product_ref', 'value', $ref);
    $results = $query->execute();
    if (isset($results['node'])) {
      $first = array_shift($results['node']);
      $nid = $first->nid;
      product_deleteProductNode($nid, $event);
      drupal_set_message(t('Drush DeleteProduct deleted Product @prod', array('@prod' => $ref)), 'success');
    }
    else {
      drupal_set_message(t('Drush DeleteProduct could not find Product @prod', array('@prod' => $ref)), 'warning');
    }
  }
  else {
    drupal_set_message(t('Drush DeleteProduct No Product provided.'), 'error');
  }
  // switch back to original user
  $user = $original_user;
  drupal_save_session(TRUE);
}

function drush_product_massupdate($products, $event = 'bgsync') {
  if (!empty($products)) {
    $products = json_decode($products, TRUE);
    foreach ($products as $product) {
      drush_product_createOrUpdateProduct('MassUpdate', $event, $product);
    }
  }
}

function drush_product_tidy($prodrefs) {
  $prodrefs = json_decode($prodrefs, TRUE);
  if (!empty($prodrefs)) {
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'node')
      ->entityCondition('bundle', 'product')
      ->fieldCondition('product_ref', 'value', $prodrefs, 'NOT IN');
    $results = $query->execute();
    if (isset($results['node'])) {
      foreach ($results['node'] as $item) {
        node_delete($item->nid);
      }
    }
  }
}