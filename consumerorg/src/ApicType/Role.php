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

namespace Drupal\consumerorg\ApicType;


class Role {

  private $id;

  private $url;

  private $name;

  private $title;

  private $summary;

  private $permissions;

  private $scope;

  private $org_url;

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
  public function getName(): string {
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
   * they will be translated to strings.
   *
   * @param array $permissions
   */
  public function setPermissions($permissions = NULL): void {
    $permission_names = [];
    if ($permissions !== null && !empty($permissions)) {
      foreach ($permissions as $permission) {
        if (strpos($permission, '/') > -1) {
          $permission_name = \Drupal::service('ibm_apim.permissions')->get($permission)['name'];
          if (empty($permission_name)) {
            \Drupal::logger('consumerorg_role')->warning('No permission found for %url', ['%url' => $permission]);
          }
          else {
            $permission_names[] = $permission_name;
          }

        }
        else {
          $permission_names[] = $permission;
        }
      }
    }
    $this->permissions = $permission_names;
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

}