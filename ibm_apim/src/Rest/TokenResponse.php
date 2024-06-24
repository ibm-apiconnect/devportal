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

namespace Drupal\ibm_apim\Rest;

/**
 * Response to GET /token.
 */
class TokenResponse extends RestResponse {

  /**
   * @var string|null
   */
  private ?string $bearer_token = NULL;

  /**
   * @var int|null
   */
  private ?int $expires_in = NULL;

  /**
   * @var string|null
   */
  private ?string $refresh_token = NULL;

  /**
   * @var int|null
   */
  private ?int $refresh_expires_in = NULL;

  /**
   * @return string|null
   */
  public function getBearerToken(): ?string {
    return $this->bearer_token;
  }

  /**
   * @param string|null $bearer_token
   */
  public function setBearerToken(?string $bearer_token): void {
    $this->bearer_token = $bearer_token;
  }

  /**
   * @return int|null
   */
  public function getExpiresIn(): ?int {
    return $this->expires_in;
  }

  /**
   * @param int|null $expires_in
   */
  public function setExpiresIn(?int $expires_in): void {
    $this->expires_in = $expires_in;
  }

  /**
   * @return string|null
   */
  public function getRefreshToken(): ?string {
    return $this->refresh_token;
  }

  /**
   * @param string|null $refresh_token
   */
  public function setRefreshToken(?string $refresh_token): void {
    $this->refresh_token = $refresh_token;
  }

  /**
   * @return int|null
   */
  public function getRefreshExpiresIn(): ?int {
    return $this->refresh_expires_in;
  }

  /**
   * @param int|null $refresh_expires_in
   */
  public function setRefreshExpiresIn(?int $refresh_expires_in): void {
    $this->refresh_expires_in = $refresh_expires_in;
  }

}
