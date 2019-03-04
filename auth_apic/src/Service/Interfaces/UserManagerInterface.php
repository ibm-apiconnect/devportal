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

namespace Drupal\auth_apic\Service\Interfaces;

use Drupal\auth_apic\JWTToken;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\Core\Session\AccountInterface;
use Drupal\auth_apic\UserManagerResponse;
use Drupal\user\Entity\User;

interface UserManagerInterface {

  /**
   * Register a user that has been invited into the org. (Andre invited Andre).
   *
   * @param JWTToken $token
   *    The invitation object used to auth with the mgmt appliance.
   * @param ApicUser $invitedUser
   *    Invited user.
   *
   */
  public function registerInvitedUser(JWTToken $token, ApicUser $invitedUser);

  /**
   * Accepts a user invitation representing by the JWT as the user represented
   * by the ApicUser object provided. (Invite from APIM)
   *
   * @param \Drupal\auth_apic\JWTToken $token
   * @param \Drupal\ibm_apim\ApicType\ApicUser $acceptingUser
   *
   * @return mixed
   */
  public function acceptInvite(JWTToken $token, ApicUser $acceptingUser);

  /**
   * @param ApicUser $new_user
   *
   * Self signup of a new user to a user managed registry, i.e. the user will be created for example LUR.
   *
   * @return \Drupal\auth_apic\UserManagerResponse
   */
  public function userManagedSignUp(ApicUser $new_user): UserManagerResponse;

  /**
   * @param ApicUser $new_user
   *
   * Self signup of a new user to a registry that doesn't support user-manged, i.e. i.e. the users already exist, for example LDAP, Auth URL.
   *
   * @return \Drupal\auth_apic\UserManagerResponse
   */
  public function nonUserManagedSignUp(ApicUser $new_user): UserManagerResponse;


  /**
   * Register new user in drupal DB.
   *
   * @param string $username
   *   The username of the user to create.
   * @param array $fields
   *   The other fields associated with that user e.g. firstName, lastName etc.
   *
   * @return AccountInterface|null
   *   the account for the registered user
   *
   * @throws \Drupal\externalauth\Exception\ExternalAuthRegisterException
   */
  public function registerApicUser($username, array $fields): ?AccountInterface;

  /**
   * Check whether the user is known in apim and log them in.
   *
   * Involves call to /token and /me.
   *
   * @param ApicUser $user
   *   The user to be logged in.
   *
   * @return UserManagerResponse
   *   containing success of login and the uid of the logged in user if successful.
   *
   */
  public function login(ApicUser $user): UserManagerResponse;

  /**
   * Updates the local drupal user account based on the possibly updated
   * user provided.
   *
   * @param ApicUser $user
   *  the user to be updated
   *
   * @return bool
   */
  public function updateLocalAccount(ApicUser $user): bool;

  /**
   * Updates the roles of a given user. The roles specified replace all existing
   * roles. The list must be a complete list of all roles that you want the user
   * to have.
   *
   * @param ApicUser $user
   *  the user to update
   * @param array roles
   *  the complete list of roles to set for this user
   *
   * @return bool
   */
  public function updateLocalAccountRoles(ApicUser $user, $roles): bool;

  /**
   * Updates the user profile of an apim user.
   *
   * @param \Drupal\ibm_apim\ApicType\ApicUser $user
   *   the user profile to update including the updated fields
   *
   * @return bool
   */
  public function updateApicAccount(ApicUser $user): bool;

  /**
   * Reset users password.
   *
   * @param JWTToken $obj
   *   Parsed resetPasswordToken.
   * @param string $password
   *   New Password.
   *
   * @return number
   *   HTTP response code received from the management server.
   */
  public function resetPassword(JWTToken $obj, $password);

  /**
   * Change users password.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user account.
   * @param $old_password
   *   Current password.
   * @param $new_password
   *   New password.
   */
  public function changePassword(User $user, $old_password, $new_password);

  /**
   * Deletes the local drupal user account based on the user provided.
   * Reassigns content belonging to that user to anonymous user.
   *
   * @param ApicUser $user
   *  the user to be deleted
   */
  public function deleteLocalAccount(ApicUser $user): void;

  /**
   * Attempts to find and retrieve the user account for the user with the given
   * username by querying the underlying authentication registry (externalauth).
   *
   * @param $username
   *
   * @return \Drupal\Core\Session\AccountInterface
   */
  public function findUserInDatabase($username): ?AccountInterface;

  /**
   * Attempts to find and retrieve the user account for the user with the given
   * url.
   *
   * @param $url
   *   The APIC url for the user to find
   *
   * @return \Drupal\Core\Session\AccountInterface
   */
  public function findUserByUrl($url): ?AccountInterface;


  /**
   * Delete current user in apim and local database.
   *
   * @return UserManagerResponse
   *   containing success of deletion and any messages to display to the user.
   */
  public function deleteUser(): UserManagerResponse;

  /**
   * @param $user user account
   * @param $form_state user.register form state
   * @param $view_mode entity view mode
   *
   */
  public function saveCustomFields($user, $form_state, $view_mode): void;

  /**
   * @param $user user account
   */
  public function setDefaultLanguage($user): void;
}
