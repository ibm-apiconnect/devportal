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


class Role {

  public $id;

  public $url;

  public $name;

  public $title;

  public $summary;

  public $permissions;

  public $scope;

  public $org_url;

  /**
   * @return string
   */
  public function getId(): string {
    return $this->id;
  }

  /**
   * @param string $id
   */
  public function setId($id): void {
    $this->id = $id;
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
  public function setUrl($url): void {
    $this->url = $url;
  }

  /**
   * @return string|null
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
   * @return string
   */
  public function getTitle(): string {
    return $this->title;
  }

  /**
   * @param string $title
   */
  public function setTitle($title): void {
    $this->title = $title;
  }

  /**
   * @return string
   */
  public function getSummary(): string {
    return $this->summary;
  }

  /**
   * @param string $summary
   */
  public function setSummary($summary): void {
    $this->summary = $summary;
  }

  /**
   * @return array
   */
  public function getPermissions(): array {
    return $this->permissions;
  }

  /**
   * Can be either strings like "member:manage" or urls. If urls are given
   * they will be translated to strings when stored in the session by setOrgSessionData().
   *
   * @param array $permissions
   */
  public function setPermissions($permissions = NULL): void {
    if ($permissions === NULL) {
      $permissions = [];
    }

    $this->permissions = $permissions;
  }

  /**
   * @return string
   */
  public function getScope(): string {
    return $this->scope;
  }

  /**
   * @param string $scope
   */
  public function setScope($scope): void {
    $this->scope = $scope;
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
   * convert array to object
   *
   * @param array $content
   */
  public function createFromArray(array $content): void {
    if (array_key_exists('id', $content)) {
      $this->setId($content['id']);
    }
    if (array_key_exists('url', $content)) {
      $this->setUrl($content['url']);
    }
    if (array_key_exists('name', $content)) {
      $this->setName($content['name']);
    }
    if (array_key_exists('title', $content)) {
      $this->setTitle($content['title']);
    }
    if (array_key_exists('summary', $content)) {
      $this->setSummary($content['summary']);
    }

    // TODO: permissions has changed to permissions_urls, can this be cleaned up. Leaving for backwards compatability for the time being.
    if (array_key_exists('permissions', $content)) {
      $this->setPermissions($content['permissions']);
    } elseif (array_key_exists('permission_urls', $content)) {
      $this->setPermissions($content['permission_urls']);
    }

    if (array_key_exists('scope', $content)) {
      $this->setScope($content['scope']);
    }
    if (array_key_exists('org_url', $content)) {
      $this->setOrgUrl($content['org_url']);
    }
  }

  /**
   * Convert object to array
   *
   * @return array
   */
  public function toArray(): array {
    $content = [];
    if ($this->id !== NULL) {
      $content['id'] = $this->id;
    }
    if ($this->url !== NULL) {
      $content['url'] = $this->url;
    }
    if ($this->name !== NULL) {
      $content['name'] = $this->name;
    }
    if ($this->title !== NULL) {
      $content['title'] = $this->title;
    }
    if ($this->summary !== NULL) {
      $content['summary'] = $this->summary;
    }
    if ($this->permissions !== NULL) {
      $content['permissions'] = $this->permissions;
    }
    if ($this->scope !== NULL) {
      $content['scope'] = $this->scope;
    }
    if ($this->org_url !== NULL) {
      $content['org_url'] = $this->org_url;
    }
    return $content;
  }
}
