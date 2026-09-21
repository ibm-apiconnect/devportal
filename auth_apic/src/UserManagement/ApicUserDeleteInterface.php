<?php

namespace Drupal\auth_apic\UserManagement;


use Drupal\auth_apic\UserManagerResponse;
use Drupal\ibm_apim\ApicType\ApicUser;

interface ApicUserDeleteInterface {

  /**
   * Delete current user in apim and local database.
   *
   * @return UserManagerResponse
   *   containing success of deletion and any messages to display to the user.
   */
  public function deleteUser(): UserManagerResponse;

  /**
   * Deletes the local drupal user account based on the user provided.
   * Reassigns content belonging to that user to anonymous user.
   *
   * @param \Drupal\ibm_apim\ApicType\ApicUser $user
   *
   * @return bool
   */
  public function deleteLocalAccount(ApicUser $user): bool;

}
