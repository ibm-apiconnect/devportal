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

namespace Drupal\auth_apic\Rest;

use Drupal\ibm_apim\Rest\RestResponse;

/**
 * Response to GET /token.
 */
class TokenResponse extends RestResponse {

  private $bearer_token;

  /**
   * Token Response constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * @return mixed
   */
  public function getBearerToken() {
    return $this->bearer_token;
  }

  /**
   * @param mixed $bearer_token
   */
  public function setBearerToken($bearer_token) {
    $this->bearer_token = $bearer_token;
  }

}
