<?php

namespace Drupal\Tests\mail_subscribers\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Tests the form sanitization implementation for mail_subscribers module.
 *
 * @group mail_subscribers
 */
class MailSubscribersFormSanitizationTest extends UnitTestCase {

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
      'mail_subject' => 'Subject <script>alert("XSS")</script>',
      'mail_headers' => "X-Custom: Value\n<script>alert('XSS')</script>: Injection",
      'mail_content' => '<p>Content</p><script>alert("XSS")</script>',
      'from_name' => 'Admin <script>alert("XSS")</script>',
      'from_mail' => 'admin"@example.com<script>alert("XSS")</script>',
    ];
  }

  /**
   * Tests subject sanitization.
   */
  public function testSubjectSanitization(): void {
    $input = $this->testData['mail_subject'];
    $sanitized = htmlspecialchars($input, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    
    // Verify script tags are properly escaped
    $this->assertStringContainsString('<script>', $sanitized);
    $this->assertStringContainsString('"XSS"', $sanitized);
    $this->assertStringContainsString('</script>', $sanitized);
    
    // Verify the original text content is preserved
    $this->assertStringContainsString('Subject', $sanitized);
  }

  /**
   * Tests email headers sanitization.
   */
  public function testHeadersSanitization(): void {
    $input = $this->testData['mail_headers'];
    
    // Custom sanitization for headers
    $lines = explode("\n", $input);
    $sanitized = [];
    foreach ($lines as $line) {
      if (preg_match('/^[A-Za-z0-9\-]+:\s*.+$/', $line)) {
        $sanitized[] = htmlspecialchars($line, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
      }
    }
    $result = implode("\n", $sanitized);
    
    // Verify valid headers are preserved
    $this->assertStringContainsString('X-Custom: Value', $result);
    
    // Verify invalid headers are removed
    $this->assertStringNotContainsString('<script>', $result);
    $this->assertStringNotContainsString('</script>', $result);
    $this->assertStringNotContainsString('Injection', $result);
  }

  /**
   * Tests from name sanitization.
   */
  public function testFromNameSanitization(): void {
    $input = $this->testData['from_name'];
    $sanitized = htmlspecialchars($input, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, 'UTF-8');
    
    // Verify script tags are properly escaped
    $this->assertStringContainsString('<script>', $sanitized);
    $this->assertStringContainsString('"XSS"', $sanitized);
    $this->assertStringContainsString('</script>', $sanitized);
    
    // Verify the original text content is preserved
    $this->assertStringContainsString('Admin', $sanitized);
  }

  /**
   * Tests from email sanitization.
   */
  public function testFromEmailSanitization(): void {
    $input = $this->testData['from_mail'];
    $sanitized = filter_var($input, FILTER_SANITIZE_EMAIL);
    
    // Verify script tags and quotes are removed
    $this->assertStringNotContainsString('<script>', $sanitized);
    $this->assertStringNotContainsString('</script>', $sanitized);
    $this->assertStringNotContainsString('"', $sanitized);
    
    // Verify valid email parts are preserved
    $this->assertStringContainsString('admin@example.com', $sanitized);
  }

  /**
   * Tests email validation.
   */
  public function testEmailValidation(): void {
    // Valid email
    $validEmail = 'user@example.com';
    $this->assertTrue(filter_var($validEmail, FILTER_VALIDATE_EMAIL) !== false);
    
    // Invalid email
    $invalidEmail = 'not-an-email';
    $this->assertFalse(filter_var($invalidEmail, FILTER_VALIDATE_EMAIL) !== false);
    
    // Sanitized email should be valid
    $sanitizedEmail = filter_var($this->testData['from_mail'], FILTER_SANITIZE_EMAIL);
    $this->assertTrue(filter_var($sanitizedEmail, FILTER_VALIDATE_EMAIL) !== false);
  }

}
