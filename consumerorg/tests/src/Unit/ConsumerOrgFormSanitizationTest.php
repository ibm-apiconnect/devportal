<?php

namespace Drupal\Tests\consumerorg\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Tests the form sanitization implementation for consumerorg module.
 *
 * @group consumerorg
 */
class ConsumerOrgFormSanitizationTest extends UnitTestCase {

  /**
   * Test data with potentially malicious input.
   *
   * @var array
   */
  protected $testData;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    
    // Set up test data with potentially malicious input
    $this->testData = [
      'organization_title' => 'Test Org <script>alert("XSS")</script>',
      'email' => 'test"@example.com<script>alert("XSS")</script>',
      'payment_title' => 'Payment <script>alert("XSS")</script>',
    ];
  }

  /**
   * Tests string sanitization using htmlspecialchars.
   */
  public function testStringSanitization(): void {
    $input = $this->testData['organization_title'];
    $sanitized = htmlspecialchars($input, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    
    // Verify script tags are properly escaped
    $this->assertStringContainsString('<script>', $sanitized);
    $this->assertStringContainsString('"XSS"', $sanitized);
    $this->assertStringContainsString('</script>', $sanitized);
    
    // Verify the original text content is preserved
    $this->assertStringContainsString('Test Org', $sanitized);
  }

  /**
   * Tests email sanitization.
   */
  public function testEmailSanitization(): void {
    $input = $this->testData['email'];
    $sanitized = filter_var($input, FILTER_SANITIZE_EMAIL);
    
    // Verify script tags and quotes are removed
    $this->assertStringNotContainsString('<script>', $sanitized);
    $this->assertStringNotContainsString('</script>', $sanitized);
    $this->assertStringNotContainsString('"', $sanitized);
    
    // Verify valid email parts are preserved
    $this->assertStringContainsString('test@example.com', $sanitized);
  }

  /**
   * Tests payment title sanitization.
   */
  public function testPaymentTitleSanitization(): void {
    $input = $this->testData['payment_title'];
    $sanitized = htmlspecialchars($input, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    
    // Verify script tags are properly escaped
    $this->assertStringContainsString('<script>', $sanitized);
    $this->assertStringContainsString('"XSS"', $sanitized);
    $this->assertStringContainsString('</script>', $sanitized);
    
    // Verify the original text content is preserved
    $this->assertStringContainsString('Payment', $sanitized);
  }

  /**
   * Tests URL pattern validation.
   */
  public function testUrlPatternValidation(): void {
    $pattern = '/(https?|ftp|ftps|file):\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[-A-Z0-9+&@#\/%=~_|]|www\.[A-Z0-9.-]+\.[A-Z]{2,4}/i';
    
    // Test with URL in title
    $titleWithUrl = 'Title with http://example.com embedded';
    $this->assertEquals(1, preg_match($pattern, $titleWithUrl));
    
    // Test with no URL
    $titleWithoutUrl = 'Title with no URL';
    $this->assertEquals(0, preg_match($pattern, $titleWithoutUrl));
  }

}
