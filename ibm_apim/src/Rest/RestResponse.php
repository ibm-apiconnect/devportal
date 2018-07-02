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

namespace Drupal\ibm_apim\Rest;

/**
 * REST response from APIC Management Server apis.
 */
class RestResponse {

  private $code = NULL;
  private $headers = NULL;
  private $data = NULL;
  private $errors = NULL;

  /**
   * Constructor.
   */
  public function __construct() {
  }

  /**
   * Set status code.
   */
  public function setCode($code) {
    $this->code = $code;
  }

  /**
   * Get status code.
   *
   * @return int
   *   Status code.
   */
  public function getCode() {
    return $this->code;
  }

  /**
   * Set headers.
   */
  public function setHeaders($headers) {
    $this->headers = $headers;
  }

  /**
   * Get headers.
   *
   * @return array
   *   HTTP headers
   */
  public function getHeaders() {
    return $this->headers;
  }

  /**
   * Set data.
   */
  public function setData($data) {
    $this->data = $data;
  }

  /**
   * Get HTTP response body.
   *
   * @return array
   *   HTTP response body.
   */
  public function getData() {
    return $this->data;
  }

  /**
   * Set errors.
   */
  public function setErrors($errors) {
    $this->errors = $errors;
  }

  /**
   * Get errors.
   *
   * @return array
   *   Errors.
   */
  public function getErrors() {
    return $this->errors;
  }

}
