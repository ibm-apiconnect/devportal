<?php

namespace Drupal\Tests\ibm_apim\Unit\External;

use Drupal\ibm_apim\External\SerializationUtility;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the SerializationUtility class.
 *
 * @group ibm_apim
 */
class SerializationUtilityTest extends UnitTestCase {

  public function testEncodeData() {
    $array = ['key' => 'value', 'nested' => ['foo' => 'bar']];
    $encoded = SerializationUtility::encodeData($array);
    $this->assertEquals('{"key":"value","nested":{"foo":"bar"}}', $encoded);
    $this->assertEquals('null', SerializationUtility::encodeData(NULL));
  }

  public function testDecodeDataWithJson() {
    $json = '{"key":"value","nested":{"foo":"bar"}}';
    $decoded = SerializationUtility::decodeData($json);
    $this->assertEquals(['key' => 'value', 'nested' => ['foo' => 'bar']], $decoded);
    $this->assertNull(SerializationUtility::decodeData(NULL));
    $this->assertNull(SerializationUtility::decodeData(''));
  }

  public function testDecodeDataWithSerialized() {
    $serialized = 'a:2:{s:3:"key";s:5:"value";s:6:"nested";a:1:{s:3:"foo";s:3:"bar";}}';
    $decoded = SerializationUtility::decodeData($serialized);
    $this->assertEquals(['key' => 'value', 'nested' => ['foo' => 'bar']], $decoded);
  }

  public function testIsSerializedData() {
    $serialized = 'a:2:{s:3:"key";s:5:"value";s:6:"nested";a:1:{s:3:"foo";s:3:"bar";}}';
    $this->assertTrue(SerializationUtility::isSerializedData($serialized));
    
    $serialized = 's:5:"hello";';
    $this->assertTrue(SerializationUtility::isSerializedData($serialized));
    
    $serialized = 'i:42;';
    $this->assertTrue(SerializationUtility::isSerializedData($serialized));
    
    $serialized = 'b:1;';
    $this->assertTrue(SerializationUtility::isSerializedData($serialized));
    
    $json = '{"key":"value"}';
    $this->assertFalse(SerializationUtility::isSerializedData($json));
    
    $this->assertFalse(SerializationUtility::isSerializedData('hello world'));
    
    $this->assertFalse(SerializationUtility::isSerializedData(123));
  }

  public function testMigrateToJson() {
    $serialized = 'a:2:{s:3:"key";s:5:"value";s:6:"nested";a:1:{s:3:"foo";s:3:"bar";}}';
    $json = SerializationUtility::migrateToJson($serialized);
    $this->assertEquals('{"key":"value","nested":{"foo":"bar"}}', $json);
    
    $originalJson = '{"key":"value","nested":{"foo":"bar"}}';
    $json = SerializationUtility::migrateToJson($originalJson);
    $this->assertEquals($originalJson, $json);
  }
}
