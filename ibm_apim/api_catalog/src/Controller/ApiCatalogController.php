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

namespace Drupal\api_catalog\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

class ApiCatalogController extends ControllerBase {

  /**
   * Retrieves all public APIs and saves them to an array.
   *
   * @return \Drupal\node\Entity\Node[] Array of public API node objects.
   */
  public function getPublicApis(): array {
    $public_apis = [];
    $product_nids = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'product')
      ->condition('product_visibility_public', TRUE)
      ->execute();

    if (empty($product_nids)) {
      return [];
    }

    $product_nodes = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->loadMultiple($product_nids);

    foreach ($product_nodes as $product_node) {
      $product_apis = $product_node->get('product_apis')->getValue();
      $api_names = [];

      foreach ($product_apis as $product_api) {
        $data = unserialize($product_api['value']);
        if (!empty($data['name'])) {
          $api_names[] = $data['name'];
        }
      }

      if (!empty($api_names)) {
        $api_nids = \Drupal::entityTypeManager()
          ->getStorage('node')
          ->getQuery()
          ->accessCheck(TRUE)
          ->condition('type', 'api')
          ->condition('apic_ref', $api_names, "IN")
          ->execute();

        if (!empty($api_nids)) {
          $api_nodes = \Drupal::entityTypeManager()
            ->getStorage('node')
            ->loadMultiple($api_nids);

          foreach ($api_nodes as $api_node) {
            $public_apis[] = $api_node;
          }
        }
      }
    }

    return $public_apis;
  }

  /**
   * Generate Linkset and return as JSON response.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with linkset data.
   */
  public function generateLinkset() {
    $apis = $this->getPublicApis();
    $linkset = [];
    
    // Get the base URL 
    $base_url = \Drupal::request()->getSchemeAndHttpHost();
    
    foreach ($apis as $api) {
      $swagger_raw = $api->get('api_swagger')->value;
      $swagger = @unserialize($swagger_raw);
      
      // Extract API details
      $api_title = $swagger['info']['title'] ?? $api->getTitle();
      $api_version = $swagger['info']['version'] ?? '';
      $api_ref = $api->get('apic_ref')->value ?? '';
      $api_name = $api_ref;
      
      if (strpos($api_ref, ':') !== false) {
        list($api_name, $api_ref_version) = explode(':', $api_ref, 2);
      }
      
      $api_description = $swagger['info']['description'] 
        ?? $swagger['info']['x-ibm-summary'] 
        ?? '';
      
      $api_url = '';
      if (!empty($api_name) && !empty($api_version)) {
        $api_url = $base_url . '/productselect/' . $api_name . ':' . $api_version;
      }
      
      if (!empty($api_url)) {
        $link = [
          'anchor' => $base_url,
          'rel' => 'service-desc',
          'href' => $api_url,
          'title' => $api_title,
        ];
        
        if (!empty($api_name)) {
          $link['name'] = $api_name;
        }
        
        if (!empty($api_version)) {
          $link['version'] = $api_version;
        }

        if (!empty($api_description)) {
          $link['description'] = strip_tags($api_description);
        }
        
        $linkset[] = $link;
      }
    }
    
    $response = new JsonResponse($linkset);
    $response->headers->set('Content-Type', 'application/linkset+json');
    
    return $response;
  }

  /**
   * Main controller method for the API catalog endpoint.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with linkset data.
   */
  public function getApiCatalog() {
    return $this->generateLinkset();
  }
}