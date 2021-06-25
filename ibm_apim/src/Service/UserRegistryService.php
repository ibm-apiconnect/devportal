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

namespace Drupal\ibm_apim\Service;

use Drupal\Core\State\StateInterface;
use Drupal\ibm_apim\ApicType\UserRegistry;
use Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface;
use Psr\Log\LoggerInterface;

/**
 * Functionality for handling user registries
 */
class UserRegistryService implements UserRegistryServiceInterface {

  // injected registry url for admin user. This does not represent a user registry in apim or anywhere else.
  private $admin_registry_url = '/admin';

  protected $state;

  protected $logger;

  public function __construct(StateInterface $state, LoggerInterface $logger) {
    $this->state = $state;
    $this->logger = $logger;
  }

  /**
   * get all the user_registries
   *
   * @return array an array of the registries.
   */
  public function getAll(): array {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    $registries = $this->state->get('ibm_apim.user_registries');

    if ($registries === NULL || empty($registries)) {
      $this->logger->warning('Found no user registries in the catalog config. Potentially missing data from APIM.');
      $registries = [];
    }
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    return $registries;

  }

  /**
   * get a specific user_registry by url
   *
   * @param string $key
   *
   * @return null|UserRegistry
   */
  public function get(string $key): ?UserRegistry {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $key);
    }

    $registry = NULL;
    if (isset($key)) {
      // clear caches if config different to previous requests
      //$current_data = $this->state->get('ibm_apim.user_registries');
      $current_data = $this->getAll();

      if (isset($current_data[$key])) {
        $registry = $current_data[$key];
      }
    }

    if (function_exists('ibm_apim_exit_trace')) {
      $url = NULL;
      if ($registry !== NULL) {
        $url = $registry->getUrl();
      }
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $url);
    }
    return $registry;
  }

  /**
   * Update all user_registries
   *
   * @param array $data
   *
   * @return bool
   */
  public function updateAll(array $data): bool {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $success = FALSE;
    if (isset($data)) {
      $user_registries = [];
      foreach ($data as $ur) {
        if (is_object($ur) && $ur instanceof UserRegistry) {
          $user_registries[$ur->getUrl()] = $ur;
        }
        else {
          $registry_object = new UserRegistry();
          $registry_object->setValues($ur);
          $user_registries[$ur['url']] = $registry_object;
        }
      }
      $this->state->set('ibm_apim.user_registries', $user_registries);
      $success = TRUE;
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $success);
    }
    return $success;
  }

  /**
   * Update a specific user_registry
   *
   * @param string $key
   * @param array $data
   */
  public function update(string $key, array $data): void {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $key);
    }

    if (isset($key, $data)) {
      $current_data = $this->state->get('ibm_apim.user_registries');

      if (!is_array($current_data)) {
        $current_data = [];
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
   * @param string $key (user_registries url)
   */
  public function delete(string $key): void {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $key);
    }

    if (isset($key)) {
      $current_data = $this->state->get('ibm_apim.user_registries');

      if (isset($current_data)) {
        $new_data = [];
        foreach ($current_data as $url => $value) {
          if ($url !== $key) {
            $new_data[$url] = $value;
          }
        }
        $this->state->set('ibm_apim.user_registries', $new_data);
      }
      // delete all users from this user registry
      $query = \Drupal::entityTypeManager()->getStorage('user')->getQuery();
      $query->condition('apic_user_registry_url', $key);
      $results = $query->execute();
      if ($results !== NULL && !empty($results)) {
        foreach ($results as $id) {
          // DO NOT DELETE THE ADMIN USER!
          if ((int) $id > 1) {
            user_cancel([], $id, 'user_cancel_reassign');
          }
        }
      }
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
  }

  /**
   * Delete all current user registries
   */
  public function deleteAll(): void {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $this->state->set('ibm_apim.user_registries', []);

    // TODO this needs to delete all users

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
  }

  /**
   * @param string $identityProviderName
   *
   * @return UserRegistry|null
   */
  public function getRegistryContainingIdentityProvider(string $identityProviderName): ?UserRegistry {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $result = NULL;
    $all_registries = $this->state->get('ibm_apim.user_registries');
    foreach ($all_registries as $registry) {
      if ($registry->hasIdentityProviderNamed($identityProviderName)) {
        $result = $registry;
        break;
      }
    }

    if (function_exists('ibm_apim_exit_trace')) {
      $url = NULL;
      if ($result !== NULL) {
        $url = $result->getUrl();
      }
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $url);
    }

    return $result;
  }

  /**
   * @inheritdoc
   */
  public function getDefaultRegistry(): ?UserRegistry {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $default_url = $this->state->get('ibm_apim.default_user_registry');
    $default = NULL;

    $fallback_required = FALSE;
    if (!$default_url) {
      $this->logger->debug('Unexpected result while retrieving default registry - none set.');
      $fallback_required = TRUE;
    }
    else {
      $default = $this->get($default_url);
      if (!$default) {
        $this->logger->debug('Unexpected result while retrieving default registry - not found: %defaultUrl', ['%defaultUrl' => $default_url]);
        $fallback_required = TRUE;
      }
    }

    if ($fallback_required) {
      $default = $this->calculateFallbackDefaultRegistry();
      if ($default) {
        $this->setDefaultRegistry($default->getUrl());
      }
    }

    if (function_exists('ibm_apim_exit_trace')) {
      $url = NULL;
      if ($default) {
        $url = $default->getUrl();
      }
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $url);
    }
    return $default;
  }

  /**
   * @return mixed|null
   */
  private function calculateFallbackDefaultRegistry() {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $default = NULL;

    $all_registries = $this->getAll();

    if (sizeof($all_registries) === 0) {
      $this->logger->warning('No registries available when trying to calculate the default.');
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return NULL;
    }

    // if we have any user_managed registries then use the first of them, otherwise just return the first one.
    $user_managed = array_filter($all_registries, static function ($reg) {
      return $reg->isUserManaged();
    });
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

  /**
   * @param string|null $url
   *
   * @return void
   */
  public function setDefaultRegistry(?string $url): void {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $url);
    }
    $this->state->set('ibm_apim.default_user_registry', $url);
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
  }

  /**
   * @inheritdoc
   */
  public function getAdminRegistryUrl(): string {
    return $this->admin_registry_url;
  }

  /**
   * This is used bu the session manager to decide what cookie setting to use
   *
   * @return bool
   */
  public function isOidcRegistryPresent(): bool {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $result = FALSE;
    $all_registries = $this->state->get('ibm_apim.user_registries');
    if (is_array($all_registries) && !empty($all_registries)) {
      foreach ($all_registries as $registry) {
        if ($registry->getRegistryType() === 'oidc') {
          $result = TRUE;
        }
      }
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $result);
    }

    return $result;
  }

}
