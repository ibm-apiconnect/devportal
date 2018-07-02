<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;
use Drupal\Core\Routing\RouteMatch;

/**
 * Checks is not one of our fields.
 */
class IbmLanguageDeleteCheck implements AccessInterface {

  public function access(RouteMatch $routeMatch = NULL) {
    $allowed = TRUE;

    $protected_languages = array('en', 'de', 'fr', 'it', 'zh-hant', 'zh-hans', 'ja', 'pt-br', 'es', 'ko', 'nl', 'tr', 'pl', 'cs', 'ru');

    if (isset($routeMatch)) {
      $parameters = $routeMatch->getParameters();
      if(in_array($parameters->get('configurable_language')->getId(), $protected_languages)) {
        $allowed = FALSE;
      }
    }

    return AccessResult::allowedIf($allowed);
  }
}
