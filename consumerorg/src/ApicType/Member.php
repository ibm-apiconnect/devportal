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

namespace Drupal\consumerorg\ApicType;


use Drupal\ibm_apim\ApicType\ApicUser;

class Member {

  private $url;

  private $state;

  private $user_url;

  /** @var ApicUser */
  private $user;

  private $role_urls = [];

  private $org_url;

  /**
   * Gets the user that this member record relates to.
   *
   * @return \Drupal\ibm_apim\ApicType\ApicUser
   */
  public function getUser(): \Drupal\ibm_apim\ApicType\ApicUser {
    return $this->user;
  }

  /**
   * Sets the user for this Member record.
   *
   * @param \Drupal\ibm_apim\ApicType\ApicUser $user
   */
  public function setUser($user): void {
    $this->user = $user;
  }

  /**
   * @return string
   */
  public function getUserUrl(): string {
    return $this->user_url;
  }

  /**
   * @param mixed $user_url
   */
  public function setUserUrl($user_url): void {
    $this->user_url = $user_url;
  }

  /**
   * Gets the role urls for this member. Role definitions are stored on a consumer org.
   * objects.
   *
   * @return array
   */
  public function getRoleUrls(): array {
    return $this->role_urls;
  }

  /**
   * Set the roles for this member. Role definitions are stored on a consumer org.
   *
   * @param array $role_urls
   */
  public function setRoleUrls($role_urls): void {
    $this->role_urls = $role_urls;
  }

  /**
   * @return string
   */
  public function getUrl(): string {
    return $this->url;
  }

  /**
   * @param string $url
   */
  public function setUrl($url): void {
    $this->url = $url;
  }

  /**
   * @return string
   */
  public function getOrgUrl(): string {
    return $this->org_url;
  }

  /**
   * @param string $org_url
   */
  public function setOrgUrl($org_url): void {
    $this->org_url = $org_url;
  }

  /**
   * @return string
   */
  public function getId(): string {
    return basename($this->url);
  }

  /**
   * @return string
   */
  public function getState(): string {
    return $this->state;
  }

  /**
   * @param string $state
   */
  public function setState($state): void {
    $this->state = $state;
  }

  /**
   * convert array to object
   *
   * @param array $content
   */
  public function createFromArray(array $content): void {

    if (array_key_exists('url', $content)) {
      $this->setUrl($content['url']);
    }
    if (array_key_exists('state', $content)) {
      $this->setState($content['state']);
    }
    if (array_key_exists('user_url', $content)) {
      $this->setUserUrl($content['user_url']);
    } elseif (array_key_exists('user', $content) && array_key_exists('url', $content['user'])) {
      $this->setUserUrl($content['user']['url']);
    }
    if (array_key_exists('role_urls', $content)) {
      $this->setRoleUrls($content['role_urls']);
    }
    if (array_key_exists('org_url', $content)) {
      $this->setOrgUrl($content['org_url']);
    }
    if (array_key_exists('user', $content)) {
      $user = new ApicUser();
      $user->createFromArray($content['user']);
      $this->setUser($user);
    }
  }

  /**
   * Convert object to array
   *
   * @return array
   */
  public function toArray(): array {
    $content = [];
    if ($this->url !== NULL) {
      $content['url'] = $this->url;
    }
    if ($this->state !== NULL) {
      $content['state'] = $this->state;
    }
    if ($this->user_url !== NULL) {
      $content['user_url'] = $this->user_url;
    }
    if ($this->role_urls !== NULL) {
      $content['role_urls'] = $this->role_urls;
    }
    if ($this->org_url !== NULL) {
      $content['org_url'] = $this->org_url;
    }
    if ($this->user !== NULL) {
      $content['user'] = $this->user->toArray();
    }
    return $content;
  }
}
