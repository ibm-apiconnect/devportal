<?php


namespace Drupal\Tests\themegenerator\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * themegenerator tests.
 *
 * @group themegenerator
 */
class ThemegeneratorUnitTest extends UnitTestCase {

  public function testTrue(): void {
    $found = TRUE;

    self::assertTrue($found, 'True is not true');
  }

}
