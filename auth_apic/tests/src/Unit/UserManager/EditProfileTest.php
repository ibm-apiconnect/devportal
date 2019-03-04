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

namespace Drupal\Tests\auth_apic\Unit;

use Drupal\auth_apic\Rest\MeResponse;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\Tests\auth_apic\Unit\UserManager\UserManagerTestBaseClass;
use Prophecy\Argument;


/**
 * PHPUnit tests for:
 *   public function update(\Drupal\ibm_apim\ApicType\ApicUser $user);
 *
 * @group auth_apic
 */
class EditProfileTest extends UserManagerTestBaseClass {

  public function testEditUser(): void {

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

  public function testBadManagementNodeResponse(): void {

    $user = $this->createUser();
    $meResponse = $this->createMeResponse();
    $meResponse->setCode(401);
    $meResponse->setErrors(['TEST ERROR']);

    $accountStub = $this->createAccountStub();

    $this->mgmtServer->updateMe($user)->willReturn($meResponse);
    $this->externalAuth->load('andre', 'auth_apic')->willReturn($accountStub);

    $this->logger->error('Failed to update a user in the management server. Response code was @code and error message was @error', [
      '@code' => '401',
      '@error' => 'TEST ERROR',
    ])->shouldBeCalled();

    $userManager = $this->createUserManager();
    $result = $userManager->updateApicAccount($user);
    $this->assertEquals(FALSE, $result);

  }

  public function testBadExternalAuthLoad(): void {

    $user = $this->createUser();
    $meResponse = $this->createMeResponse();

    $this->mgmtServer->updateMe($user)->willReturn($meResponse);
    $this->externalAuth->load('andre', 'auth_apic')->willReturn(FALSE);

    $userManager = $this->createUserManager();
    $result = $userManager->updateLocalAccount($user);
    $this->assertEquals(FALSE, $result);

  }

  private function createUser(): ApicUser {
    $user = new ApicUser();

    $user->setUsername('andre');
    $user->setMail('abc@me.com');
    $user->setPassword('abc');
    $user->setFirstname('abc');
    $user->setLastname('def');
    $user->setOrganization('org1');

    return $user;

  }

  private function createMeResponse(): MeResponse {
    $meResponse = new MeResponse();

    $meResponse->setCode(200);
    $meResponse->setUser($this->createUser());

    return $meResponse;
  }

}
