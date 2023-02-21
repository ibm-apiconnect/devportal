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

namespace Drupal\Tests\auth_apic\Unit\UserManagement {

  use Drupal\auth_apic\UserManagement\ApicLoginService;
  use Drupal\consumerorg\ApicType\ConsumerOrg;
  use Drupal\consumerorg\Service\ConsumerOrgLoginService;
  use Drupal\Core\Entity\Entity\EntityFormDisplay;
  use Drupal\Core\Entity\EntityStorageInterface;
  use Drupal\Core\Entity\EntityTypeManagerInterface;
  use Drupal\Core\Extension\ModuleHandlerInterface;
  use Drupal\Core\Field\FieldItemList;
  use Drupal\Core\Session\AccountProxyInterface;
  use Drupal\Core\TempStore\PrivateTempStore;
  use Drupal\Core\TempStore\PrivateTempStoreFactory;
  use Drupal\ibm_apim\ApicType\ApicUser;
  use Drupal\ibm_apim\ApicType\UserRegistry;
  use Drupal\ibm_apim\Rest\MeResponse;
  use Drupal\ibm_apim\Rest\TokenResponse;
  use Drupal\ibm_apim\Service\ApicUserService;
  use Drupal\ibm_apim\Service\ApicUserStorage;
  use Drupal\ibm_apim\Service\APIMServer;
  use Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface;
  use Drupal\ibm_apim\Service\SiteConfig;
  use Drupal\ibm_apim\Service\UserUtils;
  use Drupal\ibm_apim\Service\Utils;
  use Drupal\ibm_apim\UserManagement\ApicAccountService;
  use Drupal\user\Entity\User;
  use Drupal\user\UserStorageInterface;
  use Prophecy\Argument;
  use Psr\Log\LoggerInterface;

  /**
   * @coversDefaultClass \Drupal\auth_apic\UserManagement\ApicLoginService
   * @group auth_apic
   */
  class ApicLoginServiceTest extends AuthApicUserManagementBaseTestClass {

    /**
     * @var \Drupal\ibm_apim\Service\APIMServer|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $mgmtServer;

    /**
     * @var \Drupal\ibm_apim\UserManagement\ApicAccountService|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $accountService;

    /**
     * @var \Drupal\ibm_apim\Service\UserUtils|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $userUtils;

    /**
     * @var \Drupal\ibm_apim\Service\ApicUserStorage|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $userStorage;

    /**
     * @var \Drupal\Core\TempStore\PrivateTempStoreFactory|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $tempStore;

    /**
     * @var \Prophecy\Prophecy\ObjectProphecy|\Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Drupal\ibm_apim\Service\SiteConfig|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $siteConfig;

    /**
     * @var \Drupal\Core\Session\AccountProxyInterface|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $currentUser;

    /**
     * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $entityTypeManager;

    /**
     * @var \Drupal\user\UserStorageInterface|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $drupalUser;

    /**
     * @var \Drupal\consumerorg\Service\ConsumerOrgLoginService|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $consumerOrgLogin;

    /**
     * @var \Drupal\Core\TempStore\PrivateTempStore|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $sessionStore;

    /**
     * @var \Drupal\ibm_apim\Service\ApicUserService|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $userService;

    /**
     * @var \Drupal\Core\Entity\Entity\EntityFormDisplay|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $entityFormDisplay;

    /**
     * @var \Drupal\Core\Extension\ModuleHandlerInterface|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $moduleHandler;

    /**
     * @var \Drupal\Core\Entity\EntityStorageInterface|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $entityFormDisplayStorage;

    /**
     * @var \Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $userRegistryService;

    /**
     * @var \Drupal\ibm_apim\Service\Utils|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $utils;

    /**
     * @var \Drupal\ibm_apim\ApicType\UserRegistry|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $oidcRegistry;

    /**
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     */
    protected function setup(): void {
      parent::setup();


      $this->mgmtServer = $this->prophet->prophesize(APIMServer::class);
      $this->accountService = $this->prophet->prophesize(ApicAccountService::class);
      $this->userUtils = $this->prophet->prophesize(UserUtils::class);
      $this->userStorage = $this->prophet->prophesize(ApicUserStorage::class);
      $this->tempStore = $this->prophet->prophesize(PrivateTempStoreFactory::class);
      $this->sessionStore = $this->prophet->prophesize(PrivateTempStore::class);
      $this->logger = $this->prophet->prophesize(LoggerInterface::class);
      $this->siteConfig = $this->prophet->prophesize(SiteConfig::class);
      $this->currentUser = $this->prophet->prophesize(AccountProxyInterface::class);
      $this->entityTypeManager = $this->prophet->prophesize(EntityTypeManagerInterface::class);
      $this->consumerOrgLogin = $this->prophet->prophesize(ConsumerOrgLoginService::class);
      $this->userService = $this->prophet->prophesize(ApicUserService::class);
      $this->entityFormDisplay = $this->prophet->prophesize(EntityFormDisplay::class);
      $this->entityFormDisplayStorage = $this->prophet->prophesize(EntityStorageInterface::class);
      $this->drupalUser = $this->prophet->prophesize(UserStorageInterface::class);
      $this->moduleHandler = $this->prophet->prophesize(ModuleHandlerInterface::class);
      $this->userRegistryService = $this->prophet->prophesize(UserRegistryServiceInterface::class);
      $this->utils = $this->prophet->prophesize(Utils::class);

      $this->moduleHandler->moduleExists('terms_of_use')->willReturn(FALSE);
      $this->oidcRegistry = $this->prophet->prophesize(UserRegistry::class);
      $this->oidcRegistry->getRegistryType()->willReturn('oidc');

      $this->entityTypeManager->getStorage('entity_form_display')->willReturn($this->entityFormDisplayStorage);
      $this->entityFormDisplayStorage->load('user.user.register')->willReturn($this->entityFormDisplay);
      $this->entityFormDisplay->getComponents()->willReturn([]);
      $this->entityTypeManager->getStorage('user')->willReturn($this->drupalUser->reveal());
      $this->tempStore->get('ibm_apim')->willReturn($this->sessionStore);

    }

