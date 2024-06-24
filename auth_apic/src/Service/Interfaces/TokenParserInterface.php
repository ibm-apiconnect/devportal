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

namespace Drupal\auth_apic\Service\Interfaces;

use Drupal\auth_apic\JWTToken;

/**
 * User activation token parse service.
 */
interface TokenParserInterface {

  /**
   * Parse activation token..
   *
   * @param $token
   *
   * @return \Drupal\auth_apic\JWTToken|null
   */
  public function parse($token): ?JWTToken;

}
