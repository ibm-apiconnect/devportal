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

/**
 * Checks whether monetization is enabled.
 */
class BillingAdminAccessCheck implements AccessInterface {

  /**
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access(): AccessResult {
    $allowed = FALSE;
    $billingEnabled = \Drupal::service('ibm_apim.billing')->isEnabled();
    $current_user = \Drupal::currentUser();
    if ($billingEnabled === TRUE && !$current_user->isAnonymous() && (int) $current_user->id() === 1) {
      $allowed = TRUE;
    }

    return AccessResult::allowedIf($allowed);
  }
}