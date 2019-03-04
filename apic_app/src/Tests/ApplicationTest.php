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

namespace Drupal\apic_app\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\apic_app\Application;
use Drupal\node\Entity\Node;

/**
 * application tests.
 *
 * @group apic_app
 */
class ApplicationTest extends WebTestBase {

  /**
   * Our module dependencies.
   *
   * In Drupal 8's SimpleTest, we declare module dependencies in a public
   * static property called $modules. WebTestBase automatically enables these
   * modules for us.
   *
   * @var array
   */
  static public $modules = ['apic_app', 'ibm_apim'];

  /**
   * The installation profile to use with this test.
   *
   * @var string
   */
  protected $profile = 'minimal';

  /**
   * A user with permission to create and edit books and to administer blocks.
   *
   * @var \Drupal\core\session\AccountInterface $entity
   */
  protected $adminUser;

  /**
   * votingapi_widget seems to fail the strict validator
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * Setup basic environment.
   */
  protected function setUp() {
    parent::setUp();

  }

  /**
   * REMOVE THIS EXAMPLE TEST WHEN ACTUAL TESTS ARE IMPLEMENTED
   */
  public function testTrue() {
    $this->assertTrue(TRUE);
  }

}
