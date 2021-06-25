<?php

namespace Drupal\restrict_by_ip;

/**
 * Interface IPToolsInterface.
 *
 * @package Drupal\restrict_by_ip
 */
interface IPToolsInterface {

  /**
   * Return the IP address of the current user using restrict_by_ip general
   * settings.
   */
  public function getUserIP();

  /**
   * Given a string, validate that it is valid IP address.
   */
  public static function validateIP($ip);

  /**
   * Given an singe IP address and a range of IP addresses in CIDR notation,
   * validate that the single IP address is withing the range of IP addresses.
   */
  public static function validateCIDR($ip, $range);

}
