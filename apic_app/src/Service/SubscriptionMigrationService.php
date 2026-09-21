<?php


namespace Drupal\apic_app\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Database;
use Drupal\ibm_apim\Service\ApimUtils;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\node\Entity\Node;

/**
 * Service to detect subscriptions that can be migrated to newer plan versions.
 *
 * Uses Drupal Cache API with cache tags for automatic invalidation.
 */
class SubscriptionMigrationService {

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected CacheBackendInterface $cache;

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
  protected UserUtils $userUtils;

  /**
   * @var \Drupal\apic_app\Service\ApplicationService
   */
  protected ApplicationService $applicationService;

  /**
   * @var \Drupal\ibm_apim\Service\ApimUtils
   */
  protected ApimUtils $apimUtils;

  /**
   * SubscriptionMigrationService constructor.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   * @param \Drupal\ibm_apim\Service\UserUtils $userUtils
   * @param \Drupal\apic_app\Service\ApplicationService $applicationService
   * @param \Drupal\ibm_apim\Service\ApimUtils $apimUtils
   */
  public function __construct(
    CacheBackendInterface $cache,
    UserUtils $userUtils,
    ApplicationService $applicationService,
    ApimUtils $apimUtils
  ) {
    $this->cache = $cache;
    $this->userUtils = $userUtils;
    $this->applicationService = $applicationService;
    $this->apimUtils = $apimUtils;
  }

