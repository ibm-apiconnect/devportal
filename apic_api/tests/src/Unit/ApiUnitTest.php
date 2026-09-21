<?php


namespace Drupal\Tests\apic_api\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * api tests.
 *
 * @group apic_api
 */
class ApiUnitTest extends UnitTestCase {

  public function testTrue(): void {
    self::assertEquals(TRUE, TRUE);
  }

    /**
   * Tests metadata setter/getter for API entity.
   */
  public function testApiMetadataStoredAndRetrieved(): void {
    // Build a basic mock API entity using your existing test builder.
    $api = (new \Drupal\Tests\ibm_apim\Unit\mocks\MockApiNodeBuilder($this))
      ->setApicUrl('mock/url')
      ->build();

    $metadata = [
      'visibility' => 'external',
      'category' => 'banking',
    ];

    $api->setMetadata($metadata);

    $this->assertEquals($metadata, $api->getMetadata());
    $this->assertEquals('external', $api->getMetadata()['visibility']);
  }

  /**
   * Tests that metadata defaults to empty array when not set.
   */
  public function testApiMetadataDefaultsToEmptyArray(): void {
    $api = (new \Drupal\Tests\ibm_apim\Unit\mocks\MockApiNodeBuilder($this))
      ->build();

    $this->assertEquals([], $api->getMetadata());
  }
}
