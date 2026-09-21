<?php

namespace Drupal\ibm_apim\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Checks whether analytics is enabled.
 */
class AnalyticsAccessCheck implements AccessInterface {

  /**
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access(): AccessResult {
    $allowed = FALSE;
    $config = \Drupal::config('ibm_apim.settings');
    $show_analytics = (boolean) $config->get('show_analytics');

    $analytics_service = \Drupal::service('ibm_apim.analytics')->getDefaultService();
    if (isset($analytics_service)) {
      $analyticsClientUrl = $analytics_service->getClientEndpoint();
      $current_user = \Drupal::currentUser();
      if ($show_analytics === TRUE && isset($analyticsClientUrl) && !$current_user->isAnonymous() && (int) $current_user->id() !== 1) {
        $allowed = TRUE;
      }
    }

    return AccessResult::allowedIf($allowed);
  }

}