<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2022
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

use Drupal\consumerorg\ApicType\Member;
use Drupal\Core\Session\UserSession;

function consumerorg_drush_command(): array {

  $items['consumerorg-cleanse-drush-command'] = [
    'description' => 'Clears the consumerorg entries back to a clean state.',
    'aliases' => ['cleanse_consumerorgs'],
  ];
  $items['consumerorg-update'] = [
    'description' => 'Updates a consumerorg',
    'aliases' => ['uorg'],
    'arguments' => [
      'event' => 'The event type',
      'product' => 'The JSON consumerorg payload',
    ],
  ];
  $items['consumerorg-create'] = [
    'description' => 'Creates a consumerorg',
    'aliases' => ['corg'],
    'arguments' => [
      'event' => 'The event type',
      'product' => 'The JSON consumerorg payload',
    ],
  ];
  $items['consumerorg-delete'] = [
    'description' => 'Deletes a consumerorg',
    'aliases' => ['dorg'],
    'arguments' => [
      'event' => 'The event type',
      'id' => 'The consumerorg ID',
    ],
  ];
  $items['consumerorg-massupdate'] = [
    'description' => 'Mass updates a list of Consumerorgs',
    'aliases' => ['morg'],
    'arguments' => [
      'consumerorgs' => 'The JSON array of Consumerorgs',
      'event' => 'The event type',
    ],
  ];
  $items['consumerorg-tidy'] = [
    'description' => 'Tidies the list of Consumerorgs to ensure consistency with APIm',
    'aliases' => ['torg'],
    'arguments' => [
      'consumerorgids' => 'The JSON array of consumerorg IDs',
    ],
  ];
  $items['consumerorg-role-create'] = [
    'description' => 'Creates a role (which belongs to an org)',
    'aliases' => ['crole'],
    'arguments' => [
      'event' => 'The event type',
      'product' => 'The JSON role payload',
    ],
  ];
  $items['consumerorg-member-create'] = [
    'description' => 'Create a member in the org',
    'aliases' => ['cmembercreate'],
    'arguments' => [
      'payload' => 'The JSON member payload',
      'event' => 'The event type',
    ],
  ];
  $items['consumerorg-payment-method-create'] = [
    'description' => 'Creates a payment method (which belongs to an org)',
    'aliases' => ['cpaymentmethod'],
    'arguments' => [
      'event' => 'The event type',
      'product' => 'The JSON payment method payload',
    ],
  ];
  $items['consumerorg-payment-method-update'] = [
    'description' => 'Creates a payment method (which belongs to an org)',
    'aliases' => ['upaymentmethod'],
    'arguments' => [
      'event' => 'The event type',
      'product' => 'The JSON payment method payload',
    ],
  ];
  $items['consumerorg-payment-method-delete'] = [
    'description' => 'Creates a payment method (which belongs to an org)',
    'aliases' => ['dpaymentmethod'],
    'arguments' => [
      'event' => 'The event type',
      'product' => 'The JSON payment method payload',
    ],
  ];
  return $items;
}

/**
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function drush_consumerorg_cleanse_drush_command() {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = Drupal::service('account_switcher');
  $original_user = \Drupal::currentUser();
  if ($original_user->id() !== 1) {
    $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
  }
  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'consumerorg']);

  foreach ($nodes as $node) {
    $node->delete();
  }
  if ($original_user->id() !== 1) {
    $accountSwitcher->switchBack();
  }
  drush_log(dt('All consumer organization entries deleted.'), 'success');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $func
 * @param $event
 * @param $content
 */
