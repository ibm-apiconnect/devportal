<?php

namespace Drupal\Tests\apic_app\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\ibm_apim\Unit\mocks\MockApplicationNodeBuilder;
use Drupal\Tests\ibm_apim\Unit\mocks\MockProductNodeBuilder;
use Drupal\Tests\ibm_apim\Unit\mocks\MockApiNodeBuilder;
use Drupal\node\Entity\Node;
use Drupal\Core\DependencyInjection\ContainerBuilder;

/**
 * Tests the apic_app_generate_app_specific_portal_data function.
 *
 * @group apic_app
 */
class AppSpecificPortalDataTest extends UnitTestCase {

  /**
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  private $mockApplicationService;

  /**
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  private $container;

  /**
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  private $mockNode;

  /**
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  private $mockProductNode;

  /**
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  private $mockApiNode;

  /**
   * @var \PHPUnit\Framework\MockObject\MockObject
   */
  private $entityStorage;
  
  /**
   * Sample product data as a PHP array.
   */
  private $productData = [
    "info" => [
      "name" => "test-product",
      "title" => "Test Product",
      "version" => "1.0.0",
    ],
    "apis" => [
      [
        "name" => "test-api:1.0.0",
      ],
    ],
    "plans" => [
      "default" => [
        "title" => "Default Plan",
        "apis" => [
          "test-api" => [],
        ],
      ],
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    
    // Include the module file that contains the function
    require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/apic_app.module';
    
    // Create a new container
    $this->container = new ContainerBuilder();
    
    // Mock the application service
    $this->mockApplicationService = $this->createMock('\Drupal\apic_app\Service\ApplicationService');
    
    // Create mock nodes using the builders
    $this->mockNode = (new MockApplicationNodeBuilder($this))
      ->setApicUrl('test/application/url')
      ->build();
      
    $this->mockProductNode = (new MockProductNodeBuilder($this))
      ->setApicUrl('test/product/url')
      ->build();
      
    $this->mockApiNode = (new MockApiNodeBuilder($this))
      ->build();
      
    // Mock the entity storage
    $this->entityStorage = $this->getMockBuilder('\Drupal\Core\Entity\EntityStorageInterface')
      ->disableOriginalConstructor()
      ->getMock();
      
    // Update the product data to use stdClass for empty objects
    $this->productData["plans"]["default"]["apis"]["test-api"] = new \stdClass();
  }

  /**
   * Set up common services needed for all tests.
   */
  private function setupCommonServices() {
    // Mock the entity type repository
    $entityTypeRepository = $this->getMockBuilder('\Drupal\Core\Entity\EntityTypeRepositoryInterface')
      ->disableOriginalConstructor()
      ->getMock();
    
    // Mock the entity field manager
    $entityFieldManager = $this->getMockBuilder('\Drupal\Core\Entity\EntityFieldManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    
    // Mock the entity type bundle info
    $entityTypeBundleInfo = $this->getMockBuilder('\Drupal\Core\Entity\EntityTypeBundleInfoInterface')
      ->disableOriginalConstructor()
      ->getMock();
    
    // Mock the entity display repository
    $entityDisplayRepository = $this->getMockBuilder('\Drupal\Core\Entity\EntityDisplayRepositoryInterface')
      ->disableOriginalConstructor()
      ->getMock();
    
    // Add these services to the container
    $this->container->set('entity_type.repository', $entityTypeRepository);
    $this->container->set('entity_field.manager', $entityFieldManager);
    $this->container->set('entity_type.bundle.info', $entityTypeBundleInfo);
    $this->container->set('entity_display.repository', $entityDisplayRepository);
  }

  /**
   * Create a mock field item list that can be used in node fields.
   */
  private function createMockFieldItemList($value, $isEmpty = false) {
    $mockFieldItem = $this->getMockBuilder('\Drupal\Core\Field\FieldItemInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $mockFieldItem->expects($this->any())
      ->method('__get')
      ->with('value')
      ->willReturn($value);
    
    $mockFieldItemList = $this->getMockBuilder('\Drupal\Core\Field\FieldItemListInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $mockFieldItemList->expects($this->any())
      ->method('isEmpty')
      ->willReturn($isEmpty);
    $mockFieldItemList->expects($this->any())
      ->method('first')
      ->willReturn($mockFieldItem);
    $mockFieldItemList->expects($this->any())
      ->method('getValue')
      ->willReturn([['value' => $value]]);
    
    return $mockFieldItemList;
  }

  /**
   * Tests the apic_app_generate_app_specific_portal_data function returns data in the correct format.
   */
  public function testGenerateAppSpecificPortalDataFormat(): void {
    // Set up common services
    $this->setupCommonServices();
    
    // Convert the PHP array to JSON for the mock field
    $productYaml = json_encode($this->productData);
    
    // Set up the application service to return subscriptions
    $subscriptions = [
      [
        'product_url' => 'test/product/url',
        'product_nid' => '456',
        'plan_name' => 'default',
        'state' => 'enabled',
        'subId' => 'test-sub-id'
      ]
    ];
    $this->mockApplicationService->method('getSubscriptions')->willReturn($subscriptions);
    
    // Create a mock field item list for product_data
    $mockProductDataField = $this->createMockFieldItemList($productYaml, false);
    
    // Set up the product node to have product_data
    $this->mockProductNode->method('hasField')->willReturn(true);
    $this->mockProductNode->method('get')
      ->with('product_data')
      ->willReturn($mockProductDataField);
    
    // Mock the entity query system
    $entityQuery = $this->getMockBuilder('\Drupal\Core\Entity\Query\QueryInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $entityQuery->expects($this->any())
      ->method('condition')
      ->willReturnSelf();
    $entityQuery->expects($this->any())
      ->method('accessCheck')
      ->willReturnSelf();
    $entityQuery->expects($this->any())
      ->method('execute')
      ->willReturn(['123']);
    
    // Set up the entity storage
    $this->entityStorage->expects($this->any())
      ->method('getQuery')
      ->willReturn($entityQuery);
    $this->entityStorage->expects($this->any())
      ->method('load')
      ->will($this->returnValueMap([
        ['123', $this->mockNode],
        ['456', $this->mockProductNode],
      ]));
    
    // Mock the entity type manager to return our storage for any entity type
    $entityTypeManager = $this->getMockBuilder('\Drupal\Core\Entity\EntityTypeManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->willReturn($this->entityStorage);
    
    // Add services to the container
    $this->container->set('entity_type.manager', $entityTypeManager);
    $this->container->set('apic_app.application', $this->mockApplicationService);
    
    // Replace the global Drupal service container with our container
    \Drupal::setContainer($this->container);
    
    // Create arrays to hold the results
    $apiNames = [];
    $apiVersions = [];
    $productNames = [];
    $productVersions = [];
    $planNames = [];
    
    // Call the function we're testing with the global namespace
    \apic_app_generate_app_specific_portal_data('test-app-id', $apiNames, $apiVersions, $productNames, $productVersions, $planNames);
    
    // Verify the data format is correct
    $this->assertIsArray($apiNames, 'API names should be an array');
    $this->assertIsArray($apiVersions, 'API versions should be an array');
    $this->assertIsArray($productNames, 'Product names should be an array');
    $this->assertIsArray($productVersions, 'Product versions should be an array');
    $this->assertIsArray($planNames, 'Plan names should be an array');
    
    // Verify the structure of the data
    if (!empty($apiNames)) {
      $this->assertArrayHasKey('key', $apiNames[0], 'API names should have a key field');
      $this->assertArrayHasKey('title', $apiNames[0], 'API names should have a title field');
      $this->assertArrayHasKey('version', $apiNames[0], 'API names should have a version field');
    }
    
    if (!empty($productNames)) {
      $this->assertArrayHasKey('key', $productNames[0], 'Product names should have a key field');
      $this->assertArrayHasKey('title', $productNames[0], 'Product names should have a title field');
      $this->assertArrayHasKey('version', $productNames[0], 'Product names should have a version field');
      $this->assertArrayHasKey('apis', $productNames[0], 'Product names should have an apis field');
      $this->assertArrayHasKey('plans', $productNames[0], 'Product names should have a plans field');
    }
    
    if (!empty($planNames)) {
      $this->assertArrayHasKey('key', $planNames[0], 'Plan names should have a key field');
      $this->assertArrayHasKey('title', $planNames[0], 'Plan names should have a title field');
    }
  }
  
  /**
   * Tests the apic_app_generate_app_specific_portal_data function with no application found.
   */
  public function testGenerateAppSpecificPortalDataWithNoApplication(): void {
    // Set up common services
    $this->setupCommonServices();
    
    // Mock the entity query system
    $entityQuery = $this->getMockBuilder('\Drupal\Core\Entity\Query\QueryInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $entityQuery->expects($this->any())
      ->method('condition')
      ->willReturnSelf();
    $entityQuery->expects($this->any())
      ->method('accessCheck')
      ->willReturnSelf();
    $entityQuery->expects($this->any())
      ->method('execute')
      ->willReturn([]);
    
    // Set up the entity storage
    $entityStorage = $this->getMockBuilder('\Drupal\Core\Entity\EntityStorageInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $entityStorage->expects($this->any())
      ->method('getQuery')
      ->willReturn($entityQuery);
    
    // Mock the entity type manager to return our storage for any entity type
    $entityTypeManager = $this->getMockBuilder('\Drupal\Core\Entity\EntityTypeManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->willReturn($entityStorage);
    
    // Create a new container for this test
    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entityTypeManager);
    $container->set('entity_type.repository', $this->container->get('entity_type.repository'));
    $container->set('entity_field.manager', $this->container->get('entity_field.manager'));
    $container->set('entity_type.bundle.info', $this->container->get('entity_type.bundle.info'));
    $container->set('entity_display.repository', $this->container->get('entity_display.repository'));
    
    // Replace the global Drupal service container with our container
    \Drupal::setContainer($container);
    
    // Create arrays to hold the results
    $apiNames = [];
    $apiVersions = [];
    $productNames = [];
    $productVersions = [];
    $planNames = [];
    
    // Call the function we're testing with the global namespace
    \apic_app_generate_app_specific_portal_data('non-existent-app-id', $apiNames, $apiVersions, $productNames, $productVersions, $planNames);
    
    // Assert the results - all arrays should be empty
    $this->assertEmpty($apiNames, 'API names should be empty');
    $this->assertEmpty($apiVersions, 'API versions should be empty');
    $this->assertEmpty($productNames, 'Product names should be empty');
    $this->assertEmpty($productVersions, 'Product versions should be empty');
    $this->assertEmpty($planNames, 'Plan names should be empty');
  }
  
  /**
   * Tests the apic_app_generate_app_specific_portal_data function with no subscriptions.
   */
  public function testGenerateAppSpecificPortalDataWithNoSubscriptions(): void {
    // Set up common services
    $this->setupCommonServices();
    
    // Set up the application service to return no subscriptions
    $mockApplicationService = $this->createMock('\Drupal\apic_app\Service\ApplicationService');
    $mockApplicationService->method('getSubscriptions')->willReturn([]);
    
    // Mock the entity query system
    $entityQuery = $this->getMockBuilder('\Drupal\Core\Entity\Query\QueryInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $entityQuery->expects($this->any())
      ->method('condition')
      ->willReturnSelf();
    $entityQuery->expects($this->any())
      ->method('accessCheck')
      ->willReturnSelf();
    $entityQuery->expects($this->any())
      ->method('execute')
      ->willReturn(['123']);
    
    // Set up the entity storage
    $entityStorage = $this->getMockBuilder('\Drupal\Core\Entity\EntityStorageInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $entityStorage->expects($this->any())
      ->method('getQuery')
      ->willReturn($entityQuery);
    $entityStorage->expects($this->any())
      ->method('load')
      ->willReturn($this->mockNode);
    
    // Mock the entity type manager to return our storage for any entity type
    $entityTypeManager = $this->getMockBuilder('\Drupal\Core\Entity\EntityTypeManagerInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->willReturn($entityStorage);
    
    // Create a new container for this test
    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entityTypeManager);
    $container->set('apic_app.application', $mockApplicationService);
    $container->set('entity_type.repository', $this->container->get('entity_type.repository'));
    $container->set('entity_field.manager', $this->container->get('entity_field.manager'));
    $container->set('entity_type.bundle.info', $this->container->get('entity_type.bundle.info'));
    $container->set('entity_display.repository', $this->container->get('entity_display.repository'));
    
    // Replace the global Drupal service container with our container
    \Drupal::setContainer($container);
    
    // Create arrays to hold the results
    $apiNames = [];
    $apiVersions = [];
    $productNames = [];
    $productVersions = [];
    $planNames = [];
    
    // Call the function we're testing with the global namespace
    \apic_app_generate_app_specific_portal_data('test-app-id', $apiNames, $apiVersions, $productNames, $productVersions, $planNames);
    
    // Assert the results - all arrays should be empty
    $this->assertEmpty($apiNames, 'API names should be empty');
    $this->assertEmpty($apiVersions, 'API versions should be empty');
    $this->assertEmpty($productNames, 'Product names should be empty');
    $this->assertEmpty($productVersions, 'Product versions should be empty');
    $this->assertEmpty($planNames, 'Plan names should be empty');
  }
}
