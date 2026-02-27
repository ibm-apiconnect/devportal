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

namespace Drupal\Tests\product\Unit;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\product\Plugin\views\filter\SubscriptionStatusFilter;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Tests for SubscriptionStatusFilter valueForm method.
 *
 * @group product
 * @coversDefaultClass \Drupal\product\Plugin\views\filter\SubscriptionStatusFilter
 */
class SubscriptionStatusFilterTest extends UnitTestCase {

  /**
   * The mocked current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $currentUser;

  /**
   * The mocked form state.
   *
   * @var \Drupal\Core\Form\FormStateInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $formState;

  /**
   * The filter instance.
   *
   * @var \Drupal\product\Plugin\views\filter\SubscriptionStatusFilter|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $filter;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the current user service
    $this->currentUser = $this->createMock(AccountProxyInterface::class);

    // Mock form state
    $this->formState = $this->createMock(FormStateInterface::class);

    // Create the filter mock (constructor disabled to avoid plugin setup)
    $this->filter = $this->getMockBuilder(SubscriptionStatusFilter::class)
      ->disableOriginalConstructor()
      ->onlyMethods([])
      ->getMock();

    $this->setFilterOptions([]);
    $this->setFilterValue([]);
  }

  /**
   * Invoke protected valueForm for testing.
   *
   * @param array $form
   */
  protected function invokeValueForm(array &$form): void {
    $method = new \ReflectionMethod($this->filter, 'valueForm');
    $method->setAccessible(TRUE);
    $method->invokeArgs($this->filter, [&$form, $this->formState]);
  }

  /**
   * Set filter options with sensible defaults for the parent implementation.
   */
  protected function setFilterOptions(array $overrides): void {
    $defaults = [
      'exposed' => FALSE,
      'expose' => [
        'identifier' => 'subscription_status',
        'use_operator' => FALSE,
        'operator_id' => '',
        'reduce' => FALSE,
        'multiple' => FALSE,
        'required' => FALSE,
        'limit' => FALSE,
      ],
      'value' => [],
    ];

    $options = array_replace_recursive($defaults, $overrides);
    $reflection = new \ReflectionClass($this->filter);
    $property = $reflection->getProperty('options');
    $property->setAccessible(TRUE);
    $property->setValue($this->filter, $options);
  }

  /**
   * Set the filter value property if present.
   *
   * @param mixed $value
   *   Value to set.
   */
  protected function setFilterValue($value): void {
    $reflection = new \ReflectionClass($this->filter);
    if ($reflection->hasProperty('value')) {
      $property = $reflection->getProperty('value');
      $property->setAccessible(TRUE);
      $property->setValue($this->filter, $value);
    }
  }

  /**
   * Set the container with required services for tests.
   */
  protected function setContainer(): void {
    $container = new ContainerBuilder();
    $container->set('current_user', $this->currentUser);
    $container->set('string_translation', $this->createMock('\Drupal\Core\StringTranslation\TranslationInterface'));
    \Drupal::setContainer($container);
  }

  /**
   * Test that anonymous users never see the filter.
   *
   * @covers ::valueForm
   */
  public function testAnonymousUserDoesNotSeeFilter(): void {
    // Mock anonymous user
    $this->currentUser->method('isAnonymous')->willReturn(TRUE);
    $this->currentUser->method('id')->willReturn(0);

    $this->setContainer();

    // Expect form_state to be set to not exposed
    $this->formState->expects($this->once())
      ->method('set')
      ->with('exposed', FALSE);

    $this->formState->method('get')
      ->with('exposed')
      ->willReturn(FALSE);

    $form = [];
    $this->invokeValueForm($form);

    // Verify form['value'] is set to empty array
    $this->assertIsArray($form['value']);
    $this->assertEmpty($form['value']);
  }

  /**
   * Test that admin user (uid=1) never sees the filter.
   *
   * @covers ::valueForm
   */
  public function testAdminUserDoesNotSeeFilter(): void {
    // Mock admin user (uid=1)
    $this->currentUser->method('isAnonymous')->willReturn(FALSE);
    $this->currentUser->method('id')->willReturn(1);

    $this->setContainer();

    // Expect form_state to be set to not exposed
    $this->formState->expects($this->once())
      ->method('set')
      ->with('exposed', FALSE);

    $this->formState->method('get')
      ->with('exposed')
      ->willReturn(FALSE);

    $form = [];
    $this->invokeValueForm($form);

    $this->assertIsArray($form['value']);
    $this->assertEmpty($form['value']);
  }

  /**
   * Test authenticated user sees filter when admin enables it.
   *
   * @covers ::valueForm
   */
  public function testAuthenticatedUserSeesFilterWhenEnabled(): void {
    // Mock authenticated non-admin user
    $this->currentUser->method('isAnonymous')->willReturn(FALSE);
    $this->currentUser->method('id')->willReturn(123);

    $this->setContainer();

    // Set filter options to exposed = TRUE (admin enabled it)
    $this->setFilterOptions(['exposed' => TRUE]);

    // Form state returns TRUE for exposed
    $this->formState->method('get')->willReturn(TRUE);

    // Form state should NOT be set to FALSE
    $this->formState->expects($this->never())
      ->method('set')
      ->with('exposed', FALSE);

    $form = [];
    $this->invokeValueForm($form);

    $this->assertArrayHasKey('value', $form);
  }

