<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2026
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\Tests\ibm_apim\Unit\Controller;

use Drupal\ibm_apim\Controller\IbmApimContentController;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\ibm_apim\Controller\IbmApimContentController
 * @group ibm_apim
 */
class IbmApimContentControllerTest extends UnitTestCase {

  /**
   * Test addAttachment() accepts null description without TypeError.
   *
   * Verifies fix for PHP 8.1+ TypeError when attachments have no description.
   *
   * @covers ::addAttachment
   */
  public function testAddAttachmentAcceptsNullDescription(): void {
    // Test that the method signature accepts null without throwing TypeError
    $reflection = new \ReflectionMethod(IbmApimContentController::class, 'addAttachment');
    $params = $reflection->getParameters();
    
    // Find the $description parameter (3rd parameter, index 2)
    $descriptionParam = $params[2];
    
    // Verify it's nullable
    $this->assertTrue($descriptionParam->getType()->allowsNull(), 
      'The $description parameter should allow null values');
    
    // Verify it has a default value
    $this->assertTrue($descriptionParam->isDefaultValueAvailable(),
      'The $description parameter should have a default value');
  }
}
