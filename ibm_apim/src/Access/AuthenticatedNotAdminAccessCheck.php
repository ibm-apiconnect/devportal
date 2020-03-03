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

/**
 * Checks whether the user is logged in but not 'admin'.
 */
class AuthenticatedNotAdminAccessCheck implements AccessInterface {

  public function access(): AccessResult {
    $allowed = FALSE;
    $current_user = \Drupal::currentUser();
    // block anonymous and admin
    if (!$current_user->isAnonymous() && (int) $current_user->id() !== 1) {
      $allowed = TRUE;
    }

    return AccessResult::allowedIf($allowed);
  }
}