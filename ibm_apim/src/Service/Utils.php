<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2019
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Service;

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
   * @return array|string
   */
  public function truncate_string($string, $length = 191, $append = '…') {
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

    $number = number_format($number, $decimals, $dec_point, $thousands_sep);
    return $number;
  }

  /**
   * Used to provide common translations to analytics in both myorg and applications
   *
   * @return array
   */
  public function analytics_translations(): array {

    $translations = [
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
    return $translations;
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

}
