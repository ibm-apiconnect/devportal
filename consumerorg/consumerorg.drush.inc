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

use Drupal\Core\Session\UserSession;
use Drupal\consumerorg\ApicType\Member;

function consumerorg_drush_command() {

  $items['consumerorg-cleanse-drush-command'] = array(
    'description' => 'Clears the consumerorg entries back to a clean state.',
    'aliases' => array('cleanse_consumerorgs')
  );
  $items['consumerorg-update'] = array(
    'description' => 'Updates a consumerorg',
    'aliases' => array('uorg'),
    'arguments' => array(
      'event' => 'The event type',
      'product' => 'The JSON consumerorg payload'
    )
  );
  $items['consumerorg-create'] = array(
    'description' => 'Creates a consumerorg',
    'aliases' => array('corg'),
    'arguments' => array(
      'event' => 'The event type',
      'product' => 'The JSON consumerorg payload'
    )
  );
  $items['consumerorg-delete'] = array(
    'description' => 'Deletes a consumerorg',
    'aliases' => array('dorg'),
    'arguments' => array(
      'event' => 'The event type',
      'id' => 'The consumerorg ID'
    )
  );
  $items['consumerorg-massupdate'] = array(
    'description' => 'Mass updates a list of Consumerorgs',
    'aliases' => array('morg'),
    'arguments' => array(
      'consumerorgs' => 'The JSON array of Consumerorgs',
      'event' => 'The event type'
    )
  );
  $items['consumerorg-tidy'] = array(
    'description' => 'Tidies the list of Consumerorgs to ensure consistency with APIm',
    'aliases' => array('torg'),
    'arguments' => array(
      'consumerorgids' => 'The JSON array of consumerorg IDs'
    )
  );
  $items['consumerorg-role-create'] = array(
    'description' => 'Creates a role (which belongs to an org)',
    'aliases' => array('crole'),
    'arguments' => array(
      'event' => 'The event type',
      'product' => 'The JSON role payload'
    )
  );
  $items['consumerorg-member-create'] = array(
    'description' => 'Create a member in the org',
    'aliases' => array('cmembercreate'),
    'arguments' => array(
      'payload' => 'The JSON member payload',
      'event' => 'The event type'
    )
  );

  return $items;
}

