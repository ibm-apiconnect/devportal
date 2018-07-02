<?php

namespace Drupal\connect_theme\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * connect_theme tests.
 *
 * @group connect_theme
 */
class ConnectThemeTest extends WebTestBase {

  /**
   * The installation profile to use with this test.
   *
   * @var string
   */
  protected $profile = 'minimal';

  public function testTrue() {
    $found = TRUE;

    $this->assertTrue($found, 'True!');
  }

}
