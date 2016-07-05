<?php

namespace Drupal\drupal_helpers\Tests;

/**
 * Class ArrayRemoveEmptyUtilityTestCase.
 *
 * @package Drupal\drupal_helpers\Tests
 */
class ArrayRemoveEmptyUtilityTestCase extends \PHPUnit_Framework_TestCase {

  /**
   * Test that arrayRemoveEmpty() works correctly.
   */
  public function testArrayRemoveEmpty() {
    $haystack = [
      'k1' => 1,
      'k2' => 0,
      'k3' => NULL,
      'k4' => '',
      'k5' => FALSE,
      'k6' => 'abc',
    ];

    $this->assertArrayRemoveEmpty([
      'k1' => 1,
      'k6' => 'abc',
    ], $haystack, 'All empty elements removed from single dimension array');

    $haystack2 = [
      'k1' => 1,
      'k2' => [
        'k21' => 21,
        'k22' => 0,
        'k23' => NULL,
        'k24' => 24,
      ],
      'k6' => 'abc',
    ];
    $this->assertArrayRemoveEmpty([
      'k1' => 1,
      'k2' => [
        'k21' => 21,
        'k24' => 24,
      ],
      'k6' => 'abc',
    ], $haystack2, 'All empty elements removed from multi-dimension array');

    $haystack3 = [
      'k1' => 1,
      'k2' => [
        'k21' => '',
        'k22' => 0,
        'k23' => NULL,
        'k24' => FALSE,
      ],
      'k6' => 'abc',
    ];
    $this->assertArrayRemoveEmpty([
      'k1' => 1,
      'k6' => 'abc',
    ], $haystack3, 'All empty elements removed from multi-dimension array with an empty array parent removed');
  }

  /**
   * Assert that arrayRemoveEmpty() method removes empty values.
   */
  protected function assertArrayRemoveEmpty($expected, $haystack, $message) {
    $actual = call_user_func('Drupal\drupal_helpers\Utility::arrayRemoveEmpty', $haystack);

    $this->assertEquals($expected, $actual, $message);
  }

}

/**
 * Class ArrayGetColumnUtilityTestCase.
 *
 * @package Drupal\drupal_helpers\Tests
 */
class ArrayGetColumnUtilityTestCase extends \PHPUnit_Framework_TestCase {

  /**
   * Test valid values.
   */
  public function testValidValues() {
    // Simple scalar.
    $this->assertValidValues(1, NULL, NULL, 1);

    // Scalar array. No columns.
    $this->assertValidValues([1, 2, 3], NULL, NULL, [1, 2, 3]);

    // Array of arrays. Simple columns.
    $this->assertValidValues([
      [
        'one' => 11,
        'two' => 12,
        'three' => 13,
      ],
      [
        'one' => 21,
        'two' => 22,
        'three' => 23,
      ],
      [
        'one' => 31,
        'two' => 32,
        'three' => 33,
      ],
    ], 'one', NULL, [11, 21, 31]);

    // Array of arrays. Simple columns with getter - should make no difference.
    $this->assertValidValues([
      [
        'one' => 11,
        'two' => 12,
        'three' => 13,
      ],
      [
        'one' => 21,
        'two' => 22,
        'three' => 23,
      ],
      [
        'one' => 31,
        'two' => 32,
        'three' => 33,
      ],
    ], 'one', 'someFakeGetter', [11, 21, 31]);

    // Object. Public property is accessed directly.
    $this->assertValidValues($this->prepareObjectPublicProperty(1),
      'publicProperty', NULL, 1);

    // Object. Public property is accessed using getter.
    $this->assertValidValues($this->prepareObjectPublicProperty(1),
      NULL, 'getPublicProperty', 1);

    // Array of objects. Public property is accessed directly.
    $this->assertValidValues([
      'one' => $this->prepareObjectPublicProperty(11),
      'two' => $this->prepareObjectPublicProperty(12),
      'three' => $this->prepareObjectPublicProperty(13),
    ], 'publicProperty', NULL, [
      'one' => 11,
      'two' => 12,
      'three' => 13,
    ]);

    // Array of objects. Public property is accessed using getter.
    $this->assertValidValues([
      'one' => $this->prepareObjectPublicProperty(11),
      'two' => $this->prepareObjectPublicProperty(12),
      'three' => $this->prepareObjectPublicProperty(13),
    ], NULL, 'getPublicProperty', [
      'one' => 11,
      'two' => 12,
      'three' => 13,
    ]);

    // Array of array of objects. Public property is accessed using getter.
    $this->assertValidValues([
      [
        'one' => $this->prepareObjectPublicProperty(11),
        'two' => $this->prepareObjectPublicProperty(12),
        'three' => $this->prepareObjectPublicProperty(13),
      ],
      [
        'one' => $this->prepareObjectPublicProperty(21),
        'two' => $this->prepareObjectPublicProperty(22),
        'three' => $this->prepareObjectPublicProperty(23),
      ],
      [
        'one' => $this->prepareObjectPublicProperty(31),
        'two' => $this->prepareObjectPublicProperty(32),
        'three' => $this->prepareObjectPublicProperty(33),
      ],
    ], 'one', 'getPublicProperty', [11, 21, 31]);
  }

  /**
   * Assert valid values helper.
   */
  protected function assertValidValues($value, $column, $getter, $expected) {
    $actual = call_user_func('Drupal\drupal_helpers\Utility::arrayGetColumn', $value, $column, $getter);

    $this->assertEquals($expected, $actual);
  }

  /**
   * Helper to prepare an object with a public property and a value.
   *
   * @param mixed $value
   *   Value to set for public property.
   *
   * @return ArrayGetColumnUtilityTestCaseDummy
   *   Dummy object to be used in test assertions.
   */
  protected function prepareObjectPublicProperty($value) {
    $object = new ArrayGetColumnUtilityTestCaseDummy();
    $object->setPublicProperty($value);

    return $object;
  }

}

/**
 * Class ArrayGetColumnUtilityTestCaseDummy.
 *
 * Dummy class for testing arrayRemoveEmpty() functionality.
 */
class ArrayGetColumnUtilityTestCaseDummy {
  /**
   * Dummy public property.
   *
   * @var mixed
   */
  public $publicProperty;

  /**
   * Dummy private property.
   *
   * This has to be explicitly ignored by code review as it is as dummy test
   * property.
   *
   * @var mixed
   */
  // @codingStandardsIgnoreStart
  private $privateProperty;
  // @codingStandardsIgnoreEnd

  /**
   * Dummy public property getter.
   */
  public function getPublicProperty() {
    return $this->publicProperty;
  }

  /**
   * Dummy public property setter.
   */
  public function setPublicProperty($public_property) {
    $this->publicProperty = $public_property;
  }

  /**
   * Dummy private property getter.
   */
  public function getPrivateProperty() {
    return $this->privateProperty;
  }

  /**
   * Dummy private property setter.
   */
  public function setPrivateProperty($private_property) {
    $this->privateProperty = $private_property;
  }

}
