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

namespace Drupal\Tests\auth_apic\Unit {

  use Drupal\auth_apic\JWTToken;
  use Drupal\auth_apic\UserManagement\ApicInvitationService;
  use Drupal\ibm_apim\ApicType\ApicUser;
  use Drupal\ibm_apim\Rest\RestResponse;
  use Drupal\ibm_apim\Service\APIMServer;
  use Drupal\ibm_apim\UserManagement\ApicAccountService;
  use Drupal\Tests\auth_apic\Unit\UserManagement\AuthApicUserManagementBaseTestClass;
  use Prophecy\Argument;
  use Prophecy\Prophet;
  use Psr\Log\LoggerInterface;


  /**
   * @group auth_apic
   */
  class ApicInvitationTest extends AuthApicUserManagementBaseTestClass {

    /**
     * @var \Drupal\ibm_apim\Service\APIMServer|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $mgmtServer;

    /**
     * @var \Drupal\ibm_apim\UserManagement\ApicAccountService|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $userManager;

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy|\Psr\Log\LoggerInterface
     */
    protected $logger;

    protected function setup(): void {
      $this->prophet = new Prophet();
      $this->mgmtServer = $this->prophet->prophesize(APIMServer::class);
      $this->userManager = $this->prophet->prophesize(ApicAccountService::class);
      $this->logger = $this->prophet->prophesize(LoggerInterface::class);
    }

    protected function tearDown(): void {
      $this->prophet->checkPredictions();
    }

    /**
     * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     */
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

      self::assertTrue($result->success(), 'Exected success from mgmt call');
      self::assertEquals('<front>', $result->getRedirect(), 'Unexpected redirect location from acceptInvite call');
      self::assertEquals('Invitation process complete. Please login to continue.', $result->getMessage());
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

      self::assertFalse($result->success(), 'Unexpected result from mgmt call');
      self::assertEquals('<front>', $result->getRedirect(), 'Unexpected redirect location from acceptInvite call');
      self::assertEquals('Error while accepting invitation: @error', $result->getMessage());
    }

    /**
     * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     */
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

      self::assertTrue($result->success(), 'Expected registerInvitedUser() to be successful');
      self::assertEquals('<front>', $result->getRedirect(), 'Expected redirect to <front>');
      self::assertEquals('Invitation process complete. Please login to continue.', $result->getMessage());
    }


    /**
     * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     */
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

      self::assertFalse($result->success(), 'Expected registerInvitedUser() NOT to be successful');
      self::assertNull($result->getRedirect());
      self::assertEquals('Error during account registration: @error', $result->getMessage());
    }

    /**
     * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     */
    public function testRegisterInvitedUserNoUser(): void {
      $jwt = $this->createJWT();

      $this->mgmtServer->orgInvitationsRegister(Argument::any(), Argument::any())->shouldNotBeCalled();

      $this->logger->notice(Argument::any())->shouldNotBeCalled();
      $this->logger->error('Error during account registration: invitedUser was null')->shouldBeCalled();

      $service = new ApicInvitationService($this->mgmtServer->reveal(),
        $this->userManager->reveal(),
        $this->logger->reveal());
      $result = $service->registerInvitedUser($jwt, NULL);

      self::assertFalse($result->success(), 'Expected registerInvitedUser() NOT to be successful');
      self::assertEquals('<front>', $result->getRedirect());
      self::assertEquals('Error during account registration: invitedUser was null', $result->getMessage());
    }

    private function createJwt(): JWTToken {
      $token = new JWTToken();

      $token->setUrl('/j/w/t');
      $token->setPayload(['email' => 'andre@example.com']);

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
