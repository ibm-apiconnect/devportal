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
use Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface;
use Psr\Log\LoggerInterface;
use Drupal\ibm_apim\ApicType\UserRegistry;

/**
 * Functionality for handling user registries
 */
class UserRegistryService implements UserRegistryServiceInterface {

  protected $state;
  protected $logger;

  public function __construct(StateInterface $state, LoggerInterface $logger) {
    $this->state = $state;
    $this->logger = $logger;
  }

  /**
   * get all the user_registries
   *
   * @return NULL if an error occurs otherwise an array of the registries.
   */
  public function getAll() {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    $registries = $this->state->get('ibm_apim.user_registries');

    if (empty($registries)) {
      $this->logger->warning('Found no user registries in the catalog config. Potentially missing data from APIM.');
    }
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $registries);
    }
    return $registries;

  }

  /**
   * get a specific user_registry by url
   *
   * @param $key
   * @return null|array
   */
  public function get($key) {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $key);
    }

    $registry = NULL;
    if (isset($key)) {
      // clear caches if config different to previous requests
      //$current_data = $this->state->get('ibm_apim.user_registries');
      $current_data = $this->getAll();

      if (isset($current_data) && isset($current_data[$key])) {
        $registry = $current_data[$key];
      }
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $registry);
    }
    return $registry;
  }

  /**
   * Update all user_registries
   *
   * @param $data array of user_registries keyed on url
   */
  public function updateAll($data) {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $data);
    }

    if (isset($data)) {
      $user_registries = array();
      foreach ($data as $ur) {
        $registry_object = new UserRegistry();
        $registry_object->setValues($ur);
        $user_registries[$ur['url']] = $registry_object;
      }
      $this->state->set('ibm_apim.user_registries', $user_registries);
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
  }

  /**
   * Update a specific user_registry
   *
   * @param $key
   * @param $data
   */
  public function update($key, $data) {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $key);
    }

    if (isset($key) && isset($data)) {
      $current_data = $this->state->get('ibm_apim.user_registries');

      if (!is_array($current_data)) {
        $current_data = array();
      }
      $registry_object = new UserRegistry();
      $registry_object->setValues($data);
      $current_data[$key] = $registry_object;
      $this->state->set('ibm_apim.user_registries', $current_data);
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
  }

  /**
   * Delete a specific user_registry
   *
   * @param $key (user_registries url)
   */
  public function delete($key) {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $key);
    }

    if (isset($key)) {
      $current_data = $this->state->get('ibm_apim.user_registries');

      if (isset($current_data)) {
        $new_data = array();
        foreach ($current_data as $url => $value) {
          if ($url != $key) {
            $new_data[$url] = $value;
          }
        }
        $this->state->set('ibm_apim.user_registries', $new_data);
        // TODO this needs to delete all users from that user registry too
      }
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
  }

  /**
   * Delete all current user registries
   */
  public function deleteAll() {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $this->state->set('ibm_apim.user_registries', array());

    // TODO this needs to delete all users

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
  }

  /**
   * @param $identityProviderName
   * @return null
   */
  public function getRegistryContainingIdentityProvider($identityProviderName){
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $result = NULL;
    $all_registries = $this->state->get('ibm_apim.user_registries');
    foreach ($all_registries as $registry) {
      if($registry->hasIdentityProviderNamed($identityProviderName)) {
        $result = $registry;
        break;
      }
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $result);
    }

    return $result;
  }

  /**
   * @inheritdoc
   */
  public function getDefaultRegistry() {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $default_url = $this->state->get('ibm_apim.default_user_registry');

    $fallback_required = FALSE;
    if (!$default_url) {
      $this->logger->debug('Unexpected result while retrieving default registry - none set.');
      $fallback_required = TRUE;
    }
    else {
      $default = $this->get($default_url);
      if (!$default) {
        $this->logger->debug('Unexpected result while retrieving default registry - not found: ' . $default_url);
        $fallback_required = TRUE;
      }
    }

    if ($fallback_required) {
      $default = $this->calculateFallbackDefaultRegistry();
      if ($default) {
        $this->setDefaultRegistry($default->getUrl());
      }
    }

    $url = NULL;
    if ($default) {
      $url = $default->getUrl();
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $url);
    }
    return $default;
  }

  private function calculateFallbackDefaultRegistry() {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $default = NULL;

    $all_registries = $this->getAll();

    if (sizeof($all_registries) === 0) {
      $this->logger->warning("No registries available when trying to calculate the default.");
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return NULL;
    }

    // if we have any user_managed registries then use the first of them, otherwise just return the first one.
    $user_managed = array_filter($all_registries, function($reg) { return $reg->isUserManaged(); } );
    if ($user_managed) {
      $default = array_shift($user_managed);
    }
    else {
      $default = array_shift($all_registries);
    }
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $default->getUrl());
    }
    return $default;
  }

  public function setDefaultRegistry($url) {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $url);
    }
    $this->state->set('ibm_apim.default_user_registry', $url);
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
  }


}
