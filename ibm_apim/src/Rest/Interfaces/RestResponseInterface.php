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

namespace Drupal\ibm_apim\Rest\Interfaces;

/**
 * REST response from APIC Management Server apis.
 */
interface RestResponseInterface {

  /**
   * Set status code.
   *
   * @param $code
   */
  public function setCode($code): void;

  /**
   * Get status code.
   *
   * @return int
   *   Status code.
   */
  public function getCode(): ?int;

  /**
   * Set headers.
   *
   * @param $headers
   */
  public function setHeaders($headers): void;

  /**
   * Get headers.
   *
   * @return array
   *   HTTP headers
   */
  public function getHeaders(): ?array;

  /**
   * Set data.
   *
   * @param $data
   */
  public function setData($data): void;

  /**
   * Get HTTP response body.
   *
   * @return array
   *   HTTP response body.
   */
  public function getData(): ?array;

  /**
   * Set errors.
   *
   * @param $errors
   */
  public function setErrors($errors): void;

  /**
   * Get errors.
   *
   * @return array
   *   Errors.
   */
  public function getErrors(): ?array;

}
