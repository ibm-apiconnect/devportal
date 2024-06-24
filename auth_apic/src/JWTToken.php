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

namespace Drupal\auth_apic;

/**
 * Representation of the parsed and validated activation token received as part
 * of various user flows (activate, register, reset pwd etc.)
 */
class JWTToken {

  private $url;

  private $decodedJwt;

  private $headers;

  private $payload;

  private $signature;

  /**
   * Set the value of Url
   *
   * @param string url
   *
   * @return self
   */
  public function setUrl($url): self {
    $this->url = $url;

    return $this;
  }

  /**
   * Returns the url for this object
   *
   * @return null|string
   */
  public function getUrl(): ?string {
    return $this->url;
  }

  /**
   * Get the base64 decoded version of the JWT token
   *
   * @return mixed
   */
  public function getDecodedJwt() {
    return $this->decodedJwt;
  }

  /**
   * Set the base64 decoded version of the JWT token
   *
   * @param mixed $decodedJwt
   */
  public function setDecodedJwt($decodedJwt): void {
    $this->decodedJwt = $decodedJwt;
  }

  /**
   * Get the headers portion of the JWT
   *
   * @return mixed
   */
  public function getHeaders() {
    return $this->headers;
  }

  /**
   * Set the headers portion of the JWT
   *
   * @param mixed $headers
   */
  public function setHeaders($headers): void {
    $this->headers = $headers;
  }

  /**
   * Get the payload portion of the JWT
   *
   * @return mixed
   */
  public function getPayload() {
    return $this->payload;
  }

  /**
   * Set the payload section of the JWT
   *
   * @param mixed $payload
   */
  public function setPayload($payload): void {
    $this->payload = $payload;
  }

  /**
   * Get the signature portion of the JWT
   *
   * @return null|string
   */
  public function getSignature(): ?string {
    return $this->signature;
  }

  /**
   * Set the signature section of the JWT
   *
   * @param $signature
   */
  public function setSignature($signature): void {
    $this->signature = $signature;
  }

}
