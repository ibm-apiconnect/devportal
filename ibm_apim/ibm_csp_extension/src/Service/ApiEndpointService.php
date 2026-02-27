<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2025
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_csp_extension\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;

/**
 * Service for retrieving API endpoints for CSP whitelisting.
 */
class ApiEndpointService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ApiEndpointService object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Gets a list of API endpoints to whitelist in CSP.
   *
   * @return array
   *   An array of endpoints to whitelist.
   */
  public function getCustomEndpoints() {
    $endpoints = [];
    $endpointMap = [];

    try {
      // Get all API nodes
      $api_nids = $this->entityTypeManager->getStorage('node')
        ->getQuery()
        ->condition('type', 'api')
        ->accessCheck(TRUE)
        ->execute();

      if (!empty($api_nids)) {
        $api_nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($api_nids);
        $this->processApiNodes($api_nodes, $endpoints, $endpointMap);
      }

      // Get all product nodes to extract API references
      $product_nids = $this->entityTypeManager->getStorage('node')
        ->getQuery()
        ->condition('type', 'product')
        ->accessCheck(TRUE)
        ->execute();

      if (!empty($product_nids)) {
        $product_nodes = $this->entityTypeManager->getStorage('node')->loadMultiple($product_nids);
        $this->processProductNodes($product_nodes, $endpoints, $endpointMap);
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('ibm_csp_extension')->error('Error fetching API endpoints: @message', ['@message' => $e->getMessage()]);
    }

    return $endpoints;
  }

  /**
   * Process API nodes to extract endpoints.
   *
   * @param array $api_nodes
   *   Array of API nodes.
   * @param array &$endpoints
   *   Array of endpoints to populate.
   * @param array &$endpointMap
   *   Map of endpoints for quick lookup.
   */
  private function processApiNodes(array $api_nodes, array &$endpoints, array &$endpointMap) {
    foreach ($api_nodes as $api_node) {
      if (!($api_node instanceof NodeInterface) || !$api_node->hasField('api_swagger')) {
        continue;
      }

      $swagger_value = $api_node->get('api_swagger')->getValue();
      if (empty($swagger_value) || !isset($swagger_value[0]['value'])) {
        continue;
      }

      $swagger = unserialize($swagger_value[0]['value'], ['allowed_classes' => FALSE]);

      // Extract endpoints from the swagger definition
      $this->extractSwaggerHostEndpoints($swagger, $endpoints, $endpointMap);
      $this->extractGatewayEndpoints($swagger, $endpoints, $endpointMap);
      $this->extractServerEndpoints($swagger, $endpoints, $endpointMap);
    }
  }

  /**
   * Extract endpoints from Swagger host property.
   *
   * @param array $swagger
   *   Swagger definition.
   * @param array &$endpoints
   *   Array of endpoints to populate.
   * @param array &$endpointMap
   *   Map of endpoints for quick lookup.
   */
  private function extractSwaggerHostEndpoints(array $swagger, array &$endpoints, array &$endpointMap) {
    if (!isset($swagger['host'])) {
      return;
    }

    $schemes = isset($swagger['schemes']) && !empty($swagger['schemes']) ? $swagger['schemes'] : ['https'];

    foreach ($schemes as $scheme) {
      $endpoint = $scheme . '://' . $swagger['host'];
      $this->addEndpoint($endpoint, $endpoints, $endpointMap);
    }
  }

  /**
   * Extract endpoints from x-ibm-configuration.gateway property.
   *
   * @param array $swagger
   *   Swagger definition.
   * @param array &$endpoints
   *   Array of endpoints to populate.
   * @param array &$endpointMap
   *   Map of endpoints for quick lookup.
   */
  private function extractGatewayEndpoints(array $swagger, array &$endpoints, array &$endpointMap) {
    if (!isset($swagger['x-ibm-configuration']['gateway'])) {
      return;
    }

    $gateway = $swagger['x-ibm-configuration']['gateway'];
    if (!empty($gateway) && is_string($gateway)) {
      $gateway = $this->normalizeUrl($gateway);
      $this->addEndpoint($gateway, $endpoints, $endpointMap);
    }
  }

  /**
   * Extract endpoints from servers array (OpenAPI 3.0).
   *
   * @param array $swagger
   *   Swagger definition.
   * @param array &$endpoints
   *   Array of endpoints to populate.
   * @param array &$endpointMap
   *   Map of endpoints for quick lookup.
   */
  private function extractServerEndpoints(array $swagger, array &$endpoints, array &$endpointMap) {
    if (!isset($swagger['servers']) || !is_array($swagger['servers'])) {
      return;
    }

    foreach ($swagger['servers'] as $server) {
      if (isset($server['url']) && !empty($server['url'])) {
        $url = $this->normalizeUrl($server['url']);
        $this->addEndpoint($url, $endpoints, $endpointMap);
      }
    }
  }

  /**
   * Process product nodes to extract endpoints.
   *
   * @param array $product_nodes
   *   Array of product nodes.
   * @param array &$endpoints
   *   Array of endpoints to populate.
   * @param array &$endpointMap
   *   Map of endpoints for quick lookup.
   */
  private function processProductNodes(array $product_nodes, array &$endpoints, array &$endpointMap) {
    foreach ($product_nodes as $product_node) {
      if (!($product_node instanceof NodeInterface) || !$product_node->hasField('product_apis')) {
        continue;
      }

      $product_apis = $product_node->get('product_apis')->getValue();

      foreach ($product_apis as $product_api) {
        if (!isset($product_api['value'])) {
          continue;
        }

        $data = unserialize($product_api['value'], ['allowed_classes' => FALSE]);

        if (isset($data['url']) && !empty($data['url'])) {
          $url = $this->normalizeUrl($data['url']);
          $this->addEndpoint($url, $endpoints, $endpointMap);
        }
      }
    }
  }

  /**
   * Normalize URL by ensuring it has a scheme.
   *
   * @param string $url
   *   URL to normalize.
   *
   * @return string
   *   Normalized URL.
   */
  private function normalizeUrl($url) {
    if (strpos($url, '://') === FALSE) {
      return 'https://' . $url;
    }
    return $url;
  }

  /**
   * Add an endpoint to the list if it's not already there.
   *
   * @param string $endpoint
   *   Endpoint to add.
   * @param array &$endpoints
   *   Array of endpoints to populate.
   * @param array &$endpointMap
   *   Map of endpoints for quick lookup.
   */
  private function addEndpoint($endpoint, array &$endpoints, array &$endpointMap) {
    if (!isset($endpointMap[$endpoint])) {
      $endpoints[] = $endpoint;
      $endpointMap[$endpoint] = true;
    }
  }
}
