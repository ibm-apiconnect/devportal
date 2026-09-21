<?php


/**
 * @file
 * Provide views data for consumerorg.module.
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_views_data().
 */
function consumerorg_views_data() {
  $data = [];

  // Base table information for consumerorg_member_invite
  $data['consumerorg_member_invite']['table']['group'] = t('Consumer Organization Member/Invite');
  $data['consumerorg_member_invite']['table']['provider'] = 'consumerorg';

  $data['consumerorg_member_invite']['table']['base'] = [
    'field' => 'id',
    'title' => t('Consumer Organization Member/Invite'),
    'help' => t('Members and invitations for consumer organizations.'),
    'weight' => -10,
    'database' => 'default',
  ];

  // Entity type
  $data['consumerorg_member_invite']['table']['entity type'] = 'consumerorg_member_invite';
  $data['consumerorg_member_invite']['table']['entity revision'] = FALSE;

  // ID field
  $data['consumerorg_member_invite']['id'] = [
    'title' => t('ID'),
    'help' => t('The unique ID of the member/invite.'),
    'field' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
  ];

  // UUID field
  $data['consumerorg_member_invite']['uuid'] = [
    'title' => t('UUID'),
    'help' => t('The UUID of the member/invite.'),
    'field' => [
      'id' => 'standard',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

  // Type field
  $data['consumerorg_member_invite']['type'] = [
    'title' => t('Type'),
    'help' => t('The type: member or invitation.'),
    'field' => [
      'id' => 'standard',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'member_type_filter',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

  // URL field
  $data['consumerorg_member_invite']['url'] = [
    'title' => t('URL'),
    'help' => t('The URL of the member or invitation.'),
    'field' => [
      'id' => 'standard',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

  // State field
  $data['consumerorg_member_invite']['state'] = [
    'title' => t('State'),
    'help' => t('The state of the member or invitation.'),
    'field' => [
      'id' => 'standard',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'member_state_filter',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

  // Organization URL field
  $data['consumerorg_member_invite']['org_url'] = [
    'title' => t('Organization URL'),
    'help' => t('The URL of the consumer organization.'),
    'field' => [
      'id' => 'standard',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

  // Consumer Organization Node ID field
  $data['consumerorg_member_invite']['consumerorg_nid'] = [
    'title' => t('Consumer Organization Node ID'),
    'help' => t('The node ID of the consumer organization.'),
    'field' => [
      'id' => 'numeric',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'numeric',
    ],
    'argument' => [
      'id' => 'numeric',
    ],
    'relationship' => [
      'title' => t('Consumer Organization'),
      'help' => t('The consumer organization node.'),
      'base' => 'node_field_data',
      'base field' => 'nid',
      'id' => 'standard',
      'label' => t('Consumer Organization'),
    ],
  ];

  // Role URLs field
  $data['consumerorg_member_invite']['role_urls'] = [
    'title' => t('Role URLs'),
    'help' => t('JSON encoded array of role URLs.'),
    'field' => [
      'id' => 'member_roles_field',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'member_role_filter',
    ],
  ];

  // Email field (for invitations)
  $data['consumerorg_member_invite']['email'] = [
    'title' => t('Email'),
    'help' => t('The email address of the invited user.'),
    'field' => [
      'id' => 'standard',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

  // User URL field (for members)
  $data['consumerorg_member_invite']['user_url'] = [
    'title' => t('User URL'),
    'help' => t('The URL of the user.'),
    'field' => [
      'id' => 'standard',
    ],
    'sort' => [
      'id' => 'standard',
    ],
    'filter' => [
      'id' => 'string',
    ],
    'argument' => [
      'id' => 'string',
    ],
  ];

  // User data field (for members)
  $data['consumerorg_member_invite']['user'] = [
    'title' => t('User'),
    'help' => t('The user information.'),
    'field' => [
      'id' => 'member_user_field',
    ],
  ];

  // Created timestamp
  $data['consumerorg_member_invite']['created_at'] = [
    'title' => t('Created'),
    'help' => t('The time the member/invite was created.'),
    'field' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ],
  ];

  // Updated timestamp
  $data['consumerorg_member_invite']['updated_at'] = [
    'title' => t('Updated'),
    'help' => t('The time the member/invite was last updated.'),
    'field' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ],
  ];

  return $data;
}