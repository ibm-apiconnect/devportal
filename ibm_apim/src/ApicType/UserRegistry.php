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

namespace Drupal\ibm_apim\ApicType;


use Drupal\Core\Url;

class UserRegistry {

  private $id;

  private $name;

  private $title;

  private $url;

  private $summary;

  private $registry_type;

  private $user_managed = FALSE;

  private $user_registry_managed = FALSE;

  private $onboarding = FALSE;

  private $case_sensitive = FALSE;

  private $identity_providers = [];

  private $provider_type;

  /**
   * @return null|string
   */
  public function getId(): ?string {
    return $this->id;
  }

  /**
   * @param null $id
   */
  public function setId($id): void {
    $this->id = $id;
  }

  /**
   * @return null|string
   */
  public function getName(): ?string {
    return $this->name;
  }

  /**
   * @param string $name
   */
  public function setName($name): void {
    $this->name = $name;
  }

  /**
   * @return null|string
   */
  public function getTitle(): ?string {
    return $this->title;
  }

  /**
   * @param string $title
   */
  public function setTitle($title): void {
    $this->title = $title;
  }

  /**
   * @return null|string
   */
  public function getUrl(): ?string {
    return $this->url;
  }

  /**
   * @param string $url
   */
  public function setUrl($url): void {
    $this->url = $url;
  }

  /**
   * @return null|string
   */
  public function getSummary(): ?string {
    return $this->summary;
  }

  /**
   * @param string $summary
   */
  public function setSummary($summary): void {
    $this->summary = $summary;
  }

  /**
   * @return null|string
   */
  public function getRegistryType(): ?string {
    return $this->registry_type;
  }

  /**
   * @param string $registry_type
   */
  public function setRegistryType($registry_type): void {
    $this->registry_type = $registry_type;
  }

  /**
   * @return bool
   */
  public function isUserManaged(): bool {
    return $this->user_managed;
  }

  /**
   * @param bool $user_managed
   */
  public function setUserManaged(bool $user_managed): void {
    $this->user_managed = $user_managed;
  }

  /**
   * @return bool
   */
  public function isUserRegistryManaged(): bool {
    return $this->user_registry_managed;
  }

  /**
   * @param bool $user_registry_managed
   */
  public function setUserRegistryManaged(bool $user_registry_managed): void {
    $this->user_registry_managed = $user_registry_managed;
  }

  /**
   * @return bool
   */
  public function isOnboarding(): bool {
    return $this->onboarding;
  }

  /**
   * @param bool $onboarding
   */
  public function setOnboarding(bool $onboarding): void {
    $this->onboarding = $onboarding;
  }

  /**
   * @return bool
   */
  public function isCaseSensitive(): bool {
    return $this->case_sensitive;
  }

  /**
   * @param bool $case_sensitive
   */
  public function setCaseSensitive(bool $case_sensitive): void {
    $this->case_sensitive = $case_sensitive;
  }

  /**
   * @return null|string
   */
  public function getProviderType(): ?string {
    return $this->provider_type;
  }

  /**
   * @param string $provider_type
   */
  public function setProviderType($provider_type): void {
    $this->provider_type = $provider_type;
  }

  /**
   * @return array
   */
  public function getIdentityProviders(): array {
    return $this->identity_providers;
  }

  /**
   * @param array $identity_providers
   */
  public function setIdentityProviders(array $identity_providers): void {
    $this->identity_providers = $identity_providers;
  }

  /**
   * Attempts to find an identity provider with the specified name
   * in this user registry. Returns NULL if there is no such IDP
   * or an array representing the IDP otherwise.
   *
   * @param $idpNameToFind
   *
   * @return array|null
   */
  public function getIdentityProviderByName($idpNameToFind): ?array {

    $result = NULL;

    if (!empty($this->getIdentityProviders())) {
      foreach ($this->getIdentityProviders() as $idp) {
        if ($idp['name'] === $idpNameToFind) {
          $result = $idp;
          break;
        }
      }
    }

    return $result;
  }

