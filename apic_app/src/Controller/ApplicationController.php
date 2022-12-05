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

namespace Drupal\apic_app\Controller;

use Drupal\apic_app\Service\ApplicationService;
use Drupal\apic_app\Form\ModalApplicationCreateForm;
use Drupal\Component\Utility\Html;
use Drupal\consumerorg\Service\ConsumerOrgService;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\ibm_apim\Service\SiteConfig;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\ibm_apim\Service\Utils;
use Drupal\ibm_apim\Service\EventLogService;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\product\Product;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller routines for application routes.
 */
class ApplicationController extends ControllerBase {

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
  protected UserUtils $userUtils;

  /**
   * @var \Drupal\ibm_apim\Service\SiteConfig
   */
  protected SiteConfig $siteConfig;

  /**
   * @var \Drupal\ibm_apim\Service\Utils
   */
  protected Utils $utils;

  /**
   * @var \Drupal\ibm_apim\Service\EventLogService
   */
  protected EventLogService $eventLogService;

  /**
   * @var \Drupal\consumerorg\Service\ConsumerOrgService
   */
  protected ConsumerOrgService $consumerOrgService;

  /**
   * @var \Drupal\apic_app\Service\ApplicationService
   */
  protected ApplicationService $applicationService;

  /**
   * ApplicationController constructor.
   *
   * @param \Drupal\ibm_apim\Service\UserUtils $userUtils
   * @param \Drupal\ibm_apim\Service\SiteConfig $config
   * @param \Drupal\ibm_apim\Service\Utils $utils
   * @param \Drupal\ibm_apim\Service\EventLogService $eventLogService
   * @param \Drupal\consumerorg\Service\ConsumerOrgService $cOrgService
   * @param \Drupal\apic_app\Service\ApplicationService $applicationService
   */
  public function __construct(
    UserUtils $userUtils,
    SiteConfig $config,
    Utils $utils,
    EventLogService $eventLogService,
    ConsumerOrgService $cOrgService,
    ApplicationService $applicationService) {
    $this->userUtils = $userUtils;
    $this->siteConfig = $config;
    $this->utils = $utils;
    $this->eventLogService = $eventLogService;
    $this->consumerOrgService = $cOrgService;
    $this->applicationService = $applicationService;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return \Drupal\apic_app\Controller\ApplicationController|static
   */
  public static function create(ContainerInterface $container): ApplicationController {
    return new static(
      $container->get('ibm_apim.user_utils'),
      $container->get('ibm_apim.site_config'),
      $container->get('ibm_apim.utils'),
      $container->get('ibm_apim.event_log'),
      $container->get('ibm_apim.consumerorg'),
      $container->get('apic_app.application'),
    );
  }

  /**
   * This method simply redirects to the node/x page, with the node having been loaded via a ParamConverter
   *
   * @param \Drupal\node\NodeInterface|NULL $appId
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function applicationView(NodeInterface $appId = NULL): RedirectResponse {
    return $this->redirect('entity.node.canonical', ['node' => $appId->id()]);
  }

  public function createApplicationModal(): AjaxResponse {
    $response = new AjaxResponse();
    $form = \Drupal::getContainer()->get('form_builder')->getForm(ModalApplicationCreateForm::class);
    $response->addCommand(new OpenModalDialogCommand(t('Create an application'), $form, []));
    return $response;
  }

  /**
   * Activity feed for the current application
   *
   * @param NodeInterface|null $node
   *
   * @return array|Response
   * @throws \Drupal\Core\TempStore\TempStoreException|\JsonException
   */
  public function activity(NodeInterface $node = NULL) {
    if (isset($node)) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $node->id());
    }
    else {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $consumer_org = $this->userUtils->getCurrentConsumerOrg();
    $current_user = \Drupal::currentUser();
    $userHasAppManage = $this->userUtils->checkHasPermission('app:manage');
    $userHasSubView = $this->userUtils->checkHasPermission('subscription:view');
    $userHasSubManage = $this->userUtils->checkHasPermission('subscription:manage');
    $applifecycleEnabled = \Drupal::state()->get('ibm_apim.applifecycle_enabled');
    $analytics_access = FALSE;

    $catalogId = $this->siteConfig->getEnvId();
    $catalogName = $this->siteConfig->getCatalog()['title'];
    $pOrgId = $this->siteConfig->getOrgId();

    $theme = 'application_activity';
    $libraries = ['apic_app/basic'];

    if (isset($node)) {
      $node = Node::load($node->id());
      // ensure this application belongs to the current user's consumerorg
      if (isset($node) && $node->bundle() === 'application' && ($node->application_consumer_org_url->value === $consumer_org['url'] || $current_user->hasPermission('bypass node access'))) {
        $moduleHandler = \Drupal::service('module_handler');
        $config = \Drupal::config('ibm_apim.settings');
        $ibm_apim_show_placeholder_images = (boolean) $config->get('show_placeholder_images');
        $appImageUploadEnabled = (boolean) $config->get('application_image_upload');
        $fid = $node->application_image->getValue();
        $application_image_url = NULL;
        if (isset($fid[0]['target_id']) && !empty($fid)) {
          $file = File::load($fid[0]['target_id']);
          if ($file !== NULL) {
            $application_image_url = $file->createFileUrl();
          }
        }
        elseif ($ibm_apim_show_placeholder_images === TRUE && $moduleHandler->moduleExists('apic_app')) {
          $rawImage = $this->applicationService->getRandomImageName($node->getTitle());
          $application_image_url = base_path() . \Drupal::service('extension.list.module')->getPath('apic_app') . '/images/' . $rawImage;
        }
        $lifecycle_pending = $node->application_lifecycle_pending->value ?? NULL;
        $appnode = [
          'id' => $node->id(),
          'title' => $node->getTitle(),
          'image' => $application_image_url,
          'application_id' => $node->application_id->value,
          'application_lifecycle_pending' => $lifecycle_pending,
          'application_lifecycle_state' => $node->application_lifecycle_state->value,
        ];

        $portalAnalyticsService = \Drupal::service('ibm_apim.analytics')->getDefaultService();
        $analyticsClientUrl = NULL;
        if ($portalAnalyticsService !== NULL) {
          $analyticsClientUrl = $portalAnalyticsService->getClientEndpoint();
        }
        $user = User::load($current_user->id());
        if ($user !== NULL) {
          $userUrl = $user->get('apic_url')->value;
          $org = $this->consumerOrgService->get($node->application_consumer_org_url->value);
          $show_analytics = (boolean) $config->get('show_analytics');
        }
        if ($org !== NULL && $analyticsClientUrl !== NULL && $userUrl !== NULL && $org->hasPermission($userUrl, 'app-analytics:view') && $show_analytics) {
          $analytics_access = TRUE;
        }


        $events = $this->eventLogService->getFeedForApplication($node->apic_url->value, 50);
      }
      else {
        \Drupal::logger('ibm_apim')->info('Not a valid application node: %node', ['%node' => $node->id()]);
        return new Response(t('Not a valid application node.'), 400);
      }
    }
    else {
      \Drupal::logger('ibm_apim')->info('Not a valid application node: %node', ['%node' => NULL]);
      return new Response(t('Not a valid application node.'), 400);
    }


    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, [
      'theme' => $theme,
      'catalogId' => $catalogId,
      'catalogName' => $catalogName,
      'porgId' => $pOrgId,
      'node' => $appnode,
      'userHasAppManage' => $userHasAppManage,
      'userHasSubView' => $userHasSubView,
      'userHasSubManage' => $userHasSubManage,
      'appImageUploadEnabled' => $appImageUploadEnabled,
      'applifecycleEnabled' => $applifecycleEnabled,
      'analytics_access' => $analytics_access,
      'events' => $events,
    ]);
    $nodeId = $node->id();

