<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2024
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
   * @param int|null $code
   */
  public function setCode(?int $code): void;

  /**
   * Get status code.
   *
   * @return int|null
   *   Status code.
   */
  public function getCode(): ?int;

  /**
   * Set headers.
   *
   * @param array|null $headers
   */
  public function setHeaders(?array $headers): void;

  /**
   * Get headers.
   *
   * @return array|null
   *   HTTP headers
   */
  public function getHeaders(): ?array;

  /**
   * Set data.
   *
   * @param array|null $data
   */
  public function setData(?array $data): void;

  /**
   * Get HTTP response body.
   *
   * @return array|null
   *   HTTP response body.
   */
  public function getData(): ?array;

  /**
   * Set errors.
   *
   * @param array|null $errors
   */
  public function setErrors(?array $errors): void;

  /**
   * Get errors.
   *
   * @return array|null
   *   Errors.
   */
  public function getErrors(): ?array;

}
