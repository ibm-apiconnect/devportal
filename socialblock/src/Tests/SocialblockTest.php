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

namespace Drupal\socialblock\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * socialblock tests.
 *
 * @group socialblock
 */
class SocialblockTest extends WebTestBase {

  /**
   * Our module dependencies.
   *
   * In Drupal 8's SimpleTest, we declare module dependencies in a public
   * static property called $modules. WebTestBase automatically enables these
   * modules for us.
   *
   * @var array
   */
  static public $modules = array('socialblock');

  /**
   * The installation profile to use with this test.
   *
   * @var string
   */
  protected $profile = 'minimal';

  protected $strictConfigSchema = FALSE;

  /**
   * Setup basic environment.
   */
  protected function setUp() {
    parent::setUp();

  }

  public function testTrue() {
    $found = TRUE;

    $this->assertTrue($found, 'TRUE');
  }

}
