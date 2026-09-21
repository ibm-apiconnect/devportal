<?php

namespace Drupal\ibm_apim\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\ibm_apim\External\SerializationUtility;

/**
 * Checks whether monetization is enabled.
 */
class BillingAccessCheck implements AccessInterface {

  /**
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access(): AccessResult {
    $allowed = FALSE;
    $billingEnabled = \Drupal::service('ibm_apim.billing')->isEnabled();
    $current_user = \Drupal::currentUser();

    $billingProvider = \Drupal::config('ibm_apim.settings')->get('billing_providers');
    $billingProviderConfig = $billingProvider === null ? NULL : SerializationUtility::decodeData($billingProvider, TRUE, FALSE);

    if ($billingEnabled === TRUE && isset($billingProviderConfig) && !empty($billingProviderConfig) && !$current_user->isAnonymous() && (int) $current_user->id() !== 1) {
      $allowed = TRUE;
    }

    return AccessResult::allowedIf($allowed);
  }

}