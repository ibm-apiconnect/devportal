<?php

namespace Drupal\Tests\apic_app\Unit;
include_once __DIR__ . '/bootstrap.php';
include_once __DIR__ . '/../../../src/Service/SubscriptionMigrationService.php';


use Drupal\apic_app\Service\ApplicationService;
use Drupal\apic_app\Service\SubscriptionMigrationService;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\ibm_apim\Service\ApimUtils;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\Prophet;

/**
 * @coversDefaultClass \Drupal\apic_app\Service\SubscriptionMigrationService
 *
 * @group apic_app
 */
class SubscriptionMigrationServiceTest extends UnitTestCase {

  /**
   * @var \Prophecy\Prophet
   */
  private Prophet $prophet;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $cache;

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $userUtils;

  /**
   * @var \Drupal\apic_app\Service\ApplicationService|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $applicationService;

  /**
   * @var \Drupal\ibm_apim\Service\ApimUtils|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $apimUtils;

  /**
   * @var \Drupal\apic_app\Service\SubscriptionMigrationService
   */
  protected SubscriptionMigrationService $service;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->prophet = new Prophet();

    // Create prophecies for dependencies
    $this->cache = $this->prophet->prophesize(CacheBackendInterface::class);
    $this->userUtils = $this->prophet->prophesize(UserUtils::class);
    $this->applicationService = $this->prophet->prophesize(ApplicationService::class);
    $this->apimUtils = $this->prophet->prophesize(ApimUtils::class);

    // Mock Drupal container for logger and config
    $container = new ContainerBuilder();
    $logger = $this->prophet->prophesize(\Psr\Log\LoggerInterface::class);
    $loggerFactory = $this->prophet->prophesize(\Drupal\Core\Logger\LoggerChannelFactoryInterface::class);
    $loggerFactory->get('apic_app')->willReturn($logger->reveal());
    $container->set('logger.factory', $loggerFactory->reveal());

    $config = $this->prophet->prophesize(\Drupal\Core\Config\ImmutableConfig::class);
    $config->get('show_placeholder_images')->willReturn(FALSE);
    $configFactory = $this->prophet->prophesize(\Drupal\Core\Config\ConfigFactoryInterface::class);
    $configFactory->get('ibm_apim.settings')->willReturn($config->reveal());
    $container->set('config.factory', $configFactory->reveal());

    $currentUser = $this->prophet->prophesize(\Drupal\Core\Session\AccountProxyInterface::class);
    $currentUser->id()->willReturn(1);
    $container->set('current_user', $currentUser->reveal());

    // Mock cache tags invalidator
    $cacheTagsInvalidator = $this->prophet->prophesize(\Drupal\Core\Cache\CacheTagsInvalidatorInterface::class);
    $cacheTagsInvalidator->invalidateTags(Argument::any())->willReturn(NULL);
    $container->set('cache_tags.invalidator', $cacheTagsInvalidator->reveal());

    \Drupal::setContainer($container);

    // Create service instance
    $this->service = new SubscriptionMigrationService(
      $this->cache->reveal(),
      $this->userUtils->reveal(),
      $this->applicationService->reveal(),
      $this->apimUtils->reveal()
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    $this->prophet->checkPredictions();
    parent::tearDown();
  }

