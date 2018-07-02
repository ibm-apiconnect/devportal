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

namespace Drupal\Tests\ibm_apim\Unit;

use Drupal\auth_apic\UserManagerResponse;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Rest\RestResponse;
use Drupal\Tests\auth_apic\Unit\UserManager\UserManagerTestBaseClass;
use Drupal\auth_apic\Service\ApicUserManager;
use Drupal\auth_apic\Rest\UsersRegisterResponse;


use Prophecy\Argument;

/**
 * PHPUnit tests for:
 *   public function register(\Drupal\ibm_apim\ApicType\ApicUser $user);
 *
 * @group ibm_apim
 */
class UserManagedSignUpTest extends UserManagerTestBaseClass {

 public function testUserManagedSignUp() {

    $user = $this->createUser();
    $accountStub = $this->createAccountStub();
    $accountFields = $this->createAccountFields($user);

    $mgmtServerResponse = new RestResponse();
    $mgmtServerResponse->setCode(204);

    $this->userService->getUserAccountFields($user)->willReturn($accountFields);

    $extAuth = $this->externalAuth;
    $extAuth->register(NULL, "auth_apic", Argument::any())->will(function ($args) use ($extAuth, $accountStub) {
        $extAuth->load('fred@example.com', 'auth_apic')->willReturn($accountStub);
        return $accountStub;
      });

    $this->mgmtServer->postSignUp($user)->willReturn($mgmtServerResponse);

    $this->logger->notice('sign-up processed for %username', array('%username' => $user->getUsername()))->shouldBeCalled();

    $userManager = $this->createUserManager();
    $result = $userManager->userManagedSignUp($user);

    $this->assertEquals(TRUE, $result->success());
    $this->assertEquals('<front>', $result->getRedirect());
  }

  public function testRegisterNewUserFailure() {

    $user = $this->createUser();

    $mgmtServerResponse = new RestResponse();
    $mgmtServerResponse->setCode(401);

    $this->mgmtServer->postSignUp($user)->willReturn($mgmtServerResponse);

    $userManager = $this->createUserManager();
    $result = $userManager->userManagedSignUp($user);
    $this->assertEquals(FALSE, $result->success());
  }

  private function createUser() {
    $user = new ApicUser();

    $user->setMail('fred@example.com');
    $user->setPassword('abc');
    $user->setfirstname('fred');
    $user->setlastname('fredsonn');
    $user->setorganization('org1');

    return $user;

  }

}
