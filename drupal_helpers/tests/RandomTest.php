<?php

namespace Drupal\drupal_helpers\Tests;

/**
 * Class ArrayItemsRandomTestCase.
 *
 * @package Drupal\drupal_helpers\Tests
 */
class RandomTestCase extends \PHPUnit_Framework_TestCase {

  /**
   * Test functionality of arrayItems() method.
   *
   * @dataProvider providerArrayItems
   */
  public function testArrayItems($haystack, $count, $expected_count = NULL) {
    $expected_count = is_null($expected_count) ? $count : $expected_count;
    $actual = call_user_func('Drupal\drupal_helpers\Random::arrayItems', $haystack, $count);

    $this->assertEquals($expected_count, count($actual), 'Returned array has expected count');
    $this->assertEquals(0, count(array_diff($actual, $haystack)), 'Values of returned array are from original array');
    $actual_keys = array_keys($actual);
    $haystack_keys = array_keys($haystack);

    $this->assertEquals(0, count(array_diff($actual_keys, $haystack_keys)), 'Keys of returned array are from original array');
  }

  /**
   * Data provider for testArrayItems().
   */
  public function providerArrayItems() {
    return [
      [[], 0],
      [[1], 1],
      [[1, 2, 3], 1],
      [[1, 2, 3], 2],
      [[1], 2, 1],
      [[], 2, 0],
      [
        [4 => 1, 5 => 2, 6 => 3],
        1,
      ],
      [
        ['a' => 1, 'b' => 2, 'c' => 3],
        1,
      ],
      [
        ['a' => 1, 'b' => 2, 'c' => 3],
        2,
      ],
      [
        ['a' => 1, 'b' => 2, 'c' => 3],
        4,
        3,
      ],
      [
        ['a' => 1, 1 => 'b', 'c' => 'd'],
        2,
      ],
    ];
  }

}
