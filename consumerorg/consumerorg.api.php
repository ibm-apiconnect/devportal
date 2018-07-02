<?php

/**
 * @file
 * Hooks related to IBM APIC consumer organizations.
 */

/**
 * @addtogroup hooks
 * @{
 */

use Drupal\node\NodeInterface;

/**
 * Triggered when a consumer org is created
 *
 * @param NodeInterface $node
 *   The Drupal node representing this consumer org
 * @param array $data
 *   The array of data returned by API Manager
 */
function hook_consumerorg_create(NodeInterface $node, $data) {

}

/**
 * Triggered when a consumer org is updated
 *
 * @param NodeInterface $node
 *   The Drupal node representing this consumer org
 * @param array $data
 *   The array of data returned by API Manager
 */
function hook_consumerorg_update(NodeInterface $node, $data) {

}

/**
 * Triggered when a consumer org is deleted
 *
 * @param NodeInterface $node
 *   The Drupal node representing this consumer org
 */
function hook_consumerorg_delete(NodeInterface $node) {

}

/**
 * @} End of "addtogroup hooks".
 */
