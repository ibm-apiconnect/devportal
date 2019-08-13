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

namespace Drupal\Tests\auth_apic\Unit {

  use Drupal\auth_apic\UserManagement\UserManagedSignUp;
  use Drupal\ibm_apim\ApicType\ApicUser;
  use Drupal\ibm_apim\Rest\RestResponse;
  use Drupal\Tests\auth_apic\Unit\UserManagement\AuthApicUserManagementBaseTestClass;
  use Prophecy\Argument;
  use Prophecy\Prophet;


  /**
   * PHPUnit tests for UserManagedSignUpService
   *
   * @group auth_apic
   */
  class UserManagedSignUpTest extends AuthApicUserManagementBaseTestClass {

    /*
     Dependencies of UserManagedSignUp service.
     */
    protected $mgmtServer;

    protected $userManager;

    protected $userService;

    protected $logger;

    protected function setup() {
      $this->prophet = new Prophet();
      $this->mgmtServer = $this->prophet->prophesize(\Drupal\ibm_apim\Service\APIMServer::class);
      $this->userManager = $this->prophet->prophesize(\Drupal\ibm_apim\UserManagement\ApicAccountService::class);
      $this->userService = $this->prophet->prophesize(\Drupal\ibm_apim\Service\ApicUserService::class);
      $this->logger = $this->prophet->prophesize(\Psr\Log\LoggerInterface::class);
    }

    protected function tearDown() {
      $this->prophet->checkPredictions();
    }

    public function testUserManagedSignUpSuccess(): void {

      $user = $this->createUser();
      $accountStub = $this->createAccountStub();

      $mgmtServerResponse = new RestResponse();
      $mgmtServerResponse->setCode(204);

      $this->userManager->registerApicUser($user)->willReturn($accountStub)->shouldBeCalled();
      $this->mgmtServer->postSignUp($user)->willReturn($mgmtServerResponse);

      $this->logger->notice('sign-up processed for @username', ['@username' => $user->getUsername()])->shouldBeCalled();
      $this->logger->error(Argument::any())->shouldNotBeCalled();

      $service = new UserManagedSignUp($this->mgmtServer->reveal(),
        $this->userManager->reveal(),
        $this->userService->reveal(),
        $this->logger->reveal());
      $result = $service->signUp($user);

      $this->assertTrue($result->success());
      $this->assertEquals('<front>', $result->getRedirect());
    }

    public function testUserManagedSignUpMgmtFailure(): void {

      $user = $this->createUser();

      $mgmtServerResponse = new RestResponse();
      $mgmtServerResponse->setCode(401);

      $this->mgmtServer->postSignUp($user)->willReturn($mgmtServerResponse);

      $this->logger->notice(Argument::any())->shouldNotBeCalled();
      $this->logger->error('unexpected management server response on sign up for @username', ['@username' => $user->getUsername()])->shouldBeCalled();

      $service = new UserManagedSignUp($this->mgmtServer->reveal(),
        $this->userManager->reveal(),
        $this->userService->reveal(),
        $this->logger->reveal());
      $result = $service->signUp($user);
      $this->assertEquals(FALSE, $result->success());
      $this->assertEquals('There was a problem registering your new account. Please contact your system administrator.', $result->getMessage());
    }

    public function testUserManagedSignUpRegisterError(): void {

      $user = $this->createUser();

      $mgmtServerResponse = new RestResponse();
      $mgmtServerResponse->setCode(204);

      $this->userManager->registerApicUser($user)->willReturn(NULL)->shouldBeCalled();
      $this->mgmtServer->postSignUp($user)->willReturn($mgmtServerResponse);

      $this->logger->notice(Argument::any())->shouldNotBeCalled();
      $this->logger->error('error registering drupal account for @username', ['@username' => $user->getUsername()])->shouldBeCalled();

      $service = new UserManagedSignUp($this->mgmtServer->reveal(),
        $this->userManager->reveal(),
        $this->userService->reveal(),
        $this->logger->reveal());
      $result = $service->signUp($user);

      $this->assertFalse($result->success());
      $this->assertEquals('There was an error registering your account. Please contact your system administrator.', $result->getMessage());
      $this->assertEquals('<front>', $result->getRedirect());
    }

    private function createUser(): ApicUser {
      $user = new ApicUser();

      $user->setUsername('fred');
      $user->setMail('fred@example.com');
      $user->setPassword('abc');
      $user->setFirstname('fred');
      $user->setLastname('fredsonn');
      $user->setOrganization('org1');

      return $user;

    }

  }
}



