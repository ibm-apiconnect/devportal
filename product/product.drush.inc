<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2021
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

use Drupal\product\Product;
use Drupal\apic_api\Api;
use Drupal\Core\Session\UserSession;

function product_drush_command() {
  $items = [];
  $items['product-cleanse-drush-command'] = [
    'description' => 'Clears the product entries back to a clean state.',
    'aliases' => ['cleanse_products'],
  ];
  $items['product-update'] = [
    'description' => 'Updates a product',
    'aliases' => ['uprod'],
    'arguments' => [
      'content' => 'The webhook JSON content',
      'event' => 'The event type',
    ],
  ];
  $items['product-create'] = [
    'description' => 'Creates a product',
    'aliases' => ['cprod'],
    'arguments' => [
      'content' => 'The webhook JSON content',
      'event' => 'The event type',
    ],
  ];
  $items['product-delete'] = [
    'description' => 'Deletes a product',
    'aliases' => ['dprod'],
    'arguments' => [
      'content' => 'The webhook JSON content',
      'event' => 'The event type',
    ],
  ];
  $items['product-supersede'] = [
    'description' => 'Supersedes a product (marking it deprecated) and publishes another product',
    'aliases' => ['sprod'],
    'arguments' => [
      'content' => 'The webhook JSON content',
      'event' => 'The event type',
    ],
  ];
  $items['product-replace'] = [
    'description' => 'Replaces a product (removing it) and replaces it with another product',
    'aliases' => ['rprod'],
    'arguments' => [
      'content' => 'The webhook JSON content',
      'event' => 'The event type',
    ],
  ];
  $items['product-massupdate'] = [
    'description' => 'Mass updates a list of Products',
    'aliases' => ['mprod'],
    'arguments' => [
      'products' => 'The JSON array of Products',
      'event' => 'The event type',
    ],
  ];
  $items['product-tidy'] = [
    'description' => 'Tidies the list of Products to ensure consistent with APIm',
    'aliases' => ['tprod'],
    'arguments' => [
      'prodrefs' => 'The JSON array of Product references',
    ],
  ];

  return $items;
}

