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

use Drupal\auth_apic\JWTToken;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Rest\RestResponse;
use Drupal\Tests\auth_apic\Unit\UserManager\UserManagerTestBaseClass;
use Prophecy\Argument;

/**
 * User invited from APIM, so user already exists so we don't need to provide fields, i.e. user has been redirected to login form.
 *
 * PHPUnit tests for:
 *   public function acceptInvite(JWTToken $token, ApicUser $acceptingUser)
 *
 * @group auth_apic
 */
class AcceptInviteTest extends UserManagerTestBaseClass {

  public function testAcceptInvite(): void {

    $jwt = $this->createJWT();
    $user = $this->createUser();

    $mgmtResponse = new RestResponse();
    $mgmtResponse->setCode(201);

    $this->mgmtServer->acceptInvite($jwt, $user, 'AndreOrg')->willReturn($mgmtResponse);

    $this->logger->notice('invitation processed for @username', ['@username' => $user->getUsername()])->shouldBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $userManager = $this->createUserManager();
    $result = $userManager->acceptInvite($jwt, $user);

    $this->assertTrue($result->success(), 'Exected success from mgmt call');
    $this->assertEquals('<front>', $result->getRedirect(), 'Unexpected redirect location from acceptInvite call');

  }


  public function testAcceptInviteFailFromMgmt(): void {


    $jwt = $this->createJWT();
    $user = $this->createUser();

    $mgmtResponse = new RestResponse();
    $mgmtResponse->setCode(400);
    $mgmtResponse->setErrors(['TEST ERROR']);

    $this->mgmtServer->acceptInvite($jwt, $user, 'AndreOrg')->willReturn($mgmtResponse);

    $this->logger->error('Error during acceptInvite:  @error', ['@error' => 'TEST ERROR'])->shouldBeCalled();

    $userManager = $this->createUserManager();
    $result = $userManager->acceptInvite($jwt, $user);

    $this->assertFalse($result->success(), 'Unexpected result from mgmt call');
    $this->assertEquals('<front>', $result->getRedirect(), 'Unexpected redirect location from acceptInvite call');


  }


  // Helper functions:
  private function createUser(): ApicUser {
    $user = new ApicUser();

    $user->setMail('andre@example.com');
    $user->setPassword('abc');
    $user->setFirstname('Andre');
    $user->setLastname('Andresson');
    $user->setOrganization('AndreOrg');

    return $user;

  }

  private function createJWT(): JWTToken {
    $jwt = new JWTToken();
    $jwt->setUrl('accept/invite/url');
    return $jwt;
  }


}
