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
use Drupal\product\Plugin\views\filter\ApiTypeFilter;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Tests for ApiTypeFilter.
 *
 * @group product
 * @coversDefaultClass \Drupal\product\Plugin\views\filter\ApiTypeFilter
 */
class ApiTypeFilterTest extends UnitTestCase {

  /**
   * The mocked form state.
   *
   * @var \Drupal\Core\Form\FormStateInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $formState;

  /**
   * The filter instance.
   *
   * @var \Drupal\product\Plugin\views\filter\ApiTypeFilter|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $filter;

  /**
   * The mocked entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The mocked field storage.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $fieldStorage;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock form state
    $this->formState = $this->createMock(FormStateInterface::class);

    // Mock entity type manager
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    // Mock field storage
    $this->fieldStorage = $this->createMock(FieldStorageConfig::class);

    // Create the filter mock
    $this->filter = $this->getMockBuilder(ApiTypeFilter::class)
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
    $method->invokeArgs($this->filter, [&$form, $this->formState]);
  }

  /**
   * Set filter options with sensible defaults.
   */
  protected function setFilterOptions(array $overrides): void {
    $defaults = [
      'exposed' => FALSE,
      'expose' => [
        'identifier' => 'api_type_filter',
        'use_operator' => FALSE,
        'operator_id' => '',
        'reduce' => FALSE,
        'multiple' => FALSE,
        'required' => FALSE,
      ],
      'value' => [],
    ];

    $options = array_replace_recursive($defaults, $overrides);
    $reflection = new \ReflectionClass($this->filter);
    $property = $reflection->getProperty('options');
    $property->setValue($this->filter, $options);
  }

  /**
   * Set the filter value property.
   *
   * @param mixed $value
   *   Value to set.
   */
  protected function setFilterValue($value): void {
    $reflection = new \ReflectionClass($this->filter);
    if ($reflection->hasProperty('value')) {
      $property = $reflection->getProperty('value');
      $property->setValue($this->filter, $value);
    }
  }

  /**
   * Set the container with required services for tests.
   */
  protected function setContainer(): void {
    $container = new ContainerBuilder();
    
    // Mock entity storage
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')
      ->with('node.api_protocol')
      ->willReturn($this->fieldStorage);
    
    $this->entityTypeManager->method('getStorage')
      ->with('field_storage_config')
      ->willReturn($storage);
    
    $container->set('entity_type.manager', $this->entityTypeManager);
    
    // Mock string translation
    $stringTranslation = $this->getStringTranslationStub();
    $container->set('string_translation', $stringTranslation);
    
    \Drupal::setContainer($container);
  }

  /**
   * Test getValueOptions returns options from field configuration.
   *
   * @covers ::getValueOptions
   */
  public function testGetValueOptionsFromFieldConfig(): void {
    // Mock field storage settings
    $allowedValues = [
      ['value' => 'rest', 'label' => 'REST'],
      ['value' => 'wsdl', 'label' => 'SOAP'],
      ['value' => 'graphql', 'label' => 'GraphQL'],
      ['value' => 'asyncapi', 'label' => 'AsyncAPI'],
    ];

    $this->fieldStorage->method('getSetting')
      ->with('allowed_values')
      ->willReturn($allowedValues);

    $this->setContainer();

    $options = $this->filter->getValueOptions();

    $this->assertIsArray($options);
    $this->assertCount(4, $options);
    $this->assertArrayHasKey('rest', $options);
    $this->assertArrayHasKey('wsdl', $options);
    $this->assertArrayHasKey('graphql', $options);
    $this->assertArrayHasKey('asyncapi', $options);
    $this->assertEquals('REST', $options['rest']);
    $this->assertEquals('SOAP', $options['wsdl']);
  }

  /**
   * Test getValueOptions returns fallback values when field config fails.
   *
   * @covers ::getValueOptions
   */
  public function testGetValueOptionsFallback(): void {
    // Mock field storage to return null (simulating missing field)
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')
      ->with('node.api_protocol')
      ->willReturn(NULL);
    
    $this->entityTypeManager->method('getStorage')
      ->with('field_storage_config')
      ->willReturn($storage);
    
    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $options = $this->filter->getValueOptions();

    // Should return fallback hardcoded values
    $this->assertIsArray($options);
    $this->assertGreaterThan(0, count($options));
    $this->assertArrayHasKey('rest', $options);
    $this->assertArrayHasKey('wsdl', $options);
    $this->assertArrayHasKey('graphql', $options);
  }

  /**
   * Test valueForm shows filter when exposed and form state is exposed.
   *
   * @covers ::valueForm
   */
  public function testValueFormShowsWhenExposed(): void {
    $this->setFilterOptions(['exposed' => TRUE]);
    $this->setContainer();  // Add container setup
    
    $this->formState->method('get')
      ->with('exposed')
      ->willReturn(TRUE);

    $form = [];
    $this->invokeValueForm($form);

    $this->assertArrayHasKey('value', $form);
  }