  /**
   * Get all subscriptions that can be migrated for the current consumer organization.
   *
   * Uses Drupal cache with tags for automatic invalidation.
   *
   * @return array
   *   Array of migratable subscriptions with full details.
   */
  public function getMigratableSubscriptions(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    // Get current consumer org
    $consumerOrgUrl = $this->getCurrentConsumerOrgUrl();
    if ($consumerOrgUrl === NULL) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, []);
      return [];
    }

    // Check cache with explicit user:org key
    // This ensures per-login caching - repeated page loads don't re-query
    $userId = \Drupal::currentUser()->id();
    $orgHash = md5($consumerOrgUrl);
    $cacheKey = 'apic_app:migrations:' . $userId . ':' . $orgHash;

    $cached = $this->cache->get($cacheKey);

    if ($cached !== FALSE && isset($cached->data)) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, 'from cache');
      return $cached->data;
    }

    // Cache miss - query for migrations
    $migrations = $this->findMigratableSubscriptions($consumerOrgUrl);

    // Cache the results with tags
    // Tags allow automatic invalidation: org-specific on migration, user-specific on logout
    $cacheTags = [
      'apic_app_migrations',                    // Global fallback tag
      'apic_app_migrations:' . $orgHash,        // Org-specific tag (cleared on migration)
      'user:' . $userId,                        // User-specific tag (cleared on logout)
    ];

    $this->cache->set($cacheKey, $migrations, CacheBackendInterface::CACHE_PERMANENT, $cacheTags);

    // Only log when migrations are found
    if (count($migrations) > 0) {
      \Drupal::logger('apic_app')->info('SubscriptionMigrationService: Found @count migratable subscriptions', [
        '@count' => count($migrations),
      ]);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, count($migrations));
    return $migrations;
  }

  /**
   * Invalidate migration cache.
   *
   * This will be called automatically via cache tags, but can also be called manually.
   */
  public function invalidateCache(): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    // Invalidate all migration caches
    \Drupal\Core\Cache\Cache::invalidateTags(['apic_app_migrations']);

    \Drupal::logger('apic_app')->info('SubscriptionMigrationService: Cache INVALIDATED via cache tags');

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Get the current consumer organization URL.
   *
   * @return string|null
   *   Consumer org URL or NULL if not found.
   */
  protected function getCurrentConsumerOrgUrl(): ?string {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    try {
      $consumerOrg = $this->userUtils->getCurrentConsumerOrg();
      if (isset($consumerOrg['url'])) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $consumerOrg['url']);
        return $consumerOrg['url'];
      }
    } catch (\Exception $e) {
      \Drupal::logger('apic_app')->error('SubscriptionMigrationService: Failed to get consumer org: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return NULL;
  }

  /**
   * Find all subscriptions that can be migrated for a consumer organization.
   *
   * @param string $consumerOrgUrl
   *   The consumer organization URL.
   *
   * @return array
   *   Array of migratable subscriptions.
   */
  protected function findMigratableSubscriptions(string $consumerOrgUrl): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $consumerOrgUrl);

    // Get all subscriptions for this consumer org
    $allSubscriptions = $this->getAllSubscriptionsForOrg($consumerOrgUrl);
    \Drupal::logger('apic_app')->debug('SubscriptionMigrationService: Found @count total subscriptions', [
      '@count' => count($allSubscriptions),
    ]);

    // Process each subscription to check for migrations
    $migratableSubscriptions = [];
    foreach ($allSubscriptions as $subscription) {
      $migrationData = $this->processSubscriptionForMigration($subscription);
      if ($migrationData !== NULL) {
        $migratableSubscriptions[] = $migrationData;
        \Drupal::logger('apic_app')->debug('SubscriptionMigrationService: Found migratable subscription: @subId for app: @app', [
          '@subId' => $subscription['uuid'],
          '@app' => $migrationData['app_title'] ?? 'unknown',
        ]);
      }
    }

    \Drupal::logger('apic_app')->info('SubscriptionMigrationService: Found @count migratable subscriptions out of @total total', [
      '@count' => count($migratableSubscriptions),
      '@total' => count($allSubscriptions),
    ]);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, count($migratableSubscriptions));
    return $migratableSubscriptions;
  }

  /**
   * Get all subscriptions for a consumer organization from the database.
   *
   * @param string $consumerOrgUrl
   *   The consumer organization URL.
   *
   * @return array
   *   Array of subscription records.
   */
  protected function getAllSubscriptionsForOrg(string $consumerOrgUrl): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $consumerOrgUrl);

    $subscriptions = [];
    $db = Database::getConnection();

    // Database stores URLs without fully qualified part
    $consumerOrgUrl = $this->apimUtils->removeFullyQualifiedUrl($consumerOrgUrl);
    \Drupal::logger('apic_app')->debug('SubscriptionMigrationService: Normalized consumer org URL for DB query: @url', [
      '@url' => $consumerOrgUrl,
    ]);

    try {
      $query = $db->select('apic_app_application_subs', 's');
      $query->fields('s', ['uuid', 'product_url', 'plan', 'state', 'app_url']);
      $query->condition('s.consumerorg_url', $consumerOrgUrl);
      $query->condition('s.state', 'enabled');
      $result = $query->execute();

      while ($record = $result->fetchAssoc()) {
        $subscriptions[] = $record;
      }

      \Drupal::logger('apic_app')->debug('SubscriptionMigrationService: Query returned @count subscriptions for org: @org', [
        '@count' => count($subscriptions),
        '@org' => $consumerOrgUrl,
      ]);
    } catch (\Exception $e) {
      \Drupal::logger('apic_app')->error('SubscriptionMigrationService: Database query failed: @message', [
        '@message' => $e->getMessage(),
      ]);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, count($subscriptions));
    return $subscriptions;
  }

  /**
   * Process a subscription to check if it can be migrated.
   *
   * @param array $subscription
   *   Subscription record from database.
   *
   * @return array|null
   *   Migration data or NULL if not migratable.
   */
  protected function processSubscriptionForMigration(array $subscription): ?array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $subscription['uuid']);

    \Drupal::logger('apic_app')->debug('SubscriptionMigrationService: Processing subscription @subId - Product: @product, Plan: @plan, State: @state', [
      '@subId' => $subscription['uuid'],
      '@product' => $subscription['product_url'],
      '@plan' => $subscription['plan'],
      '@state' => $subscription['state'],
    ]);

    // Use existing ApplicationService::processSubscription
    $config = \Drupal::config('ibm_apim.settings');
    $showPlaceholders = (bool) $config->get('show_placeholder_images');

    $subscriptionData = $this->applicationService->processSubscription(
      $subscription['product_url'],
      $subscription['plan'],
      $subscription['state'],
      $subscription['uuid'],
      $showPlaceholders
    );

    if (empty($subscriptionData)) {
      \Drupal::logger('apic_app')->warning('SubscriptionMigrationService: processSubscription returned EMPTY for @subId', [
        '@subId' => $subscription['uuid'],
      ]);
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      return NULL;
    }

    \Drupal::logger('apic_app')->debug('SubscriptionMigrationService: processSubscription returned data for @subId - has superseded_by: @has_superseded', [
      '@subId' => $subscription['uuid'],
      '@has_superseded' => isset($subscriptionData['superseded_by_product']) ? 'YES' : 'NO',
    ]);

    if (!isset($subscriptionData['superseded_by_product']) || $subscriptionData['superseded_by_product'] === NULL) {
      \Drupal::logger('apic_app')->debug('SubscriptionMigrationService: Subscription @subId has NO migration path', [
        '@subId' => $subscription['uuid'],
      ]);
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      return NULL;
    }

    // Get application details
    $appData = $this->getApplicationData($subscription['app_url']);
    if ($appData === NULL) {
      \Drupal::logger('apic_app')->warning('SubscriptionMigrationService: Could not load application for subscription @subId', [
        '@subId' => $subscription['uuid'],
      ]);
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      return NULL;
    }

    $migrationData = array_merge($subscriptionData, $appData, [
      'app_url' => $subscription['app_url'],
    ]);

    \Drupal::logger('apic_app')->debug('SubscriptionMigrationService: Subscription @subId can migrate from @old_plan to @new_plan', [
      '@subId' => $subscription['uuid'],
      '@old_plan' => $subscriptionData['plan_title'],
      '@new_plan' => $subscriptionData['superseded_by_product']['plan_title'],
    ]);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, 'migration available');
    return $migrationData;
  }

  /**
   * Get application data by application URL.
   *
   * @param string $appUrl
   *   The application URL.
   *
   * @return array|null
   *   Application data or NULL if not found.
   */
  protected function getApplicationData(string $appUrl): ?array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $appUrl);

    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'application');
    $query->condition('apic_url.value', $appUrl);
    $query->accessCheck(FALSE);
    $nids = $query->execute();

    if (empty($nids)) {
      \Drupal::logger('apic_app')->warning('SubscriptionMigrationService: Application not found for URL: @url', [
        '@url' => $appUrl,
      ]);
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      return NULL;
    }

    $nid = reset($nids);
    $node = Node::load($nid);

    if ($node === NULL) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      return NULL;
    }

    $appData = [
      'app_nid' => $node->id(),
      'app_title' => $node->getTitle(),
      'app_id' => $node->application_id->value,
    ];

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $appData);
    return $appData;
  }

}