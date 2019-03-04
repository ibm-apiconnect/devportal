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

namespace Drupal\Tests\ibm_apim\Unit;

use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Rest\RestResponse;
use Drupal\Tests\auth_apic\Unit\UserManager\UserManagerTestBaseClass;
use Prophecy\Argument;


/**
 * PHPUnit tests for:
 *   public function register(\Drupal\ibm_apim\ApicType\ApicUser $user);
 *
 * @group auth_apic
 */
class UserManagedSignUpTest extends UserManagerTestBaseClass {

  public function testUserManagedSignUp(): void {

    $user = $this->createUser();
    $accountStub = $this->createAccountStub();
    $accountFields = $this->createAccountFields($user);

    $mgmtServerResponse = new RestResponse();
    $mgmtServerResponse->setCode(204);

    $this->userService->getUserAccountFields($user)->willReturn($accountFields);

    $extAuth = $this->externalAuth;
    $extAuth->register(NULL, 'auth_apic', Argument::any())->will(function ($args) use ($extAuth, $accountStub) {
      $extAuth->load('fred@example.com', 'auth_apic')->willReturn($accountStub);
      return $accountStub;
    });

    $this->mgmtServer->postSignUp($user)->willReturn($mgmtServerResponse);

    $this->logger->notice('sign-up processed for @username', ['@username' => $user->getUsername()])->shouldBeCalled();

    $userManager = $this->createUserManager();
    $result = $userManager->userManagedSignUp($user);

    $this->assertEquals(TRUE, $result->success());
    $this->assertEquals('<front>', $result->getRedirect());
  }

  public function testRegisterNewUserFailure(): void {

    $user = $this->createUser();

    $mgmtServerResponse = new RestResponse();
    $mgmtServerResponse->setCode(401);

    $this->mgmtServer->postSignUp($user)->willReturn($mgmtServerResponse);

    $userManager = $this->createUserManager();
    $result = $userManager->userManagedSignUp($user);
    $this->assertEquals(FALSE, $result->success());
  }

  private function createUser(): ApicUser {
    $user = new ApicUser();

    $user->setMail('fred@example.com');
    $user->setPassword('abc');
    $user->setFirstname('fred');
    $user->setLastname('fredsonn');
    $user->setOrganization('org1');

    return $user;

  }

}
