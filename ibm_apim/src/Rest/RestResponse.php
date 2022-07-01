<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2022
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

  /**
   * @var int|null
   */
  private ?int $code = NULL;

  /**
   * @var array|null
   */
  private ?array $headers = NULL;

  /**
   * @var array|null
   */
  private ?array $data = NULL;

  /**
   * @var array|null
   */
  private ?array $errors = NULL;

  /**
   * Constructor.
   */
  public function __construct() {
  }

  /**
   * Set status code.
   *
   * @param int|null $code
   */
  public function setCode(?int $code): void {
    $this->code = $code;
  }

  /**
   * Get status code.
   *
   * @return int|null
   *   Status code.
   */
  public function getCode(): ?int {
    return $this->code;
  }

  /**
   * Set headers.
   *
   * @param array|null $headers
   */
  public function setHeaders(?array $headers): void {
    $this->headers = $headers;
  }

  /**
   * Get headers.
   *
   * @return array|null
   *   HTTP headers
   */
  public function getHeaders(): ?array {
    return $this->headers;
  }

  /**
   * Set data.
   *
   * @param array|null $data
   */
  public function setData(?array $data): void {
    $this->data = $data;
  }

  /**
   * Get HTTP response body.
   *
   * @return array|null
   *   HTTP response body.
   */
  public function getData(): ?array {
    return $this->data;
  }

  /**
   * Set errors.
   *
   * @param array|null $errors
   */
  public function setErrors(?array $errors): void {
    $this->errors = $errors;
  }

  /**
   * Get errors.
   *
   * @return array|null
   *   Errors.
   */
  public function getErrors(): ?array {
    return $this->errors;
  }

}
