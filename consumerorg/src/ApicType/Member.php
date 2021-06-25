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

namespace Drupal\consumerorg\ApicType;


use Drupal\ibm_apim\ApicType\ApicUser;

class Member {

  /**
   * @var string|NULL
   */
  private ?string $url = NULL;

  /**
   * @var string|NULL
   */
  private ?string $state = NULL;

  /**
   * @var string|NULL
   */
  private ?string $user_url = NULL;

  /**
   * @var \Drupal\ibm_apim\ApicType\ApicUser|NULL
   */
  private ?ApicUser $user = NULL;

  /**
   * @var array|NULL
   */
  private ?array $role_urls = [];

  /**
   * @var string|NULL
   */
  private ?string $org_url = NULL;

  /**
   * @var string|null
   */
  private ?string $created_at = NULL;

  /**
   * @var string|null
   */
  private ?string $updated_at = NULL;


  /**
   * Gets the user that this member record relates to.
   *
   * @return \Drupal\ibm_apim\ApicType\ApicUser|null
   */
  public function getUser(): ?ApicUser {
    return $this->user;
  }

  /**
   * Sets the user for this Member record.
   *
   * @param \Drupal\ibm_apim\ApicType\ApicUser $user
   */
  public function setUser(ApicUser $user): void {
    $this->user = $user;
  }

  /**
   * @return string|null
   */
  public function getUserUrl(): ?string {
    return $this->user_url;
  }

  /**
   * @param string $user_url
   */
  public function setUserUrl(string $user_url): void {
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
  public function setRoleUrls(array $role_urls): void {
    $this->role_urls = $role_urls;
  }

  /**
   * @return string|null
   */
  public function getUrl(): ?string {
    return $this->url;
  }

  /**
   * @param string $url
   */
  public function setUrl(string $url): void {
    $this->url = $url;
  }

  /**
   * @return string|null
   */
  public function getOrgUrl(): ?string {
    return $this->org_url;
  }

  /**
   * @param string $org_url
   */
  public function setOrgUrl(string $org_url): void {
    $this->org_url = $org_url;
  }

  /**
   * @return string|null
   */
  public function getId(): ?string {
    return basename($this->url);
  }

  /**
   * @return string
   */
  public function getState(): ?string {
    return $this->state;
  }

  /**
   * @param string|null $state
   */
  public function setState(string $state): void {
    $this->state = $state;
  }

  /**
   * @return string
   */
  public function getCreatedAt(): ?string {
    return $this->created_at;
  }

  /**
   * @param string $created_at
   */
  public function setCreatedAt(string $created_at): void {
    $this->created_at = $created_at;
  }

  /**
   * @return string
   */
  public function getUpdatedAt(): ?string {
    return $this->updated_at;
  }

  /**
   * @param string $updated_at
   */
  public function setUpdatedAt(string $updated_at): void {
    $this->updated_at = $updated_at;
  }

  /**
   * convert array to object
   *
   * @param array $content
   *
   * @throws \JsonException
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
    }
    elseif (array_key_exists('user', $content) && array_key_exists('url', $content['user'])) {
      $this->setUserUrl($content['user']['url']);
    }
    if (array_key_exists('role_urls', $content)) {
      $this->setRoleUrls($content['role_urls']);
    }
    if (array_key_exists('org_url', $content)) {
      $this->setOrgUrl($content['org_url']);
    }
    if (array_key_exists('created_at', $content)) {
      $this->setCreatedAt(strtotime($content['created_at']));
    }
    if (array_key_exists('updated_at', $content)) {
      $this->setUpdatedAt(strtotime($content['updated_at']));
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
   * @throws \JsonException
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
    if ($this->created_at !== NULL) {
      $content['created_at'] = $this->created_at;
    }
    if ($this->updated_at !== NULL) {
      $content['updated_at'] = $this->updated_at;
    }
    if ($this->user !== NULL) {
      $content['user'] = $this->user->toArray();
    }
    return $content;
  }

}
