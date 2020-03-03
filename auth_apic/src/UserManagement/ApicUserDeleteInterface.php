<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2020
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

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
