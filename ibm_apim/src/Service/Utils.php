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
  function startsWith($haystack, $needle) {
    return $needle === "" || mb_strpos($haystack, $needle) === 0;
  }

  /**
   * @param $haystack
   * @param $needle
   *
   * @return bool
   */
  function endsWith($haystack, $needle) {
    return $needle === "" || mb_substr($haystack, -mb_strlen($needle)) === $needle;
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
  function truncate_string($string, $length = 191, $append = "…") {
    $string = trim($string);
    if (mb_strlen($string) > $length) {
      $string = mb_substr($string, 0, ($length - mb_strlen($append)));
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
  function convert_lang_name($lang_name) {
    if (isset($lang_name)) {
      if (strtolower($lang_name) == 'zh_hans' || strtolower($lang_name) == 'zh-hans') {
        $lang_name = 'zh-cn';
      }
      elseif (strtolower($lang_name) == 'zh_hant' || strtolower($lang_name) == 'zh-hant') {
        $lang_name = 'zh-tw';
      }
      $lang_name = str_replace('_', '-', $lang_name);
      return $lang_name;
    }
    else {
      return NULL;
    }
  }

  /**
   * Utility function to convert standard locale names APIM expects to drupal ones
   *
   * @param $lang_name
   *
   * @return null|string
   */
  function convert_lang_name_to_drupal($lang_name) {
    if (isset($lang_name)) {
      if (strtolower($lang_name) == 'zh-cn' || strtolower($lang_name) == 'zh_cn') {
        $lang_name = 'zh_hans';
      }
      elseif (strtolower($lang_name) == 'zh_tw' || strtolower($lang_name) == 'zh-tw') {
        $lang_name = 'zh_hant';
      }
      $lang_name = str_replace('-', '_', $lang_name);
      return $lang_name;
    }
    else {
      return NULL;
    }
  }

  /**
   * Custom Function to return random numbers.
   *
   * @param int $n
   *
   * @return int
   */
  function random_num($n = 5) {
    return rand(0, pow(10, $n));
  }

  /**
   * Base64 encode (URL safe)
   *
   * @param $input
   *
   * @return string
   */
  function base64_url_encode($input) {
    return strtr(base64_encode($input), '+/=', '-_,');
  }

  /**
   * Base64 decode (URL safe)
   *
   * @param $input
   *
   * @return string
   */
  function base64_url_decode($input) {
    return base64_decode(strtr($input, '-_,', '+/='));
  }

  /**
   * Returns a list of all the modules in the main shared modules directory (non-site specific)
   *
   * @return array
   */
  function get_bundled_modules() {
    return $this->get_bundled_content('modules');
  }

  /**
   * Returns a list of all the themes in the main shared modules directory (non-site specific)
   *
   * @return array
   */
  function get_bundled_themes() {
    return $this->get_bundled_content('themes');
  }

  /**
   * Returns a list of all the content of the specified type in the main shared modules directory (non-site specific)
   * e.g. pass 'themes' to get all the themes in drupal_root/themes or 'foo' to get drupal_root/foo content
   *
   * @return array
   */
  private function get_bundled_content($contentDirName) {
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
  function format_number_locale($number, $decimals = NULL, $dec_point = NULL, $thousands_sep = NULL) {
    if (is_null($decimals) || is_null($dec_point) || is_null($thousands_sep)) {
      $locale = localeconv();
      if (is_null($decimals)) {
        $decimals = $locale['int_frac_digits'];
      }
      if (is_null($dec_point)) {
        $dec_point = $locale['decimal_point'];
      }
      if (is_null($thousands_sep)) {
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
  function analytics_translations() {

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
}