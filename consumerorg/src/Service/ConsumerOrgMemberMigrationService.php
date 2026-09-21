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

namespace Drupal\consumerorg\Service;

use Drupal\consumerorg\ConsumerOrgMemberInviteInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service for migrating member and invitation data to entities.
 */
class ConsumerOrgMemberMigrationService {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
  }

  /**
   * Migrate members and invitations in batches.
   *
   * @param array $sandbox
   *   The batch sandbox array.
   *
   * @return string
   *   A message about the migration progress.
   */
  public function migrateBatch(array &$sandbox): string {
    $node_storage = $this->entityTypeManager->getStorage('node');
    $entity_storage = $this->entityTypeManager->getStorage('consumerorg_member_invite');

    // Initialize sandbox on first run
    if (!isset($sandbox['progress'])) {
      $sandbox['progress'] = 0;
      $sandbox['current_nid'] = 0;
      $sandbox['members_migrated'] = 0;
      $sandbox['invitations_migrated'] = 0;
      
      // Count total consumerorg nodes
      $query = $node_storage->getQuery()
        ->condition('type', 'consumerorg')
        ->accessCheck(FALSE)
        ->count();
      $sandbox['max'] = $query->execute();
    }

    // Process nodes in batches of 10
    $batch_size = 10;
    $query = $node_storage->getQuery()
      ->condition('type', 'consumerorg')
      ->condition('nid', $sandbox['current_nid'], '>')
      ->range(0, $batch_size)
      ->sort('nid')
      ->accessCheck(FALSE);
    
    $nids = $query->execute();

    if (empty($nids)) {
      $sandbox['#finished'] = 1;
      return t('Migration complete. Migrated @members members and @invitations invitations from @total consumer organizations.', [
        '@members' => $sandbox['members_migrated'],
        '@invitations' => $sandbox['invitations_migrated'],
        '@total' => $sandbox['progress'],
      ]);
    }

    foreach ($nids as $nid) {
      $node = $node_storage->load($nid);
      if ($node === NULL) {
        continue;
      }

      $org_url = $node->get('consumerorg_url')->value;
      
      // Migrate members
      if ($node->hasField('consumerorg_members')) {
        $members = $node->get('consumerorg_members')->getValue();
        foreach ($members as $member_data) {
          if (!empty($member_data['value'])) {
            $member = json_decode($member_data['value'], TRUE);
            if ($member === NULL) {
              $member = @unserialize($member_data['value'], ['allowed_classes' => FALSE]);
            }
            if (is_array($member)) {
              $migrated = $this->migrateMember($member, $org_url, $nid, $entity_storage);
              if ($migrated) {
                $sandbox['members_migrated']++;
              }
            }
          }
        }
      }

      // Migrate invitations
      if ($node->hasField('consumerorg_invites')) {
        $invites = $node->get('consumerorg_invites')->getValue();
        foreach ($invites as $invite_data) {
          if (!empty($invite_data['value'])) {
            $invite = json_decode($invite_data['value'], TRUE);
            if ($invite === NULL) {
              $invite = @unserialize($invite_data['value'], ['allowed_classes' => FALSE]);
            }
            if (is_array($invite)) {
              $migrated = $this->migrateInvitation($invite, $org_url, $nid, $entity_storage);
              if ($migrated) {
                $sandbox['invitations_migrated']++;
              }
            }
          }
        }
      }

      $sandbox['progress']++;
      $sandbox['current_nid'] = $nid;
    }

    $sandbox['#finished'] = $sandbox['progress'] / $sandbox['max'];
    
    return t('Processed @current of @total consumer organizations...', [
      '@current' => $sandbox['progress'],
      '@total' => $sandbox['max'],
    ]);
  }

  /**
   * Migrate a single member to an entity.
   *
   * @param array $member_data
   *   The member data array.
   * @param string $org_url
   *   The organization URL.
   * @param int $nid
   *   The consumer org node ID.
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   *
   * @return bool
   *   TRUE if migrated, FALSE otherwise.
   */
  protected function migrateMember(array $member_data, string $org_url, int $nid, $entity_storage): bool {
    // Skip if no URL
    if (empty($member_data['url'])) {
      return FALSE;
    }

    // Check if already migrated
    $existing = $entity_storage->loadByProperties(['url' => $member_data['url']]);
    if (!empty($existing)) {
      return FALSE;
    }

    try {
      // Prepare timestamps
      $created_at = $this->convertTimestamp($member_data['created_at'] ?? NULL);
      $updated_at = $this->convertTimestamp($member_data['updated_at'] ?? NULL);

      // Create entity
      $entity = $entity_storage->create([
        'type' => ConsumerOrgMemberInviteInterface::TYPE_MEMBER,
        'url' => $member_data['url'],
        'state' => $member_data['state'] ?? 'enabled',
        'org_url' => $org_url,
        'consumerorg_nid' => $nid,
        'role_urls' => !empty($member_data['role_urls']) ? json_encode($member_data['role_urls']) : NULL,
        'email' => NULL,
        'user_url' => $member_data['user_url'] ?? NULL,
        'user' => !empty($member_data['user']) ? json_encode($member_data['user']) : NULL,
        'created_at' => $created_at,
        'updated_at' => $updated_at,
      ]);
      
      $entity->save();
      return TRUE;
      
    } catch (\Exception $e) {
      $this->logger->error('Failed to migrate member @url: @message', [
        '@url' => $member_data['url'],
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Migrate a single invitation to an entity.
   *
   * @param array $invite_data
   *   The invitation data array.
   * @param string $org_url
   *   The organization URL.
   * @param int $nid
   *   The consumer org node ID.
   * @param \Drupal\Core\Entity\EntityStorageInterface $entity_storage
   *   The entity storage.
   *
   * @return bool
   *   TRUE if migrated, FALSE otherwise.
   */
  protected function migrateInvitation(array $invite_data, string $org_url, int $nid, $entity_storage): bool {
    // Skip if no URL
    if (empty($invite_data['url'])) {
      return FALSE;
    }

    // Check if already migrated
    $existing = $entity_storage->loadByProperties(['url' => $invite_data['url']]);
    if (!empty($existing)) {
      return FALSE;
    }

    try {
      // Prepare timestamps
      $created_at = $this->convertTimestamp($invite_data['created_at'] ?? NULL);
      $updated_at = $this->convertTimestamp($invite_data['updated_at'] ?? NULL);

      // Create entity
      $entity = $entity_storage->create([
        'type' => ConsumerOrgMemberInviteInterface::TYPE_INVITATION,
        'url' => $invite_data['url'],
        'state' => $invite_data['state'] ?? 'pending',
        'org_url' => $org_url,
        'consumerorg_nid' => $nid,
        'role_urls' => !empty($invite_data['role_urls']) ? json_encode($invite_data['role_urls']) : NULL,
        'email' => $invite_data['email'] ?? NULL,
        'user_url' => 'N/A',  // Use 'N/A' instead of NULL for invitations to avoid NOT NULL constraint
        'user' => 'N/A',      // Use 'N/A' instead of NULL for invitations to avoid NOT NULL constraint
        'created_at' => $created_at,
        'updated_at' => $updated_at,
      ]);
      
      $entity->save();
      return TRUE;
      
    } catch (\Exception $e) {
      $this->logger->error('Failed to migrate invitation @url: @message', [
        '@url' => $invite_data['url'],
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }

  /**
   * Convert timestamp to integer.
   *
   * @param mixed $timestamp
   *   The timestamp value (string or int).
   *
   * @return int
   *   The timestamp as integer.
   */
  protected function convertTimestamp($timestamp): int {
    if (empty($timestamp)) {
      return 0;
    }
    
    if (is_numeric($timestamp)) {
      return (int) $timestamp;
    }
    
    if (is_string($timestamp)) {
      $converted = strtotime($timestamp);
      return $converted !== FALSE ? $converted : 0;
    }
    
    return 0;
  }

}
