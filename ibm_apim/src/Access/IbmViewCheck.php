<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2024
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
 * Checks is not one of our views.
 */
class IbmViewCheck implements AccessInterface {

  /**
   * @param \Drupal\Core\Routing\RouteMatch|null $routeMatch
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access(RouteMatch $routeMatch = NULL): AccessResult {
    $allowed = TRUE;

    if (isset($routeMatch)) {
      $parameters = $routeMatch->getParameters();
      $view = $parameters->get('view');
      if (isset($view)) {
        $view_id = $view->id();
        if ($view_id === 'apis' || $view_id === 'applications' || $view_id === 'products' || $view_id === 'faqs') {
          $allowed = FALSE;
        }
      }
    }

    return AccessResult::allowedIf($allowed);
  }
}