function drush_consumerorg_createOrUpdate($content, $event, $func) {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  if ($content !== NULL) {
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = Drupal::service('account_switcher');
    $original_user = \Drupal::currentUser();
    if ($original_user->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }

    $consumerOrgService = \Drupal::service('ibm_apim.consumerorg');
    $org = $consumerOrgService->createFromJSON($content);

    if ($org !== NULL) {
      $ref = $org->getUrl();
      $createdOrUpdated = $consumerOrgService->createOrUpdateNode($org, $event);
      if ($createdOrUpdated) {
        drush_log(dt('Drush @func created organization @org', [
          '@func' => $func,
          '@org' => $ref,
        ]), 'success');
      }
      else {
        drush_log(dt('Drush @func updated existing organization @org', [
          '@func' => $func,
          '@org' => $ref,
        ]), 'success');
      }
    }
    else {
      drush_log(dt('Drush @func ignoring organization update payload due to missing data', [
        '@func' => $func,
      ]), 'warning');
    }
    if ($original_user->id() !== 1) {
      $accountSwitcher->switchBack();
    }
  }
  else {
    drush_log(dt('Drush @func No organization provided', ['@func' => $func]), 'error');
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $event
 * @param $content
 */
function drush_consumerorg_create($content, $event = 'consumer_org_create') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  drush_consumerorg_createOrUpdate($content, $event, 'createOrg');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $event
 * @param $content
 */
function drush_consumerorg_update($content, $event = 'consumer_org_update') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  drush_consumerorg_createOrUpdate($content, $event, 'updateOrg');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $event
 * @param $content
 *
 * @throws \JsonException
 */
function drush_consumerorg_delete($content, $event = 'consumer_org_del') {
  ibm_apim_entry_trace(__FUNCTION__);
  if ($content !== NULL) {
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = Drupal::service('account_switcher');
    $original_user = \Drupal::currentUser();
    if ($original_user->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }

    if (is_string($content)) {
      $content = json_decode($content, TRUE, 512, JSON_THROW_ON_ERROR);
    }

    // TODO : hack until https://github.ibm.com/velox/apim/issues/6145 is fixed
    if (isset($content['portal_consumer_orgs'])) {
      $content = $content['portal_consumer_orgs'];
    }

    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'consumerorg');
    $query->condition('consumerorg_url.value', $content['consumer_org']['url']);

    $nids = $query->execute();
    if ($nids !== NULL && !empty($nids)) {
      $nid = array_shift($nids);
      $consumerOrgService = \Drupal::service('ibm_apim.consumerorg');
      $consumerOrgService->deleteNode($nid, $event);
      drush_log(dt('Drush DeleteOrg deleted organization @org', ['@org' => $content['consumer_org']['title']]), 'success');
    }
    else {
      drush_log(dt('Drush DeleteOrg could not find organization @org', ['@org' => $content['consumer_org']['title']]), 'warning');
    }
    if ($original_user->id() !== 1) {
      $accountSwitcher->switchBack();
    }
  }
  else {
    drush_log(dt('Drush DeleteOrg no ID provided', []), 'error');
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

function drush_consumerorg_massupdate($consumerOrgs = [], $event = 'content_refresh') {
  ibm_apim_entry_trace(__FUNCTION__, count($consumerOrgs));
  if (!empty($consumerOrgs)) {
    foreach ($consumerOrgs as $consumerOrg) {
      drush_consumerorg_createOrUpdate('MassUpdate', $event, $consumerOrg);
    }
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

function drush_consumerorg_tidy($consumerorgIds = []) {
  ibm_apim_entry_trace(__FUNCTION__, count($consumerorgIds));
  if (!empty($consumerorgIds)) {
    $nids = [];
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'consumerorg')->condition('consumerorg_id', $consumerorgIds, 'NOT IN');
    $results = $query->execute();
    if ($results !== NULL) {
      foreach ($results as $item) {
        $nids[] = $item;
      }
    }

    foreach ($nids as $nid) {
      $consumerOrgService = \Drupal::service('ibm_apim.consumerorg');
      $consumerOrgService->deleteNode($nid, 'content_refresh');
    }
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $event
 * @param $content
 */
function drush_consumerorg_invitation_create($content, $event = 'consumer_org_invitation_create') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  drush_consumerorg_invitation_createOrUpdate($content, $event);
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $event
 * @param $content
 */
function drush_consumerorg_invitation_update($content, $event = 'consumer_org_invitation_update') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  drush_consumerorg_invitation_createOrUpdate($content, $event);
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

function drush_consumerorg_invitation_delete($content = [], $event = 'invitation_delete') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  if ($content !== NULL) {
    $org_url = $content['consumer_org_url'];
    if (!empty($org_url)) {
      // in case moderation is on we need to run as admin
      // save the current user so we can switch back at the end
      $accountSwitcher = Drupal::service('account_switcher');
      $original_user = \Drupal::currentUser();
      if ($original_user->id() !== 1) {
        $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
      }

      $consumerOrgService = \Drupal::service('ibm_apim.consumerorg');

      $consumerOrgService->deleteInvitation($content, $event);

      if ($original_user->id() !== 1) {
        $accountSwitcher->switchBack();
      }
      drush_log(dt('Drush deleted invitation for organization @org', [
        '@org' => $org_url,
      ]), 'success');

    }
  }
  else {
    drush_log(dt('Drush @func No invitation provided', ['@func' => 'drush_consumerorg_invitation_delete']), 'error');
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $content
 * @param $event
 */
function drush_consumerorg_invitation_createOrUpdate($content, $event) {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  if ($content !== NULL) {
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = Drupal::service('account_switcher');
    $original_user = \Drupal::currentUser();
    if ($original_user->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }

    $consumerOrgService = \Drupal::service('ibm_apim.consumerorg');

    $createdOrUpdated = $consumerOrgService->createOrUpdateInvitation($content, $event);
    $ref = $content['consumer_org_url'];
    if ($createdOrUpdated) {
      drush_log(dt('Drush @func created invitation for organization @org', [
        '@func' => 'drush_consumerorg_invitation_createOrUpdate',
        '@org' => $ref,
      ]), 'success');
    }
    else {
      drush_log(dt('Drush @func updated existing invitation for organization @org', [
        '@func' => 'drush_consumerorg_invitation_createOrUpdate',
        '@org' => $ref,
      ]), 'success');
    }
    if ($original_user->id() !== 1) {
      $accountSwitcher->switchBack();
    }
  }
  else {
    drush_log(dt('Drush @func No invitation provided', ['@func' => 'drush_consumerorg_invitation_createOrUpdate']), 'error');
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $content
 * @param string $event
 *
 * @throws \JsonException
 */
function drush_consumerorg_role_create($content, $event = 'role_create') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);

  $corg_service = \Drupal::service('ibm_apim.consumerorg');
  $role_service = \Drupal::service('consumerorg.role');

  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = Drupal::service('account_switcher');
  $original_user = \Drupal::currentUser();
  if ($original_user->id() !== 1) {
    $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
  }

  if (is_string($content)) {
    $content = json_decode($content, TRUE, 512, JSON_THROW_ON_ERROR);
  }

  // first - do we have the corg that this role belongs to?
  $corg_url = $content['consumer_org_url'];
  $corg = $corg_service->get($corg_url);

  if (empty($corg)) {
    drush_log(dt('Drush create role could not find org with url @org_url', ['@org_url' => $corg_url]), 'warning');
  }
  else {
    // update the org with the new roles
    $corg_roles = $corg->getRoles();
    $new_role = $role_service->createFromJSON($content);
    $corg_roles[] = $new_role;
    $corg->setRoles($corg_roles);
    $corg_service->createOrUpdateNode($corg, 'create role');
    drush_log(dt('Drush create role added role @role to org @org', [
      '@org' => $corg->getTitle(),
      '@role' => $new_role->getName(),
    ]), 'status');
  }

  if ($original_user->id() !== 1) {
    $accountSwitcher->switchBack();
  }

  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $content
 * @param string $event
 *
 * @throws \JsonException
 */
function drush_consumerorg_role_update($content, $event = 'role_update') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);

  $corg_service = \Drupal::service('ibm_apim.consumerorg');
  $role_service = \Drupal::service('consumerorg.role');

  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = Drupal::service('account_switcher');
  $original_user = \Drupal::currentUser();
  if ($original_user->id() !== 1) {
    $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
  }

  if (is_string($content)) {
    $content = json_decode($content, TRUE, 512, JSON_THROW_ON_ERROR);
  }

  // first - do we have the corg that this role belongs to?
  $corg_url = $content['consumer_org_url'];
  $corg = $corg_service->get($corg_url);

  if (empty($corg)) {
    drush_log(dt('Drush update role could not find org with url @org_url', ['@org_url' => $corg_url]), 'warning');
  }
  else {
    // update the org with the new roles
    $corg_roles = $corg->getRoles();
    $new_roles = [];
    if (!empty($corg_roles)) {
      foreach ($corg_roles as $corg_role) {
        if ($corg_role->getUrl() !== $content['url']) {
          $new_roles[] = $corg_role;
        }
      }
    }
    $new_role = $role_service->createFromJSON($content);
    $new_roles[] = $new_role;
    $corg->setRoles($new_roles);
    $corg_service->createOrUpdateNode($corg, 'update role');
    drush_log(dt('Drush update role updated role @role from org @org', [
      '@org' => $corg->getTitle(),
      '@role' => $content['name'],
    ]), 'status');
  }

  if ($original_user->id() !== 1) {
    $accountSwitcher->switchBack();
  }

  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $content
 * @param string $event
 *
 * @throws \JsonException
 */
function drush_consumerorg_role_delete($content, $event = 'role_delete') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);

  $corg_service = \Drupal::service('ibm_apim.consumerorg');

  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = Drupal::service('account_switcher');
  $original_user = \Drupal::currentUser();
  if ($original_user->id() !== 1) {
    $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
  }

  if (is_string($content)) {
    $content = json_decode($content, TRUE, 512, JSON_THROW_ON_ERROR);
  }

  // first - do we have the corg that this role belongs to?
  $corg_url = $content['consumer_org_url'];
  $corg = $corg_service->get($corg_url);

  if (empty($corg)) {
    drush_log(dt('Drush delete role could not find org with url @org_url', ['@org_url' => $corg_url]), 'warning');
  }
  else {
    // update the org with the new roles
    $corg_roles = $corg->getRoles();
    $new_roles = [];
    if (!empty($corg_roles)) {
      foreach ($corg_roles as $corg_role) {
        if ($corg_role->getUrl() !== $content['url']) {
          $new_roles[] = $corg_role;
        }
      }
    }
    $corg->setRoles($new_roles);
    $corg_service->createOrUpdateNode($corg, 'delete role');
    drush_log(dt('Drush delete role removed role @role from org @org', [
      '@org' => $corg->getTitle(),
      '@role' => $content['name'],
    ]), 'status');
  }

  if ($original_user->id() !== 1) {
    $accountSwitcher->switchBack();
  }

  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $payload
 * @param string $event
 *
 * @throws \JsonException
 */
function drush_consumerorg_member_create($payload, $event = 'member_create') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);

  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = Drupal::service('account_switcher');
  $original_user = \Drupal::currentUser();
  if ($original_user->id() !== 1) {
    $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
  }

  $corg_service = \Drupal::service('ibm_apim.consumerorg');
  $user_service = \Drupal::service('ibm_apim.apicuser');

  if (\is_string($payload)) {
    $payload = json_decode($payload, TRUE, 512, JSON_THROW_ON_ERROR);
  }

  // first - do we have the corg that this member is being added to?
  $corg_url = $payload['consumer_org_url'];
  $corg = $corg_service->get($corg_url, 1);

  if (empty($corg)) {
    drush_log(dt('Drush member-create could not find org with url @org_url', ['@org_url' => $corg_url]), 'warning');
  }
  elseif (empty($payload['user'])) {
    drush_log(dt('Drush member-create no user object provided'), 'error');
  }
  elseif (empty($payload['role_urls'])) {
    drush_log(dt('Drush member-create no role_urls provided'), 'error');
  }
  else {
    $member = new Member();
    $member->setUrl($payload['url']);
    $member->setUser($user_service->getUserFromJSON($payload['user']));
    $member->setUserUrl($payload['user_url']);
    $member->setRoleUrls($payload['role_urls']);
    $member->setState($payload['state']);
    if (!empty($payload['created_at'])) {
      $member->setCreatedAt(strtotime($payload['created_at']));
    }
    if (!empty($payload['updated_at'])) {
      $member->setUpdatedAt(strtotime($payload['updated_at']));
    }

    $corg->addMember($member);
    $corg_service->createOrUpdateNode($corg, 'member_create_drush');

    drush_log(dt('Added member @username to consumer org @id', [
      '@username' => $member->getUser()->getUsername(),
      '@id' => $corg->getId(),
    ]));
  }

  if ($original_user->id() !== 1) {
    $accountSwitcher->switchBack();
  }

  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $payload
 * @param string $event
 *
 * @throws \JsonException
 */
function drush_consumerorg_member_update($payload, $event = 'member_update') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);

  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = Drupal::service('account_switcher');
  $original_user = \Drupal::currentUser();
  if ($original_user->id() !== 1) {
    $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
  }

  $corg_service = \Drupal::service('ibm_apim.consumerorg');
  $user_service = \Drupal::service('ibm_apim.apicuser');

  if (\is_string($payload)) {
    $payload = json_decode($payload, TRUE, 512, JSON_THROW_ON_ERROR);
  }

  // first - do we have the corg that this member is being updated in?
  $corg_url = $payload['consumer_org_url'];
  $corg = $corg_service->get($corg_url, 1);

  if (empty($corg)) {
    drush_log(dt('Drush member-update could not find org with url @org_url', ['@org_url' => $corg_url]), 'warning');
  }
  elseif (empty($payload['user'])) {
    drush_log(dt('Drush member-update no user object provided'), 'error');
  }
  elseif (empty($payload['role_urls'])) {
    drush_log(dt('Drush member-update no role_urls provided'), 'error');
  }
  else {
    $members = $corg->getMembers();
    if ($members !== NULL && !empty($members)) {
      $updated = false;
      foreach ($members as $key => $existingMember) {
        if ($existingMember->getUserUrl() === $payload['user_url']) {
          $members[$key]->setRoleUrls($payload['role_urls']);
          $members[$key]->setState($payload['state']);
          $members[$key]->setUser($user_service->getUserFromJSON($payload['user']));
          if (!empty($payload['created_at'])) {
            $members[$key]->setCreatedAt(strtotime($payload['created_at']));
          }
          if (!empty($payload['updated_at'])) {
            $members[$key]->setUpdatedAt(strtotime($payload['updated_at']));
          }
          $updated = true;
        }
      }
      if ($updated) {
        $corg->setMembers($members);
        drush_log(dt('Updated member @username in consumer org @id', [
          '@username' => $payload['user']['username'],
          '@id' => $corg->getId(),
        ]));
      }
    }
    if (!$updated) {
      $member = new Member();
      $member->setUrl($payload['url']);
      $member->setUser($user_service->getUserFromJSON($payload['user']));
      $member->setUserUrl($payload['user_url']);
      $member->setRoleUrls($payload['role_urls']);
      $member->setState($payload['state']);
      if (!empty($payload['created_at'])) {
        $member->setCreatedAt(strtotime($payload['created_at']));
      }
      if (!empty($payload['updated_at'])) {
        $member->setUpdatedAt(strtotime($payload['updated_at']));
      }
      $corg->addMember($member);
      drush_log(dt('Added member @username to consumer org @id', [
        '@username' => $member->getUser()->getUsername(),
        '@id' => $corg->getId(),
      ]));
    }

    $corg_service->createOrUpdateNode($corg, 'member_update_drush');
  }

  if ($original_user->id() !== 1) {
    $accountSwitcher->switchBack();
  }

  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * Removes a member from a consumer org
 *
 * @param $payload
 * @param string $event
 *
 * @throws \JsonException
 */
function drush_consumerorg_member_delete($payload, $event = 'member_delete') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);

  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = Drupal::service('account_switcher');
  $original_user = \Drupal::currentUser();
  if ($original_user->id() !== 1) {
    $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
  }

  $corg_service = \Drupal::service('ibm_apim.consumerorg');
  $user_service = \Drupal::service('ibm_apim.apicuser');

  if (\is_string($payload)) {
    $payload = json_decode($payload, TRUE, 512, JSON_THROW_ON_ERROR);
  }

  // first - do we have the corg that this member is being removed from?
  $corg_url = $payload['consumer_org_url'];
  $corg = $corg_service->get($corg_url, 1);

  if (empty($corg)) {
    drush_log(dt('Drush member-delete could not find org with url @org_url', ['@org_url' => $corg_url]), 'warning');
  }
  elseif (empty($payload['user'])) {
    drush_log(dt('Drush member-delete no user object provided'), 'error');
  }
  else {
    $member = new Member();
    $member->setUrl($payload['url']);
    $member->setUser($user_service->getUserFromJSON($payload['user']));
    $member->setUserUrl($payload['user_url']);
    $member->setRoleUrls($payload['role_urls']);
    $member->setState($payload['state']);
    if (!empty($payload['created_at'])) {
      $member->setCreatedAt(strtotime($payload['created_at']));
    }
    if (!empty($payload['updated_at'])) {
      $member->setUpdatedAt(strtotime($payload['updated_at']));
    }

    $corg->removeMember($member);
    $corg_service->createOrUpdateNode($corg, 'member_remove_drush');
    $user =  \Drupal::service('ibm_apim.user_storage')->loadUserByUrl($payload['user_url']);
    \Drupal::service('ibm_apim.user_utils')->removeConsumerOrgFromUser($corg->getUrl(), $user);

    drush_log(dt('Removed member @username from consumer org @id', [
      '@username' => $member->getUser()->getUsername(),
      '@id' => $corg->getId(),
    ]));

  }

  if ($original_user->id() !== 1) {
    $accountSwitcher->switchBack();
  }

  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $content
 * @param string $event
 *
 * @throws \JsonException
 */
function drush_consumerorg_payment_method_create($content, $event = 'payment_method_create') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);

  $corg_service = \Drupal::service('ibm_apim.consumerorg');

  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = Drupal::service('account_switcher');
  $original_user = \Drupal::currentUser();
  if ($original_user->id() !== 1) {
    $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
  }

  if (is_string($content)) {
    $content = json_decode($content, TRUE, 512, JSON_THROW_ON_ERROR);
  }

  // first - do we have the corg that this role belongs to?
  $corg_url = $content['consumer_org_url'];
  $corg = $corg_service->get($corg_url);

  if (empty($corg)) {
    drush_log(dt('Drush create payment method could not find org with url @org_url', ['@org_url' => $corg_url]), 'warning');
  }
  else {
    // update the org with the new payment method
    $payment_method_service = \Drupal::service('consumerorg.paymentmethod');
    $content['consumer_org_url'] = $corg_url;
    $payment_method_service->createOrUpdate($content);

    drush_log(dt('Drush create payment method added payment method @id to org @org', [
      '@org' => $corg->getTitle(),
      '@id' => $content['id'],
    ]), 'status');
  }

  if ($original_user->id() !== 1) {
    $accountSwitcher->switchBack();
  }

  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $content
 * @param string $event
 *
 * @throws \JsonException
 */
function drush_consumerorg_payment_method_update($content, $event = 'payment_method_update') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);

  $corg_service = \Drupal::service('ibm_apim.consumerorg');

  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = Drupal::service('account_switcher');
  $original_user = \Drupal::currentUser();
  if ($original_user->id() !== 1) {
    $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
  }

  if (is_string($content)) {
    $content = json_decode($content, TRUE, 512, JSON_THROW_ON_ERROR);
  }

  // first - do we have the corg that this role belongs to?
  $corg_url = $content['consumer_org_url'];
  $corg = $corg_service->get($corg_url);

  if (empty($corg)) {
    drush_log(dt('Drush update payment method could not find org with url @org_url', ['@org_url' => $corg_url]), 'warning');
  }
  else {
    // update the org with the new payment method
    $payment_method_service = \Drupal::service('consumerorg.paymentmethod');
    $content['consumer_org_url'] = $corg_url;
    $payment_method_service->createOrUpdate($content);

    drush_log(dt('Drush update payment method updated payment method @id from org @org', [
      '@org' => $corg->getTitle(),
      '@id' => $content['id'],
    ]), 'status');
  }

  if ($original_user->id() !== 1) {
    $accountSwitcher->switchBack();
  }

  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $content
 * @param string $event
 *
 * @throws \JsonException
 */
function drush_consumerorg_payment_method_delete($content, $event = 'payment_method_delete') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);

  $corg_service = \Drupal::service('ibm_apim.consumerorg');

  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = Drupal::service('account_switcher');
  $original_user = \Drupal::currentUser();
  if ($original_user->id() !== 1) {
    $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
  }

  if (is_string($content)) {
    $content = json_decode($content, TRUE, 512, JSON_THROW_ON_ERROR);
  }

  // first - do we have the corg that this role belongs to?
  $corg_url = $content['consumer_org_url'];
  $corg = $corg_service->get($corg_url);

  if (empty($corg)) {
    drush_log(dt('Drush delete payment method could not find org with url @org_url', ['@org_url' => $corg_url]), 'warning');
  }
  else {
    // delete the payment method
    $payment_method_id = $content['id'];
    $payment_method_service = \Drupal::service('consumerorg.paymentmethod');
    $payment_method_service->delete($payment_method_id, $corg_url);

    drush_log(dt('Drush delete payment method removed payment method @id from org @org', [
      '@org' => $corg->getTitle(),
      '@id' => $payment_method_id,
    ]), 'status');
  }

  if ($original_user->id() !== 1) {
    $accountSwitcher->switchBack();
  }

  ibm_apim_exit_trace(__FUNCTION__, NULL);
}