  /**
   * Test authenticated user does NOT see filter when admin disables it.
   *
   * @covers ::valueForm
   */
  public function testAuthenticatedUserDoesNotSeeFilterWhenDisabled(): void {
    // Mock authenticated non-admin user
    $this->currentUser->method('isAnonymous')->willReturn(FALSE);
    $this->currentUser->method('id')->willReturn(123);

    $this->setContainer();

    // Set filter options to exposed = FALSE (admin disabled it)
    $this->setFilterOptions(['exposed' => FALSE]);

    // Form state returns FALSE for exposed
    $this->formState->method('get')->willReturn(FALSE);

    $form = [];
    $this->invokeValueForm($form);

    // Verify form['value'] remains empty array (filter not shown)
    $this->assertIsArray($form['value']);
    $this->assertEmpty($form['value']);
  }

  /**
   * Test authenticated user does NOT see filter when form_state is not exposed.
   *
   * @covers ::valueForm
   */
  public function testAuthenticatedUserDoesNotSeeFilterWhenFormStateNotExposed(): void {
    // Mock authenticated non-admin user
    $this->currentUser->method('isAnonymous')->willReturn(FALSE);
    $this->currentUser->method('id')->willReturn(123);

    $this->setContainer();

    // Set filter options to exposed = TRUE (admin enabled it)
    $this->setFilterOptions(['exposed' => TRUE]);

    // Form state returns FALSE for exposed
    $this->formState->method('get')->willReturn(FALSE);

    $form = [];
    $this->invokeValueForm($form);

    // Verify form['value'] remains empty array (filter not shown)
    $this->assertIsArray($form['value']);
    $this->assertEmpty($form['value']);
  }

  /**
   * Test getValueOptions returns correct options.
   *
   * @covers ::getValueOptions
   */
  public function testGetValueOptions(): void {
    $options = $this->filter->getValueOptions();

    $this->assertIsArray($options);
    $this->assertCount(2, $options);
    $this->assertEquals('Subscribed To', $options[0]);
    $this->assertEquals('Not Subscribed To', $options[1]);
  }

  /**
   * Test operatorOptions returns empty array.
   *
   * @covers ::operatorOptions
   */
  public function testOperatorOptions(): void {
    $options = $this->filter->operatorOptions();

    $this->assertIsArray($options);
    $this->assertEmpty($options);
  }

  /**
   * Test authenticated user handles missing 'exposed' option gracefully.
   *
   * @covers ::valueForm
   */
  public function testAuthenticatedUserHandlesMissingExposedOption(): void {
    // Mock authenticated non-admin user
    $this->currentUser->method('isAnonymous')->willReturn(FALSE);
    $this->currentUser->method('id')->willReturn(123);

    $container = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');
    $container->method('get')
      ->with('current_user')
      ->willReturn($this->currentUser);
    \Drupal::setContainer($container);

    // Set filter options WITHOUT 'exposed' key (simulating uninitialized state)
    $reflection = new \ReflectionClass($this->filter);
    $optionsProperty = $reflection->getProperty('options');
    $optionsProperty->setAccessible(TRUE);
    $optionsProperty->setValue($this->filter, []); // Empty options array

    // Form state returns TRUE for exposed
    $this->formState->method('get')
      ->with('exposed')
      ->willReturn(TRUE);

    $form = [];
    // This should not throw an error even though 'exposed' key is missing
    $this->invokeValueForm($form);

    // Verify form['value'] remains empty array (filter not shown due to missing config)
    $this->assertIsArray($form['value']);
    $this->assertEmpty($form['value']);
  }

  /**
   * Test acceptExposedInput returns TRUE for anonymous users even when exposed.
   *
   * @covers ::acceptExposedInput
   */
  public function testAcceptExposedInputAnonymousUser(): void {
    $this->currentUser->method('isAnonymous')->willReturn(TRUE);
    $this->currentUser->method('id')->willReturn(0);

    $this->setContainer();
    $this->setFilterOptions(['exposed' => TRUE, 'expose' => ['identifier' => 'subscription_status_filter']]);

    $this->assertTrue($this->filter->acceptExposedInput([]));
  }

  /**
   * Test acceptExposedInput skips when identifier missing in input.
   *
   * @covers ::acceptExposedInput
   */
  public function testAcceptExposedInputMissingIdentifier(): void {
    $this->currentUser->method('isAnonymous')->willReturn(FALSE);
    $this->currentUser->method('id')->willReturn(123);

    $this->setContainer();
    $this->setFilterOptions(['exposed' => TRUE, 'expose' => ['identifier' => 'subscription_status_filter']]);

    $this->assertTrue($this->filter->acceptExposedInput([]));
  }

}
