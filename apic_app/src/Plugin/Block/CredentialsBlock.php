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
 * Contains \Drupal\apic_app\Plugin\Block\CredentialsBlock.
 */

namespace Drupal\apic_app\Plugin\Block;

use Drupal\consumerorg\Service\ConsumerOrgService;
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

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
  protected $userUtils;

  /**
   * @var \Drupal\consumerorg\Service\ConsumerOrgService
   */
  protected $consumerOrgService;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, UserUtils $userUtils, ConsumerOrgService $consumerOrgService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->userUtils = $userUtils;
    $this->consumerOrgService = $consumerOrgService;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition, $container->get('ibm_apim.user_utils'), $container->get('ibm_apim.consumerorg'));
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account): AccessResult {
    $current_user = \Drupal::currentUser();
    $node = \Drupal::routeMatch()->getParameter('node');
    $allowed = FALSE;
    if (!$current_user->isAnonymous() && (int) $current_user->id() !== 1) {
      $consumerorg_url = $node->application_consumer_org_url->value;
      $org = $this->consumerOrgService->get($consumerorg_url);
      $user = User::load($current_user->id());

      if ($user !== NULL && $org !== NULL && $org->isMember($user->get('apic_url')->value)) {
        $allowed = TRUE;
      }
    }

    return AccessResult::allowedIf($allowed);
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
    $userUtils = \Drupal::service('ibm_apim.user_utils');
    $node = \Drupal::routeMatch()->getParameter('node');
    $userHasAppManage = $userUtils->checkHasPermission('app:manage');

    $credentials = [];
    foreach ($node->application_credentials->getValue() as $arrayValue) {
      $credentials[] = unserialize($arrayValue['value'], ['allowed_classes' => FALSE]);
    }
    $nodeArray = [
      'application_id' => ['value' => $node->application_id->value],
      'credentials' => $credentials,
      'id' => $node->id(),
    ];

    $drupalSettings = [
      'application' => ['id' => $node->application_id->value, 'credentials' => $credentials],
    ];
    $config = \Drupal::config('ibm_apim.settings');
    $allow_new_credentials = (boolean) $config->get('allow_new_credentials');
    $allow_clientid_reset = (boolean) $config->get('allow_clientid_reset');
    $allow_clientsecret_reset = (boolean) $config->get('allow_clientsecret_reset');

    return [
      '#theme' => 'app_credentials',
      '#node' => $nodeArray,
      '#userHasAppManage' => $userHasAppManage,
      '#allowNewCredentials' => $allow_new_credentials,
      '#allowClientidReset' => $allow_clientid_reset,
      '#allowClientsecretReset' => $allow_clientsecret_reset,
      '#attached' => [
        'library' => ['apic_app/basic'],
        'drupalSettings' => $drupalSettings,
      ],
    ];
  }
}