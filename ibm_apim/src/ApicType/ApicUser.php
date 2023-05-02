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

namespace Drupal\ibm_apim\ApicType;

/**
 * A user which will be handled via the auth_apic module.
 *
 * Represents the fields that we have in the drupal database.
 * Used to hold the fields from the various forms a user interacts with.
 */
class ApicUser {

  /**
   * @var string|null
   */
  private ?string $username = NULL;

  /**
   * @var string|null
   */
  private ?string $password = NULL;

  /**
   * @var string|null
   */
  private ?string $mail = NULL;

  /**
   * @var string|null
   */
  private ?string $firstname = NULL;

  /**
   * @var string|null
   */
  private ?string $lastname = NULL;

  /**
   * @var string|null
   */
  private ?string $state = NULL;

  /**
   * @var string|null
   */
  private ?string $url = NULL;

  /**
   * @var string|null
   */
  private ?string $organization = NULL;

  /**
   * @var array|null
   */
  private ?array $consumerorgs = [];

  /**
   * @var array|null
   */
  private ?array $permissions = [];

  /**
   * @var string|null
   */
  private ?string $apic_user_registry_url = NULL;

  /**
   * @var string|null
   */
  private ?string $apic_idp = NULL;

  /**
   * @var string|null
   */
  private ?string $authcode = NULL;

  /**
   * @var array|null
   */
  private ?array $custom_fields = [];

  /**
   * @var array|null
   */
  private ?array $metadata = [];

  /**
   * Constructor.
   */
  public function __construct() {
  }

  /**
   * @return string|null
   */
  public function getDisplayName(): ?string {
    if (($firstname = $this->getFirstname()) && ($lastname = $this->getLastname())) {
      $name = "{$firstname}  {$lastname}";
    } else {
      $name = $this->getMail();
    }
    return $name ? "{$this->getUsername()} ({$name})" : $this->getUsername();

  }

  /**
   * @return string|null
   */
  public function getUsername(): ?string {
    return $this->username;
  }

  /**
   * @param string|null $username
   */
  public function setUsername(?string $username): void {
    $this->username = $username;
  }

  /**
   * @return string|null
   */
  public function getPassword(): ?string {
    return $this->password;
  }

  /**
   * @param string|null $password
   */
  public function setPassword(?string $password): void {
    $this->password = $password;
  }

  /**
   * @return string|null
   */
  public function getMail(): ?string {
    return $this->mail;
  }

  /**
   * @param string|null $mail
   */
  public function setMail(?string $mail): void {
    $this->mail = $mail;
  }

  /**
   * @return string|null
   */
  public function getFirstname(): ?string {
    return $this->firstname;
  }

  /**
   * @param string|null $firstname
   */
  public function setFirstname(?string $firstname): void {
    $this->firstname = $firstname;
  }

  /**
   * @return string|null
   */
  public function getLastname(): ?string {
    return $this->lastname;
  }

  /**
   * @param string|null $lastname
   */
  public function setLastname(?string $lastname): void {
    $this->lastname = $lastname;
  }

  /**
   * @return string|null
   */
  public function getState(): ?string {
    return $this->state;
  }

  /**
   * @param string|null $state
   */
  public function setState(?string $state): void {
    $this->state = $state;
  }

  /**
   * @return string|null
   */
  public function getAuthcode(): ?string {
    return $this->authcode;
  }

  /**
   * @param string|null $code
   */
  public function setAuthcode(?string $code): void {
    $this->authcode = $code;
  }


  /**
   * @return string|null
   */
  public function getUrl(): ?string {
    return $this->url;
  }

  /**
   * the user's ID should be the last part of their URL
   *
   * @return string|null
   */
  public function getId(): ?string {
    return basename($this->url ?? '');
  }

  /**
   * @param string|null $url
   */
  public function setUrl(?string $url): void {
    $this->url = $url;
  }

  /**
   * @return array|null
   */
  public function getConsumerorgs(): ?array {
    return $this->consumerorgs;
  }

  /**
   * @param array|null $consumerorgs
   */
  public function setConsumerorgs(?array $consumerorgs): void {
    $this->consumerorgs = $consumerorgs;
  }

  /**
   * @return array|null
   */
  public function getPermissions(): ?array {
    return $this->permissions;
  }

  /**
   * @param array|null $permissions
   */
  public function setPermissions(?array $permissions): void {
    $this->permissions = $permissions;
  }

  /**
   * @return string|null
   */
  public function getApicUserRegistryUrl(): ?string {
    return $this->apic_user_registry_url;
  }

  /**
   * @param string|null $apic_user_registry_url
   */
  public function setApicUserRegistryUrl(?string $apic_user_registry_url): void {
    $this->apic_user_registry_url = $apic_user_registry_url;
  }

  /**
   * @return string|null
   */
  public function getApicIdp(): ?string {
    return $this->apic_idp;
  }

  /**
   * @param string|null $apic_idp
   */
  public function setApicIdp(?string $apic_idp): void {
    $this->apic_idp = $apic_idp;
  }

  /**
   * Get the string name provided on the register form
   *
   * @return string|null
   */
  public function getOrganization(): ?string {
    return $this->organization;
  }

  /**
   * This is the org name provided on the register form
   *
   * @param string|null $organization
   */
  public function setOrganization(?string $organization): void {
    $this->organization = $organization;
  }

  /**
   * @return array
   */
  public function getCustomFields(): array {
    if (!isset($this->custom_fields)) {
      $this->custom_fields = [];
    }
    return $this->custom_fields;
  }

