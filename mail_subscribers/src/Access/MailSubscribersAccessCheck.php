<?php

namespace Drupal\mail_subscribers\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Checks access for accessing the mail subscriber wizards.
 */
class MailSubscribersAccessCheck implements AccessInterface {

  /**
   * Check if user has the 'mail subscribers' permission.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return \Drupal\Core\Access\AccessResult
   */
  public function access(AccountInterface $account): AccessResult {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $allowed = $account->hasPermission('mail subscribers');

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $allowed);
    return AccessResult::allowedIf($allowed)->addCacheableDependency($account);
  }

}