<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2020
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a 'SearchIcon' block.
 *
 * @Block(
 *   id = "ibm_apim_search_icon",
 *   admin_label = @Translation("Search Icon Block"),
 *   category = @Translation("IBM API Connect")
 * )
 */
class SearchIconBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account): AccessResult {
    return AccessResult::allowedIfHasPermission($account, 'search content');
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      '#theme' => 'ibm_apim_search_icon_block',
    ];
  }

}