    public function testLoginServiceCreate(): void {
      $service = $this->generateServiceUnderTest();
      self::assertNotEmpty($service);
    }

    /**
     * @throws \Drupal\Core\Entity\EntityStorageException
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Core\TempStore\TempStoreException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     * @throws \JsonException
     * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     */
    public function testLoginSuccessNoRefreshToken(): void {

      $user = $this->generateLoginUser();

      $accountStub = $this->createAccountStub();

      $tokenResponse = $this->generateTokenResponse();
      $this->accountService->setDefaultLanguage(Argument::any());
      $this->mgmtServer->getAuth($user)->willReturn($tokenResponse);
      $meResponse = $this->createMeResponse($user);
      $this->mgmtServer->getMe('aBearerToken')->willReturn($meResponse);
      $this->userStorage->loadUserByEmailAddress($meResponse->getUser()->getMail())->willReturn($accountStub);
      $this->userService->parseDrupalAccount($accountStub)->willReturn($user);
      $this->accountService->createOrUpdateLocalAccount($meResponse->getUser())->willReturn($accountStub)->shouldBeCalled();
      $this->consumerOrgLogin->createOrUpdateLoginOrg($meResponse->getUser()->getConsumerorgs()[0], $meResponse->getUser())
        ->shouldBeCalled();
      $this->userService->getMetadataFields()->willReturn([]);

      $this->userUtils->setCurrentConsumerorg(Argument::any())->willReturn(['url' => '/consumer-orgs/1234/5678/9abc'])->shouldBeCalled();
      $this->userUtils->setOrgSessionData()->shouldBeCalled();

      $this->userStorage->userLoginFinalize($accountStub)->shouldBeCalled();

      $this->logger->notice('@username [UID=@uid] logged in.', ['@username' => 'abc', '@uid' => '1'])->shouldBeCalled();
      $this->logger->notice('attempted login by blocked user: @username [UID=@uid].', Argument::any())->shouldNotBeCalled();
      $this->logger->error(Argument::any())->shouldNotBeCalled();


      $this->sessionStore->set('auth', 'aBearerToken')->shouldBeCalled();
      $this->sessionStore->set('refresh', Argument::any())->shouldNotBeCalled();

      $service = $this->generateServiceUnderTest();
      $response = $service->login($user);
      self::assertTrue($response->success());
      self::assertEquals(1, $response->getUid(), 'Expected user id from logged in user.');
    }

    /**
     * @throws \Drupal\Core\Entity\EntityStorageException
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Core\TempStore\TempStoreException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     * @throws \JsonException
     * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     * @throws \Exception
     */
    public function testLoginSuccessWithRefreshToken(): void {

      $user = $this->generateLoginUser();

      $accountStub = $this->createAccountStub();

      $tokenResponse = $this->generateTokenResponse(TRUE);
      $this->accountService->setDefaultLanguage(Argument::any());

      $this->mgmtServer->getAuth($user)->willReturn($tokenResponse);
      $meResponse = $this->createMeResponse($user);
      $this->mgmtServer->getMe('aBearerToken')->willReturn($meResponse);
      $this->userStorage->loadUserByEmailAddress($meResponse->getUser()->getMail())->willReturn($accountStub);
      $this->userService->parseDrupalAccount($accountStub)->willReturn($user);
      $this->accountService->createOrUpdateLocalAccount($meResponse->getUser())->willReturn($accountStub)->shouldBeCalled();
      $this->consumerOrgLogin->createOrUpdateLoginOrg($meResponse->getUser()->getConsumerorgs()[0], $meResponse->getUser())
        ->shouldBeCalled();
      $this->userService->getMetadataFields()->willReturn([]);

      $this->userUtils->setCurrentConsumerorg(Argument::any())->willReturn(['url' => '/consumer-orgs/1234/5678/9abc'])->shouldBeCalled();
      $this->userUtils->setOrgSessionData()->shouldBeCalled();

      $this->userStorage->userLoginFinalize($accountStub)->shouldBeCalled();

      $this->logger->notice('@username [UID=@uid] logged in.', ['@username' => 'abc', '@uid' => '1'])->shouldBeCalled();
      $this->logger->notice('attempted login by blocked user: @username [UID=@uid].', Argument::any())->shouldNotBeCalled();
      $this->logger->error(Argument::any())->shouldNotBeCalled();


      $this->sessionStore->set('auth', 'aBearerToken')->shouldBeCalled();
      $this->sessionStore->set('refresh', 'aRefreshToken')->shouldBeCalled();

      $service = $this->generateServiceUnderTest();
      $response = $service->login($user);
      self::assertTrue($response->success());
      self::assertEquals(1, $response->getUid(), 'Expected user id from logged in user.');
    }

