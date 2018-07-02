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
  private $id;

  private $organization;
  private $consumerorgs;

  private $permissions;

  private $apic_user_registry_url;
  private $apic_idp;
  private $bearer_token;

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
  public function setUsername($username) {
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
  public function setPassword($password) {
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
  public function setMail($mail) {
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
  public function setFirstname($firstname) {
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
  public function setLastname($lastname) {
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
  public function setState($state) {
    $this->state = $state;
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
  public function setUrl($url) {
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
  public function setConsumerorgs($consumerorgs) {
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
  public function setPermissions($permissions) {
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
  public function setApicUserRegistryUrl($apic_user_registry_url) {
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
  public function setApicIdp($apic_idp) {
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
  public function setBearerToken($bearer_token) {
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
  public function setOrganization($organization) {
    $this->organization = $organization;
  }

  public function getDrupalUser() {
    return user_load_by_name($this->getUsername());
  }

  public function getDrupalUid() {
    $return = null;
    $user= user_load_by_name($this->getUsername());
    if (isset($user)) {
      $return = $user->id();
    }
    return $return;
  }
}
