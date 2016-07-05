<?php

namespace Drupal\drupal_helpers;

/**
 * Class Random.
 *
 * @package Drupal\drupal_helpers
 */
class Random {

  /**
   * Generate a random string containing letters.
   *
   * The string will always start with a letter. The letters may be upper or
   * lower case. This method is better for restricted inputs that do not
   * accept certain characters. For example, when testing input fields that
   * require machine readable values (i.e. without spaces and non-standard
   * characters) this method is best.
   *
   * Do not use this method when testing unvalidated user input. Instead, use
   * DrupalWebTestCase::randomString().
   *
   * @param int $length
   *   Length of random string to generate.
   *
   * @return string
   *   Randomly generated string.
   *
   * @see DrupalWebTestCase::randomString()
   */
  public static function name($length = 8) {
    $values = array_merge(range(65, 90), range(97, 122));
    $max = count($values) - 1;
    $str = chr(mt_rand(97, 122));
    for ($i = 1; $i < $length; $i++) {
      $str .= chr($values[mt_rand(0, $max)]);
    }

    return $str;
  }

  /**
   * Generates a random string of ASCII characters of codes 32 to 126.
   *
   * The generated string includes alpha-numeric characters and common
   * miscellaneous characters. Use this method when testing general input
   * where the content is not restricted.
   *
   * Do not use this method when special characters are not possible (e.g., in
   * machine or file names that have already been validated); instead,
   * use DrupalWebTestCase::randomName().
   *
   * @param int $length
   *   Length of random string to generate.
   *
   * @return string
   *   Randomly generated string.
   *
   * @see DrupalWebTestCase::randomName()
   */
  public static function string($length = 8) {
    $str = '';
    for ($i = 0; $i < $length; $i++) {
      $str .= chr(mt_rand(32, 126));
    }

    return $str;
  }

  /**
   * Helper to get random ip address.
   */
  public static function ip() {
    return long2ip(rand(0, 4294967295));
  }

  /**
   * Return random phone number according to specified format.
   *
   * @param string $format
   *   Format string as a sequence of numeric and non-numeric characters.
   *   Defaults to '00 0000 0000'. Number will be generated based on number of
   *   numeric characters.
   *
   * @return string
   *   Random phone number formatted according to provided format or FALSE if
   *   provided format is invalid.
   */
  public static function phone($format = '00 0000 0000') {
    $result = '';

    // Count all numeric characters.
    $count = preg_match_all("/[0-9]/", $format, $matches);
    if ($count === FALSE) {
      return '';
    }

    // Generate random number.
    $phone = rand(pow(10, $count), pow(10, $count + 1) - 1);

    $fpos = 0;
    $spos = 0;
    while ($fpos <= (strlen($format) - 1)) {
      $c = substr($format, $fpos, 1);
      if ($c == '\\') {
        $fpos++;
        $c = substr($format, $fpos, 1);
        $result .= $c;
        $spos++;
      }
      elseif (ctype_digit($c) || ctype_alpha($c)) {
        $result .= substr($phone, $spos, 1);
        $spos++;
      }
      else {
        $result .= substr($format, $fpos, 1);
      }
      $fpos++;
    }

    return $result;
  }

  /**
   * Return random email according to test accounts naming schema.
   *
   * Always use this method when creating test accounts.
   *
   * @param string $domain
   *   Optional email domain. Defaults to 'example.com'.
   *
   * @return string Random email address.
   *   Random email address.
   */
  public static function email($domain = 'example.com') {
    return self::name() . '@' . $domain;
  }

  /**
   * Return random DOB.
   *
   * Always use this method when creating test accounts.
   *
   * @param string $format
   *   Date format to return result. Defaults to year ('Y').
   * @param int $min
   *   Minimum age in years. Defaults to 18.
   * @param int $max
   *   Maximum age in years. Defaults to 80.
   *
   * @return string
   *   Random date of birth.
   */
  public static function dob($format = 'Y', $min = 18, $max = 80) {
    $start = mktime(NULL, NULL, NULL, NULL, NULL, date('Y') - $max);
    $end = mktime(NULL, NULL, NULL, NULL, NULL, date('Y') - $min);

    return date($format, mt_rand($start, $end));
  }

  /**
   * Helper function to generate random path.
   *
   * @param string $path
   *   Optional path containing placeholders (% or %name) to be replaced.
   *
   * @return string
   *   Generated path.
   */
  public static function path($path = NULL) {
    if ($path === NULL) {
      return self::name(16);
    }

    // Handle slashes.
    // Handle %placeholders.
    $replacements = array_map([__CLASS__, 'name'], array_fill(0, 20, 10));
    $path = preg_replace(['/(%[^\/]*)/i'], $replacements, $path);

    return $path;
  }

  /**
   * Helper to get random array items.
   */
  public static function arrayItems($haystack, $count = 1) {
    $haystack_keys = array_keys($haystack);
    shuffle($haystack_keys);
    $haystack_keys = array_slice($haystack_keys, 0, $count);

    return array_intersect_key($haystack, array_flip($haystack_keys));
  }

}
