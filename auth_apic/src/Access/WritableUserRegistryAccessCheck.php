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

namespace Drupal\auth_apic\Access;


use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
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
  public function access(AccountInterface $account): AccessResult {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    if ((int) $account->id() === 1) {
      \Drupal::logger('auth_apic')->info('admin user allowed from %class', ['%class' => __CLASS__]);
      $allowed = TRUE;
    }
    else {
      $user = User::load($account->id());
      if ($user !== NULL) {
        $registry_url = $user->get('registry_url')->value;
        if ($registry_url !== NULL) {
          $registryService = \Drupal::service('ibm_apim.user_registry');
          $registry = $registryService->get($registry_url);
        }
        if (!isset($registry)) {
          \Drupal::logger('auth_apic')->error('No registry found for %class', ['%class' => __CLASS__]);
          $allowed = FALSE;
        }
        elseif ($registry->isUserManaged()) {
          $allowed = TRUE;
        }
        else {
          $allowed = FALSE;
        }
      }
      else {
        $allowed = FALSE;
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $allowed);
    return AccessResult::allowedIf($allowed);
  }

}


