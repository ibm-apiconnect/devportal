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
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Checks is not one of our content types.
 */
class IbmCreateNodeTypeCheck implements AccessInterface {

  /**
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface|null $node_type
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access(ConfigEntityInterface $node_type = NULL): AccessResult {
    $allowed = TRUE;
    if (isset($node_type) && in_array($node_type->id(), ['application', 'api', 'product', 'consumerorg'])) {
      $allowed = FALSE;
    }

    return AccessResult::allowedIf($allowed);
  }
}