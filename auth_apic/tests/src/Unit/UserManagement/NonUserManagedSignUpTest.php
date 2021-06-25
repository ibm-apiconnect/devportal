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

  use Drupal\auth_apic\UserManagement\NonUserManagedSignUp;
  use Drupal\ibm_apim\ApicType\ApicUser;
  use Drupal\ibm_apim\Rest\RestResponse;
  use Drupal\Tests\auth_apic\Unit\UserManagement\AuthApicUserManagementBaseTestClass;
  use Prophecy\Argument;
  use Prophecy\Prophet;


  /**
   * PHPUnit tests for:
   *   public function register(\Drupal\ibm_apim\ApicType\ApicUser $user);
   *
   * @group auth_apic
   */
  class NonUserManagedSignUpTest extends AuthApicUserManagementBaseTestClass {

    /**
     * @var \Drupal\ibm_apim\Service\APIMServer|\Prophecy\Prophecy\ObjectProphecy 
     */
    protected $mgmtServer;

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
      $this->logger = $this->prophet->prophesize(\Psr\Log\LoggerInterface::class);
      $this->siteConfig = $this->prophet->prophesize(\Drupal\ibm_apim\Service\SiteConfig::class);
    }

    protected function tearDown(): void {
      $this->prophet->checkPredictions();
    }

    /**
     * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     */
    public function testNonUserManagedSignUpSuccess(): void {

      $user = $this->createUser();

      $mgmtServerResponse = new RestResponse();
      $mgmtServerResponse->setCode(200);

      $this->logger->notice('non user managed sign-up processed for @username', ['@username' => $user->getUsername()])->shouldBeCalled();
      $this->logger->error(Argument::any())->shouldNotBeCalled();

      $this->mgmtServer->getAuth($user)->willReturn($mgmtServerResponse);

      $service = new NonUserManagedSignUp($this->mgmtServer->reveal(),
        $this->logger->reveal(),
        $this->siteConfig->reveal());
      $result = $service->signUp($user);

      self::assertTrue($result->success());
      self::assertEquals('<front>', $result->getRedirect());
      self::assertEquals('Your account was created successfully. You may now sign in.', $result->getMessage());
    }

    /**
     * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     */
    public function testUserManagedSignUpMgmtFailure(): void {

      $user = $this->createUser();

      $mgmtServerResponse = new RestResponse();
      $mgmtServerResponse->setCode(401);

      $this->logger->notice(Argument::any())->shouldNotBeCalled();
      $this->logger->error('error during sign-up process, no token retrieved.')->shouldBeCalled();

      $this->mgmtServer->getAuth($user)->willReturn($mgmtServerResponse);

      $service = new NonUserManagedSignUp($this->mgmtServer->reveal(),
        $this->logger->reveal(),
        $this->siteConfig->reveal());
      $result = $service->signUp($user);

      self::assertFalse($result->success());
      self::assertEquals('<front>', $result->getRedirect());
      self::assertEquals('There was an error creating your account. Please contact the system administrator.', $result->getMessage());
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


