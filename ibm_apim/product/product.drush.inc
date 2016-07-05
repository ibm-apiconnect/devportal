<?php
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
      'product' => 'The JSON product payload'
    )
  );
  $items['product-createproduct'] = array(
    'description' => 'Creates a product',
    'aliases' => array('cprod'),
    'arguments' => array(
      'product' => 'The JSON product payload'
    )
  );
  $items['product-deleteproduct'] = array(
    'description' => 'Deletes a product',
    'aliases' => array('dprod'),
    'arguments' => array(
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

function drush_product_createOrUpdateProduct($func,$product) {
	if (isset($product)) {
		$product = json_decode($product, TRUE);
		$ref = $product['document']['info']['name'] . ':' . $product['document']['info']['version'];
		
		if (strtolower($product['state']) == "retired") {
			drush_product_deleteproduct($ref);
		} else {
			$createdOrUpdated = product_createOrUpdateProduct($product);
		
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
function drush_product_createproduct($product) {
  drush_product_createOrUpdateProduct('CreateProduct',$product);
}

function drush_product_updateproduct($product) {
  drush_product_createOrUpdateProduct('UpdateProduct',$product);
}

function drush_product_deleteproduct($ref) {
  if (isset($ref)) {
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'node')
      ->entityCondition('bundle', 'product')
      ->fieldCondition('product_ref', 'value', $ref);
    $results = $query->execute();
    if (isset($results['node'])) {
      $first = array_shift($results['node']);
      $nid = $first->nid;
      product_deleteProductNode($nid);
      drupal_set_message(t('Drush DeleteProduct deleted Product @prod', array( '@prod' => $ref)), 'success');
    } else {
      drupal_set_message(t('Drush DeleteProduct could not find Product @prod', array( '@prod' => $ref)), 'warning');
    }
  }
  else {
    drupal_set_message(t('Drush DeleteProduct No Product provided.'), 'error');
  }
}