<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2022
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\apic_api\Service;

use Drupal\Core\State\StateInterface;
use Drupal\node\Entity\Node;

class ApiUtils {

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  private StateInterface $state;

  /**
   * ApiUtils constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * This function checks that the specified endpoint is one of those listed in the API
   *
   * @param Node $node
   * @param string $endpoint
   *
   * @return bool
   */
  public function validateApiEndpoint(Node $node, string $endpoint): bool {
    $returnValue = FALSE;
    if ($node !== NULL && $endpoint !== NULL) {

      //\Drupal::logger('apic_api')->debug('WSDLRetrieverController: searching for: '.$endpoint, []);

      // Go through the OpenAPI document and find any potential 'valid' endpoint for this API.
      $swagger = unserialize($node->api_swagger->value, ['allowed_classes' => FALSE]);
      $endpoints = [];

      // First try mechanisms common to OAI2 and OAI3
      // Endpoint could just be in an 'endpoint' at the top level
      if (!empty($swagger['endpoint'])) {
        //\Drupal::logger('apic_api')->debug('WSDLRetrieverController: Found top level endpoint', []);
        $endpoints[] = $swagger['endpoint'];
      }

      // Valid endpoints might be in 'servers' under a custom IBM configuration header
      if (!empty($swagger['x-ibm-configuration']['servers'])) {
        //\Drupal::logger('apic_api')->debug('WSDLRetrieverController: Found endpoints in x-ibm-configuration', []);
        array_walk($swagger['x-ibm-configuration']['servers'],
          static function (&$server) use (&$endpoints) {
            $endpoints[] = $server['url'];
          });
      }

      // Valid endpoints might be in 'servers' under a custom IBM endpoints header
      if (!empty($swagger['x-ibm-endpoints'])) {
        //\Drupal::logger('apic_api')->debug('WSDLRetrieverController: Found x-ibm-endpoints', []);
        array_walk($swagger['x-ibm-endpoints'],
          static function (&$server) use (&$endpoints) {
            // Add a basepath here if it exists
            if (!empty($swagger['basePath'])) {
              $endpoints[] = $server['endpointUrl'] . $swagger['basePath'];
            }
            else {
              $endpoints[] = $server['endpointUrl'];
            }
          }
        );
      }

      // Check for OAI2 to use version specific method of building endpoint
      if (!empty($swagger['swagger']) && $swagger['swagger'] === '2.0') {
        //\Drupal::logger('apic_api')->debug('WSDLRetrieverController: Processing as OAI2', []);
        // Try building the endpoint up from scheme + host + basePath
        if (!empty($swagger['host']) && !empty($swagger['schemes']) && !empty($swagger['basePath'])) {
          //\Drupal::logger('apic_api')->debug('WSDLRetrieverController: Found valid scheme, host and basePath for endpoint', []);
          $endpoints[] = $swagger['schemes']['0'] . '://' . $swagger['host'] . $swagger['basePath'];
        }
      }

      // Check for OAI3 to use version specific method of building endpoint
      if (!empty($swagger['openapi']) && $swagger['openapi' === '3.0.0']) {
        //\Drupal::logger('apic_api')->debug('WSDLRetrieverController: Processing as OAI3', []);
        // Try to use top level servers entry
        if (!empty($swagger['servers'])) {
          //\Drupal::logger('apic_api')->debug('WSDLRetrieverController: Found top level endpoint', []);
          array_walk($swagger['servers'],
            static function (&$server) use (&$endpoints) {
              $endpoints[] = $server['url'];
            });
        }
      }

      // Filter the endpoints array to find any that match our endpoint; if none then return false

      $match = array_filter($endpoints, static function ($entry) use ($endpoint) {
        //\Drupal::logger('apic_api')->debug('WSDLRetrieverController: Checking endpoint: '.$entry, []);
        // Let them match even if case isn't exact, to minimise PMRs ;-)
        if (strcasecmp($entry, $endpoint) === 0) {
          //\Drupal::logger('apic_api')->debug('WSDLRetrieverController: Match!', []);
          return $endpoint;
        }
        else {
          //\Drupal::logger('apic_api')->debug('WSDLRetrieverController: No Match!', []);
        }
        return NULL;
      });
      if (!empty($match)) {
        $returnValue = TRUE;
      }
      else {
        $returnValue = FALSE;
      }

    }
    return $returnValue;
  }

  /**
   * Returns true if AsyncAPIs present, false otherwise
   *
   * @return bool
   */
  public function areEventAPIsPresent(): bool {
    return (bool) $this->state->get('ibm_apim.asyncapis_present', FALSE);
  }

}
