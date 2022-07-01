<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2022
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Updater;

use Drupal\Core\Updater\Module;
use Drupal\Core\Updater\UpdaterException;

/**
 * Defines a class for updating modules using
 * Drupal\Core\FileTransfer\FileTransfer classes via authorize.php.
 */
class ApicModule extends Module {

  /**
   * @var string|null
   */
  public ?string $title = NULL;

  /**
   * ApicModule constructor.
   *
   * @param string $source
   *   Directory to install from.
   * @param string $root
   *   The root directory under which the project will be copied to if it's a
   *   new project. Usually this is the app root (the directory in which the
   *   Drupal site is installed).
   *
   * @throws \Drupal\Core\Updater\UpdaterException
   */
  public function __construct($source, $root) {
    parent::__construct($source, $root);
    $this->source = $source;
    $this->root = $root;
    $this->title = self::getProjectTitle($source);
  }

  /**
   * Returns the project name from a Drupal info file.
   *
   * @param string $directory
   *   Directory to search for the info file.
   *
   * @return string
   *   The title of the project.
   *
   * @throws \Drupal\Core\Updater\UpdaterException
   */
  public static function getProjectTitle($directory): string {
    $info_file = self::findInfoFile($directory);
    $info = \Drupal::service('info_parser')->parse($info_file);
    if (empty($info)) {
      throw new UpdaterException(t('Unable to parse info file: %info_file.', ['%info_file' => $info_file]));
    }

    // APIC check for our functions
    $files = \Drupal::service('file_system')->scanDirectory($directory, '/(.*\.php$|.*\.module$|.*\.install$|.*\.inc$)/');
    foreach ($files as $file) {
      $rc = self::checkFunctionNames($file->uri);
      if ($rc !== TRUE) {
        throw new UpdaterException(t('The file (%file) contains APIC source code. This is not permitted. All method names must be unique. To modify current behavior use drupal module hooks in custom modules, see: https://www.ibm.com/support/knowledgecenter/en/SSMNED_v10/com.ibm.apic.devportal.doc/rapic_portal_custom_modules_drupal8.html', ['%file' => $file->uri]));
      }
    }

    return $info['name'];
  }

  /**
   * Check to make sure no APIC source code is within this module / theme
   *
   * @param $file
   *
   * @return bool
   */
  public static function checkFunctionNames($file): bool {
    $rc = TRUE;
    if (isset($file) && file_exists($file)) {
      $data = file_get_contents($file);

      $prefixes = [
        'ghmarkdown',
        'connect_theme',
        'ibm_apim',
        'apic_api',
        'apic_app',
        'product',
        'consumerorg',
        'auth_apic',
        'featuredcontent',
        'socialblock',
        'mail-subscribers',
        'ibm_log_stdout',
        'themegenerator',
        'eventstream',
      ];
      $regex = '';
      foreach ($prefixes as $prefix) {
        $regex .= 'function\s+' . $prefix . '_|';
      }
      $regex = rtrim($regex, '|');
      if (preg_match('/^\s*(' . $regex . ')/m', $data)) {
        $rc = FALSE;
      }
    }
    else {
      $rc = FALSE;
    }
    return $rc;
  }

  /**
   * {@inheritdoc}
   */
  public static function getRootDirectoryRelativePath(): string {
    return \Drupal::getContainer()->getParameter('site.path') . '/modules';
  }

  /**
   * {@inheritdoc}
   */
  public function isInstalled(): bool {
    // Check if the module exists in the file system, regardless of whether it
    // is enabled or not.
    $modules = \Drupal::state()->get('system.module.files', []);
    return isset($modules[$this->name]);
  }

  /**
   * {@inheritdoc}
   * @throws \Drupal\Core\Updater\UpdaterException
   */
  public static function canUpdateDirectory($directory): bool {
    $info = static::getExtensionInfo($directory);

    return (isset($info['type']) && $info['type'] === 'module');
  }

  /**
   * Returns available database schema updates once a new version is installed.
   *
   * @return array
   */
  public function getSchemaUpdates(): array {
    require_once DRUPAL_ROOT . '/core/includes/install.inc';
    require_once DRUPAL_ROOT . '/core/includes/update.inc';

    if (!self::canUpdate($this->name)) {
      return [];
    }
    module_load_include('install', $this->name);

    if (!$updates = \Drupal::service('update.update_hook_registry')->getAvailableUpdates($this->name)) {
      return [];
    }
    $modules_with_updates = update_get_update_list();
    if (($updates = $modules_with_updates[$this->name]) && isset($updates['start'])) {
      return $updates['pending'];
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function postUpdateTasks() {
    // We don't want to check for DB updates here, we do that once for all
    // updated modules on the landing page.
  }

}
