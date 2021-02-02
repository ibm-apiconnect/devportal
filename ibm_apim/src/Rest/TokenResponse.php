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

namespace Drupal\ibm_apim\Rest;

/**
 * Response to GET /token.
 */
class TokenResponse extends RestResponse {

  private $bearer_token;

  private $expires_in;

  private $refresh_token;

  private $refresh_expires_in;

  /**
   * @return mixed
   */
  public function getBearerToken() {
    return $this->bearer_token;
  }

  /**
   * @param mixed $bearer_token
   */
  public function setBearerToken($bearer_token): void {
    $this->bearer_token = $bearer_token;
  }

  /**
   * @return mixed
   */
  public function getExpiresIn() {
    return $this->expires_in;
  }

  /**
   * @param mixed $expires_in
   */
  public function setExpiresIn($expires_in): void {
    $this->expires_in = $expires_in;
  }

  /**
   * @return mixed
   */
  public function getRefreshToken() {
    return $this->refresh_token;
  }

  /**
   * @param mixed $refresh_token
   */
  public function setRefreshToken($refresh_token): void {
    $this->refresh_token = $refresh_token;
  }

   /**
   * @return mixed
   */
  public function getRefreshExpiresIn() {
    return $this->refresh_expires_in;
  }

  /**
   * @param mixed $refresh_expires_in
   */
  public function setRefreshExpiresIn($refresh_expires_in): void {
    $this->refresh_expires_in = $refresh_expires_in;
  }
  
}
