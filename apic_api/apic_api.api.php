<?php

/**
 * @file
 * Hooks related to IBM APIC APIs.
 */

/**
 * @addtogroup hooks
 * @{
 */

use Drupal\node\NodeInterface;

/**
 * Triggered when an API is created
 *
 * @param NodeInterface $node
 *   The Drupal node representing this API
 * @param array $data
 *   The array of data returned by API Manager
 */
function hook_apic_api_create(NodeInterface $node, $data) {

}

/**
 * Triggered when an API is updated
 *
 * @param NodeInterface $node
 *   The Drupal node representing this API
 * @param array $data
 *   The array of data returned by API Manager
 */
function hook_apic_api_update(NodeInterface $node, $data) {

}

/**
 * Triggered when an API is deleted
 *
 * @param NodeInterface $node
 *   The Drupal node representing this API
 */
function hook_apic_api_delete(NodeInterface $node) {

}

/**
 * @} End of "addtogroup hooks".
 */
