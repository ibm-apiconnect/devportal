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
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\ibm_apim\ApicRest;
use Drupal\ibm_apim\Service\AnalyticsService;
use Drupal\ibm_apim\Service\SiteConfig;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\ibm_apim\Service\Utils;
use Drupal\node\Entity\Node;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

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
        }
      }
    }

    $theme = 'ibm_apim_analytics';
    $libraries = ['ibm_apim/analytics'];

    $url = Url::fromRoute('ibm_apim.analyticsproxy')->toString(TRUE)->getGeneratedUrl();
    $dashboard = \Drupal::config('ibm_apim.settings')->get('analytics_dashboard');
    if (empty($dashboard)) {
      $dashboard = ['total_calls', 'total_errors', 'avg_response', 'num_calls', 'status_codes', 'top_products', 'top_apis', 'response_time', 'num_throttled', 'num_errors', 'call_table'];
    }
    $drupalSettings = [
      'analytics' => [
        'proxyURL' => \Drupal::service('ibm_apim.apim_utils')->getHostUrl() . $url,
        'locale' => $this->utils->convert_lang_name(\Drupal::languageManager()->getCurrentLanguage()->getId()),
        'dashboard' => $dashboard
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

    if (!isset($response)) {
      $app = $this->requestStack->getCurrentRequest()->query->get('app');
      $start = $this->requestStack->getCurrentRequest()->query->get('start');
      $end = $this->requestStack->getCurrentRequest()->query->get('end');
      $limit = $this->requestStack->getCurrentRequest()->query->get('limit');
      $offset = $this->requestStack->getCurrentRequest()->query->get('offset');
      $timeframe = $this->requestStack->getCurrentRequest()->query->get('timeframe');
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
      if (isset($limit)) {
        $parameters['limit'] = $limit;
      }
      if (isset($offset)) {
        $parameters['offset'] = $offset;
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
          $dashboard = ['total_calls', 'total_errors', 'avg_response', 'num_calls', 'status_codes', 'top_products', 'top_apis', 'response_time', 'num_throttled', 'num_errors', 'call_table'];
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
        if (!in_array('response_time', $dashboard)) {
          unset($decoded['response_times']);
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
}