  /**
   * Test valueForm does not show filter when not exposed.
   *
   * @covers ::valueForm
   */
  public function testValueFormDoesNotShowWhenNotExposed(): void {
    $this->setFilterOptions(['exposed' => FALSE]);
    
    $this->formState->method('get')
      ->with('exposed')
      ->willReturn(FALSE);

    $form = [];
    $this->invokeValueForm($form);

    $this->assertIsArray($form['value']);
    $this->assertEmpty($form['value']);
  }

  /**
   * Test valueForm does not show when form state is not exposed.
   *
   * @covers ::valueForm
   */
  public function testValueFormDoesNotShowWhenFormStateNotExposed(): void {
    $this->setFilterOptions(['exposed' => TRUE]);
    
    $this->formState->method('get')
      ->with('exposed')
      ->willReturn(FALSE);

    $form = [];
    $this->invokeValueForm($form);

    $this->assertIsArray($form['value']);
    $this->assertEmpty($form['value']);
  }

  /**
   * Test valueForm handles missing exposed option gracefully.
   *
   * @covers ::valueForm
   */
  public function testValueFormHandlesMissingExposedOption(): void {
    // Set filter options WITHOUT 'exposed' key
    $reflection = new \ReflectionClass($this->filter);
    $optionsProperty = $reflection->getProperty('options');
    $optionsProperty->setValue($this->filter, []);

    $this->formState->method('get')
      ->with('exposed')
      ->willReturn(TRUE);

    $form = [];
    $this->invokeValueForm($form);

    // Should not throw error, form['value'] should be empty
    $this->assertIsArray($form['value']);
    $this->assertEmpty($form['value']);
  }

  /**
   * Test that filter options are correctly set when exposed.
   *
   * @covers ::defineOptions
   */
  public function testFilterOptionsWhenExposed(): void {
    $this->setFilterOptions(['exposed' => TRUE]);

    $reflection = new \ReflectionClass($this->filter);
    $property = $reflection->getProperty('options');
    $options = $property->getValue($this->filter);

    $this->assertTrue($options['exposed'] ?? FALSE, 'Filter should be exposed');
  }

  /**
   * Test that filter options are correctly set when not exposed.
   *
   * @covers ::defineOptions
   */
  public function testFilterOptionsWhenNotExposed(): void {
    $this->setFilterOptions(['exposed' => FALSE]);

    $reflection = new \ReflectionClass($this->filter);
    $property = $reflection->getProperty('options');
    $options = $property->getValue($this->filter);

    $this->assertFalse($options['exposed'] ?? NULL, 'Filter should not be exposed');
  }

  /**
   * Test getValueOptions handles invalid field storage data.
   *
   * @covers ::getValueOptions
   */
  public function testGetValueOptionsHandlesInvalidData(): void {
    // Mock field storage with invalid data structure
    $this->fieldStorage->method('getSetting')
      ->with('allowed_values')
      ->willReturn('invalid_string_data');

    $this->setContainer();

    $options = $this->filter->getValueOptions();

    // Should fall back to hardcoded values
    $this->assertIsArray($options);
    $this->assertGreaterThan(0, count($options));
  }

  /**
   * Test getValueOptions handles empty allowed values.
   *
   * @covers ::getValueOptions
   */
  public function testGetValueOptionsHandlesEmptyAllowedValues(): void {
    // Mock field storage with empty allowed values
    $this->fieldStorage->method('getSetting')
      ->with('allowed_values')
      ->willReturn([]);

    $this->setContainer();

    $options = $this->filter->getValueOptions();

    // Should fall back to hardcoded values
    $this->assertIsArray($options);
    $this->assertGreaterThan(0, count($options));
  }

  /**
   * Test getValueOptions caches results.
   *
   * @covers ::getValueOptions
   */
  public function testGetValueOptionsCachesResults(): void {
    $allowedValues = [
      ['value' => 'rest', 'label' => 'REST'],
    ];

    $this->fieldStorage->expects($this->once())
      ->method('getSetting')
      ->with('allowed_values')
      ->willReturn($allowedValues);

    $this->setContainer();

    // Call twice, but getSetting should only be called once
    $options1 = $this->filter->getValueOptions();
    $options2 = $this->filter->getValueOptions();

    $this->assertEquals($options1, $options2);
  }

  /**
   * Test defaultExposeOptions sets sort_values.
   *
   * @covers ::defaultExposeOptions
   */
  public function testDefaultExposeOptions(): void {
    // Set up minimal required options structure first
    $this->setFilterOptions([
      'exposed' => TRUE,
      'expose' => [
        'identifier' => 'api_type_filter',
        'operator_id' => 'api_type_filter_op',
      ],
    ]);
    
    $method = new \ReflectionMethod($this->filter, 'defaultExposeOptions');
    $method->invoke($this->filter);

    $reflection = new \ReflectionClass($this->filter);
    $property = $reflection->getProperty('options');
    $options = $property->getValue($this->filter);

    $this->assertEquals(1, $options['expose']['sort_values'] ?? 0);
  }

}
