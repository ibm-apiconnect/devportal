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
namespace Drupal\ibm_apim\Updater;

use Drupal\Core\Url;
use Drupal\Core\Updater\Module;
use Drupal\Core\Updater\UpdaterException;

/**
 * Defines a class for updating modules using
 * Drupal\Core\FileTransfer\FileTransfer classes via authorize.php.
 */
class ApicModule extends Module {

  /**
   * Constructs a new updater.
   *
   * @param string $source
   *   Directory to install from.
   * @param string $root
   *   The root directory under which the project will be copied to if it's a
   *   new project. Usually this is the app root (the directory in which the
   *   Drupal site is installed).
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
  public static function getProjectTitle($directory) {
    $info_file = self::findInfoFile($directory);
    $info = \Drupal::service('info_parser')->parse($info_file);
    if (empty($info)) {
      throw new UpdaterException(t('Unable to parse info file: %info_file.', ['%info_file' => $info_file]));
    }

    // APIC check for our functions
    $files = file_scan_directory($directory, '/(.*\.php$|.*\.module$|.*\.install$|.*\.inc$)/');
    foreach ($files as $file) {
      $rc = self::checkFunctionNames($file->uri);
      if ($rc != TRUE) {
        throw new UpdaterException(t("The file (%file) contains APIC source code. This is not permitted. All method names must be unique. To modify current behavior use drupal module hooks in custom modules, see: https://www.ibm.com/support/knowledgecenter/en/SSMNED_2018/com.ibm.apic.devportal.doc/rapic_portal_custom_modules_drupal8.html", array('%file' => $file->uri)));
      }
    }

    return $info['name'];
  }

  /**
   * Check to make sure no APIC source code is within this module / theme
   *
   * @param $file
   * @return bool
   */
  public static function checkFunctionNames($file) {
    $rc = TRUE;
    if (isset($file) && file_exists($file)) {
      $data = file_get_contents($file);

      $prefixes = array(
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
        'eventstream'
      );
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
   * Returns the directory where a module should be installed.
   *
   * If the module is already installed, drupal_get_path() will return a valid
   * path and we should install it there. If we're installing a new module, we
   * always want it to go into /modules, since that's where all the
   * documentation recommends users install their modules, and there's no way
   * that can conflict on a multi-site installation, since the Update manager
   * won't let you install a new module if it's already found on your system,
   * and if there was a copy in the top-level we'd see it.
   *
   * @return string
   *   The absolute path of the directory.
   */
  public function getInstallDirectory() {
    if ($this->isInstalled() && ($relative_path = drupal_get_path('module', $this->name))) {
      // The return value of drupal_get_path() is always relative to the site,
      // so prepend DRUPAL_ROOT.
      return DRUPAL_ROOT . '/' . dirname($relative_path);
    }
    else {
      // When installing a new module, prepend the requested root directory.
      return $this->root . '/' . $this->getRootDirectoryRelativePath();
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getRootDirectoryRelativePath() {
    return \Drupal::service('site.path') . '/modules';
  }

  /**
   * {@inheritdoc}
   */
  public function isInstalled() {
    // Check if the module exists in the file system, regardless of whether it
    // is enabled or not.
    $modules = \Drupal::state()->get('system.module.files', []);
    return isset($modules[$this->name]);
  }

  /**
   * {@inheritdoc}
   */
  public static function canUpdateDirectory($directory) {
    $info = static::getExtensionInfo($directory);

    return (isset($info['type']) && $info['type'] == 'module');
  }

  /**
   * Determines whether this class can update the specified project.
   *
   * @param string $project_name
   *   The project to check.
   *
   * @return bool
   */
  public static function canUpdate($project_name) {
    return (bool) drupal_get_path('module', $project_name);
  }

  /**
   * Returns available database schema updates once a new version is installed.
   *
   * @return array
   */
  public function getSchemaUpdates() {
    require_once DRUPAL_ROOT . '/core/includes/install.inc';
    require_once DRUPAL_ROOT . '/core/includes/update.inc';

    if (!self::canUpdate($this->name)) {
      return [];
    }
    module_load_include('install', $this->name);

    if (!$updates = drupal_get_schema_versions($this->name)) {
      return [];
    }
    $modules_with_updates = update_get_update_list();
    if ($updates = $modules_with_updates[$this->name]) {
      if ($updates['start']) {
        return $updates['pending'];
      }
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function postInstallTasks() {
    // Since this is being called outsite of the primary front controller,
    // the base_url needs to be set explicitly to ensure that links are
    // relative to the site root.
    // @todo Simplify with https://www.drupal.org/node/2548095
    $default_options = [
      '#type' => 'link',
      '#options' => [
        'absolute' => TRUE,
        'base_url' => $GLOBALS['base_url'],
      ],
    ];
    return [
      $default_options + [
        '#url' => Url::fromRoute('update.module_install'),
        '#title' => t('Install another module'),
      ],
      $default_options + [
        '#url' => Url::fromRoute('system.modules_list'),
        '#title' => t('Enable newly added modules'),
      ],
      $default_options + [
        '#url' => Url::fromRoute('system.admin'),
        '#title' => t('Administration pages'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function postUpdateTasks() {
    // We don't want to check for DB updates here, we do that once for all
    // updated modules on the landing page.
  }

}
