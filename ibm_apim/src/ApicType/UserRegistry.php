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

namespace Drupal\ibm_apim\ApicType;


class UserRegistry {

  private $id = NULL;
  private $name = NULL;
  private $title = NULL;
  private $url = NULL;
  private $summary = NULL;
  private $registry_type = NULL;
  private $user_managed = false;
  private $user_registry_managed = false;
  private $onboarding = false;
  private $case_sensitive = false;
  private $identity_providers = array();

  /**
   * @return null
   */
  public function getId() {
    return $this->id;
  }

  /**
   * @param null $id
   */
  public function setId($id) {
    $this->id = $id;
  }

  /**
   * @return null
   */
  public function getName() {
    return $this->name;
  }

  /**
   * @param null $name
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * @return null
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * @param null $title
   */
  public function setTitle($title) {
    $this->title = $title;
  }

  /**
   * @return null
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * @param null $url
   */
  public function setUrl($url) {
    $this->url = $url;
  }

  /**
   * @return null
   */
  public function getSummary() {
    return $this->summary;
  }

  /**
   * @param null $summary
   */
  public function setSummary($summary) {
    $this->summary = $summary;
  }

  /**
   * @return null
   */
  public function getRegistryType() {
    return $this->registry_type;
  }

  /**
   * @param null $registry_type
   */
  public function setRegistryType($registry_type) {
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
  public function setUserManaged(bool $user_managed) {
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
  public function setUserRegistryManaged(bool $user_registry_managed) {
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
  public function setOnboarding(bool $onboarding) {
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
  public function setCaseSensitive(bool $case_sensitive) {
    $this->case_sensitive = $case_sensitive;
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
  public function setIdentityProviders(array $identity_providers) {
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
  public function getIdentityProviderByName($idpNameToFind){

    $result = NULL;

    if(!empty($this->getIdentityProviders())){
      foreach ($this->getIdentityProviders() as $idp) {
        if($idp['name'] === $idpNameToFind){
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
  public function hasIdentityProviderNamed($idpNameToFind) {
    return ($this->getIdentityProviderByName($idpNameToFind) !== NULL);
  }

  /**
   * Constructs the 'realm' string for the given named IDP
   *
   * @param $idpName
   *
   * @return string
   */
  public function getRealmForIdp($idpName) {
    $config_service = \Drupal::service('ibm_apim.site_config');
    return "consumer:" . $config_service->getOrgId() . ":" . $config_service->getEnvId() . "/" . $idpName;
  }

  /**
   * This is a temporary function - there is only one IDP right now so we can
   * hard code the realm for that idp.
   *
   * @return string
   * @deprecated
   */
  public function getRealm() {
    return $this->getRealmForIdp($this->getIdentityProviders()[0]['name']);
  }

  /**
   * Extract a single UserRegistry definition from a JSON string
   * representation e.g. as returned by a call to the consumer-api.
   *
   * @param $registryJson
   */
  public function setValues($registryJson) {

    if(is_string($registryJson)) {
      $registryJson = json_decode($registryJson, 1);
    }

    $this->setId($registryJson['id']);
    $this->setName($registryJson['name']);
    $this->setTitle($registryJson['title']);
    $this->setUrl($registryJson['url']);
    $this->setSummary($registryJson['summary']);
    $this->setRegistryType($registryJson['registry_type']);
    if ($registryJson['user_managed'] === true) {
      $this->setUserManaged(true);
    } else {
      $this->setUserManaged(false);
    }
    if ($registryJson['user_registry_managed'] === true) {
      $this->setUserRegistryManaged(true);
    } else {
      $this->setUserRegistryManaged(false);
    }
    if ($registryJson['onboarding'] === true) {
      $this->setOnboarding(true);
    } else {
      $this->setOnboarding(false);
    }
    if ($registryJson['case_sensitive'] === true) {
      $this->setCaseSensitive(true);
    } else {
      $this->setCaseSensitive(false);
    }
    $this->setIdentityProviders($registryJson['identity_providers']);

  }

}