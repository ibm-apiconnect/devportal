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

use Drupal\product\Product;
use Drupal\Core\Session\UserSession;

function product_drush_command() {
  $items = array();
  $items['product-cleanse-drush-command'] = array(
    'description' => 'Clears the product entries back to a clean state.',
    'aliases' => array('cleanse_products')
  );
  $items['product-update'] = array(
    'description' => 'Updates a product',
    'aliases' => array('uprod'),
    'arguments' => array(
      'content' => 'The webhook JSON content',
      'event' => 'The event type'
    )
  );
  $items['product-create'] = array(
    'description' => 'Creates a product',
    'aliases' => array('cprod'),
    'arguments' => array(
      'content' => 'The webhook JSON content',
      'event' => 'The event type'
    )
  );
  $items['product-delete'] = array(
    'description' => 'Deletes a product',
    'aliases' => array('dprod'),
    'arguments' => array(
      'content' => 'The webhook JSON content',
      'event' => 'The event type'
    )
  );
  $items['product-supersede'] = array(
    'description' => 'Supersedes a product (marking it deprecated) and publishes another product',
    'aliases' => array('sprod'),
    'arguments' => array(
      'content' => 'The webhook JSON content',
      'event' => 'The event type'
    )
  );
  $items['product-replace'] = array(
    'description' => 'Replaces a product (removing it) and replaces it with another product',
    'aliases' => array('rprod'),
    'arguments' => array(
      'content' => 'The webhook JSON content',
      'event' => 'The event type'
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
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = Drupal::service('account_switcher');
  $original_user = \Drupal::currentUser();
  if ($original_user->id() != 1) {
    $accountSwitcher->switchTo(new UserSession(array('uid' => 1)));
  }
  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(array('type' => 'product'));

  foreach ($nodes as $node) {
    $node->delete();
  }
  if (isset($original_user) && $original_user->id() != 1) {
    $accountSwitcher->switchBack();
  }
  drush_log(dt('All Product entries deleted.', array()), 'success');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $content
 * @param $event
 * @param $func
 */
function drush_product_createOrUpdate($content, $event, $func) {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  if (isset($content)) {

    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = Drupal::service('account_switcher');
    $original_user = \Drupal::currentUser();
    if ($original_user->id() != 1) {
      $accountSwitcher->switchTo(new UserSession(array('uid' => 1)));
    }
    if (is_string($content)) {
      $content = json_decode($content, TRUE);
    }

    if (isset ($content['product'])) {
      $product = $content['product'];
      $ref = $product['catalog_product']['info']['name'] . ':' . $product['catalog_product']['info']['version'];

      // we don't care about staged, retired, or whatever other state products might be in. we only want published products in the portal.
      if (strtolower($product['state']) !== "published" && strtolower($product['state']) !== "deprecated") {
        drush_product_delete($product, $product['state']);
      }
      else {

        $portalProduct = new Product();
        $createdOrUpdated = $portalProduct->createOrUpdate($product, $event);

        if ($createdOrUpdated) {
          drush_log(dt('Drush @func created Product @prod', array(
            '@func' => $func,
            '@prod' => $ref
          )), 'success');
        }
        else {
          drush_log(dt('Drush @func updated existing Product @prod', array(
            '@func' => $func,
            '@prod' => $ref
          )), 'success');
        }
      }
      $moduleHandler = \Drupal::service('module_handler');
      if ($moduleHandler->moduleExists('views') && $func != 'MassUpdate') {
        views_invalidate_cache();
      }
      if (isset($original_user) && $original_user->id() != 1) {
        $accountSwitcher->switchBack();
      }

      if (isset($content['consumer_apis']) && !empty($content['consumer_apis'])) {
        drush_apic_api_massupdate($content['consumer_apis'], $event);
      }
    }
    else {
      drush_log(dt('Drush @func No Product provided', array('@func' => $func)), 'error');
    }

  }
  else {
    drush_log(dt('Drush @func No Product provided', array('@func' => $func)), 'error');
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $content
 * @param $event
 */
function drush_product_create($content, $event = 'product_lifecycle') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  drush_product_createOrUpdate($content, $event, 'CreateProduct');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $content
 * @param $event
 */
function drush_product_update($content, $event = 'product_update') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  drush_product_createOrUpdate($content, $event, 'UpdateProduct');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $product
 * @param $event
 */
function drush_product_delete($product, $event = 'product_del') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  if (isset($product)) {
    // handle being sent the content payload or the sub-element 'product'
    if (isset($product['product']) && !empty($product['product'])) {
      $product = $product['product'];
    }
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = Drupal::service('account_switcher');
    $original_user = \Drupal::currentUser();
    if ($original_user->id() != 1) {
      $accountSwitcher->switchTo(new UserSession(array('uid' => 1)));
    }
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'product');
    $query->condition('apic_ref.value', $product['name'] . ':' . $product['version']);

    $nids = $query->execute();
    if (isset($nids) && !empty($nids)) {
      $nid = array_shift($nids);
      Product::deleteNode($nid, $event);
      drush_log(dt('Drush DeleteProduct deleted Product @prod', array('@prod' => $product['id'])), 'success');
      $moduleHandler = \Drupal::service('module_handler');
      if ($moduleHandler->moduleExists('views')) {
        views_invalidate_cache();
      }
    }
    else {
      drush_log(dt('Drush DeleteProduct could not find Product @prod', array('@prod' => $product['id'])), 'warning');
    }
    if (isset($original_user) && $original_user->id() != 1) {
      $accountSwitcher->switchBack();
    }
  }
  else {
    drush_log(dt('Drush DeleteProduct No Product provided.', array()), 'error');
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $content
 */
function drush_product_supersede($content) {
  ibm_apim_entry_trace(__FUNCTION__);
  if (isset($content)) {
    if (is_string($content)) {
      $content = json_decode($content, TRUE);
    }
    // We should have two products in the content['products'] array
    if (sizeof($content['products']) === 2) {
      foreach ($content['products'] as $product) {
        if ($product['state'] === 'deprecated') {
          drush_product_update(array('product' => $product), 'product_supersede');
        }
        else {
          if ($product['state'] === 'published') {
            drush_product_create(array('product' => $product), 'product_supersede');
          }
          else {
            // this shouldn't happen
            drush_log(dt('Drush product supersede : found a product in a strange lifecycle state \'@state\'. Expected \'published\' or \'deprecated\'.', array('@state' => $product['state'])), 'error');
          }
        }
      }
      // process apis
      drush_apic_api_massupdate($content['consumer_apis'], 'product_supersede');
    }
    else {
      drush_log(dt('Drush product supersede : incorrect number of products found. Should be 2 but was @count.', array('@count' => sizeof($content['products']))), 'error');
    }
  }
  else {
    drush_log(dt('Drush product supersede : no content provided.', array()), 'error');
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $content
 */
function drush_product_replace($content) {
  ibm_apim_entry_trace(__FUNCTION__);
  if (isset($content)) {
    if (is_string($content)) {
      $content = json_decode($content, TRUE);
    }
    // We should have two products in the content['products'] array
    if (sizeof($content['products']) === 2) {
      foreach ($content['products'] as $product) {
        ;
        if ($product['state'] === 'retired') {
          drush_product_delete($product, 'product_replace');
        }
        else {
          if ($product['state'] === 'deprecated') {
            drush_product_update(array('product' => $product), 'product_replace');
          }
          else {
            if ($product['state'] === 'published') {
              drush_product_create(array('product' => $product), 'product_replace');
            }
            else {
              // this shouldn't happen
              drush_log(dt('Drush product replace : found a product in a strange lifecycle state \'@state\'. Expected \'published\', \'deprecated\' or \'retired\'.', array('@state' => $product['state'])), 'error');
            }
          }
        }
      }
      // process apis
      drush_apic_api_massupdate($content['consumer_apis'], 'product_supersede');
    }
    else {
      drush_log(dt('Drush product replace : incorrect number of products found. Should be 2 but was @count.', array('@count' => sizeof($content['products']))), 'error');
    }
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param array $products
 * @param string $event
 */
function drush_product_massupdate($products = array(), $event = 'content_refresh') {
  ibm_apim_entry_trace(__FUNCTION__, count($products));
  $products = json_decode($products, TRUE);
  if (!empty($products)) {
    foreach ($products as $product) {
      drush_product_createOrUpdate($product, $event, 'MassUpdate');
    }
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('views')) {
      views_invalidate_cache();
    }
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param array $prodrefs
 */
function drush_product_tidy($prodrefs = array()) {
  ibm_apim_entry_trace(__FUNCTION__, count($prodrefs));
  $prodrefs = json_decode($prodrefs, TRUE);
  if (!empty($prodrefs)) {
    $nids = array();
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'product')
      ->condition('apic_ref', $prodrefs, 'NOT IN');
    $results = $query->execute();
    if (isset($results)) {
      foreach ($results as $item) {
        $nids[] = $item;
      }
    }

    foreach ($nids as $nid) {
      Product::deleteNode($nid, 'content_refresh');
    }
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}
