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
 * Contains \Drupal\apic_app\Plugin\Block\CredentialsBlock.
 */

namespace Drupal\apic_app\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a block to manage the credentials for a given application.
 *
 * @Block(
 *   id = "app_credentials",
 *   admin_label = @Translation("Application Credentials"),
 *   category = @Translation("IBM API Connect (Application)")
 * )
 *
 */
class CredentialsBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
    $node = \Drupal::routeMatch()->getParameter('node');
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

    $credentials = array();
    foreach ($node->application_credentials->getValue() as $arrayValue) {
      $credentials[] = unserialize($arrayValue['value']);
    }
    $nodeArray = array(
      'application_id' => array('value' => $node->application_id->value),
      'credentials' => $credentials,
      'id' => $node->id()
    );

    $drupalSettings = array(
      'application' => array('id' => $node->application_id->value, 'credentials' => $credentials)
    );

    return [
      '#theme' => 'app_credentials',
      '#node' => $nodeArray,
      '#userHasAppManage' => $userHasAppManage,
      '#attached' => array(
        'library' => array('apic_app/basic'),
        'drupalSettings' => $drupalSettings
      ),
    ];
  }
}