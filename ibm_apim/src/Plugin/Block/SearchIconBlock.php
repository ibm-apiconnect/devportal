<?php

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
 *   category = @Translation("IBM CMS Portal")
 * )
 */
class SearchIconBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      '#theme' => 'ibm_apim_search_icon_block',
    ];
  }

}
