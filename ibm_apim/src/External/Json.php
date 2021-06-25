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

namespace Drupal\ibm_apim\External;

/**
 * Class Json
 *
 * This class handles stripping invalid characters such as BOM out of a string before parsing it as json
 *
 * Based on https://gist.github.com/KrzysztofPrzygoda/2eecd1e6a477a9707f3876ca2693fdb8
 *
 * @package Drupal\ibm_apim\External
 */
abstract class Json {

  /**
   * @param bool $asString
   *
   * @return bool|int|mixed
   */
  public static function getLastError($asString = FALSE) {
    $lastError = \json_last_error();
    if (!$asString) {
      return $lastError;
    }
    // Define the errors.
    $constants = \get_defined_constants(TRUE);
    $errorStrings = [];
    foreach ($constants['json'] as $name => $value) {
      if (!strncmp($name, 'JSON_ERROR_', 11)) {
        $errorStrings[$value] = $name;
      }
    }
    return $errorStrings[$lastError] ?? FALSE;
  }

  /**
   * @return string
   */
  public static function getLastErrorMessage(): string {
    return \json_last_error_msg();
  }

  /**
   * @param $jsonString
   *
   * @return array|false|string|string[]
   */
  public static function clean($jsonString) {
    if (!is_string($jsonString) || !$jsonString) {
      return '';
    }
    // Remove unsupported characters
    // Check http://www.php.net/chr for details
    for ($i = 0; $i <= 31; ++$i) {
      $jsonString = str_replace(chr($i), '', $jsonString);
    }
    $jsonString = str_replace(chr(127), '', $jsonString);
    // Remove the BOM (Byte Order Mark)
    // It's the most common that some file begins with 'efbbbf' to mark the beginning of the file. (binary level)
    // Here we detect it and we remove it, basically it's the first 3 characters.
    if (0 === strpos(bin2hex($jsonString), 'efbbbf')) {
      $jsonString = substr($jsonString, 3);
    }
    return $jsonString;
  }

  /**
   * @param $value
   * @param int $options
   * @param int $depth
   *
   * @return false|string
   * @throws \JsonException
   */
  public static function encode($value, $options = 0, $depth = 512) {
    return \json_encode($value, JSON_THROW_ON_ERROR | $options, $depth);
  }

  /**
   * @param $jsonString
   * @param bool $asArray
   * @param int $depth
   * @param int $options
   *
   * @return mixed|null
   * @throws \JsonException
   */
  public static function decode($jsonString, $asArray = TRUE, $depth = 512, $options = JSON_BIGINT_AS_STRING) {
    if (!is_string($jsonString) || !$jsonString) {
      return NULL;
    }
    try {
      $result = \json_decode($jsonString, $asArray, $depth, JSON_THROW_ON_ERROR | $options);
    } catch (\JsonException $e) {
      // Try to clean json string if syntax error occurred
      $jsonString = self::clean($jsonString);
      $result = \json_decode($jsonString, $asArray, $depth, JSON_THROW_ON_ERROR | $options);
    }
    return $result;
  }

}
