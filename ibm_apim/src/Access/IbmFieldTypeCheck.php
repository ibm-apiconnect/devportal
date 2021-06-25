<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2021
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatch;

/**
 * Checks is not one of our fields on our content types.
 */
class IbmFieldTypeCheck implements AccessInterface {

  /**
   * @param \Drupal\Core\Routing\RouteMatch|null $routeMatch
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access(RouteMatch $routeMatch = NULL): AccessResult {
    $allowed = TRUE;

    if (isset($routeMatch)) {
      $parameters = $routeMatch->getParameters();
      $field_config = $parameters->get('field_config');
      if (isset($field_config)) {
        $config_parts = explode('.', $field_config->id());
        $moduleHandler = \Drupal::service('module_handler');
        if (isset($config_parts[0], $config_parts[1], $config_parts[2]) && $config_parts[0] === 'node') {
          if ($config_parts[1] === 'product' && $moduleHandler->moduleExists('product')) {
            $ibm_fields = \Drupal\product\Product::getIBMFields();
            if (in_array($config_parts[2], $ibm_fields, FALSE)) {
              $allowed = FALSE;
            }
          }
          elseif ($config_parts[1] === 'api' && $moduleHandler->moduleExists('apic_api')) {
            $ibm_fields = \Drupal\apic_api\Api::getIBMFields();
            if (in_array($config_parts[2], $ibm_fields, FALSE)) {
              $allowed = FALSE;
            }
          }
          elseif ($config_parts[1] === 'application' && $moduleHandler->moduleExists('apic_app')) {
            $ibm_fields = \Drupal::service('apic_app.application')->getIBMFields();
            if (in_array($config_parts[2], $ibm_fields, FALSE)) {
              $allowed = FALSE;
            }
          }
          elseif ($config_parts[1] === 'consumerorg' && $moduleHandler->moduleExists('consumerorg')) {
            $consumerOrgService = \Drupal::service('ibm_apim.consumerorg');
            $ibm_fields = $consumerOrgService->getIBMFields();
            if (in_array($config_parts[2], $ibm_fields, FALSE)) {
              $allowed = FALSE;
            }
          }
          elseif ($config_parts[1] === 'page') {
            // dont allow deletion of our fields needed for custom doc pages
            $ibm_fields = ['allapis', 'allproducts', 'apiref', 'prodref'];
            if (in_array($config_parts[2], $ibm_fields, FALSE)) {
              $allowed = FALSE;
            }
          }
          elseif ($config_parts[1] === 'faq') {
            // dont allow deletion of our fields needed for faqs
            $ibm_fields = ['faqs'];
            if (in_array($config_parts[2], $ibm_fields, FALSE)) {
              $allowed = FALSE;
            }
          }
        }
      }
    }

    return AccessResult::allowedIf($allowed);
  }
}