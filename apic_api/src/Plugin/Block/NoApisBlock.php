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
 * Contains \Drupal\apic_app\Plugin\Block\NoApisBlock.
 */

namespace Drupal\apic_app\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a block to display when the apis view page display has no results.
 *
 * @Block(
 *   id = "noapisblock",
 *   admin_label = @Translation("No Apis Block"),
 *   category = @Translation("IBM API Developer Portal (Application)")
 * )
 *
 */
class NoApisBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build(): array {

    return [
      '#theme' => 'no_apis_block'
    ];
  }

}