function drush_consumerorg_cleanse_drush_command() {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = Drupal::service('account_switcher');
  $original_user = \Drupal::currentUser();
  if ($original_user->id() != 1) {
    $accountSwitcher->switchTo(new UserSession(array('uid' => 1)));
  }
  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(array('type' => 'consumerorg'));

  foreach ($nodes as $node) {
    $node->delete();
  }
  if (isset($original_user) && $original_user->id() != 1) {
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
  if (isset($content)) {
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = Drupal::service('account_switcher');
    $original_user = \Drupal::currentUser();
    if ($original_user->id() != 1) {
      $accountSwitcher->switchTo(new UserSession(array('uid' => 1)));
    }

    $consumerOrgService = \Drupal::service('ibm_apim.consumerorg');
    $org = $consumerOrgService->createFromJSON($content);

    if (isset($org)) {
      $ref = $org->getUrl();
      $createdOrUpdated = $consumerOrgService->createOrUpdateNode($org, $event);
      if ($createdOrUpdated) {
        drush_log(dt('Drush @func created organization @org', array(
          '@func' => $func,
          '@org' => $ref
        )), 'success');
      }
      else {
        drush_log(dt('Drush @func updated existing organization @org', array(
          '@func' => $func,
          '@org' => $ref
        )), 'success');
      }
    } else {
      drush_log(dt('Drush @func ignoring organization update payload due to missing data', array(
        '@func' => $func
      )), 'warning');
    }
    if (isset($original_user) && $original_user->id() != 1) {
      $accountSwitcher->switchBack();
    }
  }
  else {
    drush_log(dt('Drush @func No organization provided', array('@func' => $func)), 'error');
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $event
 * @param $content
 */
function drush_consumerorg_create($content, $event = 'consumer_org_create') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  drush_consumerorg_createOrUpdate($content, $event, "createOrg");
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $event
 * @param $content
 */
function drush_consumerorg_update($content, $event = 'consumer_org_update') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  drush_consumerorg_createOrUpdate($content, $event, "updateOrg");
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $event
 * @param $content
 */
function drush_consumerorg_delete($content, $event = 'consumer_org_del') {
  ibm_apim_entry_trace(__FUNCTION__);
  if (isset($content)) {
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = Drupal::service('account_switcher');
    $original_user = \Drupal::currentUser();
    if ($original_user->id() != 1) {
      $accountSwitcher->switchTo(new UserSession(array('uid' => 1)));
    }

    if (is_string($content)) {
      $content = json_decode($content, TRUE);
    }

    // TODO : hack until https://github.ibm.com/velox/apim/issues/6145 is fixed
    if(isset($content['portal_consumer_orgs'])) {
      $content = $content['portal_consumer_orgs'];
    }

    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'consumerorg');
    $query->condition('consumerorg_url.value', $content['consumer_org']['url']);

    $nids = $query->execute();
    if (isset($nids) && !empty($nids)) {
      $nid = array_shift($nids);
      $consumerOrgService = \Drupal::service('ibm_apim.consumerorg');
      $consumerOrgService->deleteNode($nid, $event);
      drush_log(dt('Drush DeleteOrg deleted organization @org', array('@org' => $content['consumer_org']['title'])), 'success');
    }
    else {
      drush_log(dt('Drush DeleteOrg could not find organization @org', array('@org' => $content['consumer_org']['title'])), 'warning');
    }
    if (isset($original_user) && $original_user->id() != 1) {
      $accountSwitcher->switchBack();
    }
  }
  else {
    drush_log(dt('Drush DeleteOrg no ID provided', array()), 'error');
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

function drush_consumerorg_massupdate($consumerorgs = array(), $event = 'content_refresh') {
  ibm_apim_entry_trace(__FUNCTION__, count($consumerorgs));
  $consumerorgs = json_decode($consumerorgs, TRUE);
  if (!empty($consumerorgs)) {
    foreach ($consumerorgs as $consumerorg) {
      drush_consumerorg_createOrUpdateOrg('MassUpdate', $event, $consumerorg);
    }
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

function drush_consumerorg_tidy($consumerorgids = array()) {
  ibm_apim_entry_trace(__FUNCTION__, count($consumerorgids));
  $consumerorgids = json_decode($consumerorgids, TRUE);
  if (!empty($consumerorgids)) {
    $nids = array();
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'consumerorg')->condition('consumerorg_id', $consumerorgids, 'NOT IN');
    $results = $query->execute();
    if (isset($results)) {
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

function drush_consumerorg_invitation_delete($content = array(), $event = 'invitation_delete') {
  ibm_apim_entry_trace(__FUNCTION__, null);
  if (isset($content)) {
    $org_url = $content['consumer_org_url'];
    if (!empty($org_url)) {
      if (isset($content)) {
        // in case moderation is on we need to run as admin
        // save the current user so we can switch back at the end
        $accountSwitcher = Drupal::service('account_switcher');
        $original_user = \Drupal::currentUser();
        if ($original_user->id() != 1) {
          $accountSwitcher->switchTo(new UserSession(array('uid' => 1)));
        }

        $consumerOrgService = \Drupal::service('ibm_apim.consumerorg');

        $consumerOrgService->deleteInvitation($content, $event);

        if (isset($original_user) && $original_user->id() != 1) {
          $accountSwitcher->switchBack();
        }
        drush_log(dt('Drush deleted invitation for organization @org', array(
          '@org' => $org_url
        )), 'success');
      }
    }
  }
  else {
    drush_log(dt('Drush @func No invitation provided', array('@func' => 'drush_consumerorg_invitation_delete')), 'error');
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $content
 * @param $event
 * @param $func
 */
function drush_consumerorg_invitation_createOrUpdate($content, $event) {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  if (isset($content)) {
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = Drupal::service('account_switcher');
    $original_user = \Drupal::currentUser();
    if ($original_user->id() != 1) {
      $accountSwitcher->switchTo(new UserSession(array('uid' => 1)));
    }

    $consumerOrgService = \Drupal::service('ibm_apim.consumerorg');

    $createdOrUpdated = $consumerOrgService->createOrUpdateInvitation($content, $event);
    $ref = $content['consumer_org_url'];
    if ($createdOrUpdated) {
      drush_log(dt('Drush @func created invitation for organization @org', array(
        '@func' => 'drush_consumerorg_invitation_createOrUpdate',
        '@org' => $ref
      )), 'success');
    }
    else {
      drush_log(dt('Drush @func updated existing invitation for organization @org', array(
        '@func' => 'drush_consumerorg_invitation_createOrUpdate',
        '@org' => $ref
      )), 'success');
    }
    if (isset($original_user) && $original_user->id() != 1) {
      $accountSwitcher->switchBack();
    }
  }
  else {
    drush_log(dt('Drush @func No invitation provided', array('@func' => 'drush_consumerorg_invitation_createOrUpdate')), 'error');
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $content
 * @param string $event
 */
function drush_consumerorg_role_create($content, $event = 'role_create') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);

  $corg_service = \Drupal::service('ibm_apim.consumerorg');
  $role_service = \Drupal::service('consumerorg.role');

  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = Drupal::service('account_switcher');
  $original_user = \Drupal::currentUser();
  if ($original_user->id() != 1) {
    $accountSwitcher->switchTo(new UserSession(array('uid' => 1)));
  }

  if(is_string($content)) {
    $content = json_decode($content, 1);
  }

  // first - do we have the corg that this role belongs to?
  $corg_url = $content['consumer_org_url'];
  $corg = $corg_service->get($corg_url);

  if (empty($corg)) {
    drush_log(dt('Drush create role could not find org with url @org_url', array('@org_url' => $corg_url)), 'warning');
  }
  else {
    // update the org with the new roles
    $corg_roles = $corg->getRoles();
    $new_role = $role_service->createFromJSON($content);
    $corg_roles[] = $new_role;
    $corg->setRoles($corg_roles);
    $corg_service->createOrUpdateNode($corg, 'create role');
    drush_log(dt('Drush create role added role @role to org @org', array(
      '@org' => $corg->getTitle(),
      '@role' => $new_role->getName()
    )), 'status');
  }

  if (isset($original_user) && $original_user->id() != 1) {
    $accountSwitcher->switchBack();
  }

  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $content
 * @param string $event
 */
function drush_consumerorg_role_update($content, $event = 'role_update') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);

  $corg_service = \Drupal::service('ibm_apim.consumerorg');
  $role_service = \Drupal::service('consumerorg.role');

  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = Drupal::service('account_switcher');
  $original_user = \Drupal::currentUser();
  if ($original_user->id() != 1) {
    $accountSwitcher->switchTo(new UserSession(array('uid' => 1)));
  }

  if(is_string($content)) {
    $content = json_decode($content, 1);
  }

  // first - do we have the corg that this role belongs to?
  $corg_url = $content['consumer_org_url'];
  $corg = $corg_service->get($corg_url);

  if (empty($corg)) {
    drush_log(dt('Drush update role could not find org with url @org_url', array('@org_url' => $corg_url)), 'warning');
  }
  else {
    // update the org with the new roles
    $corg_roles = $corg->getRoles();
    $new_roles = array();
    if (!empty($corg_roles)) {
      foreach($corg_roles as $corg_role) {
        if ($corg_role->getUrl() != $content['url']) {
          $new_roles[] = $corg_role;
        }
      }
    }
    $new_role = $role_service->createFromJSON($content);
    $new_roles[] = $new_role;
    $corg->setRoles($new_roles);
    $corg_service->createOrUpdateNode($corg, 'update role');
    drush_log(dt('Drush update role updated role @role from org @org', array(
      '@org' => $corg->getTitle(),
      '@role' => $content['name']
    )), 'status');
  }

  if (isset($original_user) && $original_user->id() != 1) {
    $accountSwitcher->switchBack();
  }

  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $content
 * @param string $event
 */
function drush_consumerorg_role_delete($content, $event = 'role_delete') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);

  $corg_service = \Drupal::service('ibm_apim.consumerorg');

  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = Drupal::service('account_switcher');
  $original_user = \Drupal::currentUser();
  if ($original_user->id() != 1) {
    $accountSwitcher->switchTo(new UserSession(array('uid' => 1)));
  }

  if(is_string($content)) {
    $content = json_decode($content, 1);
  }

  // first - do we have the corg that this role belongs to?
  $corg_url = $content['consumer_org_url'];
  $corg = $corg_service->get($corg_url);

  if (empty($corg)) {
    drush_log(dt('Drush delete role could not find org with url @org_url', array('@org_url' => $corg_url)), 'warning');
  }
  else {
    // update the org with the new roles
    $corg_roles = $corg->getRoles();
    $new_roles = array();
    if (!empty($corg_roles)) {
      foreach($corg_roles as $corg_role) {
        if ($corg_role->getUrl() != $content['url']) {
          $new_roles[] = $corg_role;
        }
      }
    }
    $corg->setRoles($new_roles);
    $corg_service->createOrUpdateNode($corg, 'delete role');
    drush_log(dt('Drush delete role removed role @role from org @org', array(
      '@org' => $corg->getTitle(),
      '@role' => $content['name']
    )), 'status');
  }

  if (isset($original_user) && $original_user->id() != 1) {
    $accountSwitcher->switchBack();
  }

  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $user
 * @param string $event
 */
function drush_consumerorg_member_create($payload, $event = 'member_create') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);

  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = Drupal::service('account_switcher');
  $original_user = \Drupal::currentUser();
  if ($original_user->id() != 1) {
    $accountSwitcher->switchTo(new UserSession(array('uid' => 1)));
  }

  $corg_service = \Drupal::service('ibm_apim.consumerorg');
  $user_service = \Drupal::service('ibm_apim.apicuser');

  if(is_string($payload)) {
    $payload = json_decode($payload, 1);
  }

  // first - do we have the corg that this member is being added to?
  $corg_url = $payload['consumer_org_url'];
  $corg = $corg_service->get($corg_url, 1);

  if (empty($corg)) {
    drush_log(dt('Drush member-create could not find org with url @org_url', array('@org_url' => $corg_url)), 'warning');
  }
  else {
    if(empty($payload['user'])) {
      drush_log(dt('Drush member-create no user object provided'), 'error');
    }
    else {

      if(empty($payload['role_urls'])) {
        drush_log(dt('Drush member-create no role_urls provided'), 'error');
      }
      else {
        $member = new Member();
        $member->setUrl($payload['url']);
        $member->setUser($user_service->getUserFromJSON($payload['user']));
        $member->setUserUrl($payload['user_url']);
        $member->setRoleUrls($payload['role_urls']);
        $member->setState($payload['state']);

        $corg->addMember($member);
        $corg_service->createOrUpdateNode($corg, 'member_create_drush');

        drush_log(dt("Added member @username to consumer org @id", array("@username" => $member->getUser()->getUsername(), "@id" => $corg->getId() )));

      }

    }
  }

  if (isset($original_user) && $original_user->id() != 1) {
    $accountSwitcher->switchBack();
  }

  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * Removes a member from a consumer org
 *
 * @param $user
 * @param string $event
 */
function drush_consumerorg_member_delete($payload, $event = 'member_delete') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);

  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = Drupal::service('account_switcher');
  $original_user = \Drupal::currentUser();
  if ($original_user->id() != 1) {
    $accountSwitcher->switchTo(new UserSession(array('uid' => 1)));
  }

  $corg_service = \Drupal::service('ibm_apim.consumerorg');
  $user_service = \Drupal::service('ibm_apim.apicuser');

  if(is_string($payload)) {
    $payload = json_decode($payload, 1);
  }

  // first - do we have the corg that this member is being removed from?
  $corg_url = $payload['consumer_org_url'];
  $corg = $corg_service->get($corg_url, 1);

  if (empty($corg)) {
    drush_log(dt('Drush member-delete could not find org with url @org_url', array('@org_url' => $corg_url)), 'warning');
  }
  else {
    if(empty($payload['user'])) {
      drush_log(dt('Drush member-delete no user object provided'), 'error');
    }
    else {

     $member = new Member();
     $member->setUrl($payload['url']);
     $member->setUser($user_service->getUserFromJSON($payload['user']));
     $member->setUserUrl($payload['user_url']);
     $member->setRoleUrls($payload['role_urls']);
     $member->setState($payload['state']);

     $corg->removeMember($member);
     $corg_service->createOrUpdateNode($corg, 'member_remove_drush');

      drush_log(dt("Removed member @username from consumer org @id", array("@username" => $member->getUser()->getUsername(), "@id" => $corg->getId() )));

    }
  }

  if (isset($original_user) && $original_user->id() != 1) {
    $accountSwitcher->switchBack();
  }

  ibm_apim_exit_trace(__FUNCTION__, NULL);
}
