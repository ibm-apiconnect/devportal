<?php


namespace Drupal\apic_app\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Checks whether client secret reset is allowed.
 */
class ClientSecretResetCheck implements AccessInterface {

  /**
   * @return \Drupal\Core\Access\AccessResult|\Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultNeutral
   */
  public function access() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $allowed = FALSE;
    $config = \Drupal::config('ibm_apim.settings');
    $allow_clientsecret_reset = (boolean) $config->get('allow_clientsecret_reset');

    if ($allow_clientsecret_reset === TRUE) {
      $allowed = TRUE;
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $allowed);
    return AccessResult::allowedIf($allowed);
  }

}