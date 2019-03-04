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

namespace Drupal\Tests\auth_apic\Unit;

use Drupal\auth_apic\JWTToken;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Rest\RestResponse;
use Drupal\Tests\auth_apic\Unit\UserManager\UserManagerTestBaseClass;
use Prophecy\Argument;

/**
 * This is an Andre inviting Andre flow. Used on the register form to gather more information about
 * the invited user.
 *
 * PHPUnit tests for:
 *   public function registerInvitedUser(JWTToken $token, ApicUser $invitedUser = NULL) {
 *
 * @group auth_apic
 */
class RegisterInvitedUserTest extends UserManagerTestBaseClass {

  public function testRegisterInvitedUser(): void {

    $jwt = $this->createJWT();
    $user = $this->createUser();

    $invitationResponse = new RestResponse();
    $invitationResponse->setCode(201);

    $accountFields = $this->createAccountFields($user);
    $this->userService->getUserAccountFields($user)->willReturn($accountFields);
    $this->externalAuth->register('andre', 'auth_apic', $accountFields)->willReturn($this->createAccountStub());
    $this->mgmtServer->orgInvitationsRegister($jwt, $user)->willReturn($invitationResponse);

    $this->logger->notice('Activating @uid directly in the database.', ['@uid' => '1'])->shouldBeCalled();
    $this->logger->notice('invitation processed for @username', ['@username' => 'andre'])->shouldBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();
    $this->logger->debug("Registering @username in database as new account.", ["@username" => "andre"])->shouldBeCalled();

    $user_manager = $this->createUserManager();
    $response = $user_manager->registerInvitedUser($jwt, $user);

    $this->assertTrue($response->success(), 'Expected registerInvitedUser() to be successful');
    $this->assertEquals('<front>', $response->getRedirect(), 'Expected redirect to <front>');

  }


  public function testRegisterInvitedUserMgmtError(): void {
    $jwt = $this->createJWT();
    $user = $this->createUser();

    $invitationResponse = new RestResponse();
    $invitationResponse->setCode(400);
    $invitationResponse->setErrors(['TEST ERROR']);

    $this->mgmtServer->orgInvitationsRegister($jwt, $user)->willReturn($invitationResponse);

    $this->logger->notice('Activating @uid directly in the database.', ['@uid' => '1'])->shouldNotBeCalled();
    $this->logger->error('Error during account registration: @error', ['@error' => 'TEST ERROR'])->shouldBeCalled();

    $user_manager = $this->createUserManager();
    $response = $user_manager->registerInvitedUser($jwt, $user);

    $this->assertFalse($response->success(), 'Expected registerInvitedUser() NOT to be successful');
  }


  // Helper functions:
  private function createUser(): ApicUser {
    $user = new ApicUser();

    $user->setUsername('andre');
    $user->setMail('andre@example.com');
    $user->setPassword('abc');
    $user->setFirstname('Andre');
    $user->setLastname('Andresson');
    $user->setOrganization('AndreOrg');


    return $user;

  }

  private function createJWT(): JWTToken {
    $jwt = new JWTToken();
    $jwt->setUrl('accept/invite/url');
    return $jwt;
  }


}
