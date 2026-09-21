<?php

namespace Drupal\auth_apic\UserManagement;


use Drupal\auth_apic\JWTToken;

interface ApicActivationInterface {

  public function activate(JWTToken $jwt);

}
