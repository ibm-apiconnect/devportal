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
 * Checks is not one of our view modes.
 */
class IbmViewModeCheck implements AccessInterface {

  public function access(RouteMatch $routeMatch = NULL): AccessResult {
    $allowed = TRUE;

    if (isset($routeMatch)) {
      $parameters = $routeMatch->getParameters();
      $entity_view_mode = $parameters->get('entity_view_mode');
      if (isset($entity_view_mode)) {
        $entity_view_mode_id = $entity_view_mode->id();
        if ($entity_view_mode_id === 'node.card' || $entity_view_mode_id === 'node.full' || $entity_view_mode_id === 'node.teaser' || $entity_view_mode_id === 'node.subscribewizard') {
          $allowed = FALSE;
        }
      }
    }

    return AccessResult::allowedIf($allowed);
  }
}
