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
 * Checks is not one of our fields.
 */
class IbmUserFieldCheck implements AccessInterface {

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
        if (isset($config_parts[0], $config_parts[1], $config_parts[2]) && $config_parts[0] === 'user' && $config_parts[1] === 'user') {
          $customfields = [
            'codesnippet',
            'consumer_organization',
            'consumerorg_url',
            'first_name',
            'last_name',
            'user_picture',
            'first_time_login',
            'apic_realm',
            'apic_state',
            'apic_url',
            'apic_user_registry_url',
            'registry_url'
          ];
          if (in_array($config_parts[2], $customfields, FALSE)) {
            $allowed = FALSE;
          }
        }
      }
    }

    return AccessResult::allowedIf($allowed);
  }
}
