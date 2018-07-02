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


class ConsumerOrg {

  var $name;
  var $title;
  var $summary;
  var $id;
  var $state;
  var $created_at;
  var $updated_at;
  var $url;
  var $org_url;
  var $catalog_url;
  var $owner_url;
  var $roles = array();
  var $members = array();
  var $invites = array();

  /**
   * @return string
   */
  public function getName() {
    return $this->name;
  }

  /**
   * @param string $name
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * @return string
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * @param string $title
   */
  public function setTitle($title) {
    $this->title = $title;
  }

  /**
   * @return string
   */
  public function getSummary() {
    return $this->summary;
  }

  /**
   * @param string $summary
   */
  public function setSummary($summary) {
    $this->summary = $summary;
  }

  /**
   * @return string
   */
  public function getId() {
    return $this->id;
  }

  /**
   * @param string $id
   */
  public function setId($id) {
    $this->id = $id;
  }

  /**
   * @return string
   */
  public function getState() {
    return $this->state;
  }

  /**
   * @param string $state
   */
  public function setState($state) {
    $this->state = $state;
  }

  /**
   * @return string
   */
  public function getCreatedAt() {
    return $this->created_at;
  }

  /**
   * @param string $created_at
   */
  public function setCreatedAt($created_at) {
    $this->created_at = $created_at;
  }

  /**
   * @return string
   */
  public function getUpdatedAt() {
    return $this->updated_at;
  }

  /**
   * @param string $updated_at
   */
  public function setUpdatedAt($updated_at) {
    $this->updated_at = $updated_at;
  }

  /**
   * @return string
   */
  public function getUrl() {
    return $this->url;
  }

  /**
   * @param string $url
   */
  public function setUrl($url) {
    $this->url = $url;
  }

  /**
   * @return string
   */
  public function getOrgUrl() {
    return $this->org_url;
  }

  /**
   * @param string $org_url
   */
  public function setOrgUrl($org_url) {
    $this->org_url = $org_url;
  }

  /**
   * @return string
   */
  public function getCatalogUrl() {
    return $this->catalog_url;
  }

  /**
   * @param string $catalog_url
   */
  public function setCatalogUrl($catalog_url) {
    $this->catalog_url = $catalog_url;
  }

  /**
   * @return string
   */
  public function getOwnerUrl() {
    return $this->owner_url;
  }

  /**
   * @param string $owner_url
   */
  public function setOwnerUrl($owner_url) {
    $this->owner_url = $owner_url;
  }

  /**
   * @return array
   */
  public function getRoles() {
    return $this->roles;
  }

  /**
   * @param array $roles
   */
  public function setRoles($roles) {
    $this->roles = $roles;
  }

  /**
   * Adds the provided Role to this consumer org checking first to avoid duplicate
   * entries.
   *
   * @param \Drupal\consumerorg\ApicType\Role $role
   * @return bool
   */
  public function addRole(Role $role) {
    foreach($this->roles as $existing_role) {
      if($existing_role->getName() === $role->getName()) {
        return FALSE;
      }
    }

    $this->roles[] = $role;
    return TRUE;
  }

  /**
   * @param array $roles
   */
  public function addRoles(array $roles){
    foreach($roles as $role){
      $this->addRole($role);
    }
  }

  /**
   * Get role from url
   *
   * @param string $url
   * @return Role
   */
  public function getRoleFromUrl($url) {
    foreach($this->roles as $existing_role) {
      if($existing_role->getUrl() === $url) {
        return $existing_role;
      }
    }

    \Drupal::logger('consumerorg')->warning("No role found for %url", array("%url"=>$url));
    return NULL;
  }

  /**
   * Gets the members of this org returning an array of
   * \Drupal\consumerorg\ApicType\Member objects.
   *
   * @return array
   */
  public function getMembers() {
    return $this->members;
  }

  /**
   * Sets the members of this org. The array should contain a collection of
   * \Drupal\consumerorg\ApicType\Member objects.
   *
   * @param array $members
   */
  public function setMembers($members) {
    $this->members = $members;
  }

  /**
   * Gets the invites of this org.
   *
   * @return array
   */
  public function getInvites() {
    return $this->invites;
  }

  /**
   * Sets the invites of this org.
   *
   * @param array $invites
   */
  public function setInvites($invites) {
    $this->invites = $invites;
  }

  /**
   * Adds the provided member to this consumer org checking first if they
   * are already a member (no duplicates)
   *
   * @param Member $member
   * @return bool
   */
  public function addMember(Member $member){
    foreach($this->members as $existing_member) {
      if($existing_member->getUserUrl() === $member->getUserUrl()) {
        return FALSE;
      }
    }

    $this->members[] = $member;
    return TRUE;
  }

  /**
   * @param array $members
   */
  public function addMembers(array $members){
    foreach($members as $member) {
      $this->addMember($member);
    }
  }

  /**
   * Removes the given member from this consumer org
   *
   * @param \Drupal\consumerorg\ApicType\Member $member
   */
  public function removeMember(Member $member) {
    $new_members = array();
    foreach($this->members as $existing_member) {
      if($existing_member->getUserUrl() !== $member->getUserUrl()) {
        $new_members[] = $existing_member;
      }
    }
    $this->setMembers($new_members);
  }

  /**
   * Determine if the user specified by the provided url is a member
   * of this consumer org.
   *
   * @param $userUrl
   * @return bool
   */
  public function isMember($userUrl){
    $returnValue = FALSE;

    foreach($this->members as $member){
      if($member->getUserUrl() === $userUrl) {
        $returnValue = TRUE;
        break;
      }
    }

    return $returnValue;
  }

  /**
   * Check the provided user URL against the owner of this org and return TRUE
   * if they match.
   *
   * @param $userUrl
   *
   * @return bool
   */
  public function isOwner($userUrl) {
    return $this->getOwnerUrl() === $userUrl;
  }

  /**
   * If the provided user url represents a member of this consumer org, return their
   * roles in the org. Returns empty array if the user is not a member.
   *
   * @param $userUrl
   *
   * @return array
   */
  public function getRolesForMember($userUrl) {
    $returnValue = array();

    if($this->isMember($userUrl)){
      foreach($this->members as $member) {
        if($member->getUserUrl() === $userUrl) {
          $roleUrls = $member->getRoleUrls();
          foreach($roleUrls as $roleUrl) {
            $role = $this->getRoleFromUrl($roleUrl);
            $returnValue[] = $role;
          }
          break;
        }
      }
    }

    return $returnValue;
  }

  /**
   * Checks if the given user has the specified permission in this org. Checks
   * all roles that the user has for the permission name and returns TRUE if
   * found.
   *
   * @param $userUrl
   * @param $permissionName
   *
   * @return bool
   */
  public function hasPermission($userUrl, $permissionName) {
    $returnValue = FALSE;

    $roles = $this->getRolesForMember($userUrl);
    foreach($roles as $role) {
      if(in_array($permissionName, $role->getPermissions())) {
        $returnValue = TRUE;
        break;
      }
    }

    return $returnValue;
  }

}