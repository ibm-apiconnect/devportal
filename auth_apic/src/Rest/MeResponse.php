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


use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Rest\RestResponse;

/**
 * Response to GET /me?expanded=true.
 */
class MeResponse extends RestResponse {

  private $user;

  /**
   * Me Response constructor.
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * @param ApicUser $user
   */
  public function setUser(ApicUser $user) {
    $this->user = $user;
  }


  /**
   * @return ApicUser
   */
  public function getUser() {
    return $this->user;
  }

}
