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
 * Contains \Drupal\apic_app\Plugin\Block\SubscriptionsBlock.
 */

namespace Drupal\apic_app\Plugin\Block;

use Drupal\apic_app\Service\ApplicationService;
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
use Drupal\views\Views;
use Drupal\Core\Cache\CacheableMetadata;
/**
 * Provides a block to manage the subscriptions for a given application.
 *
 * @Block(
 *   id = "app_subscriptions",
 *   admin_label = @Translation("Application Subscriptions"),
 *   category = @Translation("IBM API Developer Portal (Application)")
 * )
 *
 */
class SubscriptionsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
  protected UserUtils $userUtils;

  /**
   * @var \Drupal\consumerorg\Service\ConsumerOrgService
   */
  protected ConsumerOrgService $consumerOrgService;

  /**
   * @var \Drupal\apic_app\Service\ApplicationService
   */
  protected ApplicationService $applicationService;

  /**
   * SubscriptionsBlock constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\ibm_apim\Service\UserUtils $userUtils
   * @param \Drupal\consumerorg\Service\ConsumerOrgService $consumerOrgService
   * @param \Drupal\apic_app\Service\ApplicationService $applicationService
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, UserUtils $userUtils, ConsumerOrgService $consumerOrgService, ApplicationService $applicationService) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->userUtils = $userUtils;
    $this->consumerOrgService = $consumerOrgService;
    $this->applicationService = $applicationService;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return \Drupal\apic_app\Plugin\Block\SubscriptionsBlock
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): SubscriptionsBlock {
    return new static($configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('ibm_apim.user_utils'),
      $container->get('ibm_apim.consumerorg'),
      $container->get('apic_app.application'));
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account): AccessResult {
    $current_user = \Drupal::currentUser();
    $node = \Drupal::routeMatch()->getParameter('node');;
    $allowed = FALSE;
    if (!$current_user->isAnonymous() && (int) $current_user->id() !== 1) {
      $consumerorg_url = $node->application_consumer_org_url->value;
      $org = \Drupal::service('ibm_apim.consumerorg')->get($consumerorg_url);
      $user = User::load($current_user->id());

      if ($user !== NULL && $org->isMember($user->get('apic_url')->value)) {
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
   * @return array|string[]
   */
  public function getCacheTags(): array {
    $userUtils = \Drupal::service('ibm_apim.user_utils');
    $org = $userUtils->getCurrentConsumerOrg();
    $tags = Cache::mergeTags(parent::getCacheTags(), ['consumerorg:' . Html::cleanCssIdentifier($org['url']), 'apic_app_application_subs_list']);

    // With this when your node change your block will rebuild
    if ($node = \Drupal::routeMatch()->getParameter('node')) {
      // if there is node add its cachetag
      $tags = Cache::mergeTags($tags, ['node:' . $node->id()]);
    }
    return $tags;
  }

  /**
   * @return array
   */


  public function build(): array
  {
    $node = \Drupal::routeMatch()->getParameter('node');
    $billingEnabled = (bool) \Drupal::state()->get('ibm_apim.billing_enabled');
    // Get contextual argument from node
    $app_url = $node->get('apic_url')->value ?? NULL;

    if (empty($app_url)) {
      \Drupal::logger('apic_app')->warning('Missing application URL on node for subscriptions view.');
      return [];
    }

    // Load and render the view programmatically with full plugin lifecycle
    $view = Views::getView('application_subscriptions');
    $view->setDisplay('block_1');
    $view->setArguments([$app_url]);
    $view->execute();
    $render_array = $view->render();

    if (empty($view->result)) {
      return [
        '#theme' => 'app_subscriptions',
        '#userHasAppManage' => false,
        '#userHasSubView' => false,
        '#userHasSubManage' => false,
        '#showVersions' => false,
        '#billing_enabled' => $billingEnabled,
        '#node' => [],
        '#attached' => [
          'library' => ['apic_app/basic'],
        ],
        '#cache' => [
          'tags' => ['node:' . $node->id(), 'apic_app_application_subs_list'],
          'contexts' => ['route'],
          'max-age' => -1,
        ],
      ];
    }

    $metadata = new CacheableMetadata();
    $metadata->addCacheTags(['node:' . $node->id(), 'apic_app_application_subs_list']);
    $metadata->addCacheContexts(['route']);
    $metadata->applyTo($render_array);
    return $render_array;

  }

}