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

namespace Drupal\ibm_apim\Service;

use DirectoryIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

/**
 * Miscellaneous utility functions.
 */
class Utils {

  public function __construct() {
  }

  /**
   * @param $haystack
   * @param $needle
   *
   * @return bool
   */
  public function startsWith($haystack, $needle): bool {
    return $needle === '' || mb_strpos($haystack, $needle) === 0;
  }

  /**
   * @param $haystack
   * @param $needle
   *
   * @return bool
   */
  public function endsWith($haystack, $needle): bool {
    return $needle === '' || mb_substr($haystack, -mb_strlen($needle)) === $needle;
  }

  /**
   * Utility method to truncate a string at a specified length and append an ellipsis
   *
   * @param $string
   * @param int $length
   * @param string $append
   *
   * @return string
   */
  public function truncate_string($string, $length = 191, $append = '…'): string {
    $string = trim($string);
    if (mb_strlen($string) > $length) {
      $string = mb_substr($string, 0, $length - mb_strlen($append));
      $string = trim($string) . $append;
    }
    return $string;
  }

  /**
   * Utility function to convert drupal locale names to standard ones APIM expects
   *
   * @param $lang_name
   *
   * @return null|string
   */
  public function convert_lang_name($lang_name): ?string {
    $returnValue = NULL;
    if (isset($lang_name)) {
      $langNameLower = strtolower($lang_name);
      if ($langNameLower === 'zh_hans' || $langNameLower === 'zh-hans') {
        $lang_name = 'zh-cn';
      }
      elseif ($langNameLower === 'zh_hant' || $langNameLower === 'zh-hant') {
        $lang_name = 'zh-tw';
      }
      $returnValue = str_replace('_', '-', $lang_name);
    }
    return $returnValue;
  }

  /**
   * Utility function to convert standard locale names APIM expects to drupal ones
   *
   * @param $lang_name
   *
   * @return null|string
   */
  public function convert_lang_name_to_drupal($lang_name): ?string {
    $returnValue = NULL;
    if (isset($lang_name)) {
      $langNameLower = strtolower($lang_name);
      if ($langNameLower === 'zh-cn' || $langNameLower === 'zh_cn') {
        $lang_name = 'zh_hans';
      }
      elseif ($langNameLower === 'zh_tw' || $langNameLower === 'zh-tw') {
        $lang_name = 'zh_hant';
      }
      $returnValue = str_replace('-', '_', $lang_name);
    }
    return $returnValue;
  }

  /**
   * Custom Function to return random numbers.
   *
   * @param int $n
   *
   * @return int
   * @throws \Exception
   */
  public function random_num($n = 5): int {
    return random_int(0, 10 ** $n);
  }

  /**
   * Base64 encode (URL safe)
   *
   * @param $input
   *
   * @return string|null
   */
  public function base64_url_encode($input): ?string {
    return strtr(base64_encode($input), '+/=', '-_,');
  }

  /**
   * Base64 decode (URL safe)
   *
   * @param $input
   *
   * @return string|null
   */
  public function base64_url_decode($input): ?string {
    return base64_decode(strtr($input, '-_,', '+/='));
  }

  /**
   * Returns a list of all the modules in the main shared modules directory (non-site specific)
   *
   * @return array
   */
  public function get_bundled_modules(): array {
    return $this->get_bundled_content('modules');
  }

  /**
   * Returns a list of all the themes in the main shared modules directory (non-site specific)
   *
   * @return array
   */
  public function get_bundled_themes(): array {
    return $this->get_bundled_content('themes');
  }

  /**
   * Returns a list of all the content of the specified type in the main shared modules directory (non-site specific)
   * e.g. pass 'themes' to get all the themes in drupal_root/themes or 'foo' to get drupal_root/foo content
   *
   * @param $contentDirName
   *
   * @return array
   */
  private function get_bundled_content($contentDirName): array {
    $content_dir = DRUPAL_ROOT . '/' . $contentDirName;
    $dirs = array_filter(glob($content_dir . '/*', GLOB_ONLYDIR), 'is_dir');
    $content_list = [];
    foreach ($dirs as $dir) {
      $parts = pathinfo($dir);
      $content_list[] = $parts['basename'];
    }
    return $content_list;
  }

