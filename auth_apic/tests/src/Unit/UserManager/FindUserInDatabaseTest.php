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
 * Unit tests for ApicUserManager::findUserInDatabase() function
 *
 * PHPUnit tests for:
 *   public function findUserInDatabase($username): ?AccountInterface
 *
 * @group auth_apic
 */
class FindUserInDatabaseTest extends UserManagerTestBaseClass {

  public function testFindUserInDatabaseNoResult(): void {

    $this->externalAuth->load('notthere@example.com', 'auth_apic')->willReturn(FALSE);

    $userManager = $this->createUserManager();
    $response = $userManager->findUserInDatabase('notthere@example.com');


    $this->assertNull($response, 'No response expected from findUserInDatabase()');

  }


  public function testFindUserInDatabaseWithResult(): void {
    $account = $this->createAccountStub();
    $this->externalAuth->load('abc@me.com', 'auth_apic')->willReturn($account);

    $userManager = $this->createUserManager();
    $response = $userManager->findUserInDatabase('abc@me.com');

    $this->assertNotNull($response);
    $this->assertEquals($response->get('name')->value, 'andre');
  }

}
