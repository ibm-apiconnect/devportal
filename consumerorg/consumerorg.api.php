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
 * @deprecated this hook will be removed. Please use hook_consumerorg_pre_delete
 *             or hook_consumerorg_post_delete instead.
 *
 * @param NodeInterface $node
 *   The Drupal node representing this consumer org
 */
function hook_consumerorg_delete(NodeInterface $node) {

}


/**
 * Triggered on consumer org deletion before the node deletion or cascade has happened.
 *
 * @param NodeInterface $node
 *   The Drupal node representing this consumer org
 * @param array $data
 *   Data related to the consumerorg, if present the following is available:
 *      $data['nid'] - the node id
 *      $data['id'] - the consumerorg_id field of the consumerorg
 *      $data['url'] - the consumerorg_url field of the consumerorg
 *      $data['name'] - the consumerorg_name field of the consumerorg
 */
function hook_consumerorg_pre_delete(NodeInterface $node, $data) {

}


/**
 * Triggered on consumer org deletion after the node deletion and cascade has happened.
 *
 * @param array $data
 *   Associative array of consumer org data
 */
function hook_consumerorg_post_delete($data) {

}


/**
 * @} End of "addtogroup hooks".
 */
