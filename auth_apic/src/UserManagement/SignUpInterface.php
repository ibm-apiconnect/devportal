<?php


namespace Drupal\auth_apic\UserManagement;

use Drupal\auth_apic\UserManagerResponse;
use Drupal\ibm_apim\ApicType\ApicUser;

interface SignUpInterface {

  /**
   * @param \Drupal\ibm_apim\ApicType\ApicUser $user
   *
   * @return \Drupal\auth_apic\UserManagerResponse
   */
  public function signUp(ApicUser $user): UserManagerResponse;

}
