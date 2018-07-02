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
use Drupal\ibm_apim\ApicType\TlsClientProfile;
use Psr\Log\LoggerInterface;

/**
 * Functionality for handling catalog level tls client profiles
 */
class TlsClientProfilesService {

  private $state;
  private $logger;

  public function __construct(StateInterface $state, LoggerInterface $logger) {
    $this->state = $state;
    $this->logger = $logger;
  }

  /**
   * get all the tls client profiles
   *
   * @return NULL if an error occurs otherwise an array of the registries.
   */
  public function getAll() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $client_profiles = $this->state->get('ibm_apim.tls_client_profiles');

    if (empty($client_profiles)) {
      $this->logger->warning('Found no tls client profiles in the catalog state. Potentially missing data from APIM.');
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $client_profiles);
    return $client_profiles;
  }

  /**
   * get a specific tls client profile by url
   *
   * @param $key
   * @return null|array
   */
  public function get($key) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $key);

    $client_profile = NULL;
    if (isset($key)) {
      // clear caches if config different to previous requests
      $current_data = $this->getAll();

      if (isset($current_data) && isset($current_data[$key])) {
        $client_profile = $current_data[$key];
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $client_profile);
    return $client_profile;
  }

  /**
   * Update all tls client profile definitions
   *
   * @param $data array of tls client profiles
   */
  public function updateAll($data) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $data);

    if (isset($data)) {
      $client_profiles = array();
      foreach ($data as $next_profile) {
        $profile_object = new TlsClientProfile();
        $profile_object->setValues($next_profile);
        $client_profiles[$next_profile['url']] = $profile_object;
      }
      $this->state->set('ibm_apim.tls_client_profiles', $client_profiles);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Update a specific tls profile
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
      $profile_object = new TlsClientProfile();
      $profile_object->setValues($data);
      $current_data[$key] = $profile_object;
      $this->state->set('ibm_apim.tls_client_profiles', $current_data);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Delete a specific tls profile
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
        $this->state->set('ibm_apim.tls_client_profiles', $new_data);
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Delete all current analytics services
   */
  public function deleteAll() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $this->state->set('ibm_apim.tls_client_profiles', array());

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

}