    /**
     * @throws \Drupal\Core\Entity\EntityStorageException
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Core\TempStore\TempStoreException
     * @throws \JsonException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     */
    public function testLoginFailNoBearerToken(): void {
      $user = new ApicUser();
      $user->setUsername('abc');
      $user->setPassword('123');

      $this->mgmtServer->getAuth($user)->willReturn(NULL);
      $this->logger->error('unable to retrieve bearer token on login.')->shouldBeCalled();

      $this->mgmtServer->getMe(Argument::any())->shouldNotBeCalled();
      $this->accountService->createOrUpdateLocalAccount(Argument::any())->shouldNotBeCalled();
      $this->userStorage->userLoginFinalize(Argument::any())->shouldNotBeCalled();
      $this->userUtils->setCurrentConsumerorg(Argument::any())->shouldNotBeCalled();
      $this->userUtils->setOrgSessionData()->shouldNotBeCalled();
      $this->consumerOrgLogin->createOrUpdateLoginOrg(Argument::any(), Argument::any())->shouldNotBeCalled();

      $service = $this->generateServiceUnderTest();
      $response = $service->login($user);
      self::assertFalse($response->success());
      self::assertEquals('Unable to retrieve bearer token, please contact the system administrator.', $response->getMessage());
    }


    /**
     * @throws \Drupal\Core\Entity\EntityStorageException
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Core\TempStore\TempStoreException
     * @throws \JsonException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     */
    public function testLoginFailGetMeFail(): void {
      $user = new ApicUser();
      $user->setUsername('abc');
      $user->setPassword('123');

      $tokenResponse = $this->generateTokenResponse();

      $meResponse = new MeResponse();
      $meResponse->setCode(401);
      $meResponse->setUser($user);
      $meResponse->setData(['something']);

      $this->mgmtServer->getAuth($user)->willReturn($tokenResponse);
      $this->mgmtServer->getMe($tokenResponse->getBearerToken())->willReturn($meResponse);
      $this->logger->error('failed to authenticate with APIM server')->shouldBeCalled();

      $this->accountService->createOrUpdateLocalAccount(Argument::any())->shouldNotBeCalled();
      $this->userStorage->userLoginFinalize(Argument::any())->shouldNotBeCalled();
      $this->userUtils->setCurrentConsumerorg(Argument::any())->shouldNotBeCalled();
      $this->userUtils->setOrgSessionData()->shouldNotBeCalled();
      $this->consumerOrgLogin->createOrUpdateLoginOrg(Argument::any(), Argument::any())->shouldNotBeCalled();

      $service = $this->generateServiceUnderTest();
      $response = $service->login($user);
      self::assertFalse($response->success());
      self::assertEquals('a:1:{i:0;s:9:"something";}', $response->getMessage()); // urgh
    }

    /**
     * @throws \Drupal\Core\Entity\EntityStorageException
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Core\TempStore\TempStoreException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     * @throws \JsonException
     * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     * @throws \Exception
     */
    public function testLoginFailBlockedUser(): void {

      $user = $this->generateLoginUser();

      $accountStub = $this->createBlockedAccountStub();

      $tokenResponse = $this->generateTokenResponse();
      $this->accountService->setDefaultLanguage(Argument::any());

      $this->mgmtServer->getAuth($user)->willReturn($tokenResponse);
      $meResponse = $this->createMeResponse($user);
      $this->mgmtServer->getMe('aBearerToken')->willReturn($meResponse);
      $this->userStorage->loadUserByEmailAddress($meResponse->getUser()->getMail())->willReturn($accountStub);
      $this->userService->parseDrupalAccount($accountStub)->willReturn($user);
      $this->accountService->createOrUpdateLocalAccount($meResponse->getUser())->willReturn($accountStub)->shouldBeCalled();
      $this->userService->getMetadataFields()->willReturn([]);

      $this->sessionStore->set('auth', Argument::any())->shouldNotBeCalled();
      $this->consumerOrgLogin->createOrUpdateLoginOrg(Argument::any(), Argument::any())->shouldNotBeCalled();
      $this->userUtils->setCurrentConsumerorg(Argument::any())->shouldNotBeCalled();
      $this->userUtils->setOrgSessionData()->shouldNotBeCalled();
      $this->userStorage->userLoginFinalize(Argument::any())->shouldNotBeCalled();

      $this->logger->notice(Argument::any())->shouldNotBeCalled();
      $this->logger->error('attempted login by blocked user: @username [UID=@uid].', Argument::any())->shouldBeCalled();

      $service = $this->generateServiceUnderTest();
      $response = $service->login($user);
      self::assertFalse($response->success());
      self::assertEquals(1, $response->getUid(), 'Expected user id from logged in user.');

    }

