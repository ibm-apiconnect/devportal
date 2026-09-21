<?php

namespace Drupal\ibm_apim\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatch;

/**
 * Checks is not the main tags vocab.
 */
class IbmTaxonomyCheck implements AccessInterface {

  /**
   * @param \Drupal\Core\Routing\RouteMatch|null $routeMatch
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access(?RouteMatch $routeMatch = NULL): AccessResult {
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
