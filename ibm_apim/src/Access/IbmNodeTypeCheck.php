<?php

namespace Drupal\ibm_apim\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Checks is not one of our content types.
 */
class IbmNodeTypeCheck implements AccessInterface {

  /**
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface|null $node_type
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access(?ConfigEntityInterface $node_type = NULL): AccessResult {
    $allowed = TRUE;
    // include page in this list since we need it for custom doc pages
    if (isset($node_type) && in_array($node_type->id(), ['application', 'api', 'product', 'consumerorg', 'page', 'faq'])) {
      $allowed = FALSE;
    }

    return AccessResult::allowedIf($allowed);
  }
}