    /**
     * @throws \Drupal\Core\Entity\EntityStorageException
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Core\TempStore\TempStoreException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     * @throws \JsonException
     * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     * @throws \Exception
     */
    public function testLoginWithNoConsumerorg(): void {

      $user = $this->generateLoginUser();

      $accountStub = $this->createAccountStubNoConsumerOrgs();

      $tokenResponse = $this->generateTokenResponse();
      $this->accountService->setDefaultLanguage(Argument::any());

      $this->mgmtServer->getAuth($user)->willReturn($tokenResponse);
      $this->sessionStore->set('auth', Argument::any())->shouldBeCalled();
      $meResponse = $this->createMeResponse($user);
      $this->userStorage->loadUserByEmailAddress($meResponse->getUser()->getMail())->willReturn($accountStub);
      $this->userService->parseDrupalAccount($accountStub)->willReturn($user);
      $this->userService->getMetadataFields()->willReturn([]);

      // Get no consumer orgs back on me response.
      $meResponse->getUser()->setConsumerorgs([]);

      $this->mgmtServer->getMe('aBearerToken')->willReturn($meResponse);

      $this->accountService->createOrUpdateLocalAccount($meResponse->getUser())->willReturn($accountStub)->shouldBeCalled();
      $this->consumerOrgLogin->createOrUpdateLoginOrg(Argument::any(), Argument::any())->shouldNotBeCalled();

      $this->userUtils->setCurrentConsumerorg(NULL)->shouldBeCalled();
      $this->userUtils->setOrgSessionData()->shouldNotBeCalled();

      $this->userStorage->userLoginFinalize($accountStub)->shouldBeCalled();

      $this->logger->notice('@username [UID=@uid] logged in.', ['@username' => 'abc', '@uid' => '1'])->shouldBeCalled();
      $this->logger->notice('attempted login by blocked user: @username [UID=@uid].', Argument::any())->shouldNotBeCalled();
      $this->logger->notice('no consumer orgs set on login')->shouldBeCalled();
      $this->logger->error(Argument::any())->shouldNotBeCalled();

      $service = $this->generateServiceUnderTest();
      $response = $service->login($user);
      self::assertTrue($response->success());
      self::assertEquals(1, $response->getUid(), 'Expected user id from logged in user.');

    }

    /**
     * @throws \Drupal\Core\Entity\EntityStorageException
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Core\TempStore\TempStoreException
     * @throws \JsonException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     * @throws \Exception
     */
    public function testUserIsPending(): void {

      $user = $this->generateLoginUser();

      $accountStub = $this->createAccountStub();

      $tokenResponse = $this->generateTokenResponse();

      $this->mgmtServer->getAuth($user)->willReturn($tokenResponse);
      $this->sessionStore->set('auth', Argument::any())->shouldNotBeCalled();
      $meResponse = $this->createMeResponse($user);
      $meResponse->getUser()->setState('pending');
      $this->mgmtServer->getMe('aBearerToken')->willReturn($meResponse);
      $this->userStorage->loadUserByEmailAddress($meResponse->getUser()->getMail())->willReturn($accountStub);

      $this->accountService->createOrUpdateLocalAccount(Argument::any())->shouldNotBeCalled();
      $this->consumerOrgLogin->createOrUpdateLoginOrg(Argument::any(), Argument::any())->shouldNotBeCalled();

      $this->userUtils->setCurrentConsumerorg(Argument::any())->shouldNotBeCalled();
      $this->userUtils->setOrgSessionData()->shouldNotBeCalled();
      $this->userStorage->userLoginFinalize(Argument::any())->shouldNotBeCalled();

      $this->logger->notice('@username [UID=@uid] logged in.', Argument::any())->shouldNotBeCalled();
      $this->logger->notice('attempted login by blocked user: @username [UID=@uid].', Argument::any())->shouldNotBeCalled();
      $this->logger->error("Invalid login attempt for %user, state is %state.", ["%user" => "abc", "%state" => "pending"])
        ->shouldBeCalled();
      $this->logger->error("Login failed for %user - user failed validation check based on information from apim.", ["%user" => "abc"])
        ->shouldBeCalled();

      $this->logger->error('Login failed - login is not permitted.')->shouldBeCalled();

      $service = $this->generateServiceUnderTest();
      $response = $service->login($user);
      self::assertFalse($response->success());

    }

    /**
     * @throws \Drupal\Core\Entity\EntityStorageException
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Core\TempStore\TempStoreException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     * @throws \JsonException
     * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     * @throws \Exception
     */
    public function testUserHasNoState(): void {

      $user = $this->generateLoginUser();

      $accountStub = $this->createAccountStub();

      $tokenResponse = $this->generateTokenResponse();

      $this->mgmtServer->getAuth($user)->willReturn($tokenResponse);
      $this->sessionStore->set('auth', Argument::any())->shouldNotBeCalled();
      $meResponse = $this->createMeResponse($user);
      $meResponse->getUser()->setState(NULL);
      $this->mgmtServer->getMe('aBearerToken')->willReturn($meResponse);
      $this->userStorage->loadUserByEmailAddress($meResponse->getUser()->getMail())->willReturn($accountStub);

      $this->accountService->createOrUpdateLocalAccount(Argument::any())->shouldNotBeCalled();
      $this->consumerOrgLogin->createOrUpdateLoginOrg(Argument::any(), Argument::any())->shouldNotBeCalled();

      $this->userUtils->setCurrentConsumerorg(Argument::any())->shouldNotBeCalled();
      $this->userUtils->setOrgSessionData()->shouldNotBeCalled();
      $this->userStorage->userLoginFinalize(Argument::any())->shouldNotBeCalled();

      $this->logger->notice('@username [UID=@uid] logged in.', Argument::any())->shouldNotBeCalled();
      $this->logger->notice('attempted login by blocked user: @username [UID=@uid].', Argument::any())->shouldNotBeCalled();
      $this->logger->error("Login failed for %user - user failed validation check based on information from apim.", ["%user" => "abc"])
        ->shouldBeCalled();
      $this->logger->error("Invalid login attempt for %user, apic state cannot be determined.", ["%user" => "abc"])->shouldBeCalled();

      $this->logger->error('Login failed - login is not permitted.')->shouldBeCalled();

      $service = $this->generateServiceUnderTest();
      $response = $service->login($user);
      self::assertFalse($response->success());

    }

    /**
     * @throws \Drupal\Core\Entity\EntityStorageException
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Core\TempStore\TempStoreException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     * @throws \JsonException
     * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     * @throws \Exception
     */
    public function testNoUsernameFromAPIM(): void {

      $user = $this->generateLoginUser();

      $accountStub = $this->createAccountStub();

      $tokenResponse = $this->generateTokenResponse();

      $this->mgmtServer->getAuth($user)->willReturn($tokenResponse);
      $this->sessionStore->set('auth', Argument::any())->shouldNotBeCalled();
      $meResponse = $this->createMeResponse($user);
      $meResponse->getUser()->setUsername(NULL);
      $this->mgmtServer->getMe('aBearerToken')->willReturn($meResponse);
      $this->userStorage->loadUserByEmailAddress($meResponse->getUser()->getMail())->willReturn($accountStub);

      $this->accountService->createOrUpdateLocalAccount(Argument::any())->shouldNotBeCalled();
      $this->consumerOrgLogin->createOrUpdateLoginOrg(Argument::any(), Argument::any())->shouldNotBeCalled();

      $this->userUtils->setCurrentConsumerorg(Argument::any())->shouldNotBeCalled();
      $this->userUtils->setOrgSessionData()->shouldNotBeCalled();
      $this->userStorage->userLoginFinalize(Argument::any())->shouldNotBeCalled();

      $this->logger->notice('@username [UID=@uid] logged in.', Argument::any())->shouldNotBeCalled();
      $this->logger->notice('attempted login by blocked user: @username [UID=@uid].', Argument::any())->shouldNotBeCalled();
      $this->logger->error("login attempt with invalid user. Username and registry url needed.")->shouldBeCalled();
      $this->logger->error("Login failed for %user - user failed validation check based on information from apim.", ["%user" => NULL])
        ->shouldBeCalled();

      $this->logger->error('Login failed - login is not permitted.')->shouldBeCalled();

      $service = $this->generateServiceUnderTest();
      $response = $service->login($user);
      self::assertFalse($response->success());

    }

    /**
     * @throws \Drupal\Core\Entity\EntityStorageException
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Core\TempStore\TempStoreException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     * @throws \JsonException
     * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     * @throws \Exception
     */
    public function testNoRegistryUrlFromAPIM(): void {

      $user = $this->generateLoginUser();

      $accountStub = $this->createAccountStub();

      $tokenResponse = $this->generateTokenResponse();

      $this->mgmtServer->getAuth($user)->willReturn($tokenResponse);
      $this->sessionStore->set('auth', Argument::any())->shouldNotBeCalled();
      $meResponse = $this->createMeResponse($user);
      $meResponse->getUser()->setApicUserRegistryUrl(NULL);
      $this->mgmtServer->getMe('aBearerToken')->willReturn($meResponse);
      $this->userStorage->loadUserByEmailAddress($meResponse->getUser()->getMail())->willReturn($accountStub);

      $this->accountService->createOrUpdateLocalAccount(Argument::any())->shouldNotBeCalled();
      $this->consumerOrgLogin->createOrUpdateLoginOrg(Argument::any(), Argument::any())->shouldNotBeCalled();

      $this->userUtils->setCurrentConsumerorg(Argument::any())->shouldNotBeCalled();
      $this->userUtils->setOrgSessionData()->shouldNotBeCalled();
      $this->userStorage->userLoginFinalize(Argument::any())->shouldNotBeCalled();

      $this->logger->notice('@username [UID=@uid] logged in.', Argument::any())->shouldNotBeCalled();
      $this->logger->notice('attempted login by blocked user: @username [UID=@uid].', Argument::any())->shouldNotBeCalled();
      $this->logger->error("login attempt with invalid user. Username and registry url needed.")->shouldBeCalled();
      $this->logger->error("Login failed for %user - user failed validation check based on information from apim.", ["%user" => 'abc'])
        ->shouldBeCalled();

      $this->logger->error('Login failed - login is not permitted.')->shouldBeCalled();

      $service = $this->generateServiceUnderTest();
      $response = $service->login($user);
      self::assertFalse($response->success());

    }


    /**
     * @throws \Drupal\Core\Entity\EntityStorageException
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Core\TempStore\TempStoreException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     * @throws \JsonException
     * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     * @throws \Exception
     */
    public function testValidateEnabledNoUserInDB(): void {
      $user = $this->generateLoginUser();

      $accountStub = $this->createAccountStub();

      $tokenResponse = $this->generateTokenResponse();
      $this->accountService->setDefaultLanguage(Argument::any());

      $this->mgmtServer->getAuth($user)->willReturn($tokenResponse);
      $this->sessionStore->set('auth', Argument::any())->shouldBeCalled();
      $meResponse = $this->createMeResponse($user);
      $this->mgmtServer->getMe('aBearerToken')->willReturn($meResponse);
      $this->userStorage->loadUserByEmailAddress($meResponse->getUser()->getMail())->willReturn(NULL);
      $this->userService->parseDrupalAccount($accountStub)->willReturn($user);
      $this->accountService->createOrUpdateLocalAccount($meResponse->getUser())->willReturn($accountStub)->shouldBeCalled();
      $this->consumerOrgLogin->createOrUpdateLoginOrg($meResponse->getUser()->getConsumerorgs()[0], $meResponse->getUser())
        ->shouldBeCalled();
      $this->userService->getMetadataFields()->willReturn([]);

      $this->userUtils->setCurrentConsumerorg(Argument::any())->willReturn(['url' => '/consumer-orgs/1234/5678/9abc'])->shouldBeCalled();
      $this->userUtils->setOrgSessionData()->shouldBeCalled();

      $this->userStorage->userLoginFinalize($accountStub)->shouldBeCalled();

      $this->logger->notice('@username [UID=@uid] logged in.', ['@username' => 'abc', '@uid' => '1'])->shouldBeCalled();
      $this->logger->notice('attempted login by blocked user: @username [UID=@uid].', Argument::any())->shouldNotBeCalled();
      $this->logger->error(Argument::any())->shouldNotBeCalled();


      $service = $this->generateServiceUnderTest();
      $response = $service->login($user);
      self::assertTrue($response->success());
      self::assertEquals(1, $response->getUid(), 'Expected user id from logged in user.');

    }


