<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2022
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
use Drupal\ibm_apim\Rest\MeResponse;

interface ManagementServerInterface {

  /**
   *
   * @param string $url
   *
   * @return mixed
   */
  public function get(string $url);

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
   * @return \Drupal\ibm_apim\Rest\MeResponse
   *    Response from the GET /me call.
   */
  public function getMe($auth): MeResponse;

  /**
   * PUT /me - update user profile.
   *
   * @param \Drupal\ibm_apim\ApicType\ApicUser $user
   *   the user details to update including the changed fields
   *
   * @param string $auth
   *   The type of authentication to use
   *
   * @return \Drupal\ibm_apim\Rest\MeResponse Response from the PUT /me call.
   *    Response from the PUT /me call.
   */
  public function updateMe(ApicUser $user, $auth = 'user'): MeResponse;

  /**
   * DELETE /me - delete current user from management server.
   *
   * @return \Drupal\ibm_apim\Rest\RestResponse
   *   Response from the call.
   */
  public function deleteMe(): ?RestResponse;

  /**
   * POST /users/register.
   *
   * @param \Drupal\ibm_apim\ApicType\ApicUser $user
   *   the user
   *
   * @return \Drupal\ibm_apim\Rest\Interfaces\RestResponseInterface|\Drupal\ibm_apim\Rest\RestResponse|null
   *    Response from POST /users/register call
   */
  public function postUsersRegister(ApicUser $user);

  /**
   * Invited user register.
   *
   * POST ../org-invitations/:id/register
   *
   * @param \Drupal\auth_apic\JWTToken $token
   *   Invitation object.
   * @param \Drupal\ibm_apim\ApicType\ApicUser $invitedUser
   *   the user details from the form.
   *
   * @return \Drupal\ibm_apim\Rest\RestResponse
   *   Response from the call.
   */
  public function orgInvitationsRegister(JWTToken $token, ApicUser $invitedUser): ?RestResponse;

  /**
   * Accept the invitation represented by the JWTToken as the ApicUser provided
   *
   * @param \Drupal\auth_apic\JWTToken $token
   * @param \Drupal\ibm_apim\ApicType\ApicUser $acceptingUser
   * @param string $orgTitle
   *
   * @return \Drupal\ibm_apim\Rest\Interfaces\RestResponseInterface|\Drupal\ibm_apim\Rest\RestResponse|null
   */
  public function acceptInvite(JWTToken $token, ApicUser $acceptingUser, string $orgTitle);

  /**
   * POST to /me/request-password-reset
   *
   * Initiate forgotten password flow.
   * This will trigger the sending of an email to reset the password.
   *
   * @param string $username
   *   Username to reset password of.
   *
   * @param string|null $realm
   *   The realm for this user
   *
   * @return \Drupal\ibm_apim\Rest\Interfaces\RestResponseInterface|\Drupal\ibm_apim\Rest\RestResponse|null
   */
  public function forgotPassword(string $username, ?string $realm);

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
   * @return \Drupal\ibm_apim\Rest\RestResponse|null
   */
  public function resetPassword(JWTToken $obj, string $password): ?RestResponse;

  /**
   * PUT /me/change-password
   *
   * @param /string $old_password
   *   Current password.
   * @param /string $new_password
   *   New password.
   *
   * @return \Drupal\ibm_apim\Rest\RestResponse|null
   */
  public function changePassword($old_password, $new_password): ?RestResponse;

  /**
   * POST s/sign-up
   *
   * @param \Drupal\ibm_apim\ApicType\ApicUser $new_user
   *  The user to create
   *
   * @return \Drupal\ibm_apim\Rest\Interfaces\RestResponseInterface|\Drupal\ibm_apim\Rest\RestResponse|null
   */
  public function postSignUp(ApicUser $new_user);

  /**
   * @return \Drupal\ibm_apim\Rest\RestResponse
   */
  public function postSignOut(): RestResponse;

  /**
   * Create consumer org.
   *
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   *
   * @return \Drupal\ibm_apim\Rest\Interfaces\RestResponseInterface|\Drupal\ibm_apim\Rest\RestResponse|null
   */
  public function createConsumerOrg(ConsumerOrg $org);

  /**
   * Invite a member to a consumer org.
   *
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   * @param string $email_address
   * @param string|NULL $role
   *
   * @return \Drupal\ibm_apim\Rest\Interfaces\RestResponseInterface|\Drupal\ibm_apim\Rest\RestResponse|null
   */
  public function postMemberInvitation(ConsumerOrg $org, string $email_address, string $role = NULL);

  /**
   * Delete a member invitation.
   *
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   * @param string $inviteId
   *
   * @return \Drupal\ibm_apim\Rest\Interfaces\RestResponseInterface|\Drupal\ibm_apim\Rest\RestResponse|null
   */
  public function deleteMemberInvitation(ConsumerOrg $org, string $inviteId);

  /**
   * Resend a member invitation.
   *
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   * @param string $inviteId
   *
   * @return \Drupal\ibm_apim\Rest\Interfaces\RestResponseInterface|\Drupal\ibm_apim\Rest\RestResponse|null
   */
  public function resendMemberInvitation(ConsumerOrg $org, string $inviteId);

  /**
   * Edit a consumer org.
   *
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   * @param array $data
   *
   * @return \Drupal\ibm_apim\Rest\Interfaces\RestResponseInterface|\Drupal\ibm_apim\Rest\RestResponse
   */
  public function patchConsumerOrg(ConsumerOrg $org, array $data);

  /**
   * Delete consumer org.
   *
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   *
   * @return \Drupal\ibm_apim\Rest\Interfaces\RestResponseInterface|\Drupal\ibm_apim\Rest\RestResponse|null
   */
  public function deleteConsumerOrg(ConsumerOrg $org);

  /**
   * Edit a consumer org member.
   *
   * @param \Drupal\consumerorg\ApicType\Member $member
   * @param array $data
   *
   * @return \Drupal\ibm_apim\Rest\Interfaces\RestResponseInterface|\Drupal\ibm_apim\Rest\RestResponse|null
   */
  public function patchMember(Member $member, array $data);

  /**
   * Delete a consumer org member.
   *
   * @param \Drupal\consumerorg\ApicType\Member $member
   *
   * @return \Drupal\ibm_apim\Rest\Interfaces\RestResponseInterface|\Drupal\ibm_apim\Rest\RestResponse|null
   */
  public function deleteMember(Member $member);

  /**
   * Transfer the ownership of a consumer organization
   *
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   * @param string $newOwnerUrl
   * @param string|null $role
   *
   * @return \Drupal\ibm_apim\Rest\Interfaces\RestResponseInterface|\Drupal\ibm_apim\Rest\RestResponse|null
   */
  public function postTransferConsumerOrg(ConsumerOrg $org, string $newOwnerUrl, ?string $role);

  /**
   * @param \Drupal\auth_apic\JWTToken $jwt
   *
   * @return \Drupal\ibm_apim\Rest\RestResponse
   */
  public function activateFromJWT(JWTToken $jwt): ?RestResponse;

}
