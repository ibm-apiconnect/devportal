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

use Drupal\Component\Utility\Html;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
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
 *   category = @Translation("IBM API Developer Portal")
 * )
 */
class NewApplicationBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
  protected UserUtils $userUtils;

  /**
   * NewApplicationBlock constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\ibm_apim\Service\UserUtils $userUtils
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UserUtils $userUtils) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->userUtils = $userUtils;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return \Drupal\apic_app\Plugin\Block\NewApplicationBlock
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): NewApplicationBlock {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('ibm_apim.user_utils'));
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account): AccessResult {
    $current_user = \Drupal::currentUser();
    $config = \Drupal::config('ibm_apim.settings');
    $show_register_app = (boolean) $config->get('show_register_app');
    return AccessResult::allowedIf($show_register_app === TRUE && !$current_user->isAnonymous() && (int) $current_user->id() !== 1);
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      '#theme' => 'new_application',
      '#access' => $this->userUtils->checkHasPermission('app:manage')
    ];
  }

  public function getCacheTags(): array {
    return Cache::mergeTags(parent::getCacheTags(), ['apic_app:new_application']);
  }

  public function getCacheContexts(): array {
    return Cache::mergeContexts(parent::getCacheContexts(), ['user']);
  }

}