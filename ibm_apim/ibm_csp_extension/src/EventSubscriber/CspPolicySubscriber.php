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

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\csp\CspEvents;
use Drupal\csp\Event\PolicyAlterEvent;
use Drupal\csp\Nonce;
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
   * The CSP nonce service.
   *
   * @var \Drupal\csp\Nonce
   */
  protected $cspNonce;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Constructs a new CspPolicySubscriber object.
   *
   * @param \Drupal\ibm_csp_extension\Service\ApiEndpointService $api_endpoint_service
   *   The API endpoint service.
   * @param \Drupal\csp\Nonce $csp_nonce
   *   The CSP nonce service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
    ApiEndpointService $api_endpoint_service,
    Nonce $csp_nonce,
    ModuleHandlerInterface $module_handler,
  ) {
    $this->apiEndpointService = $api_endpoint_service;
    $this->cspNonce = $csp_nonce;
    $this->moduleHandler = $module_handler;
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
   * Alters CSP policies to add custom endpoints and CKEditor5 style nonce.
   *
   * @param \Drupal\csp\Event\PolicyAlterEvent $event
   *   The policy alter event.
   */
  public function onCspPolicyAlter(PolicyAlterEvent $event) {
    $policy = $event->getPolicy();

    // --- connect-src: whitelist API endpoints ---
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
        // Create connect-src with the same values as default-src.
        $policy->setDirective('connect-src', $defaultSrcValues);
        $shouldWhitelist = TRUE;
      }
    }

    if ($shouldWhitelist) {
      $endpoints = $this->apiEndpointService->getCustomEndpoints();

      if (!empty($endpoints)) {
        $connectSrcValues = $policy->getDirective('connect-src');
        foreach ($endpoints as $endpoint) {
          if (!in_array($endpoint, $connectSrcValues)) {
            $policy->appendDirective('connect-src', $endpoint);
          }
        }
      }
    }

    // --- style-src-elem: add nonce for CKEditor5 ---
    if ($this->moduleHandler->moduleExists('ckeditor5')) {
      $policy->fallbackAwareAppendIfEnabled('style-src-elem', $this->cspNonce->getSource());
    }
  }

}
