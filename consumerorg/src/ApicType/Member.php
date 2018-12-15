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
