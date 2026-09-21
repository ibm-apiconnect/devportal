<?php


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
