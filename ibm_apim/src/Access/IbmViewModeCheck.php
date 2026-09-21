<?php

namespace Drupal\ibm_apim\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatch;

/**
 * Checks is not one of our view modes.
 */
class IbmViewModeCheck implements AccessInterface {

  /**
   * @param \Drupal\Core\Routing\RouteMatch|null $routeMatch
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access(?RouteMatch $routeMatch = NULL): AccessResult {
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
