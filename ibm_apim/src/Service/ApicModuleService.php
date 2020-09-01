<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2020
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\ibm_apim\Service\Interfaces\ApicModuleInterface;
use Psr\Log\LoggerInterface;

class ApicModuleService implements ApicModuleInterface {

  /**
   * @var \Drupal\ibm_apim\Service\Utils
   */
  private $utils;

  /**
   * @var string
   */
  private $sitePath;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private $moduleHandler;

  /**
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  private $moduleInstaller;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $ibmSettingsConfig;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  public function __construct(Utils $utils,
                              string $site_path,
                              ModuleHandlerInterface $module_handler,
                              ModuleInstallerInterface $module_installer,
                              ConfigFactoryInterface $config_factory,
                              LoggerInterface $logger) {
    $this->utils = $utils;
    $this->sitePath = $site_path;
    $this->moduleHandler = $module_handler;
    $this->moduleInstaller = $module_installer;
    $this->ibmSettingsConfig = $config_factory->get('ibm_apim.settings');
    $this->logger = $logger;
  }


  /**
   * @inheritDoc
   */
  public function deleteModulesOnFileSystem(array $modules, bool $fail_on_no_deletion = TRUE): bool {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $modules);
    }

    $paths = [];
    $error_found = FALSE;
    foreach ($modules as $module) {
      $path = \DRUPAL_ROOT . '/' . $this->sitePath . '/modules/' . $module;
      $this->logger->debug(__FUNCTION__ . ': checking existence of %path', ['%path' => $path]);
      if (is_dir($path)) {
        $this->logger->debug(__FUNCTION__ . ': %path found and will be deleted.', ['%path' => $path]);
        $paths[] = $path;
      }
      elseif ($fail_on_no_deletion) {
        $this->logger->error(__FUNCTION__ . ': %path is not found.', ['%path' => $path]);
        $error_found = TRUE;
      }
    }

    if ($error_found) {
      $this->logger->error('Errors found while checking module directories to delete, so cancelling processing.');
      $return = FALSE;
    }
    elseif (!empty($paths)) {
      foreach ($paths as $path) {
        $this->logger->debug(__FUNCTION__ . ': recursively deleting %path', ['%path' => $path]);
        $this->utils->file_delete_recursive($path);
      }
      $return = TRUE;
    }
    else { // nothing found to delete
      if ($fail_on_no_deletion) {
        $this->logger->error(__FUNCTION__ . ': Empty list of paths to delete.');
        $return = FALSE;
      }
      else {
        $this->logger->debug(__FUNCTION__ . ': Nothing deleted.');
        $return = TRUE;
      }
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $return);
    }
    return $return;
  }


  /**
   * @inheritDoc
   */
  public function purgeBlockListedModules(): bool {
    $block_list =   $this->ibmSettingsConfig->get('module_blocklist');
    $uninstall_success = TRUE;

    // initially check if any of the modules are running, and uninstall if so.
    foreach ($block_list as $module) {
      // moduleExists returns TRUE if the module is enabled.
      if ($this->moduleHandler->moduleExists($module)) {
        $this->logger->notice('Blocklisted module ' . $module . ' found. Forcing uninstall.');
        // uninstalling module but not dependencies.
        try {
          $this->moduleInstaller->uninstall([$module], FALSE);
        }
        catch(\Throwable $e) {
          $this->logger->warning('Exception while deleting blocklist module: ' . $module);
          $this->logger->debug($module . ' uninstall exception message : ' . $e->getMessage());
          $uninstall_success = FALSE;
          // don't stop processing because uninstall of one module has failed.
        }
        finally {
          $this->logger->notice('Blocklisted module ' . $module . ': forced uninstall complete.');
        }
      }
      else {
        $this->logger->debug('Blocklisted module ' . $module . ' not found.');
      }
    }

    // regardless of whether we have attempted to uninstall any modules, try to delete all blocklisted modules
    $delete_success = $this->deleteModulesOnFileSystem($block_list, FALSE);

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $uninstall_success && $delete_success);
    }
    return $uninstall_success && $delete_success;

  }
}
