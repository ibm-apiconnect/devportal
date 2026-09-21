<?php

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
    if ($billingEnabled === TRUE && !$current_user->isAnonymous() && \Drupal::currentUser()->hasPermission('administer_apic')) {
      $allowed = TRUE;
    }

    return AccessResult::allowedIf($allowed);
  }
}