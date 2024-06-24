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

namespace Drupal\ibm_apim\Updater;

use Drupal\Core\Updater\Theme;
use Drupal\Core\Updater\UpdaterException;
use Drupal\Core\Url;

/**
 * Defines a class for updating themes using
 * Drupal\Core\FileTransfer\FileTransfer classes via authorize.php.
 */
class ApicTheme extends Theme {

  /**
   * @var string
   */
  public string $title = '';

  /**
   * ApicTheme constructor.
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
        throw new UpdaterException(t('The file (%file) contains APIC source code. This is not permitted. All method names must be unique. To modify current behavior use drupal module hooks in custom modules, see: https://www.ibm.com/docs/en/SSMNED_10.0.8?topic=extend-custom-module-development-background-prerequisites', ['%file' => $file->uri]));
      }
    }

    return $info['name'] ?? '';
  }

  /**
   * Check to ensure no APIC source code is within this module / theme
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
   * Returns the directory where a theme should be installed.
   *
   * If the theme is already installed, \Drupal::service('extension.list.theme')->getPath() will return a valid
   * path and we should install it there. If we're installing a new theme, we
   * always want it to go into /themes, since that's where all the
   * documentation recommends users install their themes, and there's no way
   * that can conflict on a multi-site installation, since the Update manager
   * won't let you install a new theme if it's already found on your system,
   * and if there was a copy in the top-level we'd see it.
   *
   * @return string
   *   The absolute path of the directory.
   */
  public function getInstallDirectory(): ?string {
    if ($this->isInstalled() && ($relative_path = \Drupal::service('extension.list.theme')->getPath($this->name))) {
      // The return value of \Drupal::service('extension.list.theme')->getPath() is always relative to the site,
      // so prepend DRUPAL_ROOT.
      $returnValue = DRUPAL_ROOT . '/' . dirname($relative_path);
    }
    else {
      // When installing a new theme, prepend the requested root directory.
      $returnValue = $this->root . '/' . self::getRootDirectoryRelativePath();
    }
    return $returnValue;
  }

  /**
   * {@inheritdoc}
   */
  public static function getRootDirectoryRelativePath(): string {
    return \Drupal::getContainer()->getParameter('site.path') . '/themes';
  }

  /**
   * {@inheritdoc}
   * @throws \Drupal\Core\Updater\UpdaterException
   */
  public static function canUpdateDirectory($directory): bool {
    $info = static::getExtensionInfo($directory);

    return (isset($info['type']) && $info['type'] === 'theme');
  }

  /**
   * {@inheritdoc}
   */
  public function postInstallTasks(): array {
    // Since this is being called outside of the primary front controller,
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
        '#url' => Url::fromRoute('system.themes_page'),
        '#title' => t('Enable newly added themes'),
      ],
      $default_options + [
        '#url' => Url::fromRoute('system.admin'),
        '#title' => t('Administration pages'),
      ],
    ];
  }

}
