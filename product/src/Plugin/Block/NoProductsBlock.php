<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2022
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * @file
 * Contains \Drupal\apic_app\Plugin\Block\NoProductsBlock.
 */

namespace Drupal\apic_app\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block to display when the products view page display has no results.
 *
 * @Block(
 *   id = "noproductsblock",
 *   admin_label = @Translation("No Products Block"),
 *   category = @Translation("IBM API Developer Portal (Application)")
 * )
 *
 */
class NoProductsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build(): array {

    return [
      '#theme' => 'no_products_block'
    ];
  }

}