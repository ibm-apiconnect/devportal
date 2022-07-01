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

namespace Drupal\ibm_apim\Service\Interfaces;

use Drupal\Core\State\StateInterface;
use Drupal\ibm_apim\ApicType\UserRegistry;
use Psr\Log\LoggerInterface;

/**
 * Functionality for handling user registries
 */
interface UserRegistryServiceInterface {

  public function __construct(StateInterface $state, LoggerInterface $logger);

  /**
   * get all the user_registries
   *
   * @return array an array of the registries.
   */
  public function getAll(): array;

  /**
   * get a specific user_registry by url
   *
   * @param string $key
   *
   * @return null|UserRegistry
   */
  public function get(string $key): ?UserRegistry;

  /**
   * Update all user_registries
   *
   * @param array $data array of user_registries keyed on url
   */
  public function updateAll(array $data): bool;

  /**
   * Update a specific user_registry
   *
   * @param string $key
   * @param array $data
   */
  public function update(string $key, array $data);

  /**
   * Delete a specific user_registry
   *
   * @param string $key (user_registries url)
   */
  public function delete(string $key);

  /**
   * Delete all current user registries
   */
  public function deleteAll();

  /**
   * @param string $identityProviderName
   *
   * @return UserRegistry
   */
  public function getRegistryContainingIdentityProvider(string $identityProviderName): ?UserRegistry;


  /**
   * The site has a default registry, this is stored in the site config.hange in the future.
   *
   * @return UserRegistry
   *   The default user registry.
   * @throws \Exception if not default registry is found.
   *
   */
  public function getDefaultRegistry(): ?UserRegistry;

  /**
   * Set default registry for this site.
   *
   * @param string|null $url
   *
   * @return mixed
   */
  public function setDefaultRegistry(?string $url);


  /**
   * Get the registry url which is used for the admin user.
   * This is not a real user registry that exists in apim or elsewhere.
   *
   * @return string|null
   */
  public function getAdminRegistryUrl(): ?string;

}
