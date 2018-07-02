<?php

/**
 * @file
 * Hooks related to IBM APIC Products.
 */

/**
 * @addtogroup hooks
 * @{
 */

use Drupal\node\NodeInterface;

/**
 * Triggered when a product is created
 *
 * @param NodeInterface $node
 *   The Drupal node representing this product
 * @param array $data
 *   The array of data returned by API Manager
 */
function hook_product_create(NodeInterface $node, $data) {

}

/**
 * Triggered when a product is updated
 *
 * @param NodeInterface $node
 *   The Drupal node representing this product
 * @param array $data
 *   The array of data returned by API Manager
 */
function hook_product_update(NodeInterface $node, $data) {

}

/**
 * Triggered when a product is deleted
 *
 * @param NodeInterface $node
 *   The Drupal node representing this product
 */
function hook_product_delete(NodeInterface $node) {

}

/**
 * @} End of "addtogroup hooks".
 */