  /**
   * Localised numbers
   *
   * @param $number
   * @param null $decimals
   * @param null $dec_point
   * @param null $thousands_sep
   *
   * @return string
   */
  public function format_number_locale($number, $decimals = NULL, $dec_point = NULL, $thousands_sep = NULL): string {
    if ($decimals === NULL || $dec_point === NULL || $thousands_sep === NULL) {
      $locale = localeconv();
      if ($decimals === NULL) {
        $decimals = $locale['int_frac_digits'];
      }
      if ($dec_point === NULL) {
        $dec_point = $locale['decimal_point'];
      }
      if ($thousands_sep === NULL) {
        $thousands_sep = $locale['thousands_sep'];
      }
    }
    if ($decimals > 4) {
      $decimals = 4;
    }

    return number_format($number, $decimals, $dec_point, $thousands_sep);
  }

  /**
   * Used to provide common translations to analytics in both myorg and applications
   *
   * @return array
   */
  public function analytics_translations(): array {

    return [
      'api_calls' => t('API Calls'),
      'api_stats' => t('API Stats'),
      'subscriptions' => t('Subscriptions'),
      'response_time' => t('Response Time'),
      'average_response_time' => t('Average Response Time'),
      'total_errors' => t('Total Errors'),
      'total_calls' => t('Total Calls'),
      'calls_last_100' => t('API Calls (Last 100)'),
      'errors_last_100' => t('Errors (Last 100)'),
      '30s' => t('30s'),
      '1m' => t('1m'),
      '30m' => t('30m'),
      '1h' => t('1h'),
      '1d' => t('1d'),
      '7d' => t('7d'),
      '30d' => t('30d'),
      'last_30days' => t('Last 30 days'),
      'calls' => t('calls'),
      'errors' => t('errors'),
      '30secs' => t('30 secs'),
      '1min' => t('1 min'),
      '30mins' => t('30 mins'),
      '1hr' => t('1 hr'),
      '1day' => t('1 day'),
      '7days' => t('7 days'),
      '30days' => t('30 days'),
    ];
  }

  /**
   * Recursively delete a directory
   *
   * @param $path
   */
  public function file_delete_recursive($path): void {
    if (isset($path)) {
      if (is_dir($path)) { // Path is directory
        $files = scandir($path, SCANDIR_SORT_NONE);
        foreach ($files as $file) {
          if ($file !== '.' && $file !== '..') {
            $this->file_delete_recursive($path . '/' . $file); // Recursive call
          }
        }
        rmdir($path);
      }
      else {
        unlink($path); // Delete the file
      }
    }
  }

