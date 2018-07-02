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

  var $id;
  var $url;
  var $name;
  var $title;
  var $summary;
  var $permissions;
  var $scope;
  var $org_url;

  /**
   * @return mixed
   */
  public function getId() {
    return $this->id;
  }

  /**
   * @param mixed $id
   */
  public function setId($id) {
    $this->id = $id;
  }

  /**
   * @return mixed
   */
  public function getUrl() {
    return $this->url;
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
  public function getName() {
    return $this->name;
  }

  /**
   * @param mixed $name
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * @return mixed
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * @param mixed $title
   */
  public function setTitle($title) {
    $this->title = $title;
  }

  /**
   * @return mixed
   */
  public function getSummary() {
    return $this->summary;
  }

  /**
   * @param mixed $summary
   */
  public function setSummary($summary) {
    $this->summary = $summary;
  }

  /**
   * @return mixed
   */
  public function getPermissions() {
    return $this->permissions;
  }

  /**
   * Can be either strings like "member:manage" or urls. If urls are given
   * they will be translated to strings.
   *
   * @param mixed $permissions
   */
  public function setPermissions($permissions = null) {
    $permission_names = array();
    if (isset($permissions) && !empty($permissions)) {
      foreach($permissions as $permission) {
        if(strpos($permission, '/') > -1) {
          $permission_name = \Drupal::service('ibm_apim.permissions')->get($permission)['name'];
          if (empty($permission_name)) {
            \Drupal::logger('consumerorg_role')->warning('No permission found for %url', array('%url' => $permission));
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
   * @return mixed
   */
  public function getScope() {
    return $this->scope;
  }

  /**
   * @param mixed $scope
   */
  public function setScope($scope) {
    $this->scope = $scope;
  }

  /**
   * @return mixed
   */
  public function getOrgUrl() {
    return $this->org_url;
  }

  /**
   * @param mixed $org_url
   */
  public function setOrgUrl($org_url) {
    $this->org_url = $org_url;
  }

}