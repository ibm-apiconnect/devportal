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

namespace Drupal\ibm_apim\Service;

use Psr\Log\LoggerInterface;

/**
 * Utility functions to smooth our interaction with the apim consumer apis.
 */
class ApimUtils {

  private $logger;

  private $siteconfig;

  /**
   * ApimUtils constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\ibm_apim\Service\SiteConfig $site_config
   */
  public function __construct(LoggerInterface $logger,
                              SiteConfig $site_config) {
    $this->logger = $logger;
    $this->siteconfig = $site_config;
  }


  /**
   * Given a url fragment such as we receive from webhooks, return a fully
   * qualified url including the hostname.
   *
   * @param $url
   *
   * @return string
   */
  public function createFullyQualifiedUrl($url): string {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $url);
    }

    // We always need the hostname on the front
    if (strpos($url, 'https://') !== 0) {

      // Should start with a /
      if (strpos($url, '/') !== 0) {
        $url = '/' . $url;
        $this->logger->debug('createFullyQualifiedUrl: url does not start with / so updated to ' . $url);
      }

      $hostname = $this->siteconfig->getApimHost();

      // but we don't want the /consumer-api part on the end
      $hostname = $this->stripConsumerApiSuffix($hostname);

      $complete_url = $hostname . $url;
    }
    else {
      $complete_url = $url;
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $complete_url);
    }
    return $complete_url;
  }

  public function removeFullyQualifiedUrl($url): string {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $url);
    }

    if (strpos($url, 'https://') === 0) {

      $hostname = $this->siteconfig->getApimHost();

      // but we don't want the /consumer-api part on the end

      $hostname = $this->stripConsumerApiSuffix($hostname);

      $redacted_url = str_replace($hostname, '', $url);
    }
    else {
      $redacted_url = $url;
    }

    // Should start with a /
    if (strpos($redacted_url, '/') !== 0) {
      $redacted_url = '/' . $redacted_url;
      $this->logger->debug('removeFullyQualifiedUrl: url does not start with / so updated to ' . $redacted_url);
    }
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $redacted_url);
    }
    return $redacted_url;
  }

  /**
   * if a hostname ends with /consumer-api then strip it off
   *
   * @param $hostname
   *
   * @return string
   */
  private function stripConsumerApiSuffix($hostname): string {
    $consumer_api_path = '/consumer-api';
    $length = strlen($consumer_api_path);

    if (substr($hostname, -$length) === $consumer_api_path) {
      $hostname = substr($hostname, 0, -$length);
    }
    return $hostname;
  }

  /**
   * Sanitize registry_url parameters - used to specify which registry is active in a form.
   *
   * Example: /api/catalogs/:uuid/:uuid/configured-catalog-user-registries/:uuid
   *
   * @param $url
   *   registry url
   *
   * @return int
   *   0 = not valid, 1 = valid
   */
  public function sanitizeRegistryUrl($url): int {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $url);
    }
    $pattern = "/^\/api\/catalogs\/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}\/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}\/configured-catalog-user-registries\/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/";
    $result = preg_match($pattern, $url);
    if ($result === 0) {
      $this->logger->warning('invalid registry url, discarding.');
    }
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $result);
    }
    return $result;
  }

  /**
   * Return the host url for the portal site.
   *
   * Note: If you need to add the site specifics (porg/catalog) to it then you can append `. base_path()`
   *
   * @return string
   *  url
   */
  public function getHostUrl(): string {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    $url = 'https://' . $_SERVER['SERVER_NAME'];
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $url);
    }
    return $url;
  }

}
