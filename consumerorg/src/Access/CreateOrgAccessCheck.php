<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\consumerorg\Access;

use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Access\AccessResult;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Checks whether the user is logged in but not 'admin'
 * and whether self service onboarding is enabled
 */
class CreateOrgAccessCheck implements AccessInterface {

  public function access() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $allowed = FALSE;
    $current_user = \Drupal::currentUser();
    // block anonymous and admin & self service onboarding must be enabled
    $selfService = \Drupal::state()->get('ibm_apim.selfSignUpEnabled');
    $config = \Drupal::config('ibm_apim.settings');
    $allow_consumerorg_creation = $config->get('allow_consumerorg_creation');
    if (!$current_user->isAnonymous() && $current_user->id() != 1 && $selfService != 0 && $allow_consumerorg_creation) {
      $allowed = TRUE;
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $allowed);
    return AccessResult::allowedIf($allowed);
  }
}