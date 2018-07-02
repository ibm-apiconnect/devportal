<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * @file
 * Contains \Drupal\apic_app\Plugin\Block\SubscriptionsBlock.
 */

namespace Drupal\apic_app\Plugin\Block;

use Drupal\apic_app\Application;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block to manage the subscriptions for a given application.
 *
 * @Block(
 *   id = "app_subscriptions",
 *   admin_label = @Translation("Application Subscriptions"),
 *   category = @Translation("IBM API Connect (Application)")
 * )
 *
 */
class SubscriptionsBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
  protected function blockAccess(AccountInterface $account) {
    $current_user = \Drupal::currentUser();
    $node = \Drupal::routeMatch()->getParameter('node');;
    $allowed = FALSE;
    if (!$current_user->isAnonymous() && $current_user->id() != 1) {
      $consumerorg_url = $node->application_consumer_org_url->value;
      $org = \Drupal::service("ibm_apim.consumerorg")->get($consumerorg_url);
      $user = User::load($current_user->id());

      if ($org->isMember($user->get('apic_url')->value)) {
        $allowed = TRUE;
      }
    }

    return AccessResult::allowedIf($allowed);
  }

  public function getCacheContexts() {
    //if you depends on \Drupal::routeMatch()
    //you must set context of this block with 'route' context tag.
    //Every new route this block will rebuild
    return Cache::mergeContexts(parent::getCacheContexts(), array('route'));
  }

  public function getCacheTags() {
    //With this when your node change your block will rebuild
    if ($node = \Drupal::routeMatch()->getParameter('node')) {
      //if there is node add its cachetag
      return Cache::mergeTags(parent::getCacheTags(), array('node:' . $node->id()));
    }
    else {
      //Return default tags instead.
      return parent::getCacheTags();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $userUtils = \Drupal::service('ibm_apim.user_utils');
    $node = \Drupal::routeMatch()->getParameter('node');
    $userHasAppManage = $userUtils->checkHasPermission('app:manage');
    $userHasSubView = $userUtils->checkHasPermission('subscription:view');
    $userHasSubManage = $userUtils->checkHasPermission('subscription:manage');
    $ibm_apim_show_versions = \Drupal::config('ibm_apim.settings')->get('showVersions');
    if ($ibm_apim_show_versions === NULL) {
      $ibm_apim_show_versions = 1;
    }
    $billing_enabled = \Drupal::state()->get('ibm_apim.billing_enabled');
    $billing_enabled = $billing_enabled ? TRUE : FALSE;

    $nodeArray = array(
      'application_id' => array('value' => $node->application_id->value),
      'subscriptions' => Application::getSubscriptions($node),
      'id' => $node->id()
    );

    return [
      '#theme' => 'app_subscriptions',
      '#node' => $nodeArray,
      '#showVersions' => $ibm_apim_show_versions,
      '#billing_enabled' => $billing_enabled,
      '#userHasAppManage' => $userHasAppManage,
      '#userHasSubView' => $userHasSubView,
      '#userHasSubManage' => $userHasSubManage,
      '#attached' => array(
        'library' => array('apic_app/basic'),
      ),
    ];
  }
}