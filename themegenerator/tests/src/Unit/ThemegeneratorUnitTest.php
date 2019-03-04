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

namespace Drupal\Tests\themegenerator\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * themegenerator tests.
 *
 * @group themegenerator
 */
class ThemegeneratorUnitTest extends UnitTestCase {

  public function testTrue() {
    $found = TRUE;

    $this->assertTrue($found, 'True is not true');
  }

}
