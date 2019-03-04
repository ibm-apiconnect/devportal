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

namespace Drupal\ibm_apim\Rest;

use Drupal\ibm_apim\Rest\Interfaces\RestResponseInterface;

/**
 * REST response from APIC Management Server apis.
 */
class RestResponse implements RestResponseInterface {

  private $code;

  private $headers;

  private $data;

  private $errors;

  /**
   * Constructor.
   */
  public function __construct() {
  }

  /**
   * Set status code.
   *
   * @param $code
   */
  public function setCode($code): void {
    $this->code = $code;
  }

  /**
   * Get status code.
   *
   * @return int
   *   Status code.
   */
  public function getCode(): ?int {
    return $this->code;
  }

  /**
   * Set headers.
   *
   * @param $headers
   */
  public function setHeaders($headers): void {
    $this->headers = $headers;
  }

  /**
   * Get headers.
   *
   * @return array
   *   HTTP headers
   */
  public function getHeaders(): ?array {
    return $this->headers;
  }

  /**
   * Set data.
   *
   * @param $data
   */
  public function setData($data): void {
    $this->data = $data;
  }

  /**
   * Get HTTP response body.
   *
   * @return array
   *   HTTP response body.
   */
  public function getData(): ?array {
    return $this->data;
  }

  /**
   * Set errors.
   *
   * @param $errors
   */
  public function setErrors($errors): void {
    $this->errors = $errors;
  }

  /**
   * Get errors.
   *
   * @return array
   *   Errors.
   */
  public function getErrors(): ?array {
    return $this->errors;
  }

}