    /**
     * @throws \Drupal\Core\Entity\EntityStorageException
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Core\TempStore\TempStoreException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     * @throws \JsonException
     * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     * @throws \Exception
     */
    public function testLoginAsExternalAdmin(): void {

      $user = $this->generateLoginUser();

      $accountStub = $this->createAccountStub();

      $tokenResponse = $this->generateTokenResponse();

      $this->mgmtServer->getAuth($user)->willReturn($tokenResponse);
      $this->sessionStore->set('auth', Argument::any())->shouldNotBeCalled();
      $meResponse = $this->createMeResponse($user);
      $meResponse->getUser()->setUsername('admin');
      $this->mgmtServer->getMe('aBearerToken')->willReturn($meResponse);
      $this->userStorage->loadUserByEmailAddress($meResponse->getUser()->getMail())->willReturn($accountStub);
      $this->accountService->createOrUpdateLocalAccount(Argument::any())->shouldNotBeCalled();
      $this->consumerOrgLogin->createOrUpdateLoginOrg(Argument::any(), Argument::any())->shouldNotBeCalled();

      $this->userUtils->setCurrentConsumerorg(Argument::any())->shouldNotBeCalled();
      $this->userUtils->setOrgSessionData()->shouldNotBeCalled();
      $this->userStorage->userLoginFinalize(Argument::any())->shouldNotBeCalled();

      $this->logger->notice('@username [UID=@uid] logged in.', Argument::any())->shouldNotBeCalled();
      $this->logger->notice('attempted login by blocked user: @username [UID=@uid].', Argument::any())->shouldNotBeCalled();

      $this->logger->error("Login failed because %name user from external registry is prohibited.", ["%name" => "admin"])->shouldBeCalled();
      $this->logger->error('Login failed - login is not permitted.')->shouldBeCalled();

      $service = $this->generateServiceUnderTest();
      $response = $service->login($user);
      self::assertFalse($response->success());

    }

    /**
     * @throws \Drupal\Core\Entity\EntityStorageException
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Core\TempStore\TempStoreException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     * @throws \JsonException
     * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     * @throws \Exception
     */
    public function testLoginAsExternalAnonymous(): void {

      $user = $this->generateLoginUser();

      $accountStub = $this->createAccountStub();

      $tokenResponse = $this->generateTokenResponse();

      $this->mgmtServer->getAuth($user)->willReturn($tokenResponse);
      $this->sessionStore->set('auth', Argument::any())->shouldNotBeCalled();
      $meResponse = $this->createMeResponse($user);
      $meResponse->getUser()->setUsername('Anonymous');
      $this->mgmtServer->getMe('aBearerToken')->willReturn($meResponse);
      $this->userStorage->loadUserByEmailAddress($meResponse->getUser()->getMail())->willReturn($accountStub);
      $this->accountService->createOrUpdateLocalAccount(Argument::any())->shouldNotBeCalled();
      $this->consumerOrgLogin->createOrUpdateLoginOrg(Argument::any(), Argument::any())->shouldNotBeCalled();

      $this->userUtils->setCurrentConsumerorg(Argument::any())->shouldNotBeCalled();
      $this->userUtils->setOrgSessionData()->shouldNotBeCalled();
      $this->userStorage->userLoginFinalize(Argument::any())->shouldNotBeCalled();

      $this->logger->notice('@username [UID=@uid] logged in.', Argument::any())->shouldNotBeCalled();
      $this->logger->notice('attempted login by blocked user: @username [UID=@uid].', Argument::any())->shouldNotBeCalled();

      $this->logger->error("Login failed because %name user from external registry is prohibited.", ["%name" => "Anonymous"])
        ->shouldBeCalled();
      $this->logger->error('Login failed - login is not permitted.')->shouldBeCalled();

      $service = $this->generateServiceUnderTest();
      $response = $service->login($user);
      self::assertFalse($response->success());

    }


    /**
     * @throws \Drupal\Core\Entity\EntityStorageException
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Core\TempStore\TempStoreException
     * @throws \JsonException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException|\Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     */
    public function testLoginViaAzCodeValid(): void {
      $this->accountService->setDefaultLanguage(Argument::any());

      $user = $this->setUpOidcLoginTest();
      $this->userService->parseDrupalAccount(Argument::any())->willReturn($user);
      $this->userRegistryService->get('/reg/oidc1')->willReturn($this->oidcRegistry);
      $service = $this->generateServiceUnderTest();
      $this->userService->getMetadataFields()->willReturn([]);
      $response = $service->loginViaAzCode('validCode', '/reg/oidc1');

      self::assertEquals('<front>', $response);
    }

    /**
     * @throws \Drupal\Core\Entity\EntityStorageException
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     * @throws \Drupal\Core\TempStore\TempStoreException
     * @throws \JsonException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     */
    public function testLoginViaAzCodeErrorOnApimAuthenticate(): void {

      $badUser = new ApicUser();
      $badUser->setAuthcode('bad');
      $badUser->setApicUserRegistryUrl('/reg/oidc1');

      $this->mgmtServer->getAuth($badUser)->willReturn(FALSE);
      $this->sessionStore->set('auth', Argument::any())->shouldNotBeCalled();

      $service = $this->generateServiceUnderTest();
      $response = $service->loginViaAzCode('bad', '/reg/oidc1');

      self::assertEquals('ERROR', $response);
    }

