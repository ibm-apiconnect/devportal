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
      $consumerorgResults = $query->execute();
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
    $drupalSettings = [
      'analytics' => [
        'proxyURL' => \Drupal::service('ibm_apim.apim_utils')->getHostUrl() . $url,
        'locale' => $this->utils->convert_lang_name(\Drupal::languageManager()->getCurrentLanguage()->getId())
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
      $consumerOrgResults = $query->execute();
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
        $url = "/consumer-analytics/orgs/${consumerOrgId}/dashboard";

      } else {
        $url = "/consumer-analytics/orgs/${consumerOrgId}/apps/${app}/dashboard";
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
      } else if ($apimResponse->getCode() !== 200 || empty($apimResponse->getData()))  {
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
          '%error' => $errors]);
        if (empty($errors)) {
          $errors = "The website encountered an unexpected error.";
        }
        $response = new Response($errors, $apimResponse->getCode(), []);
      } else {
        $response = new Response();
        $data = $apimResponse->getData();
        $data = array_shift($data);
        $response->setContent($data);
        $response->setStatusCode($apimResponse->getCode());
        foreach ($apimResponse->getHeaders() as $key => $val) {
          $response->headers->set($key, $val);
        }
      }
    }
    \Drupal::messenger()->deleteAll();
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $response;
  }

}
