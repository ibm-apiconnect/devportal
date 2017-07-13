<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2016
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * @file
 * Subclasses of the Updater class to update Drupal core knows how to update.
 * At this time, only modules and themes are supported.
 */

/**
 * Class for updating modules using FileTransfer classes via authorize.php.
 */
class SiteModuleUpdater extends Updater implements DrupalUpdaterInterface {

  /**
   * @param string $directory
   * @return bool
   */
  public static function canUpdateDirectory($directory) {
    if (file_scan_directory($directory, '/.*\.module$/')) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * @param $project_name
   * @return bool
   */
  public static function canUpdate($project_name) {
    return (bool) drupal_get_path('module', $project_name);
  }

  /**
   * Return the directory where a module should be installed.
   *
   * If the module is already installed, drupal_get_path() will return
   * a valid path and we should install it there (although we need to use an
   * absolute path, so we prepend DRUPAL_ROOT). If we're installing a new
   * module, we always want it to go into sites/all/modules, since that's
   * where all the documentation recommends users install their modules, and
   * there's no way that can conflict on a multi-site installation, since
   * the Update manager won't let you install a new module if it's already
   * found on your system, and if there was a copy in sites/all, we'd see it.
   *
   * @return string
   */
  public function getInstallDirectory() {
    if ($relative_path = drupal_get_path('module', $this->name)) {
      $relative_path = is_int(strpos($relative_path, 'sites/all/modules')) ? (conf_path() . '/modules') : dirname($relative_path);
    }
    else {
      $dir = 'all';
      $input = $GLOBALS['base_url'];
      // in case scheme relative URI is passed, e.g., //www.example.com/
      $input = trim($input, '/');

      // If scheme not included, prepend it
      if (!preg_match('#^http(s)?://#', $input)) {
        $input = 'http://' . $input;
      }

      $urlParts = parse_url($input);
      if (isset($urlParts['host']) && !empty($urlParts['host'])) {
        $dir = $urlParts['host'];
      }

      //Add on the path to the start of the URL it is here as we support paths for sites now
      if (isset($urlParts['path']) && !empty($urlParts['path']) && strlen($urlParts['path']) > 1) {
        $dir = str_replace('/','.',substr($urlParts['path'],1)) . '.' . $dir;
      }

      $relative_path = 'sites/' . $dir . '/modules';
    }
    return DRUPAL_ROOT . '/' . $relative_path;
  }

  /**
   * @return bool
   */
  public function isInstalled() {
    return (bool) drupal_get_path('module', $this->name);
  }

  /**
   * Return available database schema updates one a new version is installed.
   *
   * @return array
   */
  public function getSchemaUpdates() {
    require_once DRUPAL_ROOT . '/includes/install.inc';
    require_once DRUPAL_ROOT . '/includes/update.inc';

    if (_update_get_project_type($project) != 'module') {
      return array();
    }
    module_load_include('install', $project);

    if (!$updates = drupal_get_schema_versions($project)) {
      return array();
    }
    $updates_to_run = array();
    $modules_with_updates = update_get_update_list();
    if ($updates = $modules_with_updates[$project]) {
      if ($updates['start']) {
        return $updates['pending'];
      }
    }
    return array();
  }

  /**
   * Returns a list of post install actions.
   *
   * @return array
   */
  public function postInstallTasks() {
    return array(
      l(t('Install another module'), 'admin/modules/install'),
      l(t('Enable newly added modules'), 'admin/modules'),
      l(t('Administration pages'), 'admin')
    );
  }

  public function postUpdateTasks() {
    // We don't want to check for DB updates here, we do that once for all
    // updated modules on the landing page.
  }
}

/**
 * Class for updating themes using FileTransfer classes via authorize.php.
 */
class SiteThemeUpdater extends Updater implements DrupalUpdaterInterface {

  /**
   * @param string $directory
   * @return bool
   */
  static function canUpdateDirectory($directory) {
    // This is a lousy test, but don't know how else to confirm it is a theme.
    if (file_scan_directory($directory, '/.*\.module$/')) {
      return FALSE;
    }
    return TRUE;
  }

  /**
   * @param $project_name
   * @return bool
   */
  public static function canUpdate($project_name) {
    return (bool) drupal_get_path('theme', $project_name);
  }

  /**
   * Return the directory where a theme should be installed.
   *
   * If the theme is already installed, drupal_get_path() will return
   * a valid path and we should install it there (although we need to use an
   * absolute path, so we prepend DRUPAL_ROOT). If we're installing a new
   * theme, we always want it to go into sites/all/themes, since that's
   * where all the documentation recommends users install their themes, and
   * there's no way that can conflict on a multi-site installation, since
   * the Update manager won't let you install a new theme if it's already
   * found on your system, and if there was a copy in sites/all, we'd see it.
   *
   * @return string
   */
  public function getInstallDirectory() {
    if ($relative_path = drupal_get_path('theme', $this->name)) {
      $relative_path = dirname($relative_path);
    }
    else {
      $dir = 'all';
      $input = $GLOBALS['base_url'];
      // in case scheme relative URI is passed, e.g., //www.example.com/
      $input = trim($input, '/');

      // If scheme not included, prepend it
      if (!preg_match('#^http(s)?://#', $input)) {
        $input = 'http://' . $input;
      }

      $urlParts = parse_url($input);
      if (isset($urlParts['host']) && !empty($urlParts['host'])) {
        $dir = $urlParts['host'];
      }

      //Add on the path to the start of the URL it is here as we support paths for sites now
      if (isset($urlParts['path']) && !empty($urlParts['path']) && strlen($urlParts['path']) > 1) {
        $dir = str_replace('/','.',substr($urlParts['path'],1)) . '.' . $dir;
      }

      $relative_path = 'sites/' . $dir . '/themes';
    }
    return DRUPAL_ROOT . '/' . $relative_path;
  }

  /**
   * @return bool
   */
  public function isInstalled() {
    return (bool) drupal_get_path('theme', $this->name);
  }

  public function postInstall() {
    // Update the system table.
    clearstatcache();
    system_rebuild_theme_data();
  }

  /**
   * @return array
   */
  public function postInstallTasks() {
    return array(
      l(t('Enable newly added themes'), 'admin/appearance'),
      l(t('Administration pages'), 'admin')
    );
  }
}
