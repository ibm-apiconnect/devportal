<?php

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
