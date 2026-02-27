<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2024
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

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
