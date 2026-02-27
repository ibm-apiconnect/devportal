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

namespace Drupal\Tests\ibm_csp_extension\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\ibm_csp_extension\Service\ApiEndpointService;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Unit tests for the ApiEndpointService class.
 *
 * @group ibm_csp_extension
 * @coversDefaultClass \Drupal\ibm_csp_extension\Service\ApiEndpointService
 */
class ApiEndpointServiceTest extends UnitTestCase {

  use ProphecyTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityTypeManager;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityStorage;

  /**
   * The query interface.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $query;

  /**
   * The API endpoint service.
   *
   * @var \Drupal\ibm_csp_extension\Service\ApiEndpointService
   */
  protected $apiEndpointService;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->query = $this->prophesize(QueryInterface::class);
    $this->entityStorage = $this->prophesize(EntityStorageInterface::class);
    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);

    $this->apiEndpointService = new ApiEndpointService(
      $this->entityTypeManager->reveal()
    );
  }

  /**
   * Tests the getCustomEndpoints method with no nodes.
   *
   * @covers ::getCustomEndpoints
   */
  public function testGetCustomEndpointsWithNoNodes() {
    // Set up the query to return no results.
    $this->query->condition('type', 'api')->willReturn($this->query);
    $this->query->accessCheck(TRUE)->willReturn($this->query);
    $this->query->execute()->willReturn([]);

    $product_query = $this->prophesize(QueryInterface::class);
    $product_query->condition('type', 'product')->willReturn($product_query);
    $product_query->accessCheck(TRUE)->willReturn($product_query);
    $product_query->execute()->willReturn([]);

    $this->entityStorage->getQuery()->willReturn($this->query->reveal(), $product_query->reveal());
    $this->entityTypeManager->getStorage('node')->willReturn($this->entityStorage->reveal());
    $this->entityStorage->loadMultiple([])->willReturn([]);

    // Test the method.
    $endpoints = $this->apiEndpointService->getCustomEndpoints();

    // Assert that the result is an empty array.
    $this->assertEquals([], $endpoints);
  }

  /**
   * Tests the getCustomEndpoints method with API nodes.
   *
   * @covers ::getCustomEndpoints
   */
  public function testGetCustomEndpointsWithApiNodes() {
    // Mock API node query.
    $api_query = $this->prophesize(QueryInterface::class);
    $api_query->condition('type', 'api')->willReturn($api_query);
    $api_query->accessCheck(TRUE)->willReturn($api_query);
    $api_query->execute()->willReturn([1, 2]);

    // Mock product node query.
    $product_query = $this->prophesize(QueryInterface::class);
    $product_query->condition('type', 'product')->willReturn($product_query);
    $product_query->accessCheck(TRUE)->willReturn($product_query);
    $product_query->execute()->willReturn([]);

    $this->entityStorage->getQuery()->willReturn($api_query->reveal(), $product_query->reveal());
    
    // Create mock API nodes.
    $api_node1 = $this->createMockApiNode('api1.example.com', ['http', 'https'], 'gateway.example.com');
    $api_node2 = $this->createMockApiNode('api2.example.com', ['https'], null, [
      ['url' => 'https://server1.example.com'],
      ['url' => 'https://server2.example.com'],
    ]);
    
    $this->entityStorage->loadMultiple([1, 2])->willReturn([$api_node1, $api_node2]);
    $this->entityTypeManager->getStorage('node')->willReturn($this->entityStorage->reveal());

    // Test the method.
    $endpoints = $this->apiEndpointService->getCustomEndpoints();

    // Assert that the expected endpoints are returned.
    $expected_endpoints = [
      'http://api1.example.com',
      'https://api1.example.com',
      'https://gateway.example.com',
      'https://api2.example.com',
      'https://server1.example.com',
      'https://server2.example.com',
    ];
    
    $this->assertEquals($expected_endpoints, $endpoints);
  }

  /**
   * Tests the getCustomEndpoints method with product nodes.
   *
   * @covers ::getCustomEndpoints
   */
  public function testGetCustomEndpointsWithProductNodes() {
    // Mock API node query.
    $api_query = $this->prophesize(QueryInterface::class);
    $api_query->condition('type', 'api')->willReturn($api_query);
    $api_query->accessCheck(TRUE)->willReturn($api_query);
    $api_query->execute()->willReturn([]);

    // Mock product node query.
    $product_query = $this->prophesize(QueryInterface::class);
    $product_query->condition('type', 'product')->willReturn($product_query);
    $product_query->accessCheck(TRUE)->willReturn($product_query);
    $product_query->execute()->willReturn([3, 4]);

    $this->entityStorage->getQuery()->willReturn($api_query->reveal(), $product_query->reveal());
    
    // Create mock product nodes.
    $product_node1 = $this->createMockProductNode([
      ['url' => 'https://product1.example.com'],
    ]);
    $product_node2 = $this->createMockProductNode([
      ['url' => 'product2.example.com'], // No scheme, should add https://
      ['url' => 'https://product3.example.com'],
    ]);
    
    $this->entityStorage->loadMultiple([])->willReturn([]);
    $this->entityStorage->loadMultiple([3, 4])->willReturn([$product_node1, $product_node2]);
    $this->entityTypeManager->getStorage('node')->willReturn($this->entityStorage->reveal());

    // Test the method.
    $endpoints = $this->apiEndpointService->getCustomEndpoints();

    // Assert that the expected endpoints are returned.
    $expected_endpoints = [
      'https://product1.example.com',
      'https://product2.example.com',
      'https://product3.example.com',
    ];
    
    $this->assertEquals($expected_endpoints, $endpoints);
  }
  
  /**
   * Tests error handling in getCustomEndpoints.
   *
   * @covers ::getCustomEndpoints
   */
  public function testGetCustomEndpointsErrorHandling() {
    // Mock the entity type manager to throw an exception
    $this->entityTypeManager->getStorage('node')->willThrow(new \Exception('Test exception'));

    // Test the method.
    $endpoints = $this->apiEndpointService->getCustomEndpoints();

    // Assert that an empty array is returned when an exception occurs
    $this->assertEquals([], $endpoints);
  }

  /**
   * Creates a mock API node.
   *
   * @param string $host
   *   The host value.
   * @param array $schemes
   *   The schemes array.
   * @param string|null $gateway
   *   The gateway value.
   * @param array $servers
   *   The servers array for OpenAPI 3.0.
   *
   * @return \Drupal\node\NodeInterface
   *   The mock API node.
   */
  protected function createMockApiNode($host, array $schemes = ['https'], $gateway = null, array $servers = []) {
    $node = $this->prophesize(NodeInterface::class);
    
    // Create swagger data.
    $swagger = [
      'host' => $host,
      'schemes' => $schemes,
    ];
    
    if ($gateway !== null) {
      $swagger['x-ibm-configuration'] = [
        'gateway' => $gateway,
      ];
    }
    
    if (!empty($servers)) {
      $swagger['servers'] = $servers;
    }
    
    // Mock the API swagger field.
    $node->hasField('api_swagger')->willReturn(true);
    $node->get('api_swagger')->willReturn(new class($swagger) {
      protected $swagger;
      
      public function __construct($swagger) {
        $this->swagger = $swagger;
      }
      
      public function getValue() {
        return [
          [
            'value' => serialize($this->swagger),
          ],
        ];
      }
    });
    
    $node->instanceof(NodeInterface::class)->willReturn(true);
    
    return $node->reveal();
  }

  /**
   * Creates a mock product node.
   *
   * @param array $apis
   *   The APIs array with URLs.
   *
   * @return \Drupal\node\NodeInterface
   *   The mock product node.
   */
  protected function createMockProductNode(array $apis) {
    $node = $this->prophesize(NodeInterface::class);
    
    // Create product_apis data.
    $product_apis = [];
    foreach ($apis as $api) {
      $product_apis[] = [
        'value' => serialize($api),
      ];
    }
    
    // Mock the product_apis field.
    $node->hasField('product_apis')->willReturn(true);
    $node->get('product_apis')->willReturn(new class($product_apis) {
      protected $apis;
      
      public function __construct($apis) {
        $this->apis = $apis;
      }
      
      public function getValue() {
        return $this->apis;
      }
    });
    
    $node->instanceof(NodeInterface::class)->willReturn(true);
    
    return $node->reveal();
  }
}