    /**
     * @throws \Drupal\Core\Entity\EntityStorageException
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Core\TempStore\TempStoreException
     * @throws \JsonException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException|\Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     */
    public function testLoginViaAzCodeFirstTime(): void {

      $user = $this->setUpOidcLoginTest();
      $this->userService->parseDrupalAccount(Argument::any())->willReturn($user);
      $this->userService->getMetadataFields()->willReturn([]);
      $this->userRegistryService->get('/reg/oidc1')->willReturn($this->oidcRegistry);

      $first_time_user = $this->prophet->prophesize(User::class);

      $first_time_field = $this->prophet->prophesize(FieldItemList::class);

      $first_time_field->getString()->willReturn('1');
      $first_time_user->get('first_time_login')->willReturn($first_time_field);
      $first_time_user->set('first_time_login', 0)->shouldBeCalled();
      $first_time_user->save()->shouldBeCalled();

      $this->currentUser->id()->willReturn('1');
      $this->drupalUser->load('1')->willReturn($first_time_user->reveal());

      $this->accountService->setDefaultLanguage(Argument::any())->shouldBeCalled();

      $service = $this->generateServiceUnderTest();
      $response = $service->loginViaAzCode('validCode', '/reg/oidc1');

      self::assertEquals('ibm_apim.get_started', $response);

    }

    /**
     * @throws \Drupal\Core\Entity\EntityStorageException
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Core\TempStore\TempStoreException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     * @throws \JsonException|\Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     */
    public function testLoginViaAzCodeCreateNewOrg(): void {
      $user = $this->setUpOidcLoginTest();
      $this->userService->parseDrupalAccount(Argument::any())->willReturn($user);
      $this->userService->getMetadataFields()->willReturn([]);
      $this->userRegistryService->get('/reg/oidc1')->willReturn($this->oidcRegistry);
      $this->accountService->setDefaultLanguage(Argument::any());

      $this->userUtils->getCurrentConsumerorg()->willReturn(NULL);
      $this->siteConfig->isSelfOnboardingEnabled()->willReturn(TRUE);

      $service = $this->generateServiceUnderTest();
      $response = $service->loginViaAzCode('validCode', '/reg/oidc1');

      self::assertEquals('consumerorg.create', $response);

    }

    /**
     * @throws \Drupal\Core\Entity\EntityStorageException
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Core\TempStore\TempStoreException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     * @throws \JsonException|\Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     */
    public function testLoginViaAzCodeNoOrgNoPerms(): void {
      $this->accountService->setDefaultLanguage(Argument::any());

      $user = $this->setUpOidcLoginTest();
      $this->userService->parseDrupalAccount(Argument::any())->willReturn($user);
      $this->userService->getMetadataFields()->willReturn([]);
      $this->userRegistryService->get('/reg/oidc1')->willReturn($this->oidcRegistry);

      $this->userUtils->getCurrentConsumerorg()->willReturn(NULL);
      $this->siteConfig->isSelfOnboardingEnabled()->willReturn(FALSE);

      $service = $this->generateServiceUnderTest();
      $response = $service->loginViaAzCode('validCode', '/reg/oidc1');

      self::assertEquals('ibm_apim.noperms', $response);
    }

    /**
     * @throws \Drupal\Core\Entity\EntityStorageException
     * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
     * @throws \Drupal\Core\TempStore\TempStoreException
     * @throws \JsonException
     * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
     * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     * @throws \Exception
     */
    public function testLoginFailMoreThanOneUserAlreadyInDBWithEmailAddress(): void {

      $user = $this->generateLoginUser();

      $tokenResponse = $this->generateTokenResponse();

      $this->mgmtServer->getAuth($user)->willReturn($tokenResponse);
      $this->sessionStore->set('auth', Argument::any())->shouldNotBeCalled();
      $meResponse = $this->createMeResponse($user);
      $this->mgmtServer->getMe('aBearerToken')->willReturn($meResponse);

      $multipleEmailHitsException = new \Exception('Multiple users (2) returned matching email "andre@example.com" unable to continue.');
      $this->userStorage->loadUserByEmailAddress($meResponse->getUser()->getMail())->willThrow($multipleEmailHitsException);

      $this->accountService->createOrUpdateLocalAccount(Argument::any())->shouldNotBeCalled();
      $this->consumerOrgLogin->createOrUpdateLoginOrg(Argument::any(), Argument::any())->shouldNotBeCalled();

      $this->userUtils->setCurrentConsumerorg(Argument::any())->shouldNotBeCalled();
      $this->userUtils->setOrgSessionData()->shouldNotBeCalled();

      $this->userStorage->userLoginFinalize(Argument::any())->shouldNotBeCalled();

      $this->logger->notice('@username [UID=@uid] logged in.', ['@username' => 'abc', '@uid' => '1'])->shouldNotBeCalled();
      $this->logger->error('Login failed because there was a problem searching for users based on email: %message',
        ["%message" => 'Multiple users (2) returned matching email "andre@example.com" unable to continue.'])->shouldBeCalled();
      $this->logger->error("Login failed - login is not permitted.")->shouldBeCalled();

      $service = $this->generateServiceUnderTest();
      $response = $service->login($user);
      self::assertFalse($response->success());

    }


    // Helper Functions for login().

