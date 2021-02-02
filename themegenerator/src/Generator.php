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

namespace Drupal\themegenerator;

use Drupal\Core\StreamWrapper\PublicStream;

/**
 * Class to generate custom sub-themes using the bundled stub
 */
class Generator {

  /**
   * @param null $name
   * @param string $type
   * @param string $template
   *
   * @return array|null
   */
  public static function generate($name = NULL, $type = 'css', $template = 'connect_theme'): ?array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, ['name' => $name, 'type' => $type, 'template' => $template]);

    if ($name !== NULL) {
      $name = trim($name);
      $tempDir = \Drupal::service('file_system')->getTempDirectory();
      if ($type !== 'scss') {
        $type = 'css';
      }
      // use sessionId_type as unique identifier
      $sessionId = \Drupal::service('session')->getId() . '_' . $type;
      $baseDir = $tempDir . '/themegenerator/' . $sessionId;
      $targetDir = DRUPAL_ROOT . '/' . $baseDir . '/' . $name;

      if (!is_dir(DRUPAL_ROOT . '/' . $tempDir . '/themegenerator')) {
        mkdir(DRUPAL_ROOT . '/' . $tempDir . '/themegenerator');
      }
      if (!is_dir(DRUPAL_ROOT . '/' . $tempDir . '/themegenerator/' . $sessionId)) {
        mkdir(DRUPAL_ROOT . '/' . $tempDir . '/themegenerator/' . $sessionId);
      }
      if (is_dir($targetDir)) {
        self::delTree($targetDir);
      }
      mkdir($targetDir);
      self::recursiveCopy(DRUPAL_ROOT . '/' . drupal_get_path('module', 'themegenerator') . '/stub/' . $type, $targetDir, $name);
      // handle the different overrides files in the other templates
      if ($template !== 'connect_theme') {
        $srcDir = DRUPAL_ROOT . '/' . drupal_get_path('module', 'themegenerator') . '/stub/templates/' . $template;
        if (is_dir($srcDir)) {
          $srcFile = $srcDir . '/overrides.' . $type;
          $tgtFile = $targetDir . '/' . $type . '/overrides.' . $type;
          copy($srcFile, $tgtFile);

          $srcFile = $srcDir . '/logo.svg';
          $tgtFile = $targetDir . '/logo.svg';
          copy($srcFile, $tgtFile);

          $srcFile = $srcDir . '/screenshot.png';
          $tgtFile = $targetDir . '/screenshot.png';
          copy($srcFile, $tgtFile);

        }
        else {
          \Drupal::logger('themegenerator')
            ->notice('Sub-theme @name specified an invalid template @template', [
              '@name' => $name,
              '@template' => $template,
            ]);
        }
      }

      self::editTheme(DRUPAL_ROOT . '/' . $baseDir, $name);

      // create output directory
      if (!is_dir(DRUPAL_ROOT . '/' . PublicStream::basePath() . '/themegenerator')) {
        mkdir(DRUPAL_ROOT . '/' . PublicStream::basePath() . '/themegenerator');
      }
      if (!is_dir(DRUPAL_ROOT . '/' . PublicStream::basePath() . '/themegenerator/' . $sessionId)) {
        mkdir(DRUPAL_ROOT . '/' . PublicStream::basePath() . '/themegenerator/' . $sessionId);
      }
      // create the zip in temp dir
      $zipName = self::createZip($baseDir, $name);

      // move zip to public dir
      $newZipName = PublicStream::basePath() . '/themegenerator/' . $sessionId . '/' . $name . '.zip';
      rename(DRUPAL_ROOT . '/' . $zipName, DRUPAL_ROOT . '/' . $newZipName);

      $theme = ['zipPath' => $newZipName];
      \Drupal::logger('themegenerator')->notice('Sub-theme @name generated', ['@name' => $name]);
    }
    else {
      $theme = NULL;
      \Drupal::logger('themegenerator')->error('Theme name not provided');
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $theme);
    return $theme;
  }

  /**
   * Util method to copy all the files in a given directory to a target
   *
   * @param $src (the source)
   * @param $dst (the destination)
   * @param $name (the theme name)
   */
  protected static function recursiveCopy($src, $dst, $name): void {
    $utils = \Drupal::service('ibm_apim.utils');
    $dir = opendir($src);
    while (FALSE !== ($file = readdir($dir))) {
      if (($file !== '.') && ($file !== '..')) {
        if (is_dir($src . '/' . $file)) {
          if (!is_dir($dst . '/' . $file)) {
            mkdir($dst . '/' . $file);
          }
          self::recursiveCopy($src . '/' . $file, $dst . '/' . $file, $name);
        }
        else {
          // rename any stub.* files in the process
          $targetFile = str_replace('stub', $name, $file);
          // remove trailing '.dummy' from the name (purely done to avoid drupal trying to install the stub)
          if ($utils->endsWith($targetFile, '.dummy')) {
            $targetFile = preg_replace('/\.dummy$/', '', $targetFile);
          }
          copy($src . '/' . $file, $dst . '/' . $targetFile);
        }
      }
    }
    closedir($dir);
  }

  /**
   * Edit the files as needed
   *
   * @param $dir
   * @param $name
   */
  protected static function editTheme($dir, $name): void {
    // edit theme.info.yml
    $infoFile = $dir . '/' . $name . '/' . $name . '.info.yml';
    $fileContents = file_get_contents($infoFile);
    $fileContents = str_replace('stub', $name, $fileContents);
    file_put_contents($infoFile, $fileContents);
    // edit composer.json
    $composerFile = $dir . '/' . $name . '/composer.json';
    $fileContents = file_get_contents($composerFile);
    $fileContents = str_replace('stub', $name, $fileContents);
    file_put_contents($composerFile, $fileContents);
    // edit .theme
    $themeFile = $dir . '/' . $name . '/' . $name . '.theme';
    $fileContents = file_get_contents($themeFile);
    $fileContents = str_replace('stub', $name, $fileContents);
    file_put_contents($themeFile, $fileContents);
    //install file
    $installFile = $dir . '/' . $name . '/' . $name . '.install';
    if (file_exists($installFile)) {
      $fileContents = file_get_contents($installFile);
      $fileContents = str_replace('stub', $name, $fileContents);
      file_put_contents($installFile, $fileContents);
    }
  }

  /**
   * Zip up the target directory and then delete its contents leaving only the zip
   *
   * @param $dir
   * @param $zipFileName
   *
   * @return string
   */
  protected static function createZip($dir, $zipFileName) :string{
    $rootPath = realpath(DRUPAL_ROOT . '/' . $dir);

    $zipName = $dir . '/' . $zipFileName . '.zip';
    $zip = new \ZipArchive();
    $zip->open(DRUPAL_ROOT . '/' . $zipName, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

    $filesToDelete = [];

    /** @var SplFileInfo[] $files */
    $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($rootPath), \RecursiveIteratorIterator::LEAVES_ONLY);

    foreach ($files as $name => $file) {
      // Skip directories (they would be added automatically)
      if (!$file->isDir()) {
        $filePath = $file->getRealPath();
        $relativePath = substr($filePath, \strlen($rootPath) + 1);

        $zip->addFile($filePath, $relativePath);

        // Add current file to "delete list"
        // delete it later because ZipArchive creates the archive only after calling close function)
        $filesToDelete[] = $filePath;
      }
    }

    $zip->close();

    // Delete all files afterwards
    foreach ($filesToDelete as $file) {
      unlink($file);
    }
    return $zipName;
  }

  /**
   * Util method to recursively delete a directory
   *
   * @param null $dir
   *
   * @return bool|null
   */
  public static function delTree($dir = NULL) : ?bool{
    if ($dir !== null) {
      $files = array_diff(scandir($dir, SCANDIR_SORT_NONE), ['.', '..']);
      foreach ($files as $file) {
        is_dir("$dir/$file") ? self::delTree("$dir/$file") : unlink("$dir/$file");
      }
      return rmdir($dir);
    }
    return NULL;
  }
}
