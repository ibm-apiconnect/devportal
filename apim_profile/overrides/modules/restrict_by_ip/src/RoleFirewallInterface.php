<?php

/**
 * @file
 * Contains Drupal\restrict_by_ip\RoleFirewallInterface.
 */

namespace Drupal\restrict_by_ip;

/**
 * Interface RoleFirewallInterface.
 *
 * @package Drupal\restrict_by_ip
 */
interface RoleFirewallInterface {

  /**
   * Checks which roles should be removed based on IP whitelists.
   *
   * @return array
   *   Array of role IDs that should be removed from user.
   */
  public function rolesToRemove();

}
