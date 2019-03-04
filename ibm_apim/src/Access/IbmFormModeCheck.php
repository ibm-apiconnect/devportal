<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2019
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
 * Checks is not one of our form modes.
 */
class IbmFormModeCheck implements AccessInterface {

  public function access(RouteMatch $routeMatch = NULL): AccessResult {
    $allowed = TRUE;

    if (isset($routeMatch)) {
      $parameters = $routeMatch->getParameters();
      $entity_form_mode = $parameters->get('entity_form_mode');
      if (isset($entity_form_mode)) {
        $entity_form_mode_id = $entity_form_mode->id();
        if ($entity_form_mode_id === 'user.activate' || $entity_form_mode_id === 'user.register') {
          $allowed = FALSE;
        }
      }
    }

    return AccessResult::allowedIf($allowed);
  }
}
