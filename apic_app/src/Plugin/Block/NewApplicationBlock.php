<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2021
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * @file
 * Contains \Drupal\apic_app\Plugin\Block\NewApplicationBlock.
 */

namespace Drupal\apic_app\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ibm_apim\Service\UserUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'New Application link' block.
 *
 * @Block(
 *   id = "new_application",
 *   admin_label = @Translation("New Application Link"),
 *   category = @Translation("IBM API Connect")
 * )
 */
class NewApplicationBlock extends BlockBase implements ContainerFactoryPluginInterface {

  protected $userUtils;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, UserUtils $userUtils) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->userUtils = $userUtils;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('ibm_apim.user_utils'));
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account): AccessResult {
    $current_user = \Drupal::currentUser();
    $config = \Drupal::config('ibm_apim.settings');
    $show_register_app = (boolean) $config->get('show_register_app');
    return AccessResult::allowedIf($show_register_app === TRUE && !$current_user->isAnonymous() && (int) $current_user->id() !== 1 && $this->userUtils->checkHasPermission('app:manage'));
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      '#theme' => 'new_application',
      '#cache' => [
        'contexts' => ['user'],
      ],
    ];
  }
}