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

namespace Drupal\ibm_apim\Service\Interfaces;

use Drupal\auth_apic\JWTToken;
use Drupal\consumerorg\ApicType\ConsumerOrg;
use Drupal\consumerorg\ApicType\Member;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Rest\RestResponse;

interface ManagementServerInterface {

  /**
   * Set up authentication for this management server by saving the credentials in a session variable.
   *
   * @param ApicUser $user
   *   user to authenticate for calls to this mgmt server.
   */
  public function setAuth(ApicUser $user);

  /**
   * Get an authentication token from the management server without storing it in
   * the private tempstore for the user.
   *
   * @param \Drupal\ibm_apim\ApicType\ApicUser $user
   *
   * @return mixed
   */
  public function getAuth(ApicUser $user);

  /**
   * GET /me call.
   *
   * @param $auth
   *   Optionally provide the authentication to use for this call
   *
   * @return \Drupal\auth_apic\Rest\MeResponse
   *    Response from the GET /me call.
   */
  public function getMe($auth);

  /**
   * PUT /me - update user profile.
   *
   * @param \Drupal\auth_apic\ApicUser $user
   *   the user details to update including the changed fields
   *
   * @return \Drupal\auth_apic\Rest\MeResponse
   *    Response from the PUT /me call.
   */
  public function updateMe(ApicUser $user);

  /**
   * DELETE /me - delete current user from management server.
   *
   * @return \Drupal\ibm_apim\Rest\RestReponse
   *   Response from the call.
   */
  public function deleteMe(): ?RestResponse;

  /**
   * POST /users/register.
   *
   * @param \Drupal\auth_apic\ApicUser $user
   *   the user
   *
   * @return \Drupal\auth_apic\Rest\UsersRegisterResponse
   *    Response from POST /users/register call
   */
  public function postUsersRegister(ApicUser $user);

  /**
   * Invited user register.
   *
   * POST ../org-invitations/:id/register
   *
   * @param \Drupal\auth_apic\InvitationObject $obj
   *   Invitation object.
   * @param \Drupal\auth_apic\ApicUser $invitedUser
   *   the user details from the form.
   *
   * @return \Drupal\ibm_apim\Rest\RestReponse
   *   Response from the call.
   */
  public function orgInvitationsRegister(JWTToken $token, ApicUser $invitedUser);

  /**
   * Accept the invitation represented by the JWTToken as the ApicUser provided
   *
   * @param \Drupal\auth_apic\JWTToken $token
   * @param \Drupal\ibm_apim\ApicType\ApicUser $acceptingUser
   * @param string $orgTitle
   *
   * @return mixed
   */
  public function acceptInvite(JWTToken $token, ApicUser $acceptingUser, $orgTitle);

  /**
   * POST to /me/request-password-reset
   *
   * Initiate forgotten password flow.
   * This will trigger the sending of an email to reset the password.
   *
   * @param string $username
   *   Username to reset password of.
   *
   * @param string $realm
   *   The realm for this user
   *
   * @return NULL|\stdClass
   */
  public function forgotPassword($username, $realm);

  /**
   * Reset users password.
   *
   * POST to /me/reset-password
   *
   * @param \Drupal\auth_apic\JWTToken $obj
   *   Reset password object.
   * @param \string $password
   *   New password.
   *
   * @return \Drupal\ibm_apim\Rest\RestResponse
   */
  public function resetPassword(JWTToken $obj, $password);

  /**
   * PUT /me/change-password
   *
   * @param /string $old_password
   *   Current password.
   * @param /string $new_password
   *   New password.
   *
   * @return \Drupal\ibm_apim\Rest\RestResponse
   */
  public function changePassword($old_password, $new_password);

  /**
   * POST s/sign-up
   *
   * @param \Drupal\ibm_apim\ApicType\ApicUser $new_user
   *  The user to create
   *
   * @return mixed
   */
  public function postSignUp(ApicUser $new_user);


  /**
   * Create consumer org.
   *
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   *
   * @return mixed
   */
  public function createConsumerOrg(ConsumerOrg $org);

  /**
   * Invite a member to a consumer org.
   *
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   * @param string $email_address
   * @param string|NULL $role
   *
   * @return mixed
   */
  public function postMemberInvitation(ConsumerOrg $org, string $email_address, string $role = NULL);

  /**
   * Delete a member invitation.
   *
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   * @param string $inviteId
   *
   * @return mixed
   */
  public function deleteMemberInvitation(ConsumerOrg $org, string $inviteId);

  /**
   * Resend a member invitation.
   *
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   * @param string $inviteId
   *
   * @return mixed
   */
  public function resendMemberInvitation(ConsumerOrg $org, string $inviteId);

  /**
   * Edit a consumer org.
   *
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   * @param array $data
   *
   * @return mixed
   */
  public function patchConsumerOrg(ConsumerOrg $org, array $data);

  /**
   * Delete consumer org.
   *
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   *
   * @return mixed
   */
  public function deleteConsumerOrg(ConsumerOrg $org);

  /**
   * Edit a consumer org member.
   *
   * @param \Drupal\consumerorg\ApicType\Member $member
   * @param array $data
   *
   * @return mixed
   */
  public function patchMember(Member $member, array $data);

  /**
   * Delete a consumer org member.
   *
   * @param \Drupal\consumerorg\ApicType\Member $member
   *
   * @return mixed
   */
  public function deleteMember(Member $member);

  /**
   * Transfer the ownership of a consumer organization
   *
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   * @param string $newOwnerUrl
   * @param string|null $role
   *
   * @return mixed
   */
  public function postTransferConsumerOrg(ConsumerOrg $org, string $newOwnerUrl, $role);

  /**
   * @param \Drupal\auth_apic\JWTToken $jwt
   *
   * @return \Drupal\ibm_apim\Rest\RestResponse
   */
  public function activateFromJWT(JWTToken $jwt): RestResponse;
}
