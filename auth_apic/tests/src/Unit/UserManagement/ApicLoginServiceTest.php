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

namespace Drupal\Tests\auth_apic\Unit\UserManagement {

  use Drupal\auth_apic\UserManagement\ApicLoginService;
  use Drupal\consumerorg\ApicType\ConsumerOrg;
  use Drupal\ibm_apim\ApicType\ApicUser;
  use Drupal\ibm_apim\Rest\MeResponse;
  use Drupal\ibm_apim\Rest\TokenResponse;
  use Prophecy\Argument;


  /**
   * @coversDefaultClass \Drupal\auth_apic\UserManagement\ApicLoginService
   * @group auth_apic
   */
  class ApicLoginServiceTest extends AuthApicUserManagementBaseTestClass {

    /*
     Dependencies of ApicLoginService
     */
    protected $mgmtServer;

    protected $accountService;

    protected $userUtils;

    protected $userStorage;

    protected $tempStore;

    protected $logger;

    protected $siteConfig;

    protected $currentUser;

    protected $entityTypeManager;

    protected $drupalUser;

    protected $consumerOrgLogin;

    protected $sessionStore;

    protected function setup() {
      parent::setup();

      $this->mgmtServer = $this->prophet->prophesize(\Drupal\ibm_apim\Service\APIMServer::class);
      $this->accountService = $this->prophet->prophesize(\Drupal\ibm_apim\UserManagement\ApicAccountService::class);
      $this->userUtils = $this->prophet->prophesize(\Drupal\ibm_apim\Service\UserUtils::class);
      $this->userStorage = $this->prophet->prophesize(\Drupal\ibm_apim\Service\ApicUserStorage::class);
      $this->tempStore = $this->prophet->prophesize(\Drupal\Core\TempStore\PrivateTempStoreFactory::class);
      $this->sessionStore = $this->prophet->prophesize(\Drupal\Core\TempStore\PrivateTempStore::class);
      $this->logger = $this->prophet->prophesize(\Psr\Log\LoggerInterface::class);
      $this->siteConfig = $this->prophet->prophesize(\Drupal\ibm_apim\Service\SiteConfig::class);
      $this->currentUser = $this->prophet->prophesize(\Drupal\Core\Session\AccountProxyInterface::class);
      $this->entityTypeManager = $this->prophet->prophesize(\Drupal\Core\Entity\EntityTypeManagerInterface::class);
      $this->consumerOrgLogin = $this->prophet->prophesize(\Drupal\consumerorg\Service\ConsumerOrgLoginService::class);

      $this->drupalUser = $this->prophet->prophesize(\Drupal\user\UserStorageInterface::class);

      $this->entityTypeManager->getStorage('user')->willReturn($this->drupalUser->reveal());
      $this->tempStore->get('ibm_apim')->willReturn($this->sessionStore);

    }

    protected function tearDown() {
      parent::tearDown();
    }


    public function testLoginServiceCreate(): void {
      $service = $this->generateServiceUnderTest();
      $this->assertNotEmpty($service);
    }

    public function testLoginSuccessNoRefreshToken(): void {

      $user = $this->generateLoginUser();

      $accountStub = $this->createAccountStub();

      $tokenResponse = $this->generateTokenResponse();

      $this->mgmtServer->getAuth($user)->willReturn($tokenResponse);
      $meResponse = $this->createMeResponse($user);
      $this->mgmtServer->getMe('aBearerToken')->willReturn($meResponse);
      $this->userStorage->loadUserByEmailAddress($meResponse->getUser()->getMail())->willReturn($accountStub);

      $this->accountService->createOrUpdateLocalAccount($meResponse->getUser())->willReturn($accountStub)->shouldBeCalled();
      $this->consumerOrgLogin->createOrUpdateLoginOrg($meResponse->getUser()->getConsumerorgs()[0], $meResponse->getUser())->shouldBeCalled();

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
      $this->assertTrue($response->success());
      $this->assertEquals(1, $response->getUid(), 'Expected user id from logged in user.');
    }

