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

namespace Drupal\ibm_apim;

interface ApicRestInterface {

  /**
   * A helper function to use when submitting an IBM API GET call
   *
   * @param string $url
   *          The IBM APIm API URL
   *
   * @param string $auth
   *          The authorization string to use, the default is the current user
   *
   * @param bool $gettingConfig
   *          Getting /portal/config or not
   *
   * @param bool $messageErrors
   *          Log errors or not
   *
   * @param bool $returnResult
   *          return entire result object or just data
   *
   * @return null|\stdClass
   *
   * @see _ibm_apim_call_base()
   */
  public static function get(string $url, $auth = 'user', $gettingConfig = FALSE, $messageErrors = TRUE, $returnResult = FALSE): ?\stdClass;

  /**
   * A helper function to use when submitting an IBM API POST call
   *
   * @param string $url
   *          The IBM APIm API URL
   *
   * @param string $data
   *          A string containing the JSON data to submit to the IBM API
   *
   * @param string $auth
   *          The authorization string to use, the default is the current user
   *
   * @param bool $messageErrors
   *          If you need to hide the message errors on a post, set to FALSE
   *
   * @return null|\stdClass
   *
   * @see _ibm_apim_call_base()
   */
  public static function post(string $url, string $data, $auth = 'user', $messageErrors = TRUE): ?\stdClass;

  /**
   * A helper function to use when submitting an IBM API PUT call
   *
   * @param string $url
   *          The IBM APIm API URL
   *
   * @param string $data
   *          A string containing the JSON data to submit to the IBM API
   *
   * @param string $auth
   *          The authorization string to use, the default is the current user
   *
   * @return null|\stdClass
   *
   * @see _ibm_apim_call_base()
   */
  public static function put(string $url, string $data, $auth = 'user'): ?\stdClass;

  /**
   * A helper function to use when submitting an IBM API PATCH call
   *
   * @param string $url
   *          The IBM APIm API URL
   *
   * @param string $data
   *          A string containing the JSON data to submit to the IBM API
   *
   * @param string $auth
   *          The authorization string to use, the default is the current user
   *
   * @return null|\stdClass
   *
   * @see _ibm_apim_call_base()
   */
  public static function patch(string $url, string $data, $auth = 'user'): ?\stdClass;

  /**
   * A helper function to use when submitting an IBM API DELETE call
   *
   * @param string $url
   *          The IBM APIm API URL
   *
   * @param string $auth
   *          The authorization string to use, the default is the current user
   *
   * @return null|\stdClass
   *         Note that DELETE calls usually return nothing in which this function
   *         will return an empty string.
   *
   * @see _ibm_apim_call_base()
   */
  public static function delete(string $url, $auth = 'user'): ?\stdClass;

  /**
   * A helper function to use when submitting an IBM API GET call to get RAW data
   * e.g. when downloading a WSDL or binary file
   *
   * @param string $url
   *          The IBM APIm API URL
   *
   * @param string $auth
   *          The authorization string to use, the default is the current user
   *
   * @param bool $gettingConfig
   *          Getting /portal/config or not
   *
   * @param bool $messageErrors
   *          Log errors or not
   *
   * @param bool $returnResult
   *          return entire result object or just data
   *
   * @return null|\stdClass
   *
   * @see _ibm_apim_call_base()
   */
  public static function raw(string $url, $auth = 'user', $gettingConfig = FALSE, $messageErrors = FALSE, $returnResult = TRUE): ?\stdClass;


  /**
   * Used when proxying requests to the management node, for example analytics or downloading files
   *
   * @param string $url
   * @param string $verb
   * @param null $node
   * @param bool $filter
   * @param null $data
   * @param null $extraHeaders
   *
   * @return mixed
   */
  public static function proxy(string $url, $verb = 'GET', $node = NULL, $filter = FALSE, $data = NULL, $extraHeaders = NULL);

}