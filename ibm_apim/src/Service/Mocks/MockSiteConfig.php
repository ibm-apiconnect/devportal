<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2021
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Service\Mocks;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\State\StateInterface;
use Drupal\ibm_apim\Service\Billing;
use Drupal\ibm_apim\Service\Interfaces\PermissionsServiceInterface;
use Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface;
use Drupal\ibm_apim\Service\SiteConfig;
use Drupal\ibm_apim\Service\Group;
use Drupal\ibm_apim\Service\AnalyticsService;
use Drupal\ibm_apim\Service\TlsClientProfilesService;
use Drupal\ibm_apim\Service\VendorExtension;
use Psr\Log\LoggerInterface;

/**
 * Mock functionality for handling configuration updates
 *
 * Pulls mocked snapshot data from ibm_apim/src/Service/Mocks/MockData/snapshot.json
 */
class MockSiteConfig extends SiteConfig {

  private $state;

  /**
   * MockSiteConfig constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface $urService
   * @param \Drupal\ibm_apim\Service\Billing $billService
   * @param \Drupal\ibm_apim\Service\Interfaces\PermissionsServiceInterface $permsService
   * @param \Drupal\ibm_apim\Service\AnalyticsService $analyticsService
   * @param \Drupal\ibm_apim\Service\TlsClientProfilesService $tlsProfilesService
   * @param \Drupal\ibm_apim\Service\Group $groupService
   * @param \Drupal\ibm_apim\Service\VendorExtension $vendorExtService
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menuLinkManager
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *
   * @throws \Exception
   */
  public function __construct(StateInterface $state, ConfigFactoryInterface $config_factory,
                              LoggerInterface $logger, UserRegistryServiceInterface $urService, Billing $billService,
                              PermissionsServiceInterface $permsService, AnalyticsService $analyticsService,
                              TlsClientProfilesService $tlsProfilesService, Group $groupService, VendorExtension $vendorExtService, MenuLinkManagerInterface $menuLinkManager, Messenger $messenger) {
    parent::__construct($state, $config_factory, $logger, $urService, $billService, $permsService, $analyticsService, $tlsProfilesService, $groupService, $vendorExtService, $menuLinkManager, $messenger);
    $this->state = $state;
    $this->updateFromSnapshotFile();
  }

  /**
   * @throws \Exception
   */
  private function updateFromSnapshotFile(): void {

    // If a test asked for a specific snapshot to be loaded, use that. Otherwise use the default one.
    if (isset($_ENV['catalog_snapshot_file'])) {
      $catalog_snapshot_file = $_ENV['catalog_snapshot_file'];
    }
    if (!isset($catalog_snapshot_file)) {
      $catalog_snapshot_file = drupal_get_path('module', 'ibm_apim') . '/src/Service/Mocks/MockData/catalog-snapshot.json';
      \Drupal::logger('apictest')->info('loading catalog-snapshot.json from @file', ['@file' => $catalog_snapshot_file]);
    }

    $snapshot = json_decode(file_get_contents($catalog_snapshot_file), TRUE);
    $content = $snapshot['content'];
    $this->update($content);
  }

  protected function get(): array {
    $catalog_config = $this->state->get('ibm_apim.mock_site_config');
    if (!isset($catalog_config)) {
      $catalog_config = [];
    }
    return $catalog_config;
  }

  public function isSet(): bool {
    $catalog_config = $this->get();
    return !empty($catalog_config);
  }

  public function update($config = NULL): void {
    if (!empty($config['catalog_setting']) && is_array($config['catalog_setting'])) {
      $config = array_merge($config, $config['catalog_setting']);
      unset($config['catalog_setting']);
    }

    $current_config = $this->get();
    if (!isset($current_config) || $current_config !== $config) {
      $this->state->set('ibm_apim.mock_site_config', $config);
      $this->getCheckAndStore();
      drupal_flush_all_caches();
    }
  }

  public function getOrgId(): ?string {
    return "orgId";
  }

  /**
   * Get current catalog ID
   *
   * @return string|null
   */
  public function getEnvId(): ?string {
    return "envId";
  }

  public function getClientId() {
    return "clientId";
  }
}
