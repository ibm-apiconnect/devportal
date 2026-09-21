<?php


namespace Drupal\consumerorg\Entity;

use Drupal\consumerorg\ConsumerOrgMemberInviteInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines a Consumer Organization Member/Invite entity class.
 *
 * This entity combines both members and invitations into a single table
 * for simplified Views configuration and unified pagination.
 *
 * @ContentEntityType(
 *   id = "consumerorg_member_invite",
 *   label = @Translation("Consumer Organization Member/Invite"),
 *   label_singular = @Translation("Consumer Organization Member/Invite"),
 *   label_plural = @Translation("Consumer Organization Members/Invites"),
 *   label_count = @PluralTranslation(
 *     singular = @Translation("consumer organization member/invite"),
 *     plural = @Translation("consumer organization members/invites"),
 *   ),
 *   handlers = {
 *     "storage_schema" = "Drupal\consumerorg\ConsumerOrgMemberInviteSchema",
 *   },
 *   fieldable = FALSE,
 *   translatable = FALSE,
 *   base_table = "consumerorg_member_invite",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   }
 * )
 */
class ConsumerOrgMemberInvite extends ContentEntityBase implements ConsumerOrgMemberInviteInterface {

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->get('id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function uuid() {
    return $this->get('uuid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getType(): ?string {
    return $this->get('type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl(): ?string {
    return $this->get('url')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getState(): ?string {
    return $this->get('state')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrgUrl(): ?string {
    return $this->get('org_url')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoleUrls(): ?string {
    return $this->get('role_urls')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getEmail(): ?string {
    return $this->get('email')->value;
  }
  
  /**
   * {@inheritdoc}
   */
  public function getUserUrl(): ?string {
    $value = $this->get('user_url')->value;
    return ($value === 'N/A' || $value === '') ? NULL : $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getUser(): ?string {
    $value = $this->get('user')->value;
    return ($value === 'N/A' || $value === '') ? NULL : $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedAt(): ?int {
    return $this->get('created_at')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getUpdatedAt(): ?int {
    return $this->get('updated_at')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isMember(): bool {
    return $this->getType() === self::TYPE_MEMBER;
  }

  /**
   * {@inheritdoc}
   */
  public function isInvitation(): bool {
    return $this->getType() === self::TYPE_INVITATION;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = [];

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the entity.'))
      ->setSetting('unsigned', TRUE)
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the entity.'))
      ->setReadOnly(TRUE);

    $fields['type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Type'))
      ->setDescription(t('The type: "member" or "invitation"'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 20)
      ->setReadOnly(TRUE);

    $fields['url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('URL'))
      ->setDescription(t('The URL of the member or invitation'))
      ->setReadOnly(TRUE);

    $fields['state'] = BaseFieldDefinition::create('string')
      ->setLabel(t('State'))
      ->setDescription(t('The state (e.g., enabled, disabled, pending, accepted, expired)'))
      ->setReadOnly(TRUE);

    $fields['org_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Organization URL'))
      ->setDescription(t('The URL of the consumer organization'))
      ->setReadOnly(TRUE);

    $fields['consumerorg_nid'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Consumer Organization Node ID'))
      ->setDescription(t('The node ID of the consumer organization'))
      ->setReadOnly(TRUE);

    $fields['role_urls'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Role URLs'))
      ->setDescription(t('JSON encoded array of role URLs'))
      ->setReadOnly(TRUE);

    // Fields specific to invitations (NULL for members)
    $fields['email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Email'))
      ->setDescription(t('The email address of the invited user (NULL for members)'))
      ->setRequired(FALSE)
      ->setReadOnly(TRUE);

    // Fields specific to members (NULL for invitations)
    $fields['user_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('User URL'))
      ->setDescription(t('The URL of the user this member represents (NULL for invitations)'))
      ->setRequired(FALSE)
      ->setReadOnly(TRUE);

    $fields['user'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('User Data'))
      ->setDescription(t('JSON encoded ApicUser data (NULL for invitations)'))
      ->setRequired(FALSE)
      ->setReadOnly(TRUE);

    $fields['created_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Creation time'))
      ->setDescription(t('The APIC creation timestamp'))
      ->setDefaultValue(0)
      ->setReadOnly(TRUE);

    $fields['updated_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Modification time'))
      ->setDescription(t('The APIC modification timestamp'))
      ->setDefaultValue(0)
      ->setReadOnly(TRUE);

    return $fields;
  }

}