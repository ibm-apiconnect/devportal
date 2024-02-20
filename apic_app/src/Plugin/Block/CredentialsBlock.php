<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2024
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

use Drupal\Component\Utility\Html;
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
 *   category = @Translation("IBM API Developer Portal (Application)")
 * )
 *
 */
class CredentialsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
  protected UserUtils $userUtils;

  /**
   * @var \Drupal\consumerorg\Service\ConsumerOrgService
   */
  protected ConsumerOrgService $consumerOrgService;

  /**
   * CredentialsBlock constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\ibm_apim\Service\UserUtils $userUtils
   * @param \Drupal\consumerorg\Service\ConsumerOrgService $consumerOrgService
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UserUtils $userUtils, ConsumerOrgService $consumerOrgService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->userUtils = $userUtils;
    $this->consumerOrgService = $consumerOrgService;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return \Drupal\apic_app\Plugin\Block\CredentialsBlock|static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): CredentialsBlock {
    return new static($configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('ibm_apim.user_utils'),
      $container->get('ibm_apim.consumerorg'));
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

  /**
   * @return array|string[]
   */
  public function getCacheContexts(): array {
    //if you depends on \Drupal::routeMatch()
    //you must set context of this block with 'route' context tag.
    //Every new route this block will rebuild
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

  /**
   * @return array
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function getCacheTags(): array {
    $org = $this->userUtils->getCurrentConsumerOrg();
    $tags = Cache::mergeTags(parent::getCacheTags(), ['consumerorg:' . Html::cleanCssIdentifier($org['url'])]);

    // With this when your node change your block will rebuild
    if ($node = \Drupal::routeMatch()->getParameter('node')) {
      // if there is node add its cachetag
      $tags = Cache::mergeTags($tags, ['node:' . $node->id()]);
    }
    return $tags;
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $node = \Drupal::routeMatch()->getParameter('node');
    $userHasAppManage = $this->userUtils->checkHasPermission('app:manage');
    $credentials = [];
    $credsArray = $node->application_credentials_refs->referencedEntities();
    foreach ($credsArray as $cred) {
      $credentials[] = $cred->toArray();
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

    $libraries = ['apic_app/basic'];
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('clipboardjs')) {
      $clipboard = [
        'enabled' => TRUE,
        'image_path' => \Drupal::service('extension.list.module')->getPath('apic_app'),
      ];
      $libraries[] = 'clipboardjs/drupal';
    }
    else {
      $clipboard = ['enabled' => FALSE];
    }

    $block = [
      '#theme' => 'app_credentials',
      '#node' => $nodeArray,
      '#userHasAppManage' => $userHasAppManage,
      '#allowNewCredentials' => $allow_new_credentials,
      '#allowClientidReset' => $allow_clientid_reset,
      '#allowClientsecretReset' => $allow_clientsecret_reset,
      '#clipboard' => $clipboard,
      '#attached' => [
        'library' => $libraries,
        'drupalSettings' => $drupalSettings,
      ],
    ];

    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('view_password')) {
      // Adding js for the view_password lib since it only attaches to forms by default.
      $block['#attached']['library'][] = 'view_password/pwd_lb';
      $block['#cache'] = [
        'tags' => [
            'config:view_password.settings',
        ],
      ];
      $block['#attributes'] = [
        'class' => [
            'pwd-see',
        ],
      ];
      $span_classes = \Drupal::config('view_password.settings')->get('span_classes');
      $block['#attached']['drupalSettings']['view_password'] = [
        'showPasswordLabel' => t("Show password"),
        'hidePasswordLabel' => t("Hide password"),
        'span_classes' => $span_classes
      ];
    }

    return $block;
  }

}