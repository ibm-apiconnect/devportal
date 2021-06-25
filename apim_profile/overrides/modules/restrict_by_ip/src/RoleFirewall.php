<?php

/**
 * @file
 * Contains Drupal\restrict_by_ip\RoleFirewall.
 */

namespace Drupal\restrict_by_ip;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\restrict_by_ip\IPToolsInterface;
use Drupal\restrict_by_ip\Exception\IPOutOfRangeException;

/**
 * Class RoleFirewall.
 *
 * @package Drupal\restrict_by_ip
 */
class RoleFirewall implements RoleFirewallInterface {

  protected $ipTools;
  protected $config;
  protected $entityManager;

  public function __construct(
    IPToolsInterface $ip_tools,
    ConfigFactoryInterface $config_factory,
    EntityTypeManagerInterface $entity_manager) {

    $this->ipTools = $ip_tools;
    $this->config = $config_factory->get('restrict_by_ip.settings');
    $this->entityManager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function rolesToRemove() {
    $roles = $this->getAllRoles();
    $user_ip = $this->ipTools->getUserIP();
    $remove_roles = [];

    foreach ($roles as $name) {
      $role_data = $this->config->get('role.' . $name);

      if (strlen($role_data) == 0) {
        continue;
      }

      $ranges = explode(';', $role_data);

      foreach ($ranges as $range) {
        try {
          $this->ipTools->validateCIDR($user_ip, $range);
        } catch (IPOutOfRangeException $e) {
          $remove_roles[] = $name;
        }
      }
    }

    return $remove_roles;
  }

  /**
   * Get list of all available roles.
   *
   * @return array
   *   Array of role IDs.
   */
  private function getAllRoles() {
    $entities = $this->entityManager->getStorage('user_role')->loadMultiple();
    $roles = [];

    foreach ($entities as $role) {
      $roles[] = $role->id();
    }

    return $roles;
  }

}
