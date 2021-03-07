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

namespace Drupal\auth_apic\UserManagement;

use Drupal\auth_apic\JWTToken;
use Drupal\Core\Entity\EntityInterface;
use Drupal\user\Entity\User;

interface ApicPasswordInterface {

  /**
   * Reset users password.
   *
   * @param JWTToken $obj
   *   Parsed resetPasswordToken.
   * @param string $password
   *   New Password.
   *
   * @return int
   *   HTTP response code received from the management server.
   */
  public function resetPassword(JWTToken $obj, $password): int;

  /**
   * Change users password.
   *
   * @param \Drupal\user\Entity\User $user
   * @param $old_password
   * @param $new_password
   *
   * @return bool
   */
  public function changePassword(User $user, $old_password, $new_password): bool;


  /**
   * Look up an account based on either an email or username. Used on the forgot password form.
   *
   * @param string $reset_password_for
   * @param string|NULL $registry_url
   *
   * @return \Drupal\Core\Session\AccountInterface|null
   */
  public function lookupUpAccount(string $reset_password_for, string $registry_url = NULL): ?EntityInterface;

}