function drush_product_cleanse_drush_command() {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = Drupal::service('account_switcher');
  $originalUser = \Drupal::currentUser();
  if ((int) $originalUser->id() !== 1) {
    $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
  }
  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'product']);

  foreach ($nodes as $node) {
    $node->delete();
  }
  if ($originalUser !== NULL && (int) $originalUser->id() !== 1) {
    $accountSwitcher->switchBack();
  }
  drush_log(dt('All Product entries deleted.', []), 'success');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $content
 * @param $event
 * @param $func
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function drush_product_createOrUpdate($content, $event, $func) {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  if ($content !== NULL) {

    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }
    if (is_string($content)) {
      $content = json_decode($content, TRUE);
    }

    if (isset ($content['product'])) {
      $product = $content['product'];
      $ref = $product['catalog_product']['info']['name'] . ':' . $product['catalog_product']['info']['version'];

      // we don't care about staged, retired, or whatever other state products might be in. we only want published products in the portal.
      $stateToLower = strtolower($product['state']);
      if ($stateToLower !== 'published' && $stateToLower !== 'deprecated') {
        drush_product_delete($product, $product['state']);
      }
      else {

        $portalProduct = new Product();
        $createdOrUpdated = $portalProduct->createOrUpdate($product, $event);

        if ($createdOrUpdated) {
          drush_log(dt('Drush @func created Product @prod', [
            '@func' => $func,
            '@prod' => $ref,
          ]), 'success');
        }
        else {
          drush_log(dt('Drush @func updated existing Product @prod', [
            '@func' => $func,
            '@prod' => $ref,
          ]), 'success');
        }
      }
      $moduleHandler = \Drupal::service('module_handler');
      if ($func !== 'MassUpdate' && $moduleHandler->moduleExists('views')) {
        views_invalidate_cache();
      }

      Product::clearAppCache($content['product']['url']);

      if ($originalUser !== NULL && (int) $originalUser->id() !== 1) {
        $accountSwitcher->switchBack();
      }

      if (isset($content['consumer_apis']) && !empty($content['consumer_apis'])) {
        drush_apic_api_massupdate($content['consumer_apis'], $event);
      }
    }
    else {
      drush_log(dt('Drush @func No Product provided', ['@func' => $func]), 'error');
    }

  }
  else {
    drush_log(dt('Drush @func No Product provided', ['@func' => $func]), 'error');
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $content
 * @param string $event
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function drush_product_create($content, $event = 'product_lifecycle') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  drush_product_createOrUpdate($content, $event, 'CreateProduct');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $content
 * @param string $event
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
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
  if ($product !== NULL) {
    // handle being sent the content payload or the sub-element 'product'
    if (!isset($product['product'])) {
      $newProduct['product'] = $product;
      $product = $newProduct;
    }
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'product');
    $query->condition('apic_ref.value', $product['product']['name'] . ':' . $product['product']['version']);

    $nids = $query->execute();
    if ($nids !== NULL && !empty($nids)) {
      $nid = array_shift($nids);
      Product::deleteNode($nid, $event);
      drush_log(dt('Drush DeleteProduct deleted Product @prod', ['@prod' => $product['product']['id']]), 'success');
    }
    else {
      drush_log(dt('Drush DeleteProduct could not find Product @prod', ['@prod' => $product['product']['id']]), 'warning');
    }
    // check if any APIs are in retired state, if so delete them too
    if (isset($product['consumer_apis']) && !empty($product['consumer_apis'])) {
      foreach ($product['consumer_apis'] as $consumer_api) {
        if (isset($consumer_api['state']) && strtolower($consumer_api['state']) === 'retired') {
          $query = \Drupal::entityQuery('node');
          $query->condition('type', 'api');
          $query->condition('status', 1);
          $query->condition('apic_url', $consumer_api['url']);
          $apiNids = $query->execute();
          if ($apiNids !== NULL && !empty($apiNids)) {
            $apiNid = array_shift($apiNids);
            Api::deleteNode($apiNid, $event);
            drush_log(dt('Drush DeleteProduct deleted retired API @api', ['@api' => $consumer_api['url']]), 'success');
          }
        }
      }
    }
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('views')) {
      views_invalidate_cache();
    }
    if ($originalUser !== NULL && (int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }
  }
  else {
    drush_log(dt('Drush DeleteProduct No Product provided.', []), 'error');
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $content
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function drush_product_supersede($content) {
  ibm_apim_entry_trace(__FUNCTION__);
  if ($content !== NULL) {
    if (is_string($content)) {
      $content = json_decode($content, TRUE);
    }
    // We should have two products in the content['products'] array
    if (sizeof($content['products']) === 2) {
      foreach ($content['products'] as $product) {
        if ($product['state'] === 'deprecated') {
          $deprecatedProdUrl = $product['url'];
          drush_product_update(['product' => $product], 'product_supersede');
        }
        elseif ($product['state'] === 'published') {
          drush_product_create(['product' => $product], 'product_supersede');
        }
        else {
          // this shouldn't happen
          drush_log(dt('Drush product supersede : found a product in a strange lifecycle state \'@state\'. Expected \'published\' or \'deprecated\'.', ['@state' => $product['state']]), 'error');
        }
      }
      // process apis
      drush_apic_api_massupdate($content['consumer_apis'], 'product_supersede');

      // update all subscribed apps
      if (isset($deprecatedProdUrl)) {
        $query = \Drupal::entityQuery('apic_app_application_subs');
        $query->condition('product_url', $deprecatedProdUrl);
        $subIds = $query->execute();
        if (isset($subIds) && !empty($subIds)) {
          foreach (array_chunk($subIds, 50) as $chunk) {
            $subEntities = \Drupal::entityTypeManager()->getStorage('apic_app_application_subs')->loadMultiple($chunk);
            foreach($subEntities as $sub) {
              $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['apic_url' => $sub->get('app_url')->value]);
              if (count($nodes) == 1) {
                array_pop($nodes)->save();
              } else {
                drush_log(dt('Drush product supersede : incorrect number of applications with url \'@url\' found. Should be 1 but was @count.', ['@url' => $deprecatedProdUrl,'@count' => count($nodes)]), 'error');
              }
            }
          }
        }
      }
    }
    else {
      drush_log(dt('Drush product supersede : incorrect number of products found. Should be 2 but was @count.', ['@count' => sizeof($content['products'])]), 'error');
    }
  }
  else {
    drush_log(dt('Drush product supersede : no content provided.', []), 'error');
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $content
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function drush_product_replace($content) {
  ibm_apim_entry_trace(__FUNCTION__);
  if ($content !== NULL) {
    if (is_string($content)) {
      $content = json_decode($content, TRUE);
    }
    // We should have two products in the content['products'] array
    if (sizeof($content['products']) === 2) {
      foreach ($content['products'] as $product) {
        if ($product['state'] === 'retired') {
          drush_product_delete($product, 'product_replace');
        }
        elseif ($product['state'] === 'deprecated') {
          drush_product_update(['product' => $product], 'product_replace');
        }
        elseif ($product['state'] === 'published') {
          drush_product_create(['product' => $product], 'product_replace');
        }
        else {
          // this shouldn't happen
          drush_log(dt('Drush product replace : found a product in a strange lifecycle state \'@state\'. Expected \'published\', \'deprecated\' or \'retired\'.', ['@state' => $product['state']]), 'error');
        }
      }
      // process apis
      drush_apic_api_massupdate($content['consumer_apis'], 'product_supersede');
    }
    else {
      drush_log(dt('Drush product replace : incorrect number of products found. Should be 2 but was @count.', ['@count' => sizeof($content['products'])]), 'error');
    }
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param array $products
 * @param string $event
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function drush_product_massupdate($products = [], $event = 'content_refresh') {
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
 * @param array $prodRefs
 */
function drush_product_tidy($prodRefs = []) {
  ibm_apim_entry_trace(__FUNCTION__, count($prodRefs));
  $prodRefs = json_decode($prodRefs, TRUE);
  if (!empty($prodRefs)) {
    $nids = [];
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'product')
      ->condition('apic_ref', $prodRefs, 'NOT IN');
    $results = $query->execute();
    if ($results !== NULL) {
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