  /**
   * List the directories under <site path>/themes
   *
   * @return array directory names (= custom theme names)
   */
  public function getCustomThemeDirectories(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $custom_modules = [];
    $sitePath = \Drupal::service('site.path');
    $dir = new DirectoryIterator($sitePath . '/themes');
    foreach ($dir as $fileinfo) {
      if ($fileinfo->isDir() && !$fileinfo->isDot()) {
        $custom_modules[] = $fileinfo->getFilename();
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $custom_modules);
    return $custom_modules;
  }

  /**
   * List the directories under <site path>/modules
   *
   * @return array directory names (= custom module names)
   */
  public function getCustomModuleDirectories(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $custom_modules = [];
    $sitePath = \Drupal::service('site.path');
    $dir = new DirectoryIterator($sitePath . '/modules');
    foreach ($dir as $fileinfo) {
      if ($fileinfo->isDir() && !$fileinfo->isDot()) {
        $custom_modules[] = $fileinfo->getFilename();
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $custom_modules);
    return $custom_modules;
  }

  /**
   * Return information about all of the modules which are eligible for deletion.
   *
   * Custom modules which are listed satisfy the following criteria:
   *  - are installed in <site_dir>
   *  - have a valid info.yml file
   *  - are not marked as hidden in the info.yml
   *  - don't have any enabled submodules.
   *
   * @return array options to pass into tableselect
   */
  public function getDisabledCustomModules(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $custom_module_dirs = $this->getCustomModuleDirectories();

    $uninstall_list = [];
    $sitePath = \Drupal::service('site.path');
    $moduleHandler = \Drupal::service('module_handler');

    foreach ($custom_module_dirs as $cm) {
      if ($moduleHandler->moduleExists($cm)) {
        \Drupal::logger('ibm_apim')->debug('getDisabledCustomModules: %cm is enabled so not listed.', ['%cm' => $cm]);
      }
      else {

        $info_yml = DRUPAL_ROOT . '/' . $sitePath . '/modules' . '/' . $cm . '/' . $cm . '.info.yml';

        if (file_exists($info_yml) && !$this->isHidden($info_yml)) {

          $submodules = $this->getSubModules($cm);
          $enabled_submodule = FALSE;

          foreach ($submodules as $sm) {
            if ($moduleHandler->moduleExists($sm)) {
              \Drupal::logger('ibm_apim')
                ->info('getDisabledCustomModules: not listing %cm as sub-module %sm is still enabled', ['%cm' => $cm, '%sm' => $sm]);
              $enabled_submodule = TRUE;
            }
          }

          if (!$enabled_submodule) {
            if (empty($submodules)) {
              $info_msg = t('No sub-modules found.');
            }
            else {
              $info_msg = t('The following sub-modules will also be deleted: %modules', ['%modules' => \implode(', ', $submodules)]);
            }

            $module = [
              'module' => \yaml_parse_file($info_yml)['name'],
              'info' => $info_msg,
            ];
            $uninstall_list[$cm] = $module;
          }
        }
        else {
          \Drupal::logger('ibm_apim')
            ->debug('getDisabledCustomModules: info.yml not found or module marked as hidden for %cm.', ['%cm' => $cm]);
        }

      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $uninstall_list;

  }

  /**
   * Check hidden property in info.yml file.
   * TODO: honour `$settings['extension_discovery_scan_tests'] = TRUE`
   *      (see https://www.drupal.org/docs/8/creating-custom-modules/let-drupal-8-know-about-your-module-with-an-infoyml-file)
   *
   * @param $info_yml - full path to info.yml
   *
   * @return bool is hidden?
   */
  public function isHidden($info_yml): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $info_yml);
    $info = \yaml_parse_file($info_yml);
    $hidden = FALSE;
    if (\array_key_exists('hidden', $info) && $info['hidden'] !== NULL && (boolean) $info['hidden'] === TRUE) {
      \Drupal::logger('ibm_apim')->debug('isHidden: module marked as hidden in %info_yml', ['%info_yml' => basename($info_yml)]);
      $hidden = TRUE;
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $hidden);
    return $hidden;
  }

  /**
   * Search for info.yml files in sub directories under a custom module.
   * Exclude hidden modules from the returned list.
   *
   * @param string $parent custom module name.
   *
   * @return array
   */
  public function getSubModules(string $parent): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $parent);
    $subs = [];
    $sitePath = \Drupal::service('site.path');
    $dir = new RecursiveDirectoryIterator($sitePath . '/modules/' . $parent);
    $ite = new RecursiveIteratorIterator($dir);
    $files = new RegexIterator($ite, '/^.+\.info.yml$/i', RegexIterator::GET_MATCH);

    foreach ($files as $file) {
      $info_yml_full_path = DRUPAL_ROOT . '/' . \array_shift($file);

      if (!$this->isHidden($info_yml_full_path)) {
        $info_yml = \basename($info_yml_full_path);
        $module_name = \substr($info_yml, 0, -\strlen('.info.yml'));
        if ($module_name !== $parent) {
          $subs[] = $module_name;
        }
      }

    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $subs);
    return $subs;

  }

}
