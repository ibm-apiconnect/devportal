<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2019
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * @file
 * Contains \Drupal\apic_app\Plugin\Block\AppCostBlock.
 */

namespace Drupal\apic_app\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ibm_apim\Service\UserUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block with the cost of a given application.
 *
 * @Block(
 *   id = "app_cost",
 *   admin_label = @Translation("Application Cost"),
 *   category = @Translation("IBM API Connect (Application)")
 * )
 *
 */
class AppCostBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
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
  protected function blockAccess(AccountInterface $account) : AccessResult{
    $current_user = \Drupal::currentUser();

    return AccessResult::allowedIf(!$current_user->isAnonymous() && (int) $current_user->id() !== 1);
  }

  public function getCacheContexts() {
    //if you depends on \Drupal::routeMatch()
    //you must set context of this block with 'route' context tag.
    //Every new route this block will rebuild
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

  public function getCacheTags() {
    //With this when your node change your block will rebuild
    if ($node = \Drupal::routeMatch()->getParameter('node')) {
      //if there is node add its cachetag
      return Cache::mergeTags(parent::getCacheTags(), ['node:' . $node->id()]);
    }
    else {
      //Return default tags instead.
      return parent::getCacheTags();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    return [
      '#theme' => 'app_cost',
      '#attached' => [
        'library' => ['apic_app/basic', 'apic_app/app_cost'],
      ],
    ];
  }
}