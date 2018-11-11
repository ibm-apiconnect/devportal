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

namespace Drupal\auth_apic;

/**
 * Representation of the parsed and validated activation token received as part
 * of various user flows (activate, register, reset pwd etc.)
 */
class JWTToken {

  private $url = NULL;
  private $decodedJwt = NULL;
  private $headers = NULL;
  private $payload = NULL;
  private $signature = NULL;

  /**
   * Set the value of Url
   *
   * @param mixed url
   *
   * @return self
   */
  public function setUrl($url) {
    $this->url = $url;

    return $this;
  }

  /**
   * Returns the url for this object
   *
   * @return string
   */
  public function getUrl(){
    return $this->url;
  }

  /**
   * Get the base64 decoded version of the JWT token
   *
   * @return null
   */
  public function getDecodedJwt() {
    return $this->decodedJwt;
  }

  /**
   * Set the base64 decoded version of the JWT token
   *
   * @param null $decodedJwt
   */
  public function setDecodedJwt($decodedJwt) {
    $this->decodedJwt = $decodedJwt;
  }

  /**
   * Get the headers portion of the JWT
   *
   * @return null
   */
  public function getHeaders() {
    return $this->headers;
  }

  /**
   * Set the headers portion of the JWT
   *
   * @param null $headers
   */
  public function setHeaders($headers) {
    $this->headers = $headers;
  }

  /**
   * Get the payload portion of the JWT
   *
   * @return null
   */
  public function getPayload() {
    return $this->payload;
  }

  /**
   * Set the payload section of the JWT
   *
   * @param null $payload
   */
  public function setPayload($payload) {
    $this->payload = $payload;
  }

  /**
   * Get the signature portion of the JWT
   *
   * @return null
   */
  public function getSignature() {
    return $this->signature;
  }

  /**
   * Set the signature section of the JWT
   *
   * @param null $signature
   */
  public function setSignature($signature) {
    $this->signature = $signature;
  }

}
