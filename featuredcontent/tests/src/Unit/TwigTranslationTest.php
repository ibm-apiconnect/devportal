<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2025
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\Tests\featuredcontent\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\ibm_apim\Unit\utils\TwigTranslationCounter;

/**
 * Tests that the number of translations in Twig templates hasn't changed.
 *
 * @group featuredcontent
 */
class TwigTranslationTest extends UnitTestCase {

  /**
   * The expected number of translations in each template file.
   *
   * @var array
   */
  protected $expectedTranslations = [];

  /**
   * The total number of expected translations.
   *
   * @var int
   */
  protected $expectedTotal = 0;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    
    // Count the current translations to establish a baseline
    $moduleDir = dirname(__DIR__, 3);
    $templatesDir = $moduleDir . '/templates';
    
    $result = TwigTranslationCounter::countTranslationsInDirectory($templatesDir);
    $this->expectedTranslations = $result['files'];
    $this->expectedTotal = $result['total'];
  }

  /**
   * Tests that the number of translations in Twig templates hasn't changed.
   */
  public function testTranslationCount(): void {
    $moduleDir = dirname(__DIR__, 3);
    $templatesDir = $moduleDir . '/templates';
    
    $result = TwigTranslationCounter::countTranslationsInDirectory($templatesDir);
    $actualTranslations = $result['files'];
    $actualTotal = $result['total'];
    
    // Test the total count
    $this->assertEquals(
      $this->expectedTotal,
      $actualTotal,
      sprintf(
        'The total number of translations has changed. Expected: %d, Actual: %d',
        $this->expectedTotal,
        $actualTotal
      )
    );
    
    // Test each file's count
    foreach ($this->expectedTranslations as $filename => $expectedCount) {
      $this->assertArrayHasKey(
        $filename,
        $actualTranslations,
        sprintf('Template file %s is missing', $filename)
      );
      
      $this->assertEquals(
        $expectedCount,
        $actualTranslations[$filename],
        sprintf(
          'The number of translations in %s has changed. Expected: %d, Actual: %d',
          $filename,
          $expectedCount,
          $actualTranslations[$filename]
        )
      );
    }
    
    // Check for new files
    foreach (array_keys($actualTranslations) as $filename) {
      $this->assertArrayHasKey(
        $filename,
        $this->expectedTranslations,
        sprintf('New template file %s was added. Update the test expectations.', $filename)
      );
    }
  }
}
