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

namespace Drupal\ibm_apim\Controller;

use Drupal\apic_api\Service\ApiUtils;
use Drupal\consumerorg\ApicType\Member;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\ibm_apim\ApicRest;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Service\AnalyticsService;
use Drupal\ibm_apim\Service\SiteConfig;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\ibm_apim\Service\Utils;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Core\Database\Database;
use Drupal\ibm_apim\External\SerializationUtility;

class AnalyticsController extends ControllerBase {

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
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private RequestStack $requestStack;

  /**
   * @var \Drupal\ibm_apim\Service\AnalyticsService
   */
  private AnalyticsService $analyticsService;

  /**
   * @var \Drupal\apic_api\Service\ApiUtils
   */
  protected ApiUtils $apiUtils;

  /**
   * AnalyticsController constructor.
   *
   * @param \Drupal\ibm_apim\Service\UserUtils $userUtils
   * @param \Drupal\ibm_apim\Service\SiteConfig $config
   * @param \Drupal\ibm_apim\Service\AnalyticsService $analytics_service
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   * @param \Drupal\ibm_apim\Service\Utils $utils
   * @param \Drupal\apic_api\Service\ApiUtils $apiUtils
   */
  public function __construct(UserUtils $userUtils, SiteConfig $config, AnalyticsService $analytics_service, RequestStack $request_stack, Utils $utils, ApiUtils $apiUtils) {
    $this->userUtils = $userUtils;
    $this->siteConfig = $config;
    $this->analyticsService = $analytics_service;
    $this->requestStack = $request_stack;
    $this->utils = $utils;
    $this->apiUtils = $apiUtils;
  }

  public static function create(ContainerInterface $container): AnalyticsController {
    /** @noinspection PhpParamsInspection */
    return new static(
      $container->get('ibm_apim.user_utils'),
      $container->get('ibm_apim.site_config'),
      $container->get('ibm_apim.analytics'),
      $container->get('request_stack'),
      $container->get('ibm_apim.utils'),
      $container->get('apic_api.utils')
    );
  }

  /**
   * Display graphs of analytics for the current consumer organization
   *
   * @return array
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function analytics(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $consumerOrg = $this->userUtils->getCurrentConsumerorg();

    $catalogId = $this->siteConfig->getEnvId();
    $catalogName = $this->siteConfig->getCatalog()['title'];
    $pOrgId = $this->siteConfig->getOrgId();
    $consumerorgId = NULL;
    $consumerorgNid = NULL;
    $consumerorgTitle = NULL;
    $members = [];

    if (isset($consumerOrg['url'])) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'consumerorg');
      $query->condition('consumerorg_url.value', $consumerOrg['url']);
      $consumerorgResults = $query->accessCheck()->execute();
      if (isset($consumerorgResults) && !empty($consumerorgResults)) {
        $consumerorgNid = array_shift($consumerorgResults);
        $consumerorg = Node::load($consumerorgNid);
        if ($consumerorg !== NULL) {
          $consumerorgId = $consumerorg->consumerorg_id->value;
          $consumerorgTitle = $consumerorg->getTitle();
          
          // Process members (excluding owner) for member count check
          $myorgOwnerUrl = $consumerorg->consumerorg_owner->value;
          $cOrgMembers = $consumerorg->consumerorg_members->getValue();
          if ($cOrgMembers !== NULL) {
            $orgService = \Drupal::service('ibm_apim.myorgsvc');
            $whitelist = [Member::class, ApicUser::class];
            foreach ($cOrgMembers as $arrayValue) {
              $orgMember = new Member();
              $orgMember->createFromArray(SerializationUtility::decodeData($arrayValue['value'], TRUE, $whitelist));
              
              $memberUserUrl = $orgMember->getUserUrl();
              if ($myorgOwnerUrl !== $memberUserUrl) {
                $newMember = $orgService->prepareOrgMemberForDisplay($orgMember);
                $members[$newMember['id']] = $newMember;
              }
            }
          }
        }
      }
    }

    $theme = 'ibm_apim_analytics';
    $libraries = ['ibm_apim/analytics'];

    $url = Url::fromRoute('ibm_apim.analyticsproxy')->toString(TRUE)->getGeneratedUrl();
    $dashboard = \Drupal::config('ibm_apim.settings')->get('analytics_dashboard');
    if (empty($dashboard)) {
      $dashboard = [
        'total_calls',
        'total_errors',
        'avg_response',
        'num_calls',
        'status_codes',
        'top_products',
        'top_apis',
        'num_throttled',
        'num_errors',
        'call_table',
        'top_api_paths',
        'apis_with_fastest_response_time',
        'apis_with_slowest_response_time'
      ];
    }
    $filters = \Drupal::config('ibm_apim.settings')->get('analytics_filters');
    if (empty($filters)) {
      $filters = ['api_name', 'api_version', 'product_name', 'product_version', 'plan_name', 'app_name', 'status_code', 'operation_path'];
    }
    // Prepare data for analytics filtering
    $apiNames = [];
    $apiVersions = [];
    $productNames = [];
    $productVersions = [];
    $planNames = [];
    $applicationData = [];
    
    // Get all data for filtering
    $this->getApiData($apiNames, $apiVersions);
    $this->getProductData($productNames, $productVersions, $planNames);
    $this->getApplicationNames($applicationData);
    
    // Extract just the application names for backward compatibility
    $applicationNames = [];
    foreach ($applicationData as $appKey => $appInfo) {
      $applicationNames[$appKey] = $appInfo['title'];
    }
    $analyticsTheme = $this->requestStack->getCurrentRequest()->cookies->get('analytics_theme', 'g10');
    if (!in_array($analyticsTheme, ['g10', 'g100'], TRUE)) {
      $analyticsTheme = 'g10';
    }

    $drupalSettings = [
      'analytics' => [
        'proxyURL' => \Drupal::service('ibm_apim.apim_utils')->getHostUrl() . $url,
        'locale' => $this->utils->convert_lang_name(\Drupal::languageManager()->getCurrentLanguage()->getId()),
        'dashboard' => $dashboard,
        'filters' => $filters,
        // Set temp will be passed a var when the Dark mode for portal will be implemented
        'theme' => $analyticsTheme,
        'portalData' => [
          'apiNames' => $apiNames,
          'apiVersions' => $apiVersions,
          'productNames' => $productNames,
          'productVersion' => $productVersions,
          'planNames' => $planNames,
          'appNames' => $applicationNames,
          'appData' => $applicationData
        ]
      ],
    ];

    $portal_analytics = $this->analyticsService->getDefaultService();
    if (!isset($portal_analytics)) {
      \Drupal::messenger()->addError(t('No analytics service was found.'));
    } else {
      $analyticsClientUrl = $portal_analytics->getClientEndpoint();
      if (!isset($analyticsClientUrl)) {
        \Drupal::messenger()->addError(t('Analytics client URL is not set.'));
      }
    }

    // Get configuration settings for consumer org actions
    $config = \Drupal::config('ibm_apim.settings');
    $hasSettingsManagePerm = $this->userUtils->checkHasPermission('settings:manage');
    $allowConsumerorgChangeOwner = (boolean) $config->get('allow_consumerorg_change_owner');
    $allowConsumerorgRename = (boolean) $config->get('allow_consumerorg_rename');
    $allowConsumerorgDelete = (boolean) $config->get('allow_consumerorg_delete');

    $canTransferOwner = $hasSettingsManagePerm && $allowConsumerorgChangeOwner;
    $canRenameOrg = $hasSettingsManagePerm && $allowConsumerorgRename;
    $canDeleteOrg = $hasSettingsManagePerm && $allowConsumerorgDelete;

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, [
      'theme' => $theme,
      'consumerorgId' => $consumerorgId,
      'catalogId' => $catalogId,
      'catalogName' => $catalogName,
      'porgId' => $pOrgId,
      'consumerorgTitle' => $consumerorgTitle,
    ]);

    return [
      '#theme' => $theme,
      '#cache' => [
        'tags' => ['consumeranalytics'],
      ],
      '#consumerorgId' => $consumerorgId,
      '#catalogId' => $catalogId,
      '#catalogName' => urlencode($catalogName),
      '#porgId' => $pOrgId,
      '#consumerorgTitle' => $consumerorgTitle,
      '#myorg_members' => $members,
      '#myorg_can_transfer_owner' => $canTransferOwner,
      '#myorg_can_rename_org' => $canRenameOrg,
      '#myorg_can_delete_org' => $canDeleteOrg,
      '#attached' => [
        'library' => $libraries,
        'drupalSettings' => $drupalSettings,
      ]
    ];
  }

  /**
   * proxy handling substitutions necessary for the analytics
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\Response
   * @throws \Drupal\Core\TempStore\TempStoreException
   * @throws \Exception
   */
  public function analyticsProxy(Request $request): Response {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    // disable caching for this page
    \Drupal::service('page_cache_kill_switch')->trigger();
    $consumerOrg = $this->userUtils->getCurrentConsumerorg();
    $consumerOrgId = NULL;
    $response = null;
    if (isset($consumerOrg['url'])) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'consumerorg');
      $query->condition('consumerorg_url.value', $consumerOrg['url']);
      $consumerOrgResults = $query->accessCheck()->execute();
      if (isset($consumerOrgResults) && !empty($consumerOrgResults)) {
        $first = array_shift($consumerOrgResults);
        $consumerorg = Node::load($first);
        if ($consumerorg !== NULL) {
          $consumerOrgId = $consumerorg->consumerorg_id->value;
        }
      }
    }
    if (empty($consumerOrgId)) {
      \Drupal::logger('analytics')->error('User is not part of a valid consumer organization.');
      $response = new Response('User is not part of a valid consumer organization.', 401, []);
    }

    $queryParams = ['limit', 'offset', 'api_name', 'api_version', 'product_name', 'product_version', 'plan_name', 'app_name', 'status_code', 'operation_path'];
    if (!isset($response)) {
      $currentRequest = $this->requestStack->getCurrentRequest();
      $app = $currentRequest->query->get('app');
      $start = $currentRequest->query->get('start');
      $end = $currentRequest->query->get('end');
      $timeframe = $currentRequest->query->get('timeframe');
      if (empty($app)) {
        $url = "/consumer-analytics/orgs/" . $consumerOrgId . "/dashboard";
      } else {
        $url = "/consumer-analytics/orgs/" . $consumerOrgId . "/apps/" . $app . "/dashboard";
      }

      $parameters = [];
      if (!empty($timeframe)) {
        $parameters['timeframe'] = $timeframe;
      }
      if (!empty($start)) {
        $parameters['start'] = $start;
      }
      if (!empty($end)) {
        $parameters['end'] = $end;
      }
      foreach ($queryParams as $key) {
        $value = $currentRequest->query->get($key);
        if (isset($value)) {
            $parameters[$key] = $value;
        }
      }
      if (!empty($parameters)) {
        $url .= '?' . http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);
      }

      $apimResponse = \Drupal::service('ibm_apim.mgmtserver')->getAnalytics($url);
      if ($apimResponse === NULL) {
        \Drupal::logger('analytics')->error('APIM REST response not set.');
        $response = new Response('No Response from server', 500, []);
      } else if ($apimResponse->getCode() !== 200 || empty($apimResponse->getData())) {
        $errors = $apimResponse->getErrors();
        if (\is_array($errors)) {
          if (!empty($errors)) {
            if (isset($errors[0]['message'])) {
              $errors = $errors[0]['message'];
            } else {
              $errors = implode(', ', $errors);
            }
          }
        }
        \Drupal::logger('analytics')->error('Receieved %code response. %error', [
          '%code' => $apimResponse->getCode(),
          '%error' => $errors
        ]);
        if (empty($errors)) {
          $errors = "The website encountered an unexpected error.";
        }
        $response = new Response($errors, $apimResponse->getCode(), []);
      } else {
        $response = new Response();
        $data = $apimResponse->getData();
        $data = array_shift($data);
        $dashboard = \Drupal::config('ibm_apim.settings')->get('analytics_dashboard');
        if (empty($dashboard)) {
          $dashboard = [
            'total_calls',
            'total_errors',
            'avg_response',
            'num_calls',
            'status_codes',
            'top_products',
            'top_apis',
            'num_throttled',
            'num_errors',
            'call_table',
            'top_api_paths',
            'apis_with_fastest_response_time',
            'apis_with_slowest_response_time'
          ];
        }
        try {
          $decoded = json_decode($data, TRUE, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
          // handle not being fed valid JSON
          $decoded = [
            'content' => $data,
            'json_last_error' => $e->getCode(),
            'json_last_error_msg' => $e->getMessage(),
            'errors' => ['json.parse.error' => 'JSON parse error'],
          ];
        }
        if (!in_array('total_calls', $dashboard)) {
          unset($decoded['total_api_calls']);
        }
        if (!in_array('total_errors', $dashboard)) {
          unset($decoded['total_errors']);
        }
        if (!in_array('avg_response', $dashboard)) {
          unset($decoded['avg_response_time']);
        }
        if (!in_array('num_calls', $dashboard)) {
          unset($decoded['api_calls_per_day']);
        }
        if (!in_array('status_codes', $dashboard)) {
          unset($decoded['status_codes']);
        }
        if (!in_array('num_throttled', $dashboard)) {
          unset($decoded['throttled_calls']);
        }
        if (!in_array('num_errors', $dashboard)) {
          unset($decoded['errors']);
        }
        if (!in_array('call_table', $dashboard)) {
          unset($decoded['last_api_calls']);
        }
        if (!in_array('top_api_paths', $dashboard)) {
          unset($decoded['top_api_paths_by_calls']);
        }
        if (!in_array('apis_with_fastest_response_time', $dashboard)) {
          unset($decoded['apis_with_fastest_response_time']);
        }
        if (!in_array('apis_with_slowest_response_time', $dashboard)) {
          unset($decoded['apis_with_slowest_response_time']);
        }
        $data = json_encode($decoded, JSON_THROW_ON_ERROR);
        $headers = $apimResponse->getHeaders();
        $headers['Content-Length'] = strlen($data);
        $response->setContent($data);
        $response->setStatusCode($apimResponse->getCode());
        foreach ($headers as $key => $val) {
          $response->headers->set($key, $val);
        }
      }
    }
    \Drupal::messenger()->deleteAll();
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $response;
  }

  /**
   * Get all API data for analytics filtering
   *
   * @param array &$apiNames
   *   Array to store API names and related data
   * @param array &$apiVersions
   *   Array to store API versions
   */
  public static function getApiData(&$apiNames, &$apiVersions) {
    $query = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'api')
      ->execute();
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($query) ?? [];
    if (empty($nodes)) {
      return;
    }
    
    foreach ($nodes as $node) {
      if ($node->hasField('api_swagger') && !$node->get('api_swagger')->isEmpty()) {
        $api = SerializationUtility::decodeData($node->get('api_swagger')->getValue()[0]['value']);
        
        if (!isset($api['info'])) {
          continue;
        }
        
        $name = $api['info']['x-ibm-name'] ?? null;
        if ($name === null && isset($api['info']['title'])) {
          $name = strtolower(str_replace(' ', '-', $api['info']['title']));
        }
        
        // Add API to apiNames array
        $apiNames[] = [
          'key' => $name,
          'title' => $api['info']['title'] ?? $name,
          'version' => $api['info']['version'] ?? '',
        ];
        
        // Add API version to apiVersions array if not already there
        if (isset($api['info']['version']) && !in_array($api['info']['version'], $apiVersions)) {
          $apiVersions[] = $api['info']['version'];
        }
      }
    }
  }

  /**
   * Get all product data including APIs and plans for analytics filtering
   *
   * @param array &$productNames
   *   Array to store product names and related data
   * @param array &$productVersions
   *   Array to store product versions
   * @param array &$planNames
   *   Array to store plan names
   */
  public static function getProductData(&$productNames, &$productVersions, &$planNames) {
    $query = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'product')
      ->execute();
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($query) ?? [];
    if (empty($nodes)) {
      return;
    }

    foreach ($nodes as $node) {
      if ($node->hasField('product_data') && !$node->get('product_data')->isEmpty()) {
        $productYaml = $node->get('product_data')->value;
        $product = yaml_parse($productYaml);
        
        if (!isset($product['info']) || !isset($product['apis'])) {
          continue;
        }
        
        $related_product_apis = [];
        $related_product_plans = [];
        
        // Process APIs
        foreach ($product['apis'] as $api) {
          if (isset($api['name'])) {
            $apiParts = explode(':', $api['name']);
            if (count($apiParts) >= 2) {
              $api_key = $apiParts[0];
              $api_version = $apiParts[1];
              
              $related_product_apis[] = [
                'key' => $api_key,
                'version' => $api_version
              ];
              
              // Add to product versions if not already there
              if (!in_array($api_version, $productVersions)) {
                $productVersions[] = $api_version;
              }
            }
          }
        }
        
        // Process all plans
        if (isset($product['plans']) && is_array($product['plans'])) {
          foreach ($product['plans'] as $name => $plan) {
            // Add to plan names if not already there
            $planExists = false;
            foreach ($planNames as $existingPlan) {
              if (isset($existingPlan['key']) && $existingPlan['key'] === $name) {
                $planExists = true;
                break;
              }
            }
            
            if (!$planExists && isset($plan['title'])) {
              $planNames[] = [
                'key' => $name,
                'title' => $plan['title'],
              ];
            }
            
            $related_product_plans[] = $name;
          }
        }
        
        // Add product version to the versions array if not already there
        if (isset($product['info']['version']) && !in_array($product['info']['version'], $productVersions)) {
          $productVersions[] = $product['info']['version'];
        }
        
        // Add product to productNames
        $productNames[] = [
          'key' => $product['info']['name'] ?? '',
          'title' => $product['info']['title'] ?? '',
          'version' => $product['info']['version'] ?? '',
          'apis' => $related_product_apis,
          'plans' => $related_product_plans,
        ];
      }
    }
  }

  /**
   * Get all application data including subscribed APIs, products, and plans
   *
   * @param array &$applicationData
   *   Array to store application data
   */
  public static function getApplicationNames(&$applicationData) {
    // First, get all applications
    $query = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->getQuery()
      ->accessCheck(TRUE)
      ->condition('type', 'application')
      ->execute();
    $appNodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($query) ?? [];
    
    if (empty($appNodes)) {
      return;
    }
    
    // Process each application
    $applicationData = [];
    foreach ($appNodes as $node) {
      if ($node->hasField('application_data') && !$node->get('application_data')->isEmpty()) {
        $app = SerializationUtility::decodeData($node->get('application_data')->value);
        if (isset($app['name']) && isset($app['title'])) {
          $appId = $node->hasField('application_id') ? $node->application_id->value : '';
          $appKey = $app['name'];
          $appTitle = $app['title'];
          
          // Get app-specific portal data using the function from apic_app.module
          $apiNames = [];
          $apiVersions = [];
          $productNames = [];
          $productVersions = [];
          $planNames = [];
          
          // Call the function from apic_app.module to get app-specific data
          if (function_exists('apic_app_generate_app_specific_portal_data')) {
            apic_app_generate_app_specific_portal_data($appId, $apiNames, $apiVersions, $productNames, $productVersions, $planNames);
          }
          
          // Create subscriptions array from products and plans
          $subscriptions = [];
          foreach ($productNames as $product) {
            if (isset($product['plans']) && is_array($product['plans']) && !empty($product['plans'])) {
              foreach ($product['plans'] as $planName) {
                $subscriptions[] = [
                  'product' => $product['key'],
                  'plan' => $planName
                ];
              }
            }
          }
          
          // Store application data with related information
          $applicationData[$appKey] = [
            'title' => $appTitle,
            'id' => $appId,
            'subscriptions' => $subscriptions,
            'apis' => $apiNames,
            'products' => $productNames,
            'plans' => $planNames
          ];
        }
      }
    }
  }
}
