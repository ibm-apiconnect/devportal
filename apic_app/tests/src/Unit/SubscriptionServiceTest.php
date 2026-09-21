<?php

namespace Drupal\Tests\apic_app\Unit;

include_once __DIR__ . '/bootstrap.php';
include_once __DIR__ . '/../../../src/Service/SubscriptionService.php';

use Drupal\apic_app\Service\SubscriptionService;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeRepositoryInterface;
use Drupal\ibm_apim\External\SerializationUtility;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\apic_app\Service\SubscriptionService
 *
 * @group apic_app
 */
class SubscriptionServiceTest extends UnitTestCase {

  /**
   * Build a SubscriptionService with stubbed dependencies.
   */
  private function buildService(): SubscriptionService {
    $userUtils = $this->createStub(\Drupal\ibm_apim\Service\UserUtils::class);
    $apimUtils = $this->createStub(\Drupal\ibm_apim\Service\ApimUtils::class);
    $moduleHandler = $this->createStub(\Drupal\Core\Extension\ModuleHandlerInterface::class);
    $utils = $this->createStub(\Drupal\ibm_apim\Service\Utils::class);

    return new SubscriptionService($userUtils, $apimUtils, $moduleHandler, $utils);
  }

  /**
   * Configure a minimal container for entity queries and node loading.
   */
  private function setEntityManagerWithNode(array $queryResults, $node): void {
    $query = new EntityQueryStub($queryResults);
    $storage = new NodeStorageStub($query, $node);
    $manager = new EntityTypeManagerStub($storage);
    $repository = $this->createStub(EntityTypeRepositoryInterface::class);
    $repository->method('getEntityTypeFromClass')->willReturn('node');
    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $manager);
    $container->set('entity_type.repository', $repository);
    \Drupal::setContainer($container);
  }

  /**
   * Invoke the private resolveSubscriptionTitles method.
   */
  private function callResolve(SubscriptionService $service, string $productUrl, string $plan): array {
    $method = new \ReflectionMethod(SubscriptionService::class, 'resolveSubscriptionTitles');
    $method->setAccessible(true);
    return $method->invoke($service, $productUrl, $plan);
  }

  /**
   * @covers ::resolveSubscriptionTitles
   */
  public function testResolveSubscriptionTitlesWithPlanTitle(): void {
    $productUrl = '/products/one';
    $plans = [
      ['value' => SerializationUtility::encodeData(['name' => 'plan-basic', 'title' => 'Basic Plan'])],
    ];
    $node = new ProductNodeStub('Product One', $plans);
    $this->setEntityManagerWithNode([123], $node);

    $service = $this->buildService();
    $result = $this->callResolve($service, $productUrl, 'plan-basic');

    $this->assertSame('Product One', $result['product_title']);
    $this->assertSame('Basic Plan', $result['plan_title']);
    $this->assertSame(0, $result['product_title_missing']);
    $this->assertSame(0, $result['plan_title_missing']);
  }

  /**
   * @covers ::resolveSubscriptionTitles
   */
  public function testResolveSubscriptionTitlesMissingPlanTitle(): void {
    $productUrl = '/products/two';
    $plans = [
      ['value' => SerializationUtility::encodeData(['name' => 'plan-basic', 'title' => 'Basic Plan'])],
    ];
    $node = new ProductNodeStub('Product Two', $plans);
    $this->setEntityManagerWithNode([124], $node);

    $service = $this->buildService();
    $result = $this->callResolve($service, $productUrl, 'plan-missing');

    $this->assertSame('Product Two', $result['product_title']);
    $this->assertNull($result['plan_title']);
    $this->assertSame(0, $result['product_title_missing']);
    $this->assertSame(1, $result['plan_title_missing']);
  }

  /**
   * @covers ::resolveSubscriptionTitles
   */
  public function testResolveSubscriptionTitlesMissingProduct(): void {
    $productUrl = '/products/missing';
    $this->setEntityManagerWithNode([], NULL);

    $service = $this->buildService();
    $result = $this->callResolve($service, $productUrl, 'plan-basic');

    $this->assertNull($result['product_title']);
    $this->assertNull($result['plan_title']);
    $this->assertSame(1, $result['product_title_missing']);
    $this->assertSame(1, $result['plan_title_missing']);
  }

}

class EntityQueryStub {
  private array $results;

  public function __construct(array $results) {
    $this->results = $results;
  }

  public function condition() {
    return $this;
  }

  public function accessCheck() {
    return $this;
  }

  public function execute(): array {
    return $this->results;
  }

}

class NodeStorageStub {
  private EntityQueryStub $query;
  private $node;

  public function __construct(EntityQueryStub $query, $node) {
    $this->query = $query;
    $this->node = $node;
  }

  public function getQuery(): EntityQueryStub {
    return $this->query;
  }

  public function load($id) {
    return $this->node;
  }

}

class EntityTypeManagerStub {
  private NodeStorageStub $storage;

  public function __construct(NodeStorageStub $storage) {
    $this->storage = $storage;
  }

  public function getStorage($entityType): NodeStorageStub {
    return $this->storage;
  }

}

class ProductPlansStub {
  private array $values;

  public function __construct(array $values) {
    $this->values = $values;
  }

  public function getValue(): array {
    return $this->values;
  }

}

class ProductNodeStub {
  private string $title;
  public ProductPlansStub $product_plans;

  public function __construct(string $title, array $plans) {
    $this->title = $title;
    $this->product_plans = new ProductPlansStub($plans);
  }

  public function getTitle(): string {
    return $this->title;
  }

  public function hasField(string $field): bool {
    return $field === 'product_plans';
  }

}