    public function testLoginSuccessWithRefreshToken(): void {

      $user = $this->generateLoginUser();

      $accountStub = $this->createAccountStub();

      $tokenResponse = $this->generateTokenResponse(true);

      $this->mgmtServer->getAuth($user)->willReturn($tokenResponse);
      $meResponse = $this->createMeResponse($user);
      $this->mgmtServer->getMe('aBearerToken')->willReturn($meResponse);
      $this->userStorage->loadUserByEmailAddress($meResponse->getUser()->getMail())->willReturn($accountStub);

      $this->accountService->createOrUpdateLocalAccount($meResponse->getUser())->willReturn($accountStub)->shouldBeCalled();
      $this->consumerOrgLogin->createOrUpdateLoginOrg($meResponse->getUser()->getConsumerorgs()[0], $meResponse->getUser())->shouldBeCalled();

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
      $this->assertTrue($response->success());
      $this->assertEquals(1, $response->getUid(), 'Expected user id from logged in user.');
    }

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
      $this->userUtils->setOrgSessionData(Argument::any())->shouldNotBeCalled();
      $this->consumerOrgLogin->createOrUpdateLoginOrg(Argument::any())->shouldNotBeCalled();

      $service = $this->generateServiceUnderTest();
      $response = $service->login($user);
      $this->assertFalse($response->success());
      $this->assertEquals('Unable to retrieve bearer token, please contact the system administrator.', $response->getMessage());
    }


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
      $this->userUtils->setOrgSessionData(Argument::any())->shouldNotBeCalled();
      $this->consumerOrgLogin->createOrUpdateLoginOrg(Argument::any())->shouldNotBeCalled();

