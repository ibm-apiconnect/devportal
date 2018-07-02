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

namespace Drupal\ibm_apim\Service\Mocks;

use Drupal\Core\State\StateInterface;
use Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface;
use Psr\Log\LoggerInterface;
use Drupal\ibm_apim\ApicType\UserRegistry;

/**
 * Mock functionality for handling user registries
 */
class MockUserRegistryService implements UserRegistryServiceInterface {

  private $state;
  private $logger;

  private $userRegistries = array();

  public function __construct(StateInterface $state, LoggerInterface $logger) {
    $this->state = $state;
    $this->logger = $logger;
    $this->logger->debug("MockUserRegistryService::__construct() loading mock data from /src/Service/Mocks/MockData/userregistries.json");
    $this->updateAll(json_decode(file_get_contents(drupal_get_path('module', 'ibm_apim') . '/src/Service/Mocks/MockData/userregistries.json'), TRUE));
  }

  /**
   * get all the user_registries
   *
   * @return NULL if an error occurs otherwise an array of the registries.
   */
  public function getAll() {
    $registries = $this->userRegistries;

    if (empty($registries)) {
      $this->logger->warning('Found no user registries in the catalog config. Potentially missing data from APIM.');
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

    $all_registries = $this->getAll();
    $registry = array_shift($all_registries);

    $this->logger->debug("MockUserRegistryService::get($key) returning " . $registry->getName());

    return $registry;
  }

  /**
   * Update all user_registries
   *
   * @param $data array of user_registries keyed on url
   */
  public function updateAll($data) {
    if (isset($data)) {
      $user_registries = array();
      foreach ($data as $ur) {
        $registry_object = new UserRegistry();
        $registry_object->setValues($ur);
        $user_registries[$ur['url']] = $registry_object;
      }
      $this->userRegistries = $user_registries;
    }
  }

  /**
   * Update a specific user_registry
   *
   * @param $key
   * @param $data
   */
  public function update($key, $data) {
    if (isset($key) && isset($data)) {
      $registry_object = new UserRegistry();
      $registry_object->setValues($data);
      $this->userRegistries[$key] = $registry_object;
    }
  }

  /**
   * Delete a specific user_registry
   *
   * @param $key (user_registries url)
   */
  public function delete($key) {
    if (isset($key)) {
      $new_data = [];
      foreach ($this->getAll() as $url => $value) {
        if ($url != $key) {
          $new_data[$url] = $value;
        }
      }
      $this->userRegistries = $new_data;
    }
  }

  /**
   * Delete all current user registries
   */
  public function deleteAll() {
    $this->userRegistries = array();
  }

  /**
   * @param $identityProviderName
   * @return null
   */
  public function getRegistryContainingIdentityProvider($identityProviderName){
    $result = NULL;
    $all_registries = $this->getAll();
    foreach ($all_registries as $registry) {
      if($registry->hasIdentityProviderNamed($identityProviderName)) {
        $result = $registry;
        break;
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $result);

    return $result;
  }

  /**
   * @inheritdoc
   */
  public function getDefaultRegistry() {

    $all = $this->getAll();

    if (isset($all) && !empty($all)) {
      $defaults = array_filter($all, function($reg) { return $reg->isUserManaged(); } );
      // 0 is possible based on current implementation, but more than 1 is a problem.
      if (sizeof($defaults) > 1) {
        throw new \Exception('Unexpected number of default registries.');
      }
      elseif (sizeof($defaults) === 1) {
        return array_pop($defaults);
      }
      else {
        // no managed user registries so return first non-managed registry if there is one
        return array_pop($all);
      }
    }
    else {
      return NULL;
    }
  }

  public function setDefaultRegistry($url) {
    // TODO: Implement setDefaultRegistry() method.
  }


}
