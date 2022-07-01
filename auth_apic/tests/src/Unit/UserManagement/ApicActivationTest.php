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

  use Drupal\auth_apic\JWTToken;
  use Drupal\auth_apic\UserManagement\ApicActivationService;
  use Drupal\Core\Extension\ModuleHandlerInterface;
  use Drupal\Core\Messenger\Messenger;
  use Drupal\Core\Utility\LinkGenerator;
  use Drupal\ibm_apim\Rest\RestResponse;
  use Drupal\ibm_apim\Service\ApicUserStorage;
  use Drupal\ibm_apim\Service\APIMServer;
  use Drupal\Tests\auth_apic\Unit\UserManagement\AuthApicUserManagementBaseTestClass;
  use Prophecy\Argument;
  use Prophecy\Prophet;
  use Psr\Log\LoggerInterface;


  /**
   * PHPUnit tests for:
   *   public function activate(JWTToken $jwt);
   *
   * @group auth_apic
   */
  class ApicActivationTest extends AuthApicUserManagementBaseTestClass {

    /**
     * @var \Drupal\ibm_apim\Service\APIMServer|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $mgmtServer;

    /**
     * @var \Drupal\ibm_apim\Service\ApicUserStorage|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $userStorage;

    /**
     * @var \Drupal\Core\Utility\LinkGenerator|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $linkGenerator;

    /**
     * @var \Drupal\Core\Messenger\Messenger|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $messenger;

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy|\Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Drupal\Core\Extension\ModuleHandlerInterface|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $moduleHandler;

    protected function setup(): void {
      $this->prophet = new Prophet();
      $this->mgmtServer = $this->prophet->prophesize(APIMServer::class);
      $this->userStorage = $this->prophet->prophesize(ApicUserStorage::class);
      $this->linkGenerator = $this->prophet->prophesize(LinkGenerator::class);
      $this->messenger = $this->prophet->prophesize(Messenger::class);
      $this->logger = $this->prophet->prophesize(LoggerInterface::class);
      $this->moduleHandler = $this->prophet->prophesize(ModuleHandlerInterface::class);
    }

    protected function tearDown(): void {
      $this->prophet->checkPredictions();
    }

    /**
     * @throws \Exception
     */
    public function testActivate(): void {

      $token = $this->createJwt();

      $mgmtServerResponse = new RestResponse();
      $mgmtServerResponse->setCode(204);

      $account = $this->prophet->prophesize(\Drupal\user\Entity\User::class);
      $account->set('apic_state', 'enabled')->shouldBeCalled();
      $account->activate()->shouldBeCalled();
      $account->save()->shouldBeCalled();

      $this->logger->error(Argument::any())->shouldNotBeCalled();

      $this->mgmtServer->activateFromJWT($token)->willReturn($mgmtServerResponse);
      $this->userStorage->loadUserByEmailAddress('andre@example.com')->willReturn($account);
      $this->linkGenerator->generate(Argument::any(), Argument::any())->willReturn('dummy_link');
      $this->messenger->addError(Argument::any())->shouldNotBeCalled();
      $this->messenger->addMessage('Your account has been activated. You can now @signin.')->shouldBeCalled();


      $service = new ApicActivationService($this->mgmtServer->reveal(),
        $this->userStorage->reveal(),
        $this->linkGenerator->reveal(),
        $this->messenger->reveal(),
        $this->logger->reveal(),
        $this->moduleHandler->reveal());
      $result = $service->activate($token);

      self::assertTrue($result);

    }

    /**
     * @throws \Exception
     */
    public function testActivate401FromMgmt(): void {

      $token = $this->createJwt();

      $mgmtServerResponse = new RestResponse();
      $mgmtServerResponse->setCode(401);
      $mgmtServerResponse->setErrors(['mock error']);

      $this->logger->error('Error while processing user activation. Received response code \'@code\' from backend.
        Message from backend was \'@message\'.', ['@code' => '401', '@message' => 'mock error'])->shouldBeCalled();

      $this->mgmtServer->activateFromJWT($token)->willReturn($mgmtServerResponse);
      $this->userStorage->loadUserByEmailAddress(Argument::any())->shouldNotBeCalled();
      $this->linkGenerator->generate(Argument::any(), Argument::any())->willReturn('dummy_link');
      $this->messenger->addError('There was an error while processing your activation. Has this activation link already been used?')
        ->shouldBeCalled();
      $this->messenger->addMessage(Argument::any())->shouldNotBeCalled();


      $service = new ApicActivationService($this->mgmtServer->reveal(),
        $this->userStorage->reveal(),
        $this->linkGenerator->reveal(),
        $this->messenger->reveal(),
        $this->logger->reveal(),
        $this->moduleHandler->reveal());
      $result = $service->activate($token);

      self::assertFalse($result);

    }

    /**
     * @throws \Exception
     */
    public function testActivateGenericErrorFromMgmt(): void {

      $token = $this->createJwt();

      $mgmtServerResponse = new RestResponse();
      $mgmtServerResponse->setCode(400);
      $mgmtServerResponse->setErrors(['mock error']);

      $this->logger->error('Error while processing user activation. Received response code \'@code\' from backend.
        Message from backend was \'@message\'.', ['@code' => '400', '@message' => 'mock error'])->shouldBeCalled();

      $this->mgmtServer->activateFromJWT($token)->willReturn($mgmtServerResponse);
      $this->userStorage->loadUserByEmailAddress(Argument::any())->shouldNotBeCalled();
      $this->linkGenerator->generate(Argument::any(), Argument::any())->willReturn('dummy_link');
      $this->messenger->addError('There was an error while processing your activation. @contact_link')->shouldBeCalled();
      $this->messenger->addMessage(Argument::any())->shouldNotBeCalled();


      $service = new ApicActivationService($this->mgmtServer->reveal(),
        $this->userStorage->reveal(),
        $this->linkGenerator->reveal(),
        $this->messenger->reveal(),
        $this->logger->reveal(),
        $this->moduleHandler->reveal());
      $result = $service->activate($token);

      self::assertFalse($result);

    }

    /**
     * @throws \Exception
     */
    public function testActivateNoAccount(): void {

      $token = $this->createJwt();

      $mgmtServerResponse = new RestResponse();
      $mgmtServerResponse->setCode(204);

      $this->logger->warning("Processing user activation. Could not find account in our database for @mail, continuing as we will act on APIM data.",
        ["@mail" => "andre@example.com"]
      )->shouldBeCalled();

      $this->mgmtServer->activateFromJWT($token)->willReturn($mgmtServerResponse);
      $this->userStorage->loadUserByEmailAddress('andre@example.com')->willReturn(NULL);
      $this->linkGenerator->generate(Argument::any(), Argument::any())->willReturn('dummy_link');
      $this->messenger->addError(Argument::any())->shouldNotBeCalled();
      $this->messenger->addMessage("Your account has been activated. You can now @signin.")->shouldBeCalled();

      $service = new ApicActivationService($this->mgmtServer->reveal(),
        $this->userStorage->reveal(),
        $this->linkGenerator->reveal(),
        $this->messenger->reveal(),
        $this->logger->reveal(),
        $this->moduleHandler->reveal());
      $result = $service->activate($token);

      self::assertTrue($result);

    }

    private function createJwt(): JWTToken {
      $token = new JWTToken();

      $token->setUrl('/j/w/t');
      $token->setPayload(['email' => 'andre@example.com']);

      return $token;

    }

  }

}
