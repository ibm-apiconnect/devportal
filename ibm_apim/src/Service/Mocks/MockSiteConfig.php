<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2024
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Service\Mocks;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
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

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  private StateInterface $state;

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
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
   *
   * @throws \Exception
   */
  public function __construct(StateInterface $state, ConfigFactoryInterface $config_factory,
                              LoggerInterface $logger, UserRegistryServiceInterface $urService, Billing $billService,
                              PermissionsServiceInterface $permsService, AnalyticsService $analyticsService,
                              TlsClientProfilesService $tlsProfilesService, Group $groupService, VendorExtension $vendorExtService,
                              MenuLinkManagerInterface $menuLinkManager, Messenger $messenger, ModuleHandlerInterface $module_handler,
                              ModuleInstallerInterface $module_installer) {
    parent::__construct($state, $config_factory, $logger, $urService, $billService, $permsService, $analyticsService, $tlsProfilesService, $groupService, $vendorExtService, $menuLinkManager, $messenger, $module_handler, $module_installer);
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
      $catalog_snapshot_file = \Drupal::service('extension.list.module')->getPath('ibm_apim') . '/src/Service/Mocks/MockData/catalog-snapshot.json';
      \Drupal::logger('apictest')->info('loading catalog-snapshot.json from @file', ['@file' => $catalog_snapshot_file]);
    }

    $snapshot = json_decode(file_get_contents($catalog_snapshot_file), TRUE, 512, JSON_THROW_ON_ERROR);
    $content = $snapshot['content'];
    $this->update($content);
  }

  /**
   * @inheritDoc
   */
  protected function get(): array {
    $catalog_config = $this->state->get('ibm_apim.mock_site_config');
    if (!isset($catalog_config)) {
      $catalog_config = [];
    }
    return $catalog_config;
  }

  /**
   * @inheritDoc
   */
  public function isSet(): bool {
    $catalog_config = $this->get();
    return !empty($catalog_config);
  }

  /**
   * @inheritDoc
   */
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

  /**
   * @inheritDoc
   */
  public function getOrgId(): ?string {
    return "orgId";
  }

  /**
   * @inheritDoc
   */
  public function getEnvId(): ?string {
    return "envId";
  }

  /**
   * @inheritDoc
   */
  public function getClientId(): string {
    return "clientId";
  }

  /**
   * @return bool
   */
  public function isConsumerOrgInvitationEnabled(): bool {
    return $this->state->get('ibm_apim.consumerOrgInvitationEnabled', TRUE);
  }

  /**
   * @return array
   */
  public function getConsumerOrgInvitationRoles(): array {
    return $this->state->get('ibm_apim.consumerOrgInvitationRoles', [ 'administrator', 'developer', 'viewer' ]);
  }

}
