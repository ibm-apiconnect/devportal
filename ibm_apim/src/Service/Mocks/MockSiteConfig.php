<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Service\Mocks;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\State\StateInterface;
use Psr\Log\LoggerInterface;
use Drupal\ibm_apim\Service\Interfaces\PermissionsServiceInterface;
use Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface;
use Drupal\ibm_apim\Service\SiteConfig;
use Drupal\ibm_apim\Service\Billing;

/**
 * Mock functionality for handling configuration updates
 *
 * Pulls mocked snapshot data from ibm_apim/src/Service/Mocks/MockData/snapshot.json
 */
class MockSiteConfig extends SiteConfig {

  private $state;

  public function __construct(StateInterface $state, ConfigFactoryInterface $config_factory, ModuleInstallerInterface $module_installer, LoggerInterface $logger, UserRegistryServiceInterface $urService, Billing $billService, PermissionsServiceInterface $permsService) {
    parent::__construct($state, $config_factory, $module_installer, $logger, $urService, $billService, $permsService);
    $this->state = $state;
    $this->updateFromSnapshotFile();
  }

  private function updateFromSnapshotFile() {

    // If a test asked for a specific snapshot to be loaded, use that. Otherwise use the default one.
    if(isset($_ENV['catalog_snapshot_file'])) {
      $catalog_snapshot_file = $_ENV['catalog_snapshot_file'];
    }
    if(!isset($catalog_snapshot_file)) {
      $catalog_snapshot_file = drupal_get_path('module', 'ibm_apim') . '/src/Service/Mocks/MockData/catalog-snapshot.json';
      \Drupal::logger('apictest')->info("loading catalog-snapshot.json from @file", array('@file' => $catalog_snapshot_file));
    }

    $snapshot = json_decode(file_get_contents($catalog_snapshot_file), TRUE);
    $content = $snapshot['content'];
    $this->update($content);
  }

  protected function get() {
    $catalog_config = $this->state->get('ibm_apim.mock_site_config');
    return $catalog_config;
  }

  public function isSet() {
    $catalog_config = $this->state->get('ibm_apim.mock_site_config');
    return !empty($catalog_config);
  }

  public function update($config = NULL) {
    if (!empty($config['catalog_setting']) && is_array($config['catalog_setting'])) {
      $config = array_merge($config, $config['catalog_setting']);
      unset($config['catalog_setting']);
    }

    $current_config = $this->state->get('ibm_apim.mock_site_config');
    if (!isset($current_config) || $current_config != $config) {
      $this->state->set('ibm_apim.mock_site_config', $config);
      $this->getCheckAndStore();
      drupal_flush_all_caches();
    }
  }

}
