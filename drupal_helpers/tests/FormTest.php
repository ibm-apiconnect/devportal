<?php

namespace Drupal\drupal_helpers\Tests;

/**
 * Class FormTest.
 *
 * @package Drupal\drupal_helpers\Tests
 */
class FormTestCase extends \PHPUnit_Framework_TestCase {

  /**
   * Test that formGetDefaults() works correctly.
   */
  public function testFormGetDefaults() {
    // Test fixture.
    $params = [
      'key1' => 'val1',
      'key2' => 'val2',
      'key3' => [
        'key31' => 'val31',
        'key32' => 'val32',
        'key33' => [
          'key331' => 'val331',
          'key332' => 'val332',
        ],
      ],
    ];

    // Valid keys.
    $this->assertFormGetDefaults('val1', $params, 'key1', '', 'First dimension scalar');
    $this->assertFormGetDefaults('val31', $params, 'key3', 'key31', '', 'Second dimension scalar');
    $this->assertFormGetDefaults('val332', $params, 'key3', 'key33', 'key332', '', 'Third dimension scalar');
    $this->assertFormGetDefaults([
      'key331' => 'val331',
      'key332' => 'val332',
    ], $params, 'key3', 'key33', '', 'Second dimension array');

    // Default fallbacks.
    $this->assertFormGetDefaults('key4 default', $params, 'key4', 'key4 default', 'Non-existing key leads to defaults');
    $this->assertFormGetDefaults('key34 default', $params, 'key3', 'key34', 'key34 default', 'Non-existing second dimension key leads to defaults');

    // Invalid params.
    $this->assertFormGetDefaults(NULL, $params, 'key1', 'Missing default value return NULL');
    $this->assertFormGetDefaults(NULL, 'key1', '', 'Missing param value return NULL');
    $this->assertFormGetDefaults(NULL, $params, '', 'Missing key value return NULL');
    $this->assertFormGetDefaults(NULL, $params, 'Missing key and default values return NULL');
    $this->assertFormGetDefaults(NULL, 'Missing all values return NULL');
  }

  /**
   * Assert that formGetDefaults() method returns extected result.
   *
   * @param mixed $expected
   *   Expected result.
   * @param ...
   *   Variable number of parameters to pass to the formGetDefaults( ) method.
   *   Last parameter is an assert message.
   */
  protected function assertFormGetDefaults($expected) {
    $args = func_get_args();
    array_shift($args);
    $message = array_pop($args);

    $actual = call_user_func_array('Drupal\drupal_helpers\Form::formGetDefaults', $args);

    $this->assertEquals($expected, $actual, $message);
  }

}
