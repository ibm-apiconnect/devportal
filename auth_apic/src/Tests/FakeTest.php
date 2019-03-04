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

namespace Drupal\auth_apic\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for the auth_apic module.
 *
 * @group auth_apic
 */
class FakeTest extends WebTestBase {

  protected function setUp(): void {
  }

  /**
   * A fake test
   */
  public function testAlwaysPass(): void {

    $this->assertTrue(TRUE);

  }

}
