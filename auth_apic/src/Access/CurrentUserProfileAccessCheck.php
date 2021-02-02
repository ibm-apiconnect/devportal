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

class CurrentUserProfileAccessCheck implements AccessInterface {

  /**
   * A custom access check for whether a user is working on their own profile
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Current user
   * @param \Drupal\user\Entity\User $user
   *   The profile form being worked on (upcast from the {user} path parameter)
   *
   * @return AccessResult
   *   True if admin and not using read only registry
   */
  public function access(AccountInterface $account, User $user): AccessResult {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    if ((int) $account->id() === (int) $user->id()) {
      $allowed = TRUE;
    }
    else {
      $allowed = FALSE;
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $allowed);
    return AccessResult::allowedIf($allowed);

  }

}