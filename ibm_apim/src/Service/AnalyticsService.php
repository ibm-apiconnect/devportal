<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Service;

use Drupal\Core\State\StateInterface;
use Drupal\ibm_apim\ApicType\AnalyticsServiceDefinition;
use Psr\Log\LoggerInterface;

/**
 * Functionality for handling catalog level analytics configuration
 */
class AnalyticsService {

  private $state;
  private $logger;

  public function __construct(StateInterface $state, LoggerInterface $logger) {
    $this->state = $state;
    $this->logger = $logger;
  }

  /**
   * get all the analytics service definitions
   *
   * @return NULL if an error occurs otherwise an array of the registries.
   */
  public function getAll() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $analytics_services = $this->state->get('ibm_apim.analytics_services');

    if (empty($analytics_services)) {
      $this->logger->warning('Found no analytics services in the catalog state. Potentially missing data from APIM.');
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $analytics_services);
    return $analytics_services;
  }

  /**
   * get a specific analytics service by url
   *
   * @param $key
   * @return null|array
   */
  public function get($key) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $key);

    $analytics_service = NULL;
    if (isset($key)) {
      // clear caches if config different to previous requests
      $current_data = $this->getAll();

      if (isset($current_data) && isset($current_data[$key])) {
        $analytics_service = $current_data[$key];
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $analytics_service);
    return $analytics_service;
  }

  /**
   * Update all analytics services
   *
   * @param $data array of analytics services
   */
  public function updateAll($data) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $data);

    if (isset($data)) {
      $analytics_services = array();
      foreach ($data as $next_service) {
        $analytics_service_object = new AnalyticsServiceDefinition();
        $analytics_service_object->setValues($next_service);
        $analytics_services[$next_service['url']] = $analytics_service_object;
      }
      $this->state->set('ibm_apim.analytics_services', $analytics_services);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Update a specific analytics service
   *
   * @param $key
   * @param $data
   */
  public function update($key, $data) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $key);

    if (isset($key) && isset($data)) {
      $current_data = $this->getAll();

      if (!is_array($current_data)) {
        $current_data = array();
      }
      $analytics_service_object = new AnalyticsServiceDefinition();
      $analytics_service_object->setValues($data);
      $current_data[$key] = $analytics_service_object;
      $this->state->set('ibm_apim.analytics_services', $current_data);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Delete a specific analytics service
   *
   * @param $key (analytics service url)
   */
  public function delete($key) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $key);

    if (isset($key)) {
      $current_data = $this->getAll();

      if (isset($current_data)) {
        $new_data = array();
        foreach ($current_data as $url => $value) {
          if ($url != $key) {
            $new_data[$url] = $value;
          }
        }
        $this->state->set('ibm_apim.analytics_services', $new_data);
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Delete all current analytics services
   */
  public function deleteAll() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $this->state->set('ibm_apim.analytics_services', array());

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Returns the "default" analytics service. For now this just means the first
   * in the list but this behaviour may change in future versions of the product
   * when/if multiple analytics services as supported.
   *
   * @return mixed
   */
  public function getDefaultService() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $default_service = NULL;

    $analytics_services = $this->getAll();
    if(!empty($analytics_services)) {
      $default_service = array_values($analytics_services)[0];
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $default_service);
    return $default_service;
  }

}
