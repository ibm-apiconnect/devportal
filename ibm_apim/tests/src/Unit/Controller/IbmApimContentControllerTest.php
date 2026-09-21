<?php


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
