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


class Member {

  private $url;
  private $state;
  private $user_url;
  private $user;
  private $role_urls = array();
  private $org_url;

  /**
   * Gets the user that this member record relates to.
   *
   * @return \Drupal\ibm_apim\ApicType\ApicUser
   */
  public function getUser() {
    return $this->user;
  }

  /**
   * Sets the user for this Member record.
   *
   * @param \Drupal\ibm_apim\ApicType\ApicUser $user
   */
  public function setUser($user) {
    $this->user = $user;
  }

  /**
   * @return mixed
   */
  public function getUserUrl() {
    return $this->user_url;
  }

  /**
   * @param mixed $user_url
   */
  public function setUserUrl($user_url) {
    $this->user_url = $user_url;
  }

  /**
   * Gets the role urls for this member. Role definitions are stored on a consumer org.
   * objects.
   *
   * @return array
   */
  public function getRoleUrls() {
    return $this->role_urls;
  }

  /**
   * Set the roles for this member. Role definitions are stored on a consumer org.
   *
   * @param array $role_urls
   */
  public function setRoleUrls($role_urls) {
    $this->role_urls = $role_urls;
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
  public function getOrgUrl() {
    return $this->org_url;
  }

  /**
   * @param mixed $org_url
   */
  public function setOrgUrl($org_url) {
    $this->org_url = $org_url;
  }

  /**
   * @return mixed
   */
  public function getId() {
    return basename($this->url);
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
//  /**
//   * Add the provided role to this member.
//   *
//   * @param \Drupal\consumerorg\ApicType\Role $role
//   */
//  public function addRole(Role $role){
//    foreach($this->roles as $existing_role) {
//      if($existing_role->getName() === $role->getName()) {
//        return FALSE;
//      }
//    }
//
//    $this->roles[] = $role;
//    return TRUE;
//  }

}
