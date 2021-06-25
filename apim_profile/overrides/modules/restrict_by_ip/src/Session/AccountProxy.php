<?php

/**
 * @file
 * Contains \Drupal\restrict_by_ip\Session\AccountProxy.
 */

namespace Drupal\restrict_by_ip\Session;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\restrict_by_ip\RoleFirewallInterface;

/**
 * When the current user is loaded, remove any roles that are restricted based
 * on IP whitelists. Proxy all other method calls to the original current_user
 * service.
 */
class AccountProxy implements AccountProxyInterface {

  /**
   * The original current_user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $original;

  protected $roleFirewall;

  public function __construct(
    AccountProxyInterface $original,
    RoleFirewallInterface $role_firewall) {

    $this->original = $original;
    $this->roleFirewall = $role_firewall;
  }

  /**
   * Return roles for this user, less any that are restricted.
   *
   * @param bool $exclude_locked_roles
   *   (optional) If TRUE, locked roles (anonymous/authenticated) are not returned.
   *
   * @return array
   *   List of role IDs.
   */
  public function getRoles($exclude_locked_roles = FALSE) {
    $roles = $this->original->getRoles($exclude_locked_roles);
    $remove_roles = $this->roleFirewall->rolesToRemove();

    return array_diff($roles, $remove_roles);
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermission($permission) {
    // User #1 has all privileges.
    if ((int) $this->id() === 1) {
      return TRUE;
    }

    return $this->getRoleStorage()->isPermissionInRoles($permission, $this->getRoles());
  }

  /**
   * {@inheritdoc}
   */
  public function setAccount(AccountInterface $account) {
    $this->original->setAccount($account);
  }

  /**
   * {@inheritdoc}
   */
  public function getAccount() {
    return $this->original->getAccount();
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->original->id();
  }

  /**
   * {@inheritdoc}
   */
  public function isAuthenticated() {
    return $this->original->isAuthenticated();
  }

  /**
   * {@inheritdoc}
   */
  public function isAnonymous() {
    return $this->original->isAnonymous();
  }

  /**
   * {@inheritdoc}
   */
  public function getPreferredLangcode($fallback_to_default = TRUE) {
    return $this->original->getPreferredLangcode($fallback_to_default);
  }

  /**
   * {@inheritdoc}
   */
  public function getPreferredAdminLangcode($fallback_to_default = TRUE) {
    return $this->original->getPreferredAdminLangcode($fallback_to_default);
  }

  /**
   * {@inheritdoc}
   */
  public function getUsername() {
    return $this->original->getUsername();
  }

  /**
   * {@inheritdoc}
   */
  public function getAccountName() {
    return $this->original->getAccountName();
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayName() {
    return $this->original->getDisplayName();
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail() {
    return $this->original->getEmail();
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeZone() {
    return $this->original->getTimeZone();
  }

  /**
   * {@inheritdoc}
   */
  public function getLastAccessedTime() {
    return $this->original->getLastAccessedTime();
  }

  /**
   * {@inheritdoc}
   */
  public function setInitialAccountId($account_id) {
    $this->original->setInitialAccountId($account_id);
  }

  /**
   * Returns the role storage object.
   *
   * @return \Drupal\user\RoleStorageInterface
   *   The role storage object.
   */
  protected function getRoleStorage() {
    return \Drupal::service('entity_type.manager')->getStorage('user_role');
  }

}
