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

namespace Drupal\ibm_csp_extension\EventSubscriber;

use Drupal\csp\CspEvents;
use Drupal\csp\Event\PolicyAlterEvent;
use Drupal\ibm_csp_extension\Service\ApiEndpointService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for altering CSP policies.
 */
class CspPolicySubscriber implements EventSubscriberInterface {

  /**
   * The API endpoint service.
   *
   * @var \Drupal\ibm_csp_extension\Service\ApiEndpointService
   */
  protected $apiEndpointService;

  /**
   * Constructs a new CspPolicySubscriber object.
   *
   * @param \Drupal\ibm_csp_extension\Service\ApiEndpointService $api_endpoint_service
   *   The API endpoint service.
   */
  public function __construct(ApiEndpointService $api_endpoint_service) {
    $this->apiEndpointService = $api_endpoint_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      CspEvents::POLICY_ALTER => ['onCspPolicyAlter'],
    ];
  }

  /**
   * Alters CSP policies to add custom endpoints.
   *
   * @param \Drupal\csp\Event\PolicyAlterEvent $event
   *   The policy alter event.
   */
  public function onCspPolicyAlter(PolicyAlterEvent $event) {
    $policy = $event->getPolicy();

    $shouldWhitelist = FALSE;

    if ($policy->hasDirective('connect-src')) {
      $connectSrcValues = $policy->getDirective('connect-src');
      if (in_array("'self'", $connectSrcValues)) {
        $shouldWhitelist = TRUE;
      }
    }
    elseif ($policy->hasDirective('default-src')) {
      $defaultSrcValues = $policy->getDirective('default-src');
      if (in_array("'self'", $defaultSrcValues)) {
        // Create connect-src with the same values as default-src
        $policy->setDirective('connect-src', $defaultSrcValues);
        \Drupal::logger('ibm_csp_extension')->info('Copied default-src values to connect-src directive');
        $shouldWhitelist = TRUE;
      }
    }
    
    // Only whitelist API endpoints if connect-src or default-src contains 'self'
    if ($shouldWhitelist) {
      $endpoints = $this->apiEndpointService->getCustomEndpoints();

      if (!empty($endpoints)) {
        $connectSrcValues = $policy->getDirective('connect-src');
        foreach ($endpoints as $endpoint) {
          if (!in_array($endpoint, $connectSrcValues)) {
            $policy->appendDirective('connect-src', $endpoint);
          }
        }

        \Drupal::logger('ibm_csp_extension')->info('Added external endpoints to CSP connect-src directive');
      }
    }
    else {
      \Drupal::logger('ibm_csp_extension')->info('Not whitelisting API endpoints because connect-src or default-src does not contain \'self\'');
    }
  }
}
