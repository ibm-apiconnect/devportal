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
 * Contains \Drupal\apic_app\Plugin\Block\AppAPICallSummaryBlock.
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
 * Provides a block with the recent API Calls for a given application.
 *
 * @Block(
 *   id = "app_api_call_summary",
 *   admin_label = @Translation("Recent Application API Calls"),
 *   category = @Translation("IBM API Developer Portal (Application)")
 * )
 *
 */
class AppAPICallSummaryBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
  protected UserUtils $userUtils;

  /**
   * AppAPICallSummaryBlock constructor.
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
   * @return \Drupal\apic_app\Plugin\Block\AppAPICallSummaryBlock
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): AppAPICallSummaryBlock {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('ibm_apim.user_utils'));
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account): AccessResult {
    $current_user = \Drupal::currentUser();

    return AccessResult::allowedIf(!$current_user->isAnonymous() && (int) $current_user->id() !== 1);
  }

  /**
   * @return array
   */
  public function getCacheContexts(): array {
    //if you depends on \Drupal::routeMatch()
    //you must set context of this block with 'route' context tag.
    //Every new route this block will rebuild
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

  /**
   * @return array|string[]
   */
  public function getCacheTags(): ?array {
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
      '#theme' => 'app_api_call_summary',
      '#attached' => [
        'library' => ['apic_app/basic', 'apic_app/app_api_call_summary'],
      ],
    ];
  }

}