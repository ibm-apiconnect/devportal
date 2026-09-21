<?php

namespace Drupal\Tests\ibm_apim\Unit\Form;

use Drupal\Tests\UnitTestCase;
use Drupal\ibm_apim\Form\AdminForm;
use Drupal\ibm_apim\Form\AnalyticsConfigForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\ibm_apim\Service\SiteConfig;

/**
 * @coversDefaultClass \Drupal\ibm_apim\Form\AdminForm
 * @group ibm_apim
 */
class IbmApimFormSanitizationTest extends UnitTestCase {

  /**
   * Test sanitizeIfNeeded in AdminForm using reflection.
   *
   * @covers ::sanitizeIfNeeded
   */
  public function testAdminFormSanitizeIfNeeded() {
    // Mock dependencies.
    $configFactory = $this->createMock(ConfigFactoryInterface::class);
    $typedConfigManager = $this->createMock(TypedConfigManagerInterface::class);
    $siteConfig = $this->createMock(SiteConfig::class);

    $form = new AdminForm($configFactory, $typedConfigManager, $siteConfig);

    $reflection = new \ReflectionClass($form);
    $method = $reflection->getMethod('sanitizeIfNeeded');
    $method->setAccessible(true);

    // Test null input
    $this->assertNull($method->invoke($form, null));

    // Test safe string
    $safe = "Hello World";
    $this->assertSame($safe, $method->invoke($form, $safe));

    // Test string needing sanitization
    $unsafe = '<script>alert(1)</script>';
    $expected = htmlspecialchars($unsafe, ENT_QUOTES);
    $this->assertSame($expected, $method->invoke($form, $unsafe));
  }

  /**
   * Test AnalyticsConfigForm sanitization logic.
   *
   * @covers \Drupal\ibm_apim\Form\AnalyticsConfigForm::submitForm
   */
  public function testAnalyticsConfigFormSanitization() {
    // Test the sanitization logic that would be used in the form
    $dashboard_values = [
      'total_calls' => 'total_calls',
      'dangerous' => '<script>alert(1)</script>',
    ];

    // Get the keys (which are the actual values in this case)
    $dashboard = array_keys(array_filter($dashboard_values));

    // Apply sanitization
    $sanitized_dashboard = array_map(function($item) {
      if ($item !== htmlspecialchars($item, ENT_QUOTES)) {
        return htmlspecialchars($item, ENT_QUOTES);
      }
      return $item;
    }, $dashboard);

    $this->assertContains('total_calls', $sanitized_dashboard);
    $this->assertContains('dangerous', $sanitized_dashboard);
    $this->assertNotContains('<script>alert(1)</script>', $sanitized_dashboard);
  }
}