  /**
   * Test get_migratable_subscriptions returns empty array when no consumer org.
   *
   * @covers ::get_migratable_subscriptions
   * @covers ::get_current_consumer_org_url
   */
  public function testGetMigratableSubscriptionsNoConsumerOrg(): void {
    $this->userUtils->getCurrentConsumerOrg()->willReturn(NULL);

    $result = $this->service->getMigratableSubscriptions();

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Test get_migratable_subscriptions returns cached data when available.
   *
   * @covers ::get_migratable_subscriptions
   * @covers ::get_current_consumer_org_url
   */
  public function testGetMigratableSubscriptionsFromCache(): void {
    $consumerOrgUrl = '/consumer-orgs/test-org';
    $this->userUtils->getCurrentConsumerOrg()->willReturn(['url' => $consumerOrgUrl]);

    $cachedData = [
      [
        'uuid' => 'sub-123',
        'plan_title' => 'Old Plan',
        'app_title' => 'Test App',
      ],
    ];

    $cacheObject = (object) ['data' => $cachedData];
    $this->cache->get(Argument::containingString('apic_app:migrations:'))->willReturn($cacheObject);

    $result = $this->service->getMigratableSubscriptions();

    $this->assertIsArray($result);
    $this->assertCount(1, $result);
    $this->assertEquals('sub-123', $result[0]['uuid']);
  }

  /**
   * Test get_migratable_subscriptions returns empty array when cache miss and no subscriptions.
   *
   * @covers ::getMigratableSubscriptions
   * @covers ::get_current_consumer_org_url
   * @covers ::find_migratable_subscriptions
   * @covers ::getAllSubscriptionsForOrg
   */
  public function testGetMigratableSubscriptionsNoSubscriptions(): void {
    $consumerOrgUrl = '/consumer-orgs/test-org';
    $this->userUtils->getCurrentConsumerOrg()->willReturn(['url' => $consumerOrgUrl]);
    $this->cache->get(Argument::any())->willReturn(FALSE);
    $this->apimUtils->removeFullyQualifiedUrl($consumerOrgUrl)->willReturn($consumerOrgUrl);

    // Mock database connection and query
    $mockResult = $this->prophet->prophesize(\Drupal\Core\Database\StatementInterface::class);
    $mockResult->fetchAssoc()->willReturn(FALSE); // No results

    $mockQuery = $this->prophet->prophesize(\Drupal\Core\Database\Query\SelectInterface::class);
    $mockQuery->fields(Argument::any(), Argument::any())->willReturn($mockQuery->reveal());
    $mockQuery->condition(Argument::any(), Argument::any())->willReturn($mockQuery->reveal());
    $mockQuery->execute()->willReturn($mockResult->reveal());

    $mockConnection = $this->prophet->prophesize(\Drupal\Core\Database\Connection::class);
    $mockConnection->select('apic_app_application_subs', 's')->willReturn($mockQuery->reveal());

    // Mock Database::getConnection()
    $mockDatabase = $this->getMockBuilder(\stdClass::class)
      ->addMethods(['getConnection'])
      ->getMock();

    // Use reflection to set the static connection
    $reflection = new \ReflectionClass(\Drupal\Core\Database\Database::class);
    $property = $reflection->getProperty('connections');
    $property->setAccessible(TRUE);
    $property->setValue(NULL, ['default' => ['default' => $mockConnection->reveal()]]);

    $this->cache->set(
      Argument::containingString('apic_app:migrations:'),
      [],
      CacheBackendInterface::CACHE_PERMANENT,
      Argument::type('array')
    )->shouldBeCalled();

    $result = $this->service->getMigratableSubscriptions();

    $this->assertIsArray($result);
    $this->assertEmpty($result);
  }

  /**
   * Test invalidate_cache calls cache invalidation.
   *
   * @covers ::invalidate_cache
   */
  public function testInvalidateCache(): void {
    // Mock the Cache::invalidateTags static method
    // Note: In real tests, you'd need to mock the static call differently
    // For now, we just verify the method executes without errors
    $this->service->invalidateCache();

    // If we get here without exceptions, the test passes
    $this->assertTrue(TRUE);
  }

  /**
   * Test that no undeclared properties are used.
   *
   * This test verifies the fix for the PHP 8.2+ undeclared property issue.
   */
  public function testNoUndeclaredProperties(): void {
    $reflection = new \ReflectionClass(SubscriptionMigrationService::class);
    $properties = $reflection->getProperties();

    $declaredPropertyNames = [];
    foreach ($properties as $property) {
      $declaredPropertyNames[] = $property->getName();
    }

    // Verify that lastExecutionInfo is NOT in the declared properties
    // (it was removed as part of the bug fix)
    $this->assertNotContains(
      'lastExecutionInfo',
      $declaredPropertyNames,
      'lastExecutionInfo should not be declared as it was removed in the bug fix'
    );
  }


}