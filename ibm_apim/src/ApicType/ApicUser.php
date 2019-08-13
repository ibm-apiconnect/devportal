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

/**
 * A user which will be handled via the auth_apic module.
 *
 * Represents the fields that we have in the drupal database.
 * Used to hold the fields from the various forms a user interacts with.
 */
class ApicUser {

  private $username;

  private $password;

  private $mail;

  private $firstname;

  private $lastname;

  private $state;

  private $url;

  private $organization;

  private $consumerorgs;

  private $permissions;

  private $apic_user_registry_url;

  private $apic_idp;

  private $bearer_token;

  private $authcode;

  /**
   * Constructor.
   */
  public function __construct() {
  }

  /**
   * @return mixed
   */
  public function getUsername() {
    return $this->username;
  }

  /**
   * @param mixed $username
   */
  public function setUsername($username): void {
    $this->username = $username;
  }

  /**
   * @return mixed
   */
  public function getPassword() {
    return $this->password;
  }

  /**
   * @param mixed $password
   */
  public function setPassword($password): void {
    $this->password = $password;
  }

  /**
   * @return mixed
   */
  public function getMail() {
    return $this->mail;
  }

  /**
   * @param mixed $mail
   */
  public function setMail($mail): void {
    $this->mail = $mail;
  }

  /**
   * @return mixed
   */
  public function getFirstname() {
    return $this->firstname;
  }

  /**
   * @param mixed $firstname
   */
  public function setFirstname($firstname): void {
    $this->firstname = $firstname;
  }

  /**
   * @return mixed
   */
  public function getLastname() {
    return $this->lastname;
  }

  /**
   * @param mixed $lastname
   */
  public function setLastname($lastname): void {
    $this->lastname = $lastname;
  }

  /**
   * @return mixed
   */
  public function getState() {
    return $this->state;
  }

  /**
   * @param mixed $state
   */
  public function setState($state): void {
    $this->state = $state;
  }

  /**
   * @return mixed
   */
  public function getAuthcode() {
    return $this->authcode;
  }

  /**
   * @param mixed $state
   */
  public function setAuthcode($code): void {
    $this->authcode = $code;
  }


  /**
   * @return mixed
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * the user's ID should be the last part of their URL
   *
   * @return mixed
   */
  public function getId() {
    return basename($this->url);
  }

  /**
   * @param mixed $url
   */
  public function setUrl($url): void {
    $this->url = $url;
  }

  /**
   * @return mixed
   */
  public function getConsumerorgs() {
    return $this->consumerorgs;
  }

  /**
   * @param mixed $consumerorgs
   */
  public function setConsumerorgs($consumerorgs): void {
    $this->consumerorgs = $consumerorgs;
  }

  /**
   * @return mixed
   */
  public function getPermissions() {
    return $this->permissions;
  }

  /**
   * @param mixed $permissions
   */
  public function setPermissions($permissions): void {
    $this->permissions = $permissions;
  }

  /**
   * @return mixed
   */
  public function getApicUserRegistryUrl() {
    return $this->apic_user_registry_url;
  }

  /**
   * @param mixed $apic_user_registry_url
   */
  public function setApicUserRegistryUrl($apic_user_registry_url): void {
    $this->apic_user_registry_url = $apic_user_registry_url;
  }

  /**
   * @return mixed
   */
  public function getApicIdp() {
    return $this->apic_idp;
  }

  /**
   * @param mixed $apic_idp
   */
  public function setApicIdp($apic_idp): void {
    $this->apic_idp = $apic_idp;
  }

  /**
   * @return mixed
   */
  public function getBearerToken() {
    return $this->bearer_token;
  }

  /**
   * @param mixed $bearer_token
   */
  public function setBearerToken($bearer_token): void {
    $this->bearer_token = $bearer_token;
  }


  /**
   * @return mixed
   */
  public function getOrganization() {
    return $this->organization;
  }

  /**
   * @param mixed $organization
   */
  public function setOrganization($organization): void {
    $this->organization = $organization;
  }

  /**
   * convert array to object
   *
   * @param array $content
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
    if (array_key_exists('consumerorgs', $content)) {
      $this->setConsumerorgs($content['consumerorgs']);
    }
    if (array_key_exists('permissions', $content)) {
      $this->setPermissions($content['permissions']);
    }
    if (array_key_exists('apic_idp', $content)) {
      $this->setApicIdp($content['apic_idp']);
    }
    if (array_key_exists('bearer_token', $content)) {
      $this->setBearerToken($content['bearer_token']);
    }
    if (array_key_exists('authcode', $content)) {
      $this->setAuthcode($content['authcode']);
    }
  }

  /**
   * convert object to array
   *
   * @return array
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
    if ($this->bearer_token !== NULL) {
      $content['bearer_token'] = $this->bearer_token;
    }
    if ($this->authcode !== NULL) {
      $content['authcode'] = $this->authcode;
    }
    return $content;
  }
}
