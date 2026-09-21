<?php

namespace Drupal\ibm_apim\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Checks whether analytics is enabled.
 */
class AnalyticsAdminAccessCheck implements AccessInterface {

  /**
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access(): AccessResult {
    $allowed = FALSE;
    $analytics_service = \Drupal::service('ibm_apim.analytics')->getDefaultService();
    if (isset($analytics_service)) {
      $analyticsClientUrl = $analytics_service->getClientEndpoint();
      $current_user = \Drupal::currentUser();
      if (isset($analyticsClientUrl) && !$current_user->isAnonymous() && \Drupal::currentUser()->hasPermission('administer_apic')) {
        $allowed = TRUE;
      }
    }

    return AccessResult::allowedIf($allowed);
  }

}
