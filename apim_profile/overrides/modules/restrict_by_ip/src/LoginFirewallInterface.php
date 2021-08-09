<?php

namespace Drupal\restrict_by_ip;

use Drupal\Core\Session\AccountInterface;

/**
 * Interface LoginFirewallInterface.
 *
 * @package Drupal\restrict_by_ip
 */
interface LoginFirewallInterface {

  /**
   * Checks that a user is allowed to login based on IP whitelists.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user to check if login is allowed.
   *
   * @return bool
   *   Whether login is allowed or not.
   */
  public function isLoginAllowed(AccountInterface $account);

  /**
   * Checks that login is allowed, and takes appropriate action if not.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user to check if login is allowed.
   */
  public function execute(AccountInterface $account);

}
