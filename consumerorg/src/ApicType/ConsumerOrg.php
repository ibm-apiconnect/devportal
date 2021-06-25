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

/**
 * Class ConsumerOrg
 *
 * @package Drupal\consumerorg\ApicType
 */
class ConsumerOrg {

  /**
   * @var string|null
   */
  private ?string $name = NULL;

  /**
   * @var string|null
   */
  private ?string $title = NULL;

  /**
   * @var string|null
   */
  private ?string $summary = NULL;

  /**
   * @var string|null
   */
  private ?string $id = NULL;

  /**
   * @var string|null
   */
  private ?string $state = NULL;

  /**
   * @var string|null
   */
  private ?string $created_at = NULL;

  /**
   * @var string|null
   */
  private ?string $updated_at = NULL;

  /**
   * @var string|null
   */
  private ?string $created_by = NULL;

  /**
   * @var string|null
   */
  private ?string $updated_by = NULL;

  /**
   * @var string|null
   */
  private ?string $url = NULL;

  /**
   * @var string|null
   */
  private ?string $org_url = NULL;

  /**
   * @var string|null
   */
  private ?string $catalog_url = NULL;

  /**
   * @var string|null
   */
  private ?string $owner_url = NULL;

  /**
   * @var array
   */
  private array $default_payment_method = [];

  /**
   * @var array
   */
  private array $roles = [];

  /**
   * @var array
   */
  private array $members = [];

  /**
   * @var array
   */
  private array $invites = [];

  /**
   * @var array
   */
  private array $tags = [];

  /**
   * @var array
   */
  private array $payment_methods = [];

  /**
   * @var array
   */
  private array $custom_fields = [];

  /**
   * @return string
   */
  public function getName(): string {
    return $this->name;
  }

  /**
   * @param string $name
   */
  public function setName(string $name): void {
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
  public function setTitle(string $title): void {
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
  public function setSummary(string $summary): void {
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
  public function setId(string $id): void {
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
   * @return string
   */
  public function getCreatedBy(): ?string {
    return $this->created_by;
  }

  /**
   * @param string $created_by
   */
  public function setCreatedBy(string $created_by): void {
    $this->created_by = $created_by;
  }

  /**
   * @return string
   */
  public function getUpdatedBy(): ?string {
    return $this->updated_by;
  }

  /**
   * @param string $updated_by
   */
  public function setUpdatedBy(string $updated_by): void {
    $this->updated_by = $updated_by;
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
  public function setUrl(string $url): void {
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
  public function setOrgUrl(string $org_url): void {
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
  public function setCatalogUrl(string $catalog_url): void {
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
  public function setOwnerUrl(string $owner_url): void {
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
  public function setTags(array $tags): void {
    if (is_array($tags)) {
      $this->tags = $tags;
    }
    else {
      $this->tags = [$tags];
    }
  }

  /**
   * Removes the specified tag from this consumer org if it exist
   *
   * @param string $tag
   *
   * @return bool
   */
  public function removeTag(string $tag): bool {
    $key = array_search($tag, $this->tags, TRUE);
    if ($key !== FALSE) {
      unset($this->tags[$key]);
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Adds the specified tag to this consumer org if does not already exist
   *
   * @param string $tag
   *
   * @return bool
   */
  public function addTag(string $tag): bool {
    if (in_array($tag, $this->tags, TRUE)) {
      return FALSE;
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
  public function setRoles(array $roles): void {
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
  public function getRoleFromUrl(string $url): ?Role {
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
  public function setMembers(array $members): void {
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
    foreach ($members as $member) {
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
  public function setInvites(array $invites): void {
    $this->invites = $invites;
  }

  /**
   * Gets the payment methods of this org.
   *
   * @return array
   */
  public function getPaymentMethods(): ?array {
    return $this->payment_methods;
  }

  /**
   * Sets the payment methods of this org.
   *
   * @param array $payment_methods
   */
  public function setPaymentMethods(array $payment_methods): void {
    $this->payment_methods = $payment_methods;
  }

  /**
   * Gets the payment methods of this org.
   *
   * @return array
   */
  public function getDefaultPaymentMethod(): array {
    if (isset($this->default_payment_method) && !empty($this->default_payment_method)) {
      return $this->default_payment_method;
    }
    if (!empty($this->payment_methods)) {
      return array_shift($this->payment_methods);
    }
    return [];
  }

  /**
   * Sets the payment methods of this org.
   *
   * @param array $default_payment_method
   */
  public function setDefaultPaymentMethod(array $default_payment_method): void {
    $this->default_payment_method = $default_payment_method;
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
  public function isOwner(string $userUrl): bool {
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
  public function getRolesForMember(string $userUrl): array {
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
  public function hasPermission(string $userUrl, string $permissionName): bool {
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

  /**
   * @return array
   */
  public function getCustomFields(): array {
    if (!isset($this->custom_fields)) {
      $this->custom_fields = [];
    }
    return $this->custom_fields;
  }

  /**
   * @param array $customFields
   */
  public function setCustomFields(array $customFields): void {
    if (empty($customFields)) {
      $this->custom_fields = [];
    }
    else {
      $this->custom_fields = $customFields;
    }
  }

  /**
   * @param $field
   * @param $value
   */
  public function addCustomField($field, $value): void {
    if (!isset($this->custom_fields)) {
      $this->custom_fields = [];
    }
    $this->custom_fields[$field] = $value;
  }


}