    $build = [
      '#theme' => $theme,
      '#catalogId' => $catalogId,
      '#catalogName' => urlencode($catalogName),
      '#porgId' => $pOrgId,
      '#node' => $appnode,
      '#events' => $events,
      '#userHasAppManage' => $userHasAppManage,
      '#userHasSubView' => $userHasSubView,
      '#userHasSubManage' => $userHasSubManage,
      '#applifecycleEnabled' => $applifecycleEnabled,
      '#appImageUploadEnabled' => $appImageUploadEnabled,
      '#analytics_access' => $analytics_access,
      '#attached' => [
        'library' => $libraries,
      ],
      '#cache' => [
        'tags' => [
          'application:' . $nodeId,
        ],
      ],
    ];
    $renderer = \Drupal::service('renderer');
    $renderer->addCacheableDependency($build, Node::load($node->id()));

    return $build;
  }

  /**
   * Display subscriptions info for the current consumer organization
   *
   * @param NodeInterface|null $node
   *
   * @return array|Response
   * @throws \Drupal\Core\TempStore\TempStoreException|\JsonException
   */
  public function subscriptions(NodeInterface $node = NULL) {
    if (isset($node)) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $node->id());
    }
    else {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $consumer_org = $this->userUtils->getCurrentConsumerOrg();
    $userHasAppManage = $this->userUtils->checkHasPermission('app:manage');
    $userHasSubView = $this->userUtils->checkHasPermission('subscription:view');
    $userHasSubManage = $this->userUtils->checkHasPermission('subscription:manage');
    $applifecycleEnabled = \Drupal::state()->get('ibm_apim.applifecycle_enabled');
    $current_user = \Drupal::currentUser();

    $catalogId = $this->siteConfig->getEnvId();
    $catalogName = $this->siteConfig->getCatalog()['title'];
    $pOrgId = $this->siteConfig->getOrgId();

    $theme = 'application_subscriptions';
    $libraries = ['apic_app/basic', 'ibm_apim/analytics'];
    //$libraries[] = 'apic_app/app_analytics_subscriptions';
    $appnode = NULL;
    $credentials = [];
    $subarray = [];
    $analytics_access = FALSE;

    $portal_analytics_service = \Drupal::service('ibm_apim.analytics')->getDefaultService();
    if (isset($portal_analytics_service)) {
      $analyticsClientUrl = $portal_analytics_service->getClientEndpoint();
    }
    if (!isset($analyticsClientUrl)) {
      \Drupal::service('messenger')->addError(t('Analytics Client URL is not set.'));
    }
    if (isset($node)) {
      $node = Node::load($node->id());
      // ensure this application belongs to the current user's consumerorg
      if (isset($node) && $node->bundle() === 'application' && ($node->application_consumer_org_url->value === $consumer_org['url'] || $current_user->hasPermission('bypass node access'))) {
        $moduleHandler = \Drupal::service('module_handler');
        $config = \Drupal::config('ibm_apim.settings');
        $ibm_apim_show_placeholder_images = (boolean) $config->get('show_placeholder_images');
        $appImageUploadEnabled = (boolean) $config->get('application_image_upload');
        $fid = $node->application_image->getValue();
        $application_image_url = NULL;
        if (isset($fid[0]['target_id']) && !empty($fid)) {
          $file = File::load($fid[0]['target_id']);
          if ($file !== NULL) {
            $application_image_url = $file->createFileUrl();
          }
        }
        elseif ($ibm_apim_show_placeholder_images === TRUE && $moduleHandler->moduleExists('apic_app')) {
          $rawImage = $this->applicationService->getRandomImageName($node->getTitle());
          $application_image_url = base_path() . \Drupal::service('extension.list.module')->getPath('apic_app') . '/images/' . $rawImage;
        }
        $lifecycle_pending = $node->application_lifecycle_pending->value ?? NULL;
        $appnode = [
          'id' => $node->id(),
          'title' => $node->getTitle(),
          'image' => $application_image_url,
          'application_id' => $node->application_id->value,
          'application_lifecycle_pending' => $lifecycle_pending,
          'application_lifecycle_state' => $node->application_lifecycle_state->value,
        ];

        $credentials = $node->application_credentials_refs->referencedEntities();
        $appnode['credentials'] = $credentials;

        $subscriptions = $node->application_subscription_refs->referencedEntities();

        if (isset($subscriptions) && is_array($subscriptions)) {
          foreach ($subscriptions as $sub) {
            $query = \Drupal::entityQuery('node');
            $query->condition('type', 'product');
            $query->condition('apic_url.value', $sub->product_url());
            $nids = $query->execute();

            if (isset($nids) && !empty($nids)) {
              $nid = array_shift($nids);
              $product = Node::load($nid);
              if ($product !== NULL) {
                $fid = $product->apic_image->getValue();
                $product_image_url = NULL;
                $cost = t('Free');
                if (isset($fid[0]['target_id']) && !empty($fid)) {
                  $file = File::load($fid[0]['target_id']);
                  if ($file !== NULL) {
                    $product_image_url = $file->createFileUrl();
                  }
                }
                elseif ($ibm_apim_show_placeholder_images && $moduleHandler->moduleExists('product')) {
                  $rawImage = Product::getRandomImageName($product->getTitle());
                  $product_image_url = base_path() . \Drupal::service('extension.list.module')->getPath('product') . '/images/' . $rawImage;
                }
                $plan_title = '';
                if ($moduleHandler->moduleExists('product')) {
                  $productPlans = [];
                  foreach ($product->product_plans->getValue() as $arrayValue) {
                    $product_plan = unserialize($arrayValue['value'], ['allowed_classes' => FALSE]);
                    $productPlans[$product_plan['name']] = $product_plan;
                  }
                  if (isset($productPlans[$sub->plan()])) {
                    $thisPlan = $productPlans[$sub->plan()];
                    if (!isset($thisPlan['billing-model'])) {
                      $thisPlan['billing-model'] = [];
                    }
                    $cost = \Drupal::service('ibm_apim.product_plan')->parseBilling($thisPlan['billing-model']);
                    $plan_title = $productPlans[$sub->plan()]['title'];
                  }
                }
                if (!isset($plan_title) || empty($plan_title)) {
                  $plan_title = Html::escape($sub->plan());
                }
                $newElement = [
                  'product_title' => Html::escape($product->getTitle()),
                  'product_version' => Html::escape($product->apic_version->value),
                  'product_nid' => $nid,
                  'product_image' => $product_image_url,
                  'plan_name' => Html::escape($sub->plan()),
                  'plan_title' => Html::escape($plan_title),
                  'state' => Html::escape($sub->state()),
                  'subId' => Html::escape($sub->id()),
                  'cost' => $cost,
                ];
                if (isset($product->apic_pathalias->value) && !empty($product->apic_pathalias->value)) {
                  $newElement['product_pathalias'] = Html::escape($product->apic_pathalias->value);
                }
                $subarray[] = $newElement;
              }
            }
          }
        }
        $user = User::load($current_user->id());
        if ($user !== NULL) {
          $userUrl = $user->get('apic_url')->value;
          $org = $this->consumerOrgService->get($node->application_consumer_org_url->value);
          $show_analytics = (boolean) $config->get('show_analytics');
        }
        if ($org !== NULL && $analyticsClientUrl !== NULL && $userUrl !== NULL && $org->hasPermission($userUrl, 'app-analytics:view') && $show_analytics) {
          $analytics_access = TRUE;
        }
      }
      else {
        \Drupal::logger('apic_app')->info('Not a valid application node: %node', ['%node' => $node->id()]);
        return (new Response(t('Not a valid application node.'), 400));
      }
    }
    else {
      \Drupal::logger('apic_app')->info('Not a valid application node: %node', ['%node' => $node->id()]);
      return (new Response(t('Not a valid application node.'), 400));
    }

    $translations = $this->utils->analytics_translations();
    $consumerorg_url = \Drupal::service('ibm_apim.user_utils')->getCurrentConsumerOrg()['url'];
    if ($consumerorg_url !== NULL && $consumerorg_url === $node->application_consumer_org_url->value) {
      $notifications_access = TRUE;
    } else {
      $notifications_access = FALSE;
    }

    $url = Url::fromRoute('ibm_apim.analyticsproxy')->toString();

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, [
      'theme' => $theme,
      'catalogId' => $catalogId,
      'catalogName' => $catalogName,
      'porgId' => $pOrgId,
      'userHasAppManage' => $userHasAppManage,
      'userHasSubManage' => $userHasSubManage,
      'userHasSubView' => $userHasSubView,
      'subscriptions' => $subarray,
      'appImageUploadEnabled' => $appImageUploadEnabled,
      'credentials' => $credentials,
      'node' => $appnode,
      'applifecycleEnabled' => $applifecycleEnabled,
      'notifications_access' => $notifications_access,
      'analytics_access' => $analytics_access,
    ]);
    $nodeId = $node->id();

    $build = [
      '#theme' => $theme,
      '#catalogId' => $catalogId,
      '#catalogName' => urlencode($catalogName),
      '#porgId' => $pOrgId,
      '#userHasAppManage' => $userHasAppManage,
      '#userHasSubManage' => $userHasSubManage,
      '#userHasSubView' => $userHasSubView,
      '#applifecycleEnabled' => $applifecycleEnabled,
      '#appImageUploadEnabled' => $appImageUploadEnabled,
      '#notifications_access' => $notifications_access,
      '#analytics_access' => $analytics_access,
      '#node' => $appnode,
      '#attached' => [
        'library' => $libraries,
      ],
      '#cache' => [
        'tags' => [
          'application:' . $nodeId,
        ],
      ],
    ];

    $renderer = \Drupal::service('renderer');
    $renderer->addCacheableDependency($build, Node::load($node->id()));

    return $build;
  }

}
