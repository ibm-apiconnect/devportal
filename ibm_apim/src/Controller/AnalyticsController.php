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

    $eventsFound = $this->apiUtils->areEventAPIsPresent();

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
    $libraries = ['ibm_apim/analytics', 'ibm_apim/org_analytics'];
    $translations = $this->utils->analytics_translations();

    $url = Url::fromRoute('ibm_apim.analyticsproxy')->toString();
    $drupalSettings = [
      'anv' => [],
      'analytics' => [
        'proxyURL' => $url,
        'translations' => $translations,
        'analyticsDir' => base_path() . \Drupal::service('extension.list.module')->getPath('ibm_apim') . '/analytics',
      ],
    ];

    $portal_analytics = $this->analyticsService->getDefaultService();
    if (!isset($portal_analytics)) {
      \Drupal::messenger()->addError(t('No analytics service was found.'));
    }
    else {
      $analyticsClientUrl = $portal_analytics->getClientEndpoint();
      if (!isset($analyticsClientUrl)) {
        \Drupal::messenger()->addError(t('Analytics client URL is not set.'));
      }
    }
    $nodeArray = ['id' => $consumerorgNid];

    $tabs = [];
    // tabs should be an array of additional tabs, eg. [{'title' => 'tab title', 'path' => '/tab/path'}, ... ]
    \Drupal::moduleHandler()->alter('consumerorg_myorg_tabs', $tabs, $nodeArray);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, [
      'theme' => $theme,
      'consumerorgId' => $consumerorgId,
      'catalogId' => $catalogId,
      'catalogName' => $catalogName,
      'porgId' => $pOrgId,
      'consumerorgTitle' => $consumerorgTitle,
      'node' => $nodeArray,
      'tabs' => $tabs,
      'eventsFound' => $eventsFound,
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
      ],
      '#node' => $nodeArray,
      '#tabs' => $tabs,
      '#eventsFound' => $eventsFound,
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

    if (isset($consumerOrg['url'])) {
      $portalAnalyticsService = $this->analyticsService->getDefaultService();
      if (isset($portalAnalyticsService)) {
        $analyticsClientUrl = $portalAnalyticsService->getClientEndpoint();
        if (isset($analyticsClientUrl)) {
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

            $pOrgId = $this->siteConfig->getOrgId();
            $catalogId = $this->siteConfig->getEnvId();
            // get the incoming POST payload
            $data = $request->getContent();

            $url = $analyticsClientUrl . '/api/apiconnect/anv';

            $verb = 'POST';
            $url .= '?org_id=' . $pOrgId . '&catalog_id=' . $catalogId . '&developer_org_id=' . $consumerOrgId . '&manage=true&dashboard=true';

            \Drupal::logger('ibm_apim')->debug('Analytics proxy URL is: %url, verb is %verb', [
              '%url' => $url,
              '%verb' => $verb,
            ]);

            $headers = [];

            // Need to use Mutual TLS on the Analytics Client Endpoint
            $mutualAuth = [];
            $analyticsTlsClient = $portalAnalyticsService->getClientEndpointTlsClientProfileUrl();
            if (isset($analyticsTlsClient)) {
              $clientEndpointTlsClientProfileUrl = $analyticsTlsClient;
              $tlsProfiles = \Drupal::service('ibm_apim.tls_client_profiles')->getAll();
              if (isset($tlsProfiles) && !empty($tlsProfiles)) {
                foreach ($tlsProfiles as $tlsProfile) {
                  if ($tlsProfile->getUrl() === $clientEndpointTlsClientProfileUrl) {
                    $keyfile = $tlsProfile->getKeyFile();
                    if (isset($keyfile)) {
                      $mutualAuth['keyFile'] = $keyfile;
                    }
                    $certFile = $tlsProfile->getCertFile();
                    if ($certFile) {
                      $mutualAuth['certFile'] = $certFile;
                    }
                  }
                }
              }
            }
            if (empty($mutualAuth)) {
              $mutualAuth = NULL;
            }
            $headers[] = 'kbn-xsrf: 5.5.1';

            $responseObject = ApicRest::proxy($url, $verb, NULL, TRUE, $data, $headers, $mutualAuth);
            $filtered = $responseObject['content'];
            if (!isset($responseObject['statusCode'])) {
              $responseObject['statusCode'] = 200;
            }
            if (!isset($responseObject['headers'])) {
              $responseObject['headers'] = [];
            }

            $response = new Response($filtered, $responseObject['statusCode'], $responseObject['headers']);
          }
          else {
            $response = new Response(t('Invalid consumer organization.'), 400);
          }
        }
        else {
          $response = new Response(t('Invalid Analytics Client URL.'), 400);
        }
      }
      else {
        $response = new Response(t('No analytics service was configured'), 400);
      }
    }
    else {
      $response = new Response(t('Invalid consumer organization.'), 400);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $response;
  }

}
