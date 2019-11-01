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

namespace Drupal\ibm_apim\Controller;

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

  protected $userUtils;

  protected $siteConfig;

  protected $utils;

  private $requestStack;

  /**
   * AnalyticsController constructor.
   *
   * @param \Drupal\ibm_apim\Service\UserUtils $userUtils
   * @param \Drupal\ibm_apim\Service\SiteConfig $config
   * @param \Drupal\ibm_apim\Service\AnalyticsService $analytics_service
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   * @param \Drupal\ibm_apim\Service\Utils $utils
   */
  public function __construct(UserUtils $userUtils, SiteConfig $config, AnalyticsService $analytics_service, RequestStack $request_stack, Utils $utils) {
    $this->userUtils = $userUtils;
    $this->siteConfig = $config;
    $this->analyticsService = $analytics_service;
    $this->requestStack = $request_stack;
    $this->utils = $utils;
  }

  public static function create(ContainerInterface $container) {
    return new static($container->get('ibm_apim.user_utils'), $container->get('ibm_apim.site_config'), $container->get('ibm_apim.analytics'), $container->get('request_stack'), $container->get('ibm_apim.utils'));
  }


  /**
   * Display graphs of analytics for the current consumer organization
   *
   * @return array
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function analytics(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $consumer_org = $this->userUtils->getCurrentConsumerorg();

    $catalogId = $this->siteConfig->getEnvId();
    $catalogName = $this->siteConfig->getCatalog()['title'];
    $pOrgId = $this->siteConfig->getOrgId();
    $consumerorgId = NULL;
    $consumerorgTitle = NULL;
    if (isset($consumer_org['url'])) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'consumerorg');
      $query->condition('consumerorg_url.value', $consumer_org['url']);
      $consumerorgresults = $query->execute();
      if (isset($consumerorgresults) && !empty($consumerorgresults)) {
        $first = array_shift($consumerorgresults);
        $consumerorg = Node::load($first);
        if ($consumerorg !== null) {
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
        'analyticsDir' => base_path() . drupal_get_path('module', 'ibm_apim') . '/analytics',
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
      ],
    ];
  }

  /**
   * proxy handling substitutions necessary for the analytics
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function analyticsProxy(Request $request) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    // disable caching for this page
    \Drupal::service('page_cache_kill_switch')->trigger();
    $consumer_org = $this->userUtils->getCurrentConsumerorg();
    $consumerorgId = NULL;

    if (isset($consumer_org) && isset($consumer_org['url'])) {
      $portal_analytics_service = $this->analyticsService->getDefaultService();
      if (isset($portal_analytics_service)) {
        $analyticsClientUrl = $portal_analytics_service->getClientEndpoint();
        if (isset($analyticsClientUrl)) {
          $query = \Drupal::entityQuery('node');
          $query->condition('type', 'consumerorg');
          $query->condition('consumerorg_url.value', $consumer_org['url']);
          $consumerOrgResults = $query->execute();
          if (isset($consumerOrgResults) && !empty($consumerOrgResults)) {
            $first = array_shift($consumerOrgResults);
            $consumerorg = Node::load($first);
            if ($consumerorg !== null) {
              $consumerorgId = $consumerorg->consumerorg_id->value;
            }

            $pOrgId = $this->siteConfig->getOrgId();
            $catalogId = $this->siteConfig->getEnvId();
            // get the incoming POST payload
            $data = $request->getContent();

            $url = $analyticsClientUrl . '/api/apiconnect/anv';

            $verb = 'POST';
            $url = $url . '?org_id=' . $pOrgId . '&catalog_id=' . $catalogId . '&developer_org_id=' . $consumerorgId . '&manage=true&dashboard=true';

            \Drupal::logger('ibm_apim')->debug('Analytics proxy URL is: %url, verb is %verb', [
              '%url' => $url,
              '%verb' => $verb,
            ]);

            $headers = [];

            // Need to use Mutual TLS on the Analytics Client Endpoint
            $mutualAuth = [];
            $analytics_tls_client = $portal_analytics_service->getClientEndpointTlsClientProfileUrl();
            if (isset($analytics_tls_client)) {
              $client_endpoint_tls_client_profile_url = $analytics_tls_client;
              $tls_profiles = \Drupal::service('ibm_apim.tls_client_profiles')->getAll();
              if (isset($tls_profiles) && !empty($tls_profiles)) {
                foreach ($tls_profiles as $tls_profile) {
                  if ($tls_profile->getUrl() === $client_endpoint_tls_client_profile_url) {
                    $keyfile = $tls_profile->getKeyFile();
                    if (isset($keyfile)) {
                      $mutualAuth['keyFile'] = $keyfile;
                    }
                    $certfile = $tls_profile->getCertFile();
                    if ($certfile) {
                      $mutualAuth['certFile'] = $certfile;
                    }
                  }
                }
              }
            }
            if (empty($mutualAuth)) {
              $mutualAuth = NULL;
            }
            $headers[] = 'kbn-xsrf: 5.5.1';

            $response_object = ApicRest::proxy($url, $verb, NULL, TRUE, $data, $headers, $mutualAuth);
            $filtered = $response_object['content'];
            if (!isset($response_object['statusCode'])) {
              $response_object['statusCode'] = 200;
            }
            if (!isset($response_object['headers'])) {
              $response_object['headers'] = [];
            }

            $response = new Response($filtered, $response_object['statusCode'], $response_object['headers']);
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
