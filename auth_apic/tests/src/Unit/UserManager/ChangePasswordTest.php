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

use Drupal\ibm_apim\Rest\RestResponse;
use Drupal\Tests\auth_apic\Unit\UserManager\UserManagerTestBaseClass;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\auth_apic\Service\ApicUserManager
 * @group auth_apic
 */
class ChangePasswordTest extends UserManagerTestBaseClass {

  public function testChangePassword(): void {
    $goodResponse = new RestResponse();
    $goodResponse->setCode(204);

    $account = $this->createAccountStub();

    $this->mgmtServer->changePassword('oldun', 'newun')->willReturn($goodResponse);

    $this->logger->notice('changePassword called for @username', ['@username' => 'andre'])->shouldBeCalled();
    $this->logger->notice('Password changed successfully.')->shouldBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $this->mgmtServer->setAuth(Argument::any())->shouldBeCalled();

    $userManager = $this->createUserManager();

    $result = $userManager->changePassword($account, 'oldun', 'newun');
    $this->assertTrue($result, 'positive result expected from change password.');
  }

  public function testChangePasswordFail(): void {
    $badResponse = new RestResponse();
    $badResponse->setCode(400);

    $account = $this->createAccountStub();

    $this->mgmtServer->changePassword('oldun', 'newun')->willReturn($badResponse);
    $this->logger->notice('changePassword called for @username', ['@username' => 'andre'])->shouldBeCalled();
    $this->logger->error('Password change failure.')->shouldBeCalled();
    $this->mgmtServer->setAuth(Argument::any())->shouldNotBeCalled();
    $userManager = $this->createUserManager();

    $result = $userManager->changePassword($account, 'oldun', 'newun');
    $this->assertFalse($result, 'negative result expected from change password.');
  }

}