      $service = $this->generateServiceUnderTest();
      $response = $service->login($user);
      $this->assertFalse($response->success());
      $this->assertEquals('a:1:{i:0;s:9:"something";}', $response->getMessage()); // urgh
    }

    public function testLoginFailBlockedUser(): void {

      $user = $this->generateLoginUser();

      $accountStub = $this->createBlockedAccountStub();

      $tokenResponse = $this->generateTokenResponse();

      $this->mgmtServer->getAuth($user)->willReturn($tokenResponse);
      $meResponse = $this->createMeResponse($user);
      $this->mgmtServer->getMe('aBearerToken')->willReturn($meResponse);
      $this->userStorage->loadUserByEmailAddress($meResponse->getUser()->getMail())->willReturn($accountStub);

      $this->accountService->createOrUpdateLocalAccount($meResponse->getUser())->willReturn($accountStub)->shouldBeCalled();

      $this->sessionStore->set('auth', Argument::any())->shouldNotBeCalled();
      $this->consumerOrgLogin->createOrUpdateLoginOrg(Argument::any())->shouldNotBeCalled();
      $this->userUtils->setCurrentConsumerorg(Argument::any())->shouldNotBeCalled();
      $this->userUtils->setOrgSessionData()->shouldNotBeCalled();
      $this->userStorage->userLoginFinalize(Argument::any())->shouldNotBeCalled();

      $this->logger->notice(Argument::any())->shouldNotBeCalled();
      $this->logger->error('attempted login by blocked user: @username [UID=@uid].', Argument::any())->shouldBeCalled();

      $service = $this->generateServiceUnderTest();
      $response = $service->login($user);
      $this->assertFalse($response->success());
      $this->assertEquals(1, $response->getUid(), 'Expected user id from logged in user.');

    }

    public function testLoginWithNoConsumerorg(): void {

      $user = $this->generateLoginUser();

      $accountStub = $this->createAccountStubNoConsumerOrgs();

      $tokenResponse = $this->generateTokenResponse();

      $this->mgmtServer->getAuth($user)->willReturn($tokenResponse);
      $this->sessionStore->set('auth', Argument::any())->shouldBeCalled();
      $meResponse = $this->createMeResponse($user);
      $this->userStorage->loadUserByEmailAddress($meResponse->getUser()->getMail())->willReturn($accountStub);

      // Get no consumer orgs back on me response.
      $meResponse->getUser()->setConsumerorgs([]);

      $this->mgmtServer->getMe('aBearerToken')->willReturn($meResponse);

      $this->accountService->createOrUpdateLocalAccount($meResponse->getUser())->willReturn($accountStub)->shouldBeCalled();
      $this->consumerOrgLogin->createOrUpdateLoginOrg(Argument::any())->shouldNotBeCalled();

      $this->userUtils->setCurrentConsumerorg(NULL)->shouldBeCalled();
      $this->userUtils->setOrgSessionData()->shouldNotBeCalled();

      $this->userStorage->userLoginFinalize($accountStub)->shouldBeCalled();

      $this->logger->notice('@username [UID=@uid] logged in.', ['@username' => 'abc', '@uid' => '1'])->shouldBeCalled();
      $this->logger->notice('attempted login by blocked user: @username [UID=@uid].', Argument::any())->shouldNotBeCalled();
      $this->logger->notice('no consumer orgs set on login')->shouldBeCalled();
      $this->logger->error(Argument::any())->shouldNotBeCalled();

      $service = $this->generateServiceUnderTest();
      $response = $service->login($user);
      $this->assertTrue($response->success());
      $this->assertEquals(1, $response->getUid(), 'Expected user id from logged in user.');

    }

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
      $this->consumerOrgLogin->createOrUpdateLoginOrg(Argument::any())->shouldNotBeCalled();

      $this->userUtils->setCurrentConsumerorg(Argument::any())->shouldNotBeCalled();
      $this->userUtils->setOrgSessionData()->shouldNotBeCalled();
      $this->userStorage->userLoginFinalize(Argument::any())->shouldNotBeCalled();

      $this->logger->notice('@username [UID=@uid] logged in.', Argument::any())->shouldNotBeCalled();
      $this->logger->notice('attempted login by blocked user: @username [UID=@uid].', Argument::any())->shouldNotBeCalled();
      $this->logger->error("Invalid login attempt for %user, state is %state.", ["%user" => "abc", "%state" => "pending"])->shouldBeCalled();
      $this->logger->error("Login failed for %user - user failed validation check based on information from apim.", ["%user" => "abc"])->shouldBeCalled();

      $this->logger->error('Login failed - login is not permitted.')->shouldBeCalled();

      $service = $this->generateServiceUnderTest();
      $response = $service->login($user);
      $this->assertFalse($response->success());

    }

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
      $this->consumerOrgLogin->createOrUpdateLoginOrg(Argument::any())->shouldNotBeCalled();

      $this->userUtils->setCurrentConsumerorg(Argument::any())->shouldNotBeCalled();
      $this->userUtils->setOrgSessionData()->shouldNotBeCalled();
      $this->userStorage->userLoginFinalize(Argument::any())->shouldNotBeCalled();

      $this->logger->notice('@username [UID=@uid] logged in.', Argument::any())->shouldNotBeCalled();
      $this->logger->notice('attempted login by blocked user: @username [UID=@uid].', Argument::any())->shouldNotBeCalled();
      $this->logger->error("Login failed for %user - user failed validation check based on information from apim.", ["%user" => "abc"])->shouldBeCalled();
      $this->logger->error("Invalid login attempt for %user, apic state cannot be determined.",["%user" => "abc"])->shouldBeCalled();

      $this->logger->error('Login failed - login is not permitted.')->shouldBeCalled();

      $service = $this->generateServiceUnderTest();
      $response = $service->login($user);
      $this->assertFalse($response->success());

    }

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
      $this->consumerOrgLogin->createOrUpdateLoginOrg(Argument::any())->shouldNotBeCalled();

      $this->userUtils->setCurrentConsumerorg(Argument::any())->shouldNotBeCalled();
      $this->userUtils->setOrgSessionData()->shouldNotBeCalled();
      $this->userStorage->userLoginFinalize(Argument::any())->shouldNotBeCalled();

      $this->logger->notice('@username [UID=@uid] logged in.', Argument::any())->shouldNotBeCalled();
      $this->logger->notice('attempted login by blocked user: @username [UID=@uid].', Argument::any())->shouldNotBeCalled();
      $this->logger->error( "login attempt with invalid user. Username and registry url needed.")->shouldBeCalled();
      $this->logger->error("Login failed for %user - user failed validation check based on information from apim.", ["%user" => NULL])->shouldBeCalled();

      $this->logger->error('Login failed - login is not permitted.')->shouldBeCalled();

      $service = $this->generateServiceUnderTest();
      $response = $service->login($user);
      $this->assertFalse($response->success());

    }

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
      $this->consumerOrgLogin->createOrUpdateLoginOrg(Argument::any())->shouldNotBeCalled();

      $this->userUtils->setCurrentConsumerorg(Argument::any())->shouldNotBeCalled();
      $this->userUtils->setOrgSessionData()->shouldNotBeCalled();
      $this->userStorage->userLoginFinalize(Argument::any())->shouldNotBeCalled();

      $this->logger->notice('@username [UID=@uid] logged in.', Argument::any())->shouldNotBeCalled();
      $this->logger->notice('attempted login by blocked user: @username [UID=@uid].', Argument::any())->shouldNotBeCalled();
      $this->logger->error( "login attempt with invalid user. Username and registry url needed.")->shouldBeCalled();
      $this->logger->error("Login failed for %user - user failed validation check based on information from apim.", ["%user" => 'abc'])->shouldBeCalled();

      $this->logger->error('Login failed - login is not permitted.')->shouldBeCalled();

      $service = $this->generateServiceUnderTest();
      $response = $service->login($user);
      $this->assertFalse($response->success());

    }


    public function testValidateEnabledNoUserInDB(): void {
      $user = $this->generateLoginUser();

      $accountStub = $this->createAccountStub();

      $tokenResponse = $this->generateTokenResponse();

      $this->mgmtServer->getAuth($user)->willReturn($tokenResponse);
      $this->sessionStore->set('auth', Argument::any())->shouldBeCalled();
      $meResponse = $this->createMeResponse($user);
      $this->mgmtServer->getMe('aBearerToken')->willReturn($meResponse);
      $this->userStorage->loadUserByEmailAddress($meResponse->getUser()->getMail())->willReturn(NULL);

      $this->accountService->createOrUpdateLocalAccount($meResponse->getUser())->willReturn($accountStub)->shouldBeCalled();
      $this->consumerOrgLogin->createOrUpdateLoginOrg($meResponse->getUser()->getConsumerorgs()[0], $meResponse->getUser())->shouldBeCalled();

      $this->userUtils->setCurrentConsumerorg(Argument::any())->willReturn(['url' => '/consumer-orgs/1234/5678/9abc'])->shouldBeCalled();
      $this->userUtils->setOrgSessionData()->shouldBeCalled();

      $this->userStorage->userLoginFinalize($accountStub)->shouldBeCalled();

      $this->logger->notice('@username [UID=@uid] logged in.', ['@username' => 'abc', '@uid' => '1'])->shouldBeCalled();
      $this->logger->notice('attempted login by blocked user: @username [UID=@uid].', Argument::any())->shouldNotBeCalled();
      $this->logger->error(Argument::any())->shouldNotBeCalled();


      $service = $this->generateServiceUnderTest();
      $response = $service->login($user);
      $this->assertTrue($response->success());
      $this->assertEquals(1, $response->getUid(), 'Expected user id from logged in user.');

    }


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
      $this->consumerOrgLogin->createOrUpdateLoginOrg(Argument::any())->shouldNotBeCalled();

      $this->userUtils->setCurrentConsumerorg(Argument::any())->shouldNotBeCalled();
      $this->userUtils->setOrgSessionData()->shouldNotBeCalled();
      $this->userStorage->userLoginFinalize(Argument::any())->shouldNotBeCalled();

      $this->logger->notice('@username [UID=@uid] logged in.', Argument::any())->shouldNotBeCalled();
      $this->logger->notice('attempted login by blocked user: @username [UID=@uid].', Argument::any())->shouldNotBeCalled();

      $this->logger->error("Login failed because %name user from external registry is prohibited.",["%name" => "admin"])->shouldBeCalled();
      $this->logger->error('Login failed - login is not permitted.')->shouldBeCalled();

      $service = $this->generateServiceUnderTest();
      $response = $service->login($user);
      $this->assertFalse($response->success());

    }

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
      $this->consumerOrgLogin->createOrUpdateLoginOrg(Argument::any())->shouldNotBeCalled();

      $this->userUtils->setCurrentConsumerorg(Argument::any())->shouldNotBeCalled();
      $this->userUtils->setOrgSessionData()->shouldNotBeCalled();
      $this->userStorage->userLoginFinalize(Argument::any())->shouldNotBeCalled();

      $this->logger->notice('@username [UID=@uid] logged in.', Argument::any())->shouldNotBeCalled();
      $this->logger->notice('attempted login by blocked user: @username [UID=@uid].', Argument::any())->shouldNotBeCalled();

      $this->logger->error("Login failed because %name user from external registry is prohibited.",["%name" => "Anonymous"])->shouldBeCalled();
      $this->logger->error('Login failed - login is not permitted.')->shouldBeCalled();

      $service = $this->generateServiceUnderTest();
      $response = $service->login($user);
      $this->assertFalse($response->success());

    }


    public function testLoginViaAzCodeValid() {

      $this->setUpOidcLoginTest();
      $service = $this->generateServiceUnderTest();
      $response = $service->loginViaAzCode('validCode', '/reg/oidc1');

      $this->assertEquals('<front>', $response);
    }

    public function testLoginViaAzCodeErrorOnApimAuthenticate() {

      $badUser = new ApicUser();
      $badUser->setAuthcode('bad');
      $badUser->setApicUserRegistryUrl('/reg/oidc1');

      $this->mgmtServer->getAuth($badUser)->willReturn(FALSE);
      $this->sessionStore->set('auth', Argument::any())->shouldNotBeCalled();

      $service = $this->generateServiceUnderTest();
      $response = $service->loginViaAzCode('bad', '/reg/oidc1');

      $this->assertEquals('ERROR', $response);
    }

    public function testLoginViaAzCodeFirstTime() {

      $this->setUpOidcLoginTest();

      $first_time_user = $this->prophet->prophesize(\Drupal\user\Entity\User::class);

      $first_time_field = $this->prophet->prophesize(\Drupal\Core\Field\FieldItemList::class);

      $first_time_field->getString()->willReturn('1');
      $first_time_user->get('first_time_login')->willReturn($first_time_field);
      $first_time_user->set('first_time_login', 0)->shouldBeCalled();
      $first_time_user->save()->shouldBeCalled();

      $this->currentUser->id()->willReturn('1');
      $this->drupalUser->load('1')->willReturn($first_time_user->reveal());

      $this->accountService->setDefaultLanguage(Argument::any())->shouldBeCalled();

      $service = $this->generateServiceUnderTest();
      $response = $service->loginViaAzCode('validCode', '/reg/oidc1');

      $this->assertEquals('ibm_apim.get_started', $response);

    }

    public function testLoginViaAzCodeCreateNewOrg(){
      $this->setUpOidcLoginTest();

      $this->userUtils->getCurrentConsumerorg()->willReturn(NULL);
      $this->siteConfig->isSelfOnboardingEnabled()->willReturn(TRUE);

      $service = $this->generateServiceUnderTest();
      $response = $service->loginViaAzCode('validCode', '/reg/oidc1');

      $this->assertEquals('consumerorg.create', $response);

    }

    public function testLoginViaAzCodeNoOrgNoPerms(){

      $this->setUpOidcLoginTest();

      $this->userUtils->getCurrentConsumerorg()->willReturn(NULL);
      $this->siteConfig->isSelfOnboardingEnabled()->willReturn(FALSE);

      $service = $this->generateServiceUnderTest();
      $response = $service->loginViaAzCode('validCode', '/reg/oidc1');

      $this->assertEquals('ibm_apim.noperms', $response);
    }

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
      $this->consumerOrgLogin->createOrUpdateLoginOrg(Argument::any())->shouldNotBeCalled();

      $this->userUtils->setCurrentConsumerorg(Argument::any())->shouldNotBeCalled();
      $this->userUtils->setOrgSessionData()->shouldNotBeCalled();

      $this->userStorage->userLoginFinalize(Argument::any())->shouldNotBeCalled();

      $this->logger->notice('@username [UID=@uid] logged in.', ['@username' => 'abc', '@uid' => '1'])->shouldNotBeCalled();
      $this->logger->error( 'Login failed because there was a problem searching for users based on email: %message',
        ["%message" => 'Multiple users (2) returned matching email "andre@example.com" unable to continue.'])->shouldBeCalled();
      $this->logger->error( "Login failed - login is not permitted.")->shouldBeCalled();

      $service = $this->generateServiceUnderTest();
      $response = $service->login($user);
      $this->assertFalse($response->success());

    }


    // Helper Functions for login().


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

      $service = new ApicLoginService($this->mgmtServer->reveal(),
        $this->accountService->reveal(),
        $this->userUtils->reveal(),
        $this->userStorage->reveal(),
        $this->tempStore->reveal(),
        $this->logger->reveal(),
        $this->siteConfig->reveal(),
        $this->currentUser->reveal(),
        $this->entityTypeManager->reveal(),
        $this->consumerOrgLogin->reveal());


      return $service;
    }

    private function setUpOidcLoginTest(): void {
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
      $this->userStorage->loadUserByEmailAddress('oidcandre@example.com')->willReturn(NULL);
      $this->accountService->createOrUpdateLocalAccount($meResponse->getUser())->willReturn($accountStub)->shouldBeCalled();
      $this->consumerOrgLogin->createOrUpdateLoginOrg($meResponse->getUser()->getConsumerorgs()[0], $meResponse->getUser())->shouldBeCalled();
      $this->userUtils->setCurrentConsumerorg(Argument::any())->willReturn(['url' => '/consumer-orgs/1234/5678/9abc'])->shouldBeCalled();
      $this->userUtils->getCurrentConsumerorg()->willReturn(['url' => '/consumer-orgs/1234/5678/9abc'])->shouldBeCalled();
      $this->userUtils->setOrgSessionData()->shouldBeCalled();
      $this->userStorage->userLoginFinalize($accountStub)->shouldBeCalled();

      $first_time_user = $this->prophet->prophesize(\Drupal\user\Entity\User::class);

      $first_time_field = $this->prophet->prophesize(\Drupal\Core\Field\FieldItemList::class);

      $first_time_field->getString()->willReturn('0');
      $first_time_user->get('first_time_login')->willReturn($first_time_field);
      $this->currentUser->id()->willReturn('1');
      $this->drupalUser->load('1')->willReturn($first_time_user->reveal());

      $this->logger->notice('@username [UID=@uid] logged in.', ['@username' => 'oidcandre', '@uid' => '1'])->shouldBeCalled();
      $this->logger->error(Argument::any())->shouldNotBeCalled();
    }

    /**
     * @return \Drupal\ibm_apim\ApicType\ApicUser
     */
    private function generateLoginUser(): \Drupal\ibm_apim\ApicType\ApicUser {
      $user = new ApicUser();
      $user->setUsername('abc');
      $user->setPassword('123');
      $user->setMail('andre@example.com');
      return $user;
    }

    /**
     * @return \Drupal\ibm_apim\Rest\TokenResponse
     */
    private function generateTokenResponse($addRefreshToken = false): \Drupal\ibm_apim\Rest\TokenResponse {
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


