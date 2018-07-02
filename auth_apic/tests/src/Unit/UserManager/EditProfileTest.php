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

use Drupal\Tests\auth_apic\Unit\UserManager\UserManagerTestBaseClass;
use Drupal\auth_apic\Rest\MeResponse;

use Drupal\ibm_apim\ApicType\ApicUser;

use Prophecy\Argument;


/**
 * PHPUnit tests for:
 *   public function update(\Drupal\ibm_apim\ApicType\ApicUser $user);
 *
 * @group auth_apic
 */
class EditProfileTest extends UserManagerTestBaseClass {

  public function testEditUser() {

    $user = $this->createUser();
    $meResponse = $this->createMeResponse();

    $accountStub = $this->createAccountStub();

    $this->mgmtServer->updateMe($user)->willReturn($meResponse);
    $this->externalAuth->load('andre', 'auth_apic')->willReturn($accountStub);

    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $userManager = $this->createUserManager();
    $result = $userManager->updateApicAccount($user);
    $this->assertEquals(TRUE, $result);
    $result = $userManager->updateLocalAccount($user);
    $this->assertEquals(TRUE, $result);

  }

  public function testBadManagementNodeResponse() {

    $user = $this->createUser();
    $meResponse = $this->createMeResponse();
    $meResponse->setCode(401);
    $meResponse->setErrors('TEST ERROR');

    $accountStub = $this->createAccountStub();

    $this->mgmtServer->updateMe($user)->willReturn($meResponse);
    $this->externalAuth->load('andre', 'auth_apic')->willReturn($accountStub);

    $this->logger->error("Failed to update a user in the management server. Response code was @code and error message was @error", array(
      '@code' => '401',
      '@error' => 'TEST ERROR'
    ))->shouldBeCalled();

    $userManager = $this->createUserManager();
    $result = $userManager->updateApicAccount($user);
    $this->assertEquals(FALSE, $result);

  }

  public function testBadExternalAuthLoad() {

    $user = $this->createUser();
    $meResponse = $this->createMeResponse();

    $this->mgmtServer->updateMe($user)->willReturn($meResponse);
    $this->externalAuth->load('andre', 'auth_apic')->willReturn(FALSE);

    $userManager = $this->createUserManager();
    $result = $userManager->updateLocalAccount($user);
    $this->assertEquals(FALSE, $result);

  }

  private function createUser() {
    $user = new ApicUser();

    $user->setUsername('andre');
    $user->setMail('abc@me.com');
    $user->setPassword('abc');
    $user->setfirstname('abc');
    $user->setlastname('def');
    $user->setorganization('org1');

    return $user;

  }

  private function createMeResponse() {
    $meResponse = new MeResponse();

    $meResponse->setCode(200);
    $meResponse->setUser($this->createUser());

    return $meResponse;
  }

}
