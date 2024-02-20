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

namespace Drupal\ibm_apim\Service;

use Drupal\ibm_apim\Service\SiteConfig;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\ibm_apim\Service\Interfaces\ApicModuleInterface;
use Psr\Log\LoggerInterface;

class ApicModuleService implements ApicModuleInterface {

  /**
   * @var \Drupal\ibm_apim\Service\Utils
   */
  private Utils $utils;

  /**
   * @var string
   */
  private string $sitePath;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private ModuleHandlerInterface $moduleHandler;

  /**
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  private ModuleInstallerInterface $moduleInstaller;

  /**
   * @var \Drupal\ibm_apim\Service\SiteConfig
   */
  private SiteConfig $site_config;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  private LoggerInterface $logger;

  public function __construct(Utils $utils,
                              string $site_path,
                              ModuleHandlerInterface $module_handler,
                              ModuleInstallerInterface $module_installer,
                              SiteConfig $site_config,
                              LoggerInterface $logger) {
    $this->utils = $utils;
    $this->sitePath = $site_path;
    $this->moduleHandler = $module_handler;
    $this->moduleInstaller = $module_installer;
    $this->siteConfig = $site_config;
    $this->logger = $logger;
  }


  /**
   * @inheritDoc
   */
  public function deleteExtensionOnFileSystem(string $extensionType, array $extensions, bool $fail_on_no_deletion = TRUE): bool {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $extensions);
    }

    $paths = [];
    $error_found = FALSE;
    foreach ($extensions as $extension) {
      if (empty($extension) || $extension === NULL) {
        $this->logger->error('%function: the module extension to check is null or empty.', ['%function' => __FUNCTION__]);
        $error_found = TRUE;
        continue;
      }

      $directory_path = \Drupal::service('extension.path.resolver')->getPath($extensionType, $extension);
      if (empty($directory_path) || $directory_path === NULL) {
        $this->logger->error('%function: The extensions directory path could not be found.', ['%function' => __FUNCTION__, '%path' => $directory_path]);
        $error_found = TRUE;
        continue;
      }

      $extension_path = DRUPAL_ROOT . DIRECTORY_SEPARATOR . $directory_path;
      $this->logger->debug('%function: Checking existence of %path', ['%function' => __FUNCTION__, '%path' => $extension_path]);
      if (is_dir($extension_path)) {
        $this->logger->debug('%function: %path found and will be deleted.', ['%function' => __FUNCTION__, '%path' => $extension_path]);
        $paths[] = $extension_path;
      }
      elseif ($fail_on_no_deletion) {
        $this->logger->error('%function: %path is not found.', ['%function' => __FUNCTION__, '%path' => $extension_path]);
        $error_found = TRUE;
      }
    }

    if ($error_found) {
      $this->logger->error('Errors found while checking module directories to delete, so cancelling processing.');
      $return = FALSE;
    }
    elseif (!empty($paths)) {
      foreach ($paths as $path) {
        $this->logger->debug('%function: Recursively deleting %path', ['%function' => __FUNCTION__, '%path' => $path]);
        $this->utils->file_delete_recursive($path);
      }
      $this->utils->clear_empty_extension_folders($extensionType);
      // Rebuild module list after removing the files to remove its also removed from the list
      $extensionType == 'module' ? \Drupal::service('extension.list.module')->reset() : \Drupal::service('extension.list.theme')->reset();
      $return = TRUE;
    }
    else { // nothing found to delete
      if ($fail_on_no_deletion) {
        $this->logger->error('%function: Empty list of paths to delete.', ['%function' => __FUNCTION__]);
        $return = FALSE;
      }
      else {
        $this->logger->debug('%function: Nothing deleted.', ['%function' => __FUNCTION__]);
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
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__);
    }
    $block_list = $this->siteConfig->getBlockList();
    $uninstall_success = TRUE;

    // initially check if any of the modules are running, and uninstall if so.
    foreach ($block_list as $module) {
      // moduleExists returns TRUE if the module is enabled.
      if ($this->moduleHandler->moduleExists($module)) {
        $this->logger->notice('Blocklisted module %module found. Forcing uninstall.', ['%module' => $module]);
        // uninstalling module but not dependencies.
        try {
          $this->moduleInstaller->uninstall([$module], FALSE);
        }
        catch(\Throwable $e) {
          $this->logger->warning('Exception while deleting blocklist module: %module', ['%module' => $module]);
          $this->logger->debug('%module uninstall exception message : %message', ['%module' => $module, '%message'=> $e->getMessage()]);
          $uninstall_success = FALSE;
          // don't stop processing because uninstall of one module has failed.
        }
        finally {
          $this->logger->notice('Blocklisted module %module: forced uninstall complete.', ['%module' => $module]);
        }
      }
      else {
        $this->logger->debug('Blocklisted module %module not found.', ['%module' => $module]);
      }
    }

    // regardless of whether we have attempted to uninstall any modules, try to delete all blocklisted modules
    $delete_success = $this->deleteExtensionOnFileSystem('module', $block_list, FALSE);

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $uninstall_success && $delete_success);
    }
    return $uninstall_success && $delete_success;

  }
}
