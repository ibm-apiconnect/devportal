<?php

namespace Drupal\restrict_by_ip;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\restrict_by_ip\Exception\InvalidIPException;
use Drupal\restrict_by_ip\Exception\IPOutOfRangeException;

/**
 * Class IPTools.
 *
 * @package Drupal\restrict_by_ip
 */
class IPTools implements IPToolsInterface {

  protected $config;

  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config = $config_factory->get('restrict_by_ip.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getUserIP() {
    $header = $this->config->get('header');
    $ip_address = '';

    if (!empty($_SERVER[$header])) {
      $ip_address = $_SERVER[$header];
    }

    return $ip_address;
  }

  /**
   * {@inheritdoc}
   */
  public static function validateIP($ip) {
    // Separate in to address and CIDR mask.
    $cidr = explode("/", $ip);

    // Check address and cidr mask entered.
    if (count($cidr) !== 2) {
      throw new InvalidIPException('IP address must be in CIDR notation.');
    }

    $ipaddr = explode(".", $cidr[0]);

    // Check four octets entered.
    if (count($ipaddr) != 4) {
      throw new InvalidIPException('IP address must have four octets.');
    }

    // Check each octet is valid - numeric and 0 < value < 255.
    for ($i = 0; $i < count($ipaddr); $i++) {
      if ((!is_numeric($ipaddr[$i])) || ($ipaddr[$i] < 0) || ($ipaddr[$i] > 255)) {
        throw new InvalidIPException('Octet must be between 0 and 255.');
      }
    }

    // Check cidr mask value - numeric and 0 < value < 33.
    if ((!is_numeric($cidr[1])) || ($cidr[1] < 1) || ($cidr[1] > 32)) {
      throw new InvalidIPException('Routing prefix must be between 0 and 32.');
    }

    // Check the validity of the network address in the given CIDR block,
    // by ensuring that the network address part is valid within the
    // CIDR block itself. If it's not, the notation is invalid.
    try {
      self::validateCIDR($cidr[0], $ip);
    }
    catch (IPOutOfRangeException $e) {
      throw new InvalidIPException('IP address and routing prefix are not a valid combination.');
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function validateCIDR($ip, $range) {
    // Separate ip address and CIDR mask.
    $netmask = explode("/", $range);

    // Get valid network as long.
    $ip_net = ip2long($netmask[0]);

    // Get valid network mask as long.
    $ip_mask = ~((1 << (32 - $netmask[1])) - 1);

    // Get ip address to check as long.
    $ip_ip = ip2long($ip);

    // Mask ip address to check to get subnet.
    $ip_ip_net = $ip_ip & $ip_mask;

    // Only returns 1 if the valid network
    // and the subnet of the ip address
    // to check are the same.
    if ($ip_ip_net != $ip_net) {
      throw new IPOutOfRangeException('IP address and routing prefix are not a valid combination.');
    }
  }

}
