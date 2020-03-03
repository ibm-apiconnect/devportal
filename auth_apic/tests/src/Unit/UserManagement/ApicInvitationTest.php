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

namespace Drupal\Tests\auth_apic\Unit {

  use Drupal\auth_apic\JWTToken;
  use Drupal\auth_apic\UserManagement\ApicInvitationService;
  use Drupal\ibm_apim\ApicType\ApicUser;
  use Drupal\ibm_apim\Rest\RestResponse;
  use Drupal\Tests\auth_apic\Unit\UserManagement\AuthApicUserManagementBaseTestClass;
  use Prophecy\Argument;
  use Prophecy\Prophet;


  /**
   * @group auth_apic
   */
  class ApicInvitationTest extends AuthApicUserManagementBaseTestClass {

    protected $mgmtServer;

    protected $userManager;

    protected $logger;

    protected function setup() {
      $this->prophet = new Prophet();
      $this->mgmtServer = $this->prophet->prophesize(\Drupal\ibm_apim\Service\APIMServer::class);
      $this->userManager = $this->prophet->prophesize(\Drupal\ibm_apim\UserManagement\ApicAccountService::class);
      $this->logger = $this->prophet->prophesize(\Psr\Log\LoggerInterface::class);
    }

    protected function tearDown() {
      $this->prophet->checkPredictions();
    }

    public function testAcceptInvite(): void {

      $jwt = $this->createJWT();
      $user = $this->createUser();

      $mgmtResponse = new RestResponse();
      $mgmtResponse->setCode(201);

      $this->mgmtServer->acceptInvite($jwt, $user, 'AndreOrg')->willReturn($mgmtResponse);

      $this->logger->notice('invitation processed for @username', ['@username' => $user->getUsername()])->shouldBeCalled();
      $this->logger->error(Argument::any())->shouldNotBeCalled();

      $service = new ApicInvitationService($this->mgmtServer->reveal(),
                                           $this->userManager->reveal(),
                                           $this->logger->reveal());
      $result = $service->acceptInvite($jwt, $user);

      $this->assertTrue($result->success(), 'Exected success from mgmt call');
      $this->assertEquals('<front>', $result->getRedirect(), 'Unexpected redirect location from acceptInvite call');
      $this->assertEquals('Invitation process complete. Please login to continue.', $result->getMessage());

    }


    public function testAcceptInviteFailFromMgmt(): void {

      $jwt = $this->createJWT();
      $user = $this->createUser();

      $mgmtResponse = new RestResponse();
      $mgmtResponse->setCode(400);
      $mgmtResponse->setErrors(['TEST ERROR']);

      $this->mgmtServer->acceptInvite($jwt, $user, 'AndreOrg')->willReturn($mgmtResponse);

      $this->logger->error('Error during acceptInvite:  @error', ['@error' => 'TEST ERROR'])->shouldBeCalled();

      $service = new ApicInvitationService($this->mgmtServer->reveal(),
                                           $this->userManager->reveal(),
                                           $this->logger->reveal());
      $result = $service->acceptInvite($jwt, $user);

      $this->assertFalse($result->success(), 'Unexpected result from mgmt call');
      $this->assertEquals('<front>', $result->getRedirect(), 'Unexpected redirect location from acceptInvite call');
      $this->assertEquals('Error while accepting invitation: @error', $result->getMessage());

    }

    public function testRegisterInvitedUser(): void {

      $jwt = $this->createJWT();
      $user = $this->createUser();

      $invitationResponse = new RestResponse();
      $invitationResponse->setCode(201);

      $this->userManager->registerApicUser($user)->shouldBeCalled();
      $this->mgmtServer->orgInvitationsRegister($jwt, $user)->willReturn($invitationResponse);

      $this->logger->notice('invitation processed for @username', ['@username' => 'andre'])->shouldBeCalled();
      $this->logger->error(Argument::any())->shouldNotBeCalled();
      //$this->logger->debug("Registering @username in database as new account.", ["@username" => "andre"])->shouldBeCalled();

      $service = new ApicInvitationService($this->mgmtServer->reveal(),
        $this->userManager->reveal(),
        $this->logger->reveal());
      $result = $service->registerInvitedUser($jwt, $user);

      $this->assertTrue($result->success(), 'Expected registerInvitedUser() to be successful');
      $this->assertEquals('<front>', $result->getRedirect(), 'Expected redirect to <front>');
      $this->assertEquals('Invitation process complete. Please login to continue.', $result->getMessage());

    }


    public function testRegisterInvitedUserMgmtError(): void {
      $jwt = $this->createJWT();
      $user = $this->createUser();

      $invitationResponse = new RestResponse();
      $invitationResponse->setCode(400);
      $invitationResponse->setErrors(['TEST ERROR']);

      $this->mgmtServer->orgInvitationsRegister($jwt, $user)->willReturn($invitationResponse);

      $this->logger->notice(Argument::any())->shouldNotBeCalled();
      $this->logger->error('Error during account registration: @error', ['@error' => 'TEST ERROR'])->shouldBeCalled();

      $service = new ApicInvitationService($this->mgmtServer->reveal(),
        $this->userManager->reveal(),
        $this->logger->reveal());
      $result = $service->registerInvitedUser($jwt, $user);

      $this->assertFalse($result->success(), 'Expected registerInvitedUser() NOT to be successful');
      $this->assertNull($result->getRedirect());
      $this->assertEquals('Error during account registration: @error', $result->getMessage());
    }

    public function testRegisterInvitedUserNoUser(): void {
      $jwt = $this->createJWT();

      $this->mgmtServer->orgInvitationsRegister(Argument::any())->shouldNotBeCalled();

      $this->logger->notice(Argument::any())->shouldNotBeCalled();
      $this->logger->error('Error during account registration: invitedUser was null')->shouldBeCalled();

      $service = new ApicInvitationService($this->mgmtServer->reveal(),
        $this->userManager->reveal(),
        $this->logger->reveal());
      $result = $service->registerInvitedUser($jwt, NULL);

      $this->assertFalse($result->success(), 'Expected registerInvitedUser() NOT to be successful');
      $this->assertEquals('<front>', $result->getRedirect());
      $this->assertEquals('Error during account registration: invitedUser was null', $result->getMessage());
    }

    private function createJwt(): JWTToken {
      $token = new JWTToken();

      $token->setUrl('/j/w/t');
      $token->setPayload(['email'=>'andre@example.com']);

      return $token;

    }

    private function createUser(): ApicUser {
      $user = new ApicUser();
      $user->setUsername('andre');
      $user->setApicUserRegistryUrl('/reg/lur');
      $user->setUrl('/user/12345');
      $user->setOrganization('AndreOrg');
      return $user;
    }
  }

 }


