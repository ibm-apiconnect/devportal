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

namespace Drupal\auth_apic\UserManagement;


use Drupal\auth_apic\UserManagerResponse;
use Drupal\ibm_apim\ApicType\ApicUser;

interface ApicLoginServiceInterface {

  /**
   * @param \Drupal\ibm_apim\ApicType\ApicUser $user
   *
   * @return \Drupal\auth_apic\UserManagerResponse
   */
  public function login(ApicUser $user): UserManagerResponse;

  /**
   * @param $authCode
   * @param $registryUrl
   *
   * @return string redirect location
   */
  public function loginViaAzCode($authCode, $registryUrl): string;

}
