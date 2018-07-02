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

namespace Drupal\product\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\product\Product;
use Drupal\node\Entity\Node;

/**
 * product tests.
 *
 * @group product
 */
class ProductTest extends WebTestBase {

  /**
   * Our module dependencies.
   *
   * In Drupal 8's SimpleTest, we declare module dependencies in a public
   * static property called $modules. WebTestBase automatically enables these
   * modules for us.
   *
   * @var array
   */
  static public $modules = array('ibm_apim', 'consumerorg', 'product');

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
   * Test that does nothing
   */
  function testNoddy() {
    $this->assertEqual(TRUE, TRUE, 'true');
  }

}
