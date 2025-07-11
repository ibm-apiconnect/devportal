<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2025
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * @file
 * Generate an IBM API Connect sub-theme.
 */
namespace Drupal\themegenerator\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\themegenerator\Generator;

class ThemeGeneratorHooks {
  /**
   * Implements hook_cron().
   */
  #[Hook('cron')]
  public function cron() {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    $parentDir = PublicStream::basePath() . '/themegenerator/';
    if (file_exists($parentDir)) {
      $dir = new \DirectoryIterator($parentDir);
      foreach ($dir as $fileInfo) {
        if ($fileInfo->isDir() && !$fileInfo->isDot() && (time() - $fileInfo->getMTime() > 86400)) {
          Generator::delTree($fileInfo->getFilename());
        }
      }
    }

    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
 }