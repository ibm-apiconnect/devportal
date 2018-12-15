<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\Tests\auth_apic\Unit;

use Drupal\consumerorg\ApicType\ConsumerOrg;
use Drupal\ibm_apim\ApicType\UserRegistry;
use Drupal\auth_apic\Rest\MeResponse;
use Drupal\ibm_apim\ApicType\ApicUser;


use Drupal\Tests\auth_apic\Unit\UserManager\UserManagerTestBaseClass;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\auth_apic\Service\ApicUserManager
 * @group auth_apic
 */
class LoginTest extends UserManagerTestBaseClass {





  public function testUserManagerCreate() {
    $userManager = $this->createUserManager();
    $this->assertNotEmpty($userManager);
  }

  public function testLoginFailNoBearerToken() {
    $user = new ApicUser();
    $user->setUsername('abc');
    $user->setPassword('123');

    $this->mgmtServer->getAuth($user)->willReturn(NULL);
    $this->logger->error('unable to retrieve bearer token on login.')->shouldBeCalled();

    $userManager = $this->createUserManager();
    $response = $userManager->login($user);
    $this->assertFalse($response->success());
  }

  public function testLoginNoLocalUser() {

    $user = new ApicUser();
    $user->setUsername('abc');
    $user->setPassword('123');

    $accountStub = $this->createAccountStub();

    $this->primeForTest($user);

    $extAuth = $this->externalAuth;
    $extAuth->load('abc', 'auth_apic')->willReturn(NULL);
    $extAuth->register("abc", "auth_apic", Argument::any())->will(function ($args) use ($extAuth, $accountStub) {
      // Redefine what load() will return as we now have a user.
      $extAuth->load('abc', 'auth_apic')->willReturn($accountStub);
      // and then return account for register as well
      return $accountStub;
    });
    $extAuth->userLoginFinalize(Argument::any(), 'abc', 'auth_apic')->willReturn($accountStub);

    $userManager = $this->createUserManager();
    $response = $userManager->login($user);
    $this->assertTrue($response->success());
    $this->assertEquals(1, $response->getUid(), 'Expected user id from logged in user.');

  }

  public function testLoginWithLocalUser() {

    // Test starts here.
    $user = new ApicUser();
    $user->setUsername('abc');
    $user->setPassword('123');

    $accountStub = $this->createAccountStub();
    $this->primeForTest($user);

    // we need to pass externalAuth into the closure so create a local var to pass through..
    $extAuth = $this->externalAuth;
    $extAuth->load('abc', 'auth_apic')->willReturn($accountStub);
    $extAuth->userLoginFinalize(Argument::any(), 'abc', 'auth_apic')->willReturn($accountStub);

    $extAuth->register(Argument::any())->shouldNotBeCalled();
    $this->database->update(Argument::any())->shouldNotBeCalled();

    $userManager = $this->createUserManager();
    $response = $userManager->login($user);
    $this->assertTrue($response->success());
    $this->assertEquals(1, $response->getUid(), 'Expected user id from logged in user.');
  }

  public function testLoginFailure() {

    $user = new ApicUser();
    $user->setUsername('xxx');
    $user->setPassword('xxx');

    $meResponse = new MeResponse();
    $meResponse->setCode(401);

    $this->mgmtServer->getAuth(Argument::any())->willReturn("aBearerToken");
    $this->mgmtServer->getMe("aBearerToken")->willReturn($meResponse);

    $this->externalAuth->load(Argument::any())->shouldNotBeCalled();
    $this->externalAuth->register(Argument::any())->shouldNotBeCalled();
    $this->externalAuth->userLoginFinalize(Argument::any())->shouldNotBeCalled();
    $this->database->update(Argument::any())->shouldNotBeCalled();
    $this->consumerorg->create(Argument::any())->shouldNotBeCalled();

    $this->logger->error('failed to authenticate with APIM server')->shouldBeCalled();

    $userManager = $this->createUserManager();
    $response = $userManager->login($user);

    $this->assertFalse($response->success(), 'Expected !success() return from login call');
  }