    /**
     * @param \Drupal\ibm_apim\ApicType\ApicUser $user
     *
     * @return \Drupal\ibm_apim\Rest\MeResponse
     */
    private function createMeResponse(ApicUser $user): MeResponse {

      $meResponse = new MeResponse();

      $meResponse->setCode(200);
      $meResponse->setUser($user);
      $meResponse->getUser()->setFirstname('abc');
      $meResponse->getUser()->setLastname('def');
      $meResponse->getUser()->setMail('abc@me.com');
      $meResponse->getUser()->setApicUserRegistryUrl('/registry/idp1');
      $meResponse->getUser()->setUrl('user/url');
      $meResponse->getUser()->setApicIdp('idp1');
      $meResponse->getUser()->setState('enabled');
      $org = new ConsumerOrg();
      $org->setUrl('/consumer-orgs/1234/5678/9abc');
      $org->setName('org1');
      $org->setTitle('org1');
      $org->setId('999');

      $meResponse->getUser()->setConsumerorgs([$org]);

      return $meResponse;
    }


    /**
     * @return \Drupal\auth_apic\UserManagement\ApicLoginService
     */
    private function generateServiceUnderTest(): ApicLoginService {

      return new ApicLoginService($this->mgmtServer->reveal(),
        $this->accountService->reveal(),
        $this->userUtils->reveal(),
        $this->userStorage->reveal(),
        $this->tempStore->reveal(),
        $this->logger->reveal(),
        $this->siteConfig->reveal(),
        $this->currentUser->reveal(),
        $this->entityTypeManager->reveal(),
        $this->consumerOrgLogin->reveal(),
        $this->userService->reveal(),
        $this->moduleHandler->reveal(),
        $this->userRegistryService->reveal(),
        $this->utils->reveal());
    }

    /**
     * @throws \Drupal\Core\Entity\EntityStorageException
     * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
     * @throws \Drupal\Core\TempStore\TempStoreException
     * @throws \Exception
     */
    private function setUpOidcLoginTest(): ApicUser {
      $loginUser = new ApicUser();
      $loginUser->setUsername('');
      $loginUser->setPassword('');
      $loginUser->setApicUserRegistryUrl('/reg/oidc1');
      $loginUser->setAuthcode('validCode');

      $accountStub = $this->createAccountStub();

      $tokenResponse = $this->generateTokenResponse();

      $this->mgmtServer->getAuth($loginUser)->willReturn($tokenResponse);
      $this->sessionStore->set('auth', Argument::any())->shouldBeCalled();
      $meResponse = new MeResponse();
      $apicUser = new ApicUser();
      $apicUser->setUsername('oidcandre');
      $apicUser->setMail('oidcandre@example.com');
      $apicUser->setState('enabled');
      $org = new ConsumerOrg();
      $org->setUrl('/consumer-orgs/1234/5678/9abc');
      $org->setName('org1');
      $org->setTitle('org1');
      $org->setId('999');

      $meResponse->setUser($apicUser);
      $meResponse->getUser()->setConsumerorgs([$org]);
      $meResponse->setCode(200);

      $this->mgmtServer->getMe('aBearerToken')->willReturn($meResponse);
      $this->accountService->setDefaultLanguage(Argument::any());
      $this->userStorage->loadUserByEmailAddress('oidcandre@example.com')->willReturn(NULL);
      $this->accountService->createOrUpdateLocalAccount($meResponse->getUser())->willReturn($accountStub)->shouldBeCalled();
      $this->consumerOrgLogin->createOrUpdateLoginOrg($meResponse->getUser()->getConsumerorgs()[0], $meResponse->getUser())
        ->shouldBeCalled();
      $this->userUtils->setCurrentConsumerorg(Argument::any())->willReturn(['url' => '/consumer-orgs/1234/5678/9abc'])->shouldBeCalled();
      $this->userUtils->getCurrentConsumerorg()->willReturn(['url' => '/consumer-orgs/1234/5678/9abc'])->shouldBeCalled();
      $this->userUtils->setOrgSessionData()->shouldBeCalled();
      $this->userStorage->userLoginFinalize($accountStub)->shouldBeCalled();

      $first_time_user = $this->prophet->prophesize(User::class);

      $first_time_field = $this->prophet->prophesize(FieldItemList::class);

      $first_time_field->getString()->willReturn('0');
      $first_time_user->get('first_time_login')->willReturn($first_time_field);
      $this->currentUser->id()->willReturn('1');
      $this->drupalUser->load('1')->willReturn($first_time_user->reveal());

      $this->logger->notice('@username [UID=@uid] logged in.', ['@username' => 'oidcandre', '@uid' => '1'])->shouldBeCalled();
      $this->logger->error(Argument::any())->shouldNotBeCalled();
      return $apicUser;
    }

    /**
     * @return \Drupal\ibm_apim\ApicType\ApicUser
     */
    private function generateLoginUser(): ApicUser {
      $user = new ApicUser();
      $user->setUsername('abc');
      $user->setPassword('123');
      $user->setMail('andre@example.com');
      return $user;
    }

    /**
     * @param bool $addRefreshToken
     *
     * @return \Drupal\ibm_apim\Rest\TokenResponse
     */
    private function generateTokenResponse($addRefreshToken = FALSE): TokenResponse {
      $tokenResponse = new TokenResponse();
      $tokenResponse->setBearerToken('aBearerToken');
      $tokenResponse->setExpiresIn('12345');
      if ($addRefreshToken) {
        $tokenResponse->setRefreshToken('aRefreshToken');
      }
      return $tokenResponse;
    }

  }
}


