<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2024, 2026
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\consumerorg;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Provides an interface defining a Consumer Organization Member/Invite entity.
 * 
 * This entity combines both members and invitations into a single table
 * for simplified Views configuration and unified pagination.
 */
interface ConsumerOrgMemberInviteInterface extends ContentEntityInterface {

  /**
   * Type: Member.
   */
  const TYPE_MEMBER = 'member';

  /**
   * Type: Invitation.
   */
  const TYPE_INVITATION = 'invitation';

  /**
   * Gets the entity ID.
   *
   * @return int
   *   The entity ID.
   */
  public function id();

  /**
   * Gets the entity UUID.
   *
   * @return string
   *   The entity UUID.
   */
  public function uuid();

  /**
   * Gets the type (member or invitation).
   *
   * @return string
   *   The type ('member' or 'invitation').
   */
  public function getType(): ?string;

  /**
   * Gets the URL.
   *
   * @return string|null
   *   The member URL or invitation URL.
   */
  public function getUrl(): ?string;

  /**
   * Gets the state.
   *
   * @return string|null
   *   The state (e.g., 'enabled', 'disabled', 'pending', 'accepted', 'expired').
   */
  public function getState(): ?string;

  /**
   * Gets the organization URL.
   *
   * @return string|null
   *   The organization URL.
   */
  public function getOrgUrl(): ?string;

  /**
   * Gets the role URLs.
   *
   * @return string|null
   *   JSON encoded array of role URLs.
   */
  public function getRoleUrls(): ?string;

  /**
   * Gets the email address (for invitations).
   *
   * @return string|null
   *   The email address of the invited user (NULL for members).
   */
  public function getEmail(): ?string;

  /**
   * Gets the user URL (for members).
   *
   * @return string|null
   *   The user URL (NULL for invitations).
   */
  public function getUserUrl(): ?string;

  /**
   * Gets the user data (for members).
   *
   * @return string|null
   *   JSON encoded user data (NULL for invitations).
   */
  public function getUser(): ?string;

  /**
   * Gets the creation timestamp.
   *
   * @return int|null
   *   The creation timestamp.
   */
  public function getCreatedAt(): ?int;

  /**
   * Gets the last updated timestamp.
   *
   * @return int|null
   *   The last updated timestamp.
   */
  public function getUpdatedAt(): ?int;

  /**
   * Checks if this is a member.
   *
   * @return bool
   *   TRUE if this is a member, FALSE otherwise.
   */
  public function isMember(): bool;

  /**
   * Checks if this is an invitation.
   *
   * @return bool
   *   TRUE if this is an invitation, FALSE otherwise.
   */
  public function isInvitation(): bool;

}
