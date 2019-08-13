<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2019
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\consumerorg\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;

/**
 * Checks whether the user is logged in but not 'admin'
 */
class DeleteOrgAccessCheck implements AccessInterface {

  public function access(): AccessResult {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $allowed = FALSE;
    $current_user = \Drupal::currentUser();

    $user_utils = \Drupal::service('ibm_apim.user_utils');

    // block anonymous and admin
    if (!$current_user->isAnonymous() && (int) $current_user->id() !== 1) {
      // Only consumerorg owner can edit the consumerorg name
      $config = \Drupal::config('ibm_apim.settings');
      $allow_consumerorg_delete = (boolean) $config->get('allow_consumerorg_delete');
      if ($allow_consumerorg_delete === TRUE && $user_utils->checkHasPermission('settings:manage')) {
        $allowed = TRUE;
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $allowed);
    return AccessResult::allowedIf($allowed);
  }
}