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

  return $items;
}

function drush_product_cleanse_drush_command() {
  $query = new EntityFieldQuery();
  $query->entityCondition('entity_type', 'node')
    ->entityCondition('bundle', 'product');
  $results = $query->execute();
  if (isset($results['node'])) {
    foreach ($results['node'] as $item) {
      node_delete($item->nid);
    }
  }
  drupal_set_message(t('All Product entries deleted.'), 'success');
}

function drush_product_createOrUpdateProduct($func, $event, $product) {
	if (isset($product)) {
		$product = json_decode($product, TRUE);
		$ref = $product['document']['info']['name'] . ':' . $product['document']['info']['version'];
		
		if (strtolower($product['state']) == "retired") {
          drush_product_deleteproduct('retired', $ref);
		} else {
          $createdOrUpdated = product_createOrUpdateProduct($product, $event);
		
			if ($createdOrUpdated) {
				drupal_set_message(t('Drush @func created Product @prod', array( '@func' => $func, '@prod' => $ref)), 'success');
			} else {
				drupal_set_message(t('Drush @func updated existing Product @prod', array( '@func' => $func, '@prod' => $ref)), 'success');
			}
		}
	}
	else {
		drupal_set_message(t('Drush @func No Product provided', array( '@func' => $func)), 'error');
	}
}

function drush_product_createproduct($event, $product) {
  drush_product_createOrUpdateProduct('CreateProduct', $event, $product);
}

function drush_product_updateproduct($event, $product) {
  drush_product_createOrUpdateProduct('UpdateProduct', $event, $product);
}

function drush_product_deleteproduct($event, $ref) {
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
      drupal_set_message(t('Drush DeleteProduct deleted Product @prod', array( '@prod' => $ref)), 'success');
    } else {
      drupal_set_message(t('Drush DeleteProduct could not find Product @prod', array( '@prod' => $ref)), 'warning');
    }
  }
  else {
    drupal_set_message(t('Drush DeleteProduct No Product provided.'), 'error');
  }
}