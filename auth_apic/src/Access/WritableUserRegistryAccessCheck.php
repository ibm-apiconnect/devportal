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

namespace Drupal\auth_apic\Access;


use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\user\Entity\User;

/**
 * Checks access based on whether a user registry is writable.
 *
 */
class WritableUserRegistryAccessCheck implements AccessInterface {

  /**
   * A custom access check for whether a user registry is writable
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for this account.
   *
   * @return AccessResult
   *   True if admin and not using read only registry
   */
  public function access(AccountInterface $account) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    if ($account->id() == 1) {
      \Drupal::logger('ibm_apim')->error('admin user allowed from ' . __CLASS__);
      $allowed = true;
    }
    else {
      $user =  User::load($account->id());
      $registry_url = $user->get('apic_user_registry_url')->value;
      $registryService = \Drupal::service('ibm_apim.user_registry');
      $registry = $registryService->get($registry_url);

      if (!$registry) {
        \Drupal::logger('ibm_apim')->error('No registry found for ' . __CLASS__);
        $allowed = FALSE;
      }
      else {
        if ($registry->isUserManaged()) {
          $allowed = TRUE;
        }
        else {
          $allowed = FALSE;
        }
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $allowed);
    return AccessResult::allowedIf($allowed);
  }
}


