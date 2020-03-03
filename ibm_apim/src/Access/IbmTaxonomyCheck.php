<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2020
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
 * Checks is not the main tags vocab.
 */
class IbmTaxonomyCheck implements AccessInterface {

  public function access(RouteMatch $routeMatch = NULL): AccessResult {
    $allowed = TRUE;

    if (isset($routeMatch)) {
      $parameters = $routeMatch->getParameters();
      $taxonomy_vocabulary = $parameters->get('taxonomy_vocabulary');
      if (isset($taxonomy_vocabulary) && $taxonomy_vocabulary->id() === 'tags') {
        $allowed = FALSE;
      }
    }

    return AccessResult::allowedIf($allowed);
  }
}