  /**
   * Determines if this user registry contains an identity provider
   * with the specified name.
   *
   * @param $idpNameToFind
   *
   * @return bool
   */
  public function hasIdentityProviderNamed($idpNameToFind): bool {
    return ($this->getIdentityProviderByName($idpNameToFind) !== NULL);
  }

  /**
   * Constructs the 'realm' string for the given named IDP
   *
   * @param $idpName
   *
   * @return string
   */
  public function getRealmForIdp($idpName): string {
    $config_service = \Drupal::service('ibm_apim.site_config');
    return 'consumer:' . $config_service->getOrgId() . ':' . $config_service->getEnvId() . '/' . $idpName;
  }

  /**
   * This is a temporary function - there is only one IDP right now so we can
   * hard code the realm for that idp.
   *
   * @return null|string
   * @deprecated
   */
  public function getRealm(): ?string {
    if (isset($this->getIdentityProviders()[0]['name'])) {
      return $this->getRealmForIdp($this->getIdentityProviders()[0]['name']);
    }
    else {
      return NULL;
    }
  }

  /**
   * Get the url which initiates the OIDC authentication.
   *
   * // TODO - too complicated for a type class, move somewhere we can inject dependencies - UserRegistryService probably
   */
  public function getOIDCauthUrl(): ?string {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    if ($this->getRegistryType() !== 'oidc') {
      \Drupal::logger('ibm_apim')->warning('attempt to get oidc authentication URL from non-oidc registry');
      return NULL;
    }

    $state = \Drupal::service('state');
    $client_id = $state->get('ibm_apim.site_client_id');

    if ($client_id === NULL) {
      \Drupal::logger('ibm_apim')->warning('unable to retrieve site client id to build oidc authentication url');
      return NULL;
    }

    $utils = \Drupal::service('ibm_apim.utils');
    $state_param = $utils->base64_url_encode($this->getUrl());
    $apim_utils = \Drupal::service('ibm_apim.apim_utils');
    $host = $apim_utils->getHostUrl();
    $route = URL::fromRoute('auth_apic.azcode')->toString();

    $redirect_uri = $host . $route;

    $url = $apim_utils->createFullyQualifiedUrl('/consumer-api/oauth2/authorize');

    $url = $url . '?client_id=' . $client_id;
    $url = $url . '&state=' . $state_param;
    $url = $url . '&redirect_uri=' . $redirect_uri;
    $url = $url . '&realm=' . $this->getRealm(); // TODO: need to support multiple IDPS.
    $url = $url . '&response_type=code';

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $url);
    }
    return $url;

  }

  /**
   * Extract a single UserRegistry definition from a JSON string
   * representation e.g. as returned by a call to the consumer-api.
   *
   * @param array $registryJson
   */
  public function setValues($registryJson): void {

    if (is_string($registryJson)) {
      $registryJson = json_decode($registryJson, 1);
    }

    $this->setId($registryJson['id']);
    $this->setName($registryJson['name']);
    $this->setTitle($registryJson['title']);
    $this->setUrl($registryJson['url']);
    $this->setSummary($registryJson['summary']);
    $this->setRegistryType($registryJson['registry_type']);
    if ((boolean) $registryJson['user_managed'] === TRUE) {
      $this->setUserManaged(TRUE);
    }
    else {
      $this->setUserManaged(FALSE);
    }
    if ((boolean) $registryJson['user_registry_managed'] === TRUE) {
      $this->setUserRegistryManaged(TRUE);
    }
    else {
      $this->setUserRegistryManaged(FALSE);
    }
    if ((boolean) $registryJson['onboarding'] === TRUE) {
      $this->setOnboarding(TRUE);
    }
    else {
      $this->setOnboarding(FALSE);
    }
    if ((boolean) $registryJson['case_sensitive'] === TRUE) {
      $this->setCaseSensitive(TRUE);
    }
    else {
      $this->setCaseSensitive(FALSE);
    }
    $this->setIdentityProviders($registryJson['identity_providers']);
    if (isset($registryJson['configuration']['provider_type'])) {
      $this->setProviderType($registryJson['configuration']['provider_type']);
    }

  }

}
