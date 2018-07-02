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
use Drupal\auth_apic\Service\ApicUserManager;
use Drupal\auth_apic\JWTToken;
use Drupal\ibm_apim\Rest\RestResponse;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\auth_apic\Service\ApicUserManager
 * @group auth_apic
 */
class ResetPasswordTest extends UserManagerTestBaseClass {

  /**
   * Test successful (204) response from management server.
   */
  public function testResetPasswordSuccess() {
    $obj = $this->createJWT();
    $password = 'abc123';

    $response = new RestResponse();
    $response->setCode(204);

    $this->mgmtServer->resetPassword($obj, $password)->willReturn($response);
    $this->logger->notice(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $userManager = $this->createUserManager();
    $rc = $userManager->resetPassword($obj, $password);
    $this->assertEquals(204, $rc);
  }

  /**
   * Test with non-204 response from management server.
   */
  public function testResetPasswordFail() {
    $obj = $this->createJWT();
    $password = 'abc123';

    $response = new RestResponse();
    $response->setCode(500);

    $this->mgmtServer->resetPassword($obj, $password)->willReturn($response);
    $this->logger->notice('Error resetting password.')->shouldBeCalled();
    $this->logger->error('Reset password response: %result', Argument::type('array'))->shouldBeCalled();

    $userManager = $this->createUserManager();
    $rc = $userManager->resetPassword($obj, $password);

    $this->assertEquals(500, $rc);

  }

  private function createJWT() {
    $jwt = new JWTToken();
    $jwt->setUrl('j/w/t');
    return $jwt;
  }

}