  public function testLoginWithNewConsumerorg() {

    // Test starts here.
    $user = new ApicUser();
    $user->setUsername('abc');
    $user->setPassword('123');

    $accountStub = $this->createAccountStub();
    $this->primeForTest($user);

    $extAuth = $this->externalAuth;
    $extAuth->load('abc', 'auth_apic')->willReturn(NULL);
    $extAuth->register("abc", "auth_apic", Argument::any())->will(function ($args) use ($extAuth, $accountStub) {
      // Redefine what load() will return as we now have a user.
      $extAuth->load('abc', 'auth_apic')->willReturn($accountStub);
      // and then return account for register as well
      return $accountStub;
    });
    $extAuth->userLoginFinalize(Argument::any(), 'abc', 'auth_apic')->willReturn($accountStub);

    $this->consumerorg->getList()->willReturn(array());
    $this->consumerorg->get('/consumer-orgs/1234/5678/9abc')->willReturn(NULL);
    $this->consumerorg->createNode(Argument::any())->shouldBeCalled()->will(function () {
      $org = new ConsumerOrg();
      $org->setUrl('/consumer-orgs/1234/5678/9abc');
      $org->setName('org1');
      $org->setTitle('org1');
      $org->setId('999');
      $this->get('/consumer-orgs/1234/5678/9abc')->willReturn($org);
    });
//
//    $this->logger->notice('Consumerorg @consumerorgname (url=@consumerorgurl) was not found in drupal database during login. It will be created.', array(
//      '@consumerorgurl' => '/consumer-orgs/1234/5678/9abc',
//      '@consumerorgname' => 'org13'
//    ))->shouldBeCalled();

    $userManager = $this->createUserManager();
    $response = $userManager->login($user);

    $this->assertTrue($response->success());
    $this->assertEquals(1, $response->getUid(), 'Expected user id from logged in user.');

  }



  // Helper Functions.





  private function createMeResponse(ApicUser $user) {

    $meResponse = new MeResponse();

    $meResponse->setCode(200);
    $meResponse->setUser($user);
    $meResponse->getUser()->setFirstname('abc');
    $meResponse->getUser()->setLastname('def');
    $meResponse->getUser()->setMail('abc@me.com');
    $meResponse->getUser()->setApicUserRegistryUrl('user/registry/url');
    $meResponse->getUser()->setUrl('user/url');
    $org = new ConsumerOrg();
    $org->setUrl('/consumer-orgs/1234/5678/9abc');
    $org->setName('org1');
    $org->setTitle('org1');
    $org->setId('999');

    $meResponse->getUser()->setConsumerorgs(array($org));
//    $meResponse->getUser()->setMail('abc@me.com');

    return $meResponse;
  }



  /**
   * Setup login test
   *
   * @param $user
   */
  private function primeForTest($user) {

    $org = new ConsumerOrg();
    $org->setUrl('/consumer-orgs/1234/5678/9abc');
    $org->setName('org1');
    $org->setTitle('org1');
    $org->setId('999');
    $this->consumerorg->get('/consumer-orgs/1234/5678/9abc')->willReturn($org);
    $this->consumerorg->create(Argument::any())->shouldNotBeCalled();
    $this->consumerorg->createOrUpdateNode(Argument::any(), Argument::any())->willReturn(FALSE);

    $this->mgmtServer->getAuth(Argument::any())->willReturn("aBearerToken");
    $this->mgmtServer->setAuth(Argument::any())->willReturn("aBearerToken");
    $meResponse = $this->createMeResponse($user);
    $this->mgmtServer->getMe("aBearerToken")->willReturn($meResponse);

    $accountFields = $this->createAccountFields($user);
    $this->userService->getUserAccountFields($user)->willReturn($accountFields);

    $registry = new UserRegistry();
    $registry->setIdentityProviders([['name' => 'idp1']]);
    $this->userRegistryService->get(Argument::any())->willReturn($registry);
  }

}
