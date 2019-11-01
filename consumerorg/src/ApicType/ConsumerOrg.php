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


class ConsumerOrg {

  private $name;

  private $title;

  private $summary;

  private $id;

  private $state;

  private $created_at;

  private $updated_at;

  private $url;

  private $org_url;

  private $catalog_url;

  private $owner_url;

  private $roles = [];

  private $members = [];

  private $invites = [];

  private $tags = [];

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
  public function getTitle(): ?string {
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
  public function getSummary(): ?string {
    return $this->summary;
  }

  /**
   * @param string $summary
   */
  public function setSummary($summary): void {
    $this->summary = $summary;
  }

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
  public function getState(): ?string {
    return $this->state;
  }

  /**
   * @param string $state
   */
  public function setState($state): void {
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
  public function setCreatedAt($created_at): void {
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
  public function setUpdatedAt($updated_at): void {
    $this->updated_at = $updated_at;
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
  public function getOrgUrl(): ?string {
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
  public function getCatalogUrl(): ?string {
    return $this->catalog_url;
  }

  /**
   * @param string $catalog_url
   */
  public function setCatalogUrl($catalog_url): void {
    $this->catalog_url = $catalog_url;
  }

  /**
   * @return string
   */
  public function getOwnerUrl(): ?string {
    return $this->owner_url;
  }

  /**
   * @param string $owner_url
   */
  public function setOwnerUrl($owner_url): void {
    $this->owner_url = $owner_url;
  }

  /**
   * @return array
   */
  public function getTags(): ?array {
    return $this->tags;
  }

  /**
   * @param array $tags
   */
  public function setTags($tags): void {
    if (is_array($tags)) {
      $this->tags = $tags;
    }
    else {
      $this->tags = [$tags];
    }
  }

  /**
   * Adds the specified tag to this consumer org if does not already exist
   *
   * @param string $tag
   *
   * @return bool
   */
  public function addTag($tag): bool {
    foreach ($this->tags as $current_tag) {
      if ($current_tag === $tag) {
        return FALSE;
      }
    }
    $this->tags[] = $tag;
    return TRUE;
  }

  /**
   * @return array
   */
  public function getRoles(): ?array {
    return $this->roles;
  }

  /**
   * @param array $roles
   */
  public function setRoles($roles): void {
    $this->roles = $roles;
  }

  /**
   * Sets the roles of this org. The array should be an array of arrays
   * each one representing a role.
   *
   * @param array $rolesArray
   */
  public function setRolesFromArray(array $rolesArray): void {
    $roles = [];
    foreach ($rolesArray as $roleArray) {
      $role = new Role();
      $role->createFromArray($roleArray);
      $roles[] = $role;
    }

    $this->setRoles($roles);
  }

  /**
   * Adds the provided Role to this consumer org checking first to avoid duplicate
   * entries.
   *
   * @param \Drupal\consumerorg\ApicType\Role $role
   *
   * @return bool
   */
  public function addRole(Role $role): bool {
    foreach ($this->roles as $existing_role) {
      $newRoleName = $role->getName();
      if ($newRoleName === NULL || $existing_role->getName() === $newRoleName) {
        return FALSE;
      }
    }

    $this->roles[] = $role;
    return TRUE;
  }

  /**
   * Adds the provided Role to this consumer org checking first to avoid duplicate
   * entries.
   *
   * @param array $roleArray
   *
   * @return bool
   */
  public function addRoleFromArray(array $roleArray): bool {
    $role = new Role();
    $role->createFromArray($roleArray);

    return $this->addRole($role);
  }

  /**
   * @param array $roles
   */
  public function addRoles(array $roles): void {
    foreach ($roles as $role) {
      $this->addRole($role);
    }
  }

  /**
   * Get role from url
   *
   * @param string $url
   *
   * @return Role
   */
  public function getRoleFromUrl($url): ?Role {
    foreach ($this->roles as $existing_role) {
      if ($url !== NULL && $existing_role->getUrl() === $url) {
        return $existing_role;
      }
    }

    \Drupal::logger('consumerorg')->warning('No role found for %url', ['%url' => $url]);
    return NULL;
  }

  /**
   * Gets the members of this org returning an array of
   * \Drupal\consumerorg\ApicType\Member objects.
   *
   * @return array
   */
  public function getMembers(): ?array {
    return $this->members;
  }

  /**
   * Sets the members of this org. The array should contain a collection of
   * \Drupal\consumerorg\ApicType\Member objects.
   *
   * @param array $members
   */
  public function setMembers($members): void {
    $this->members = $members;
  }

  /**
   * Sets the members of this org. The array should be an array of arrays
   * each one representing a member.
   *
   * @param array $membersArray
   */
  public function setMembersFromArray(array $membersArray): void {
    $members = [];
    foreach ($membersArray as $memberArray) {
      $member = new Member();
      $member->createFromArray($memberArray);
      $members[] = $member;
    }

    $this->setMembers($members);
  }

  /**
   * Returns an array of the members in the org
   *
   * @return array|null
   */
  public function getMemberEmails(): ?array {
    $members = $this->members;
    $emailList = [];
    foreach($members as $member) {
      $emailList[] = $member->getUser()->getMail();
    }
    return array_unique($emailList);
  }

  /**
   * Gets the invites of this org.
   *
   * @return array
   */
  public function getInvites(): ?array {
    return $this->invites;
  }

  /**
   * Sets the invites of this org.
   *
   * @param array $invites
   */
  public function setInvites($invites): void {
    $this->invites = $invites;
  }

  /**
   * Adds the provided member to this consumer org checking first if they
   * are already a member (no duplicates)
   *
   * @param Member $member
   *
   * @return bool
   */
  public function addMember(Member $member): bool {
    foreach ($this->members as $existing_member) {
      if ($existing_member->getUserUrl() === $member->getUserUrl()) {
        return FALSE;
      }
    }

    $this->members[] = $member;
    return TRUE;
  }

  /**
   * @param array $members
   */
  public function addMembers(array $members): void {
    foreach ($members as $member) {
      $this->addMember($member);
    }
  }

  /**
   * Removes the given member from this consumer org
   *
   * @param \Drupal\consumerorg\ApicType\Member $member
   */
  public function removeMember(Member $member): void {
    $new_members = [];
    foreach ($this->members as $existing_member) {
      if ($existing_member->getUserUrl() !== $member->getUserUrl()) {
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
   *
   * @return bool
   */
  public function isMember($userUrl): bool {
    $returnValue = FALSE;

    foreach ($this->members as $member) {
      if ($member->getUserUrl() === $userUrl) {
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
   * @param string $userUrl
   *
   * @return bool
   */
  public function isOwner($userUrl): bool {
    return $this->getOwnerUrl() === $userUrl;
  }

  /**
   * If the provided user url represents a member of this consumer org, return their
   * roles in the org. Returns empty array if the user is not a member.
   *
   * @param string $userUrl
   *
   * @return array
   */
  public function getRolesForMember($userUrl): array {
    $returnValue = [];

    if ($this->isMember($userUrl)) {
      foreach ($this->members as $member) {
        if ($member->getUserUrl() === $userUrl) {
          $roleUrls = $member->getRoleUrls();
          foreach ($roleUrls as $roleUrl) {
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
   * @param string $userUrl
   * @param string $permissionName
   *
   * @return bool
   */
  public function hasPermission($userUrl, $permissionName): bool {
    $returnValue = FALSE;

    $roles = $this->getRolesForMember($userUrl);
    foreach ($roles as $role) {
      if ($role) {
        $permURLs = $role->getPermissions();
        foreach ($permURLs as $permission) {
          if (strpos($permission, '/') > -1) {
            $permission_name = \Drupal::service('ibm_apim.permissions')->get($permission)['name'];
            if (empty($permission_name)) {
              \Drupal::logger('consumerorg')->warning('No permission found for %url', ['%url' => $permission]);
            }
          }
          else {
            $permission_name = $permission;
          }
          if ($permission_name === $permissionName) {
            $returnValue = TRUE;
          }
        }
      }
    }

    return $returnValue;
  }

}