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

use Drupal\auth_apic\JWTToken;
use Drupal\auth_apic\UserManagerResponse;
use Drupal\ibm_apim\ApicType\ApicUser;

interface ApicInvitationInterface {

  /**
   * Register a user that has been invited into the org. (Andre invited Andre).
   *
   * @param \Drupal\auth_apic\JWTToken $token
   * @param \Drupal\ibm_apim\ApicType\ApicUser|NULL $invitedUser
   *
   * @return \Drupal\auth_apic\UserManagerResponse
   */
  public function registerInvitedUser(JWTToken $token, ApicUser $invitedUser = NULL): UserManagerResponse;

  /**
   * Accepts a user invitation representing by the JWT as the user represented
   * by the ApicUser object provided. (Invite from APIM)
   *
   * @param \Drupal\auth_apic\JWTToken $token
   * @param \Drupal\ibm_apim\ApicType\ApicUser $acceptingUser
   *
   * @return mixed
   */
  public function acceptInvite(JWTToken $token, ApicUser $acceptingUser): UserManagerResponse;

}
