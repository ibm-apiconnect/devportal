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

    /**
     * @var \Drupal\ibm_apim\Service\APIMServer|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $mgmtServer;

    /**
     * @var \Drupal\ibm_apim\UserManagement\ApicAccountService|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $userManager;

    /**
     * @var \Drupal\ibm_apim\Service\ApicUserService|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $userService;

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy|\Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
    * @var \Prophecy\Prophecy\ObjectProphecy|\Drupal\ibm_apim\Service\SiteConfig
    */
    protected $siteConfig;

    protected function setup(): void {
      $this->prophet = new Prophet();
      $this->mgmtServer = $this->prophet->prophesize(\Drupal\ibm_apim\Service\APIMServer::class);
      $this->userManager = $this->prophet->prophesize(\Drupal\ibm_apim\UserManagement\ApicAccountService::class);
      $this->userService = $this->prophet->prophesize(\Drupal\ibm_apim\Service\ApicUserService::class);
      $this->logger = $this->prophet->prophesize(\Psr\Log\LoggerInterface::class);
      $this->siteConfig = $this->prophet->prophesize(\Drupal\ibm_apim\Service\SiteConfig::class);
    }

    protected function tearDown(): void {
      $this->prophet->checkPredictions();
    }

    /**
     * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     */
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
        $this->logger->reveal(),
        $this->siteConfig->reveal());
      $result = $service->signUp($user);

      self::assertTrue($result->success());
      self::assertEquals('<front>', $result->getRedirect());
    }

    /**
     * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     */
    public function testUserManagedSignUpMgmtFailure(): void {

      $user = $this->createUser();

      $mgmtServerResponse = new RestResponse();
      $mgmtServerResponse->setCode(401);

      $this->mgmtServer->postSignUp($user)->willReturn($mgmtServerResponse);

      $this->logger->notice(Argument::any())->shouldNotBeCalled();
      $this->logger->error('unexpected management server response on sign up for @username', ['@username' => $user->getUsername()])
        ->shouldBeCalled();

      $service = new UserManagedSignUp($this->mgmtServer->reveal(),
        $this->userManager->reveal(),
        $this->logger->reveal(),
        $this->siteConfig->reveal());
      $result = $service->signUp($user);
      self::assertEquals(FALSE, $result->success());
      self::assertEquals('There was a problem registering your new account. Please contact your system administrator.', $result->getMessage());
    }

    /**
     * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     */
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
        $this->logger->reveal(),
        $this->siteConfig->reveal());
      $result = $service->signUp($user);

      self::assertFalse($result->success());
      self::assertEquals('There was an error registering your account. Please contact your system administrator.', $result->getMessage());
      self::assertEquals('<front>', $result->getRedirect());
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



