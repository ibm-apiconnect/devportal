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

use Drupal\ibm_apim\Rest\RestResponse;
use Drupal\Tests\auth_apic\Unit\UserManager\UserManagerTestBaseClass;

use Drupal\auth_apic\Rest\ActivationResponse;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\auth_apic\JWTToken;

use Prophecy\Argument;

/**
 * Called from various flows to register a user in the drupal db.
 *
 * PHPUnit tests for:
 *    public function registerApicUser($username = NULL, array $fields)
 *
 * @group auth_apic
 */
class RegisterApicUserTest extends UserManagerTestBaseClass {

  public function testRegisterApicUserAndre() {
    $account = $this->createAndreAccount();
    $this->register($account);
  }

  public function testRegisterApicUserAdmin() {
    $account = $this->createAdminAccount();
    $this->register($account);
  }

  private function register($account) {

    $user = $this->createUser();

    $accountFields = $this->createAccountFields($user);
    $this->externalAuth->register('andre', 'auth_apic', $accountFields)->willReturn($account);


    $this->logger->notice(Argument::any()) ->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $user_manager = $this->createUserManager();
    $response = $user_manager->registerApicUser('andre', $accountFields);

    $this->assertNotNull($response, 'Expected a not null response from registerApicUser()');

  }



  // Helper functions:
  private function createUser() {
    $user = new ApicUser();

    $user->setUsername('andre');
    $user->setMail('andre@example.com');
    $user->setPassword('abc');
    $user->setfirstName('Andre');
    $user->setlastName('Andresson');
    $user->setorganization('AndreOrg');


    return $user;

  }

  protected function createAndreAccount() {
    $account = $this->prophet->prophesize('Drupal\user\Entity\User');

    $account->id()->willReturn(2);
    // important check here is that we null out the pw for andre.
    $account->setPassword(NULL)->shouldBeCalled();
    $account->save()->shouldBeCalled();

    return $account->reveal();
  }

  protected function createAdminAccount() {
    $account = $this->prophet->prophesize('Drupal\user\Entity\User');

    $account->id()->willReturn(1);
    $account->setPassword(Argument::any())->shouldNotBeCalled();

    return $account->reveal();
  }



}