  /**
   * @param array $customFields
   */
  public function setCustomFields(?array $customFields): void {
    if (empty($customFields)) {
      $this->custom_fields = [];
    }
    else {
      $this->custom_fields = $customFields;
    }
  }

  /**
   * @param $field
   * @param $value
   */
  public function addCustomField($field, $value): void {
    if (!isset($this->custom_fields)) {
      $this->custom_fields = [];
    }
    $this->custom_fields[$field] = $value;
  }

  /**
   * @return array
   */
  public function getMetadata(): array {
    if (!isset($this->metadata)) {
      $this->metadata = [];
    }
    return $this->metadata;
  }

  /**
   * @param array $metadata
   */
  public function setMetadata(?array $metadata): void {
    if (empty($metadata)) {
      $this->metadata = [];
    }
    else {
      $this->metadata = $metadata;
    }
  }


  /**
   * convert array to object
   *
   * @param array $content
   *
   * @throws \JsonException
   */
  public function createFromArray(array $content): void {
    $apimUtils = \Drupal::service('ibm_apim.apim_utils');

    if (array_key_exists('username', $content)) {
      $this->setUsername($content['username']);
    }
    if (array_key_exists('url', $content)) {
      $this->setUrl($apimUtils->removeFullyQualifiedUrl($content['url']));
    }
    if (array_key_exists('password', $content)) {
      $this->setPassword($content['password']);
    }
    if (array_key_exists('mail', $content)) {
      $this->setMail($content['mail']);
    }
    if (array_key_exists('firstname', $content)) {
      $this->setFirstname($content['firstname']);
    }
    if (array_key_exists('lastname', $content)) {
      $this->setLastname($content['lastname']);
    }
    if (array_key_exists('state', $content)) {
      $this->setState($content['state']);
    }

    // TODO: from create consumer org response
    // TODO: we have a user_registry_url at this point - we should be on a createFromJson flow?
    // doing this first so it is overridden if other paths through use something else
    if (array_key_exists('user_registry_url', $content)) {
      $this->setApicUserRegistryUrl($apimUtils->removeFullyQualifiedUrl($content['user_registry_url']));
    }

    if (array_key_exists('apic_user_registry_url', $content)) {
      $this->setApicUserRegistryUrl($apimUtils->removeFullyQualifiedUrl($content['apic_user_registry_url']));
    }
    // override with registry_url - apic_user_registry_url will be removed.
    if (array_key_exists('registry_url', $content)) {
      $this->setApicUserRegistryUrl($apimUtils->removeFullyQualifiedUrl($content['registry_url']));
    }

    if (array_key_exists('organization', $content)) {
      $this->setOrganization($content['organization']);
    }
    if (array_key_exists('consumerorgs', $content) && !empty($content['consumerorgs'])) {
      $this->setConsumerorgs($content['consumerorgs']);
    } else {
      $this->setConsumerorgs([]);
    }
    if (array_key_exists('permissions', $content) && !empty($content['permissions'])) {
      $this->setPermissions($content['permissions']);
    } else {
      $this->setPermissions([]);
    }
    if (array_key_exists('apic_idp', $content)) {
      $this->setApicIdp($content['apic_idp']);
    }
    if (array_key_exists('authcode', $content)) {
      $this->setAuthcode($content['authcode']);
    }

    $customFields = \Drupal::service('ibm_apim.apicuser')->getMetadataFields();
    foreach ($customFields as $field) {
      if (array_key_exists($field, $content)) {
        $this->addCustomField($field, json_decode($content[$field], TRUE, 512, JSON_THROW_ON_ERROR));
      }
    }
  }

  /**
   * convert object to array
   *
   * @return array
   * @throws \JsonException
   */
  public function toArray(): array {
    $content = [];
    if ($this->username !== NULL) {
      $content['username'] = $this->username;
    }
    if ($this->url !== NULL) {
      $content['url'] = $this->url;
    }
    if ($this->password !== NULL) {
      $content['password'] = $this->password;
    }
    if ($this->mail !== NULL) {
      $content['mail'] = $this->mail;
    }
    if ($this->firstname !== NULL) {
      $content['firstname'] = $this->firstname;
    }
    if ($this->lastname !== NULL) {
      $content['lastname'] = $this->lastname;
    }
    if ($this->state !== NULL) {
      $content['state'] = $this->state;
    }
    if ($this->apic_user_registry_url !== NULL) {
      $content['apic_user_registry_url'] = $this->apic_user_registry_url;
      $content['registry_url'] = $this->apic_user_registry_url;
    }
    if ($this->organization !== NULL) {
      $content['organization'] = $this->organization;
    }
    if ($this->consumerorgs !== NULL) {
      $content['consumerorgs'] = $this->consumerorgs;
    }
    if ($this->permissions !== NULL) {
      $content['permissions'] = $this->permissions;
    }
    if ($this->apic_idp !== NULL) {
      $content['apic_idp'] = $this->apic_idp;
    }
    if ($this->authcode !== NULL) {
      $content['authcode'] = $this->authcode;
    }
    $customFields = \Drupal::service('ibm_apim.apicuser')->getMetadataFields();
    foreach ($customFields as $field) {
      if (isset($this->custom_fields[$field])) {
        $content[$field] = json_encode($this->custom_fields[$field], JSON_THROW_ON_ERROR);
      }
    }
    return $content;
  }

}
