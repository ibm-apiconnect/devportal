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

namespace Drupal\Tests\ibm_apim\Unit\UserManagement;

use Drupal\Core\Language\LanguageManager;
use Drupal\ibm_apim\Service\ApicUserService;
use Drupal\ibm_apim\Service\ApicUserStorage;
use Drupal\ibm_apim\Service\APIMServer;
use Drupal\ibm_apim\UserManagement\ApicAccountService;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Rest\MeResponse;
use Drupal\Tests\auth_apic\Unit\Base\AuthApicTestBaseClass;
use Prophecy\Argument;
use Drupal\Core\Messenger\Messenger;
use Psr\Log\LoggerInterface;

/**
 * Called from various flows to register a user in the drupal db.
 *
 * PHPUnit tests for:
 *    public function registerApicUser(ApicUser $user)
 *
 * @coversDefaultClass \Drupal\ibm_apim\UserManagement\ApicAccountService
 *
 * @group ibm_apim
 */
class ApicAccountServiceTest extends AuthApicTestBaseClass {

  /**
   * @var \Prophecy\Prophecy\ObjectProphecy|\Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @var \Drupal\ibm_apim\Service\APIMServer|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $mgmtServer;

  /**
   * @var \Drupal\ibm_apim\Service\ApicUserService|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $userService;

  /**
   * @var \Drupal\Core\Language\LanguageManager|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $languageManager;

  /**
   * @var \Drupal\ibm_apim\Service\ApicUserStorage|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $userStorage;

  /**
   * @var \Drupal\Core\Messenger\Messenger|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $messenger;

  /**
   *
   */
  protected function setup(): void {
    parent::setup();
    $this->logger = $this->prophet->prophesize(LoggerInterface::class);
    $this->mgmtServer = $this->prophet->prophesize(APIMServer::class);
    $this->userService = $this->prophet->prophesize(ApicUserService::class);
    $this->languageManager = $this->prophet->prophesize(LanguageManager::class);
    $this->userStorage = $this->prophet->prophesize(ApicUserStorage::class);
    $this->messenger = $this->prophet->prophesize(Messenger::class);

  }

  /**
   * @return ApicAccountService
   */
  protected function createAccountService(): ApicAccountService {
    return new ApicAccountService(
      $this->logger->reveal(),
      $this->mgmtServer->reveal(),
      $this->userService->reveal(),
      $this->languageManager->reveal(),
      $this->userStorage->reveal(),
      $this->messenger->reveal()
    );
  }


  // register tests

  /**
   * @throws \Exception
   */
  public function testRegisterAndre(): void {

    $user = $this->createUser();

    $this->userStorage->load($user)->willReturn(NULL);
    $this->userStorage->register($user)->willReturn($this->createAccountStub());

    $this->logger->notice(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();
    $this->logger->debug(Argument::any())->shouldNotBeCalled();

    $user_manager = $this->createAccountService();
    $response = $user_manager->registerApicUser($user);

    self::assertNotNull($response, 'Expected a not null response from registerApicUser()');

  }

  /**
   * @throws \Exception
   */
  public function testRegisterAndreAlreadyExists(): void {

    $user = $this->createUser();

    $this->userStorage->load($user)->willReturn($this->createAccountStub());
    $this->userStorage->register(Argument::any())->shouldNotBeCalled();

    $this->logger->notice(Argument::any())->shouldNotBeCalled();
    $this->logger->error('unable to register user, already exists.')->shouldBeCalled();
    $this->logger->debug(Argument::any())->shouldNotBeCalled();

    $user_manager = $this->createAccountService();
    $response = $user_manager->registerApicUser($user);

    self::assertNull($response, 'Expected a null response from registerApicUser()');

  }

  /**
   * @throws \Exception
   */
  public function testRegisterAndreNoUsername(): void {

    $user = new ApicUser();
    $user->setApicUserRegistryUrl('/reg/lur');

    $this->userStorage->load(Argument::any())->shouldNotBeCalled();
    $this->userStorage->register(Argument::any())->shouldNotBeCalled();

    $this->logger->notice(Argument::any())->shouldNotBeCalled();
    $this->logger->error('unable to register user, need at least a username and registry details to register user.')->shouldBeCalled();
    $this->logger->debug(Argument::any())->shouldNotBeCalled();

    $user_manager = $this->createAccountService();
    $response = $user_manager->registerApicUser($user);

    self::assertNull($response, 'Expected a null response from registerApicUser()');

  }

  /**
   * @throws \Exception
   */
  public function testRegisterAndreNoRegistryUrl(): void {

    $user = new ApicUser();
    $user->setUsername('andre');

    $this->userStorage->load(Argument::any())->shouldNotBeCalled();
    $this->userStorage->register(Argument::any())->shouldNotBeCalled();

    $this->logger->notice(Argument::any())->shouldNotBeCalled();
    $this->logger->error('unable to register user, need at least a username and registry details to register user.')->shouldBeCalled();
    $this->logger->debug(Argument::any())->shouldNotBeCalled();

    $user_manager = $this->createAccountService();
    $response = $user_manager->registerApicUser($user);

    self::assertNull($response, 'Expected a null response from registerApicUser()');

  }

  // edit profile tests

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  public function testEditUser(): void {

    $user = $this->createUser();
    $meResponse = $this->createMeResponse();

    $accountStub = $this->createAccountStub();

    $this->mgmtServer->updateMe($user)->willReturn($meResponse);
    $this->userStorage->load($user)->willReturn($accountStub);
    $this->userService->getMetadataFields()->willReturn([]);

    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $userManager = $this->createAccountService();
    $apicUser = $userManager->updateApicAccount($user);
    self::assertNotNull($apicUser);
    $result = $userManager->updateLocalAccount($apicUser);
    self::assertEquals($accountStub, $result);

  }

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   * @throws \Exception
   */
  public function testEditUserWithNoEmailAddress(): void {

    $user = $this->createUser();
    $user->setMail(NULL);
    $meResponse = $this->createMeResponse();
    $this->userService->getMetadataFields()->willReturn([]);

    $accountStub = $this->createAccountStub();

    $this->mgmtServer->updateMe($user)->willReturn($meResponse);
    $this->userStorage->load($user)->willReturn($accountStub);

    $this->logger->error(Argument::any())->shouldNotBeCalled();
    $this->logger->notice('updateLocalAccount - email address not available. Not updating to maintain what is already in the database')
      ->shouldBeCalled();

    $service = $this->createAccountService();
    $result = $service->updateLocalAccount($user);
    self::assertNotNull($result);

  }

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   * @throws \Exception
   */
  public function testEditUserWithUpdatedEmailAddress(): void {

    $user = $this->createUser();
    $user->setMail('updated@example.com');
    $meResponse = $this->createMeResponse();
    $this->userService->getMetadataFields()->willReturn([]);

    $account = $this->createAccountBase();
    $account->set('mail', 'updated@example.com')->shouldBeCalled();
    $account->reveal();

    $this->mgmtServer->updateMe($user)->willReturn($meResponse);
    $this->userStorage->load($user)->willReturn($account);

    $this->logger->error(Argument::any())->shouldNotBeCalled();
    $this->logger->notice('updateLocalAccount - email address not available. Not updating to maintain what is already in the database')
      ->shouldNotBeCalled();

    $service = $this->createAccountService();
    $result = $service->updateLocalAccount($user);
    self::assertNotNull($result);

  }


  /**
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function testBadManagementNodeResponse(): void {

    $user = $this->createUser();
    $meResponse = $this->createMeResponse();
    $meResponse->setCode(401);
    $meResponse->setErrors(['TEST ERROR']);

    $this->mgmtServer->updateMe($user)->willReturn($meResponse);

    $this->logger->error('Failed to update a user in the management server. Response code was @code and error message was @error', [
      '@code' => '401',
      '@error' => 'TEST ERROR',
    ])->shouldBeCalled();

    $userManager = $this->createAccountService();
    $result = $userManager->updateApicAccount($user);
    self::assertNull($result);

  }

  /**
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function testBadExternalAuthLoad(): void {

    $user = $this->createUser();
    $meResponse = $this->createMeResponse();

    $this->mgmtServer->updateMe($user)->willReturn($meResponse);

    $userManager = $this->createAccountService();
    $result = $userManager->updateLocalAccount($user);
    self::assertNull($result);

  }

  // Helper functions:

  /**
   * @return \Drupal\ibm_apim\ApicType\ApicUser
   */
  private function createUser(): ApicUser {
    $user = new ApicUser();

    $user->setUsername('andre');
    $user->setMail('abc@me.com');
    $user->setPassword('abc');
    $user->setFirstname('abc');
    $user->setLastname('def');
    $user->setOrganization('AndreOrg');
    $user->setApicUserRegistryUrl('/registry/idp1');
    $user->setUrl('user/url');

    return $user;

  }

  /**
   * @return \Drupal\ibm_apim\Rest\MeResponse
   */
  private function createMeResponse(): MeResponse {
    $meResponse = new MeResponse();
    $meResponse->setCode(200);
    $meResponse->setUser($this->createUser());
    return $meResponse;
  }


}
