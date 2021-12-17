<?php

namespace Drupal\consumerorg\Commands;

use Drupal\consumerorg\ApicType\Member;
use Drupal\Core\Session\UserSession;
use Drush\Commands\DrushCommands;

/**
 * Class ConsumerOrgCommands.
 *
 * @package Drupal\consumerorg\Commands
 */
class ConsumerOrgCommands extends DrushCommands {

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @command apic-consumerorg-cleanse-drush-command
   * @usage drush apic-consumerorg-cleanse-drush-command
   *   Clears the consumerorg entries back to a clean state.
   * @aliases cleanse_consumerorgs
   */
  public function drush_consumerorg_cleanse_drush_command(): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = \Drupal::service('account_switcher');
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
    \Drupal::logger('consumerorg')->info('All consumer organization entries deleted.');
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $func
   * @param $event
   * @param $content
   */
  public function drush_consumerorg_createOrUpdate($content, $event, $func): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    if ($content !== NULL) {
      // in case moderation is on we need to run as admin
      // save the current user so we can switch back at the end
      $accountSwitcher = \Drupal::service('account_switcher');
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
          \Drupal::logger('consumerorg')->info('Drush @func created organization @org', [
            '@func' => $func,
            '@org' => $ref,
          ]);
        }
        else {
          \Drupal::logger('consumerorg')->info('Drush @func updated existing organization @org', [
            '@func' => $func,
            '@org' => $ref,
          ]);
        }
      }
      else {
        \Drupal::logger('consumerorg')->warning('Drush @func ignoring organization update payload due to missing data', [
          '@func' => $func,
        ]);
      }
      if ($original_user->id() !== 1) {
        $accountSwitcher->switchBack();
      }
    }
    else {
      \Drupal::logger('consumerorg')->error('Drush @func No organization provided', ['@func' => $func]);
    }
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param string|null $event
   * @param $content
   *
   * @command consumerorg-create
   * @usage drush consumerorg-create [content] [event]
   *   Creates a consumerorg.
   * @aliases corg
   */
  public function drush_consumerorg_create($content, ?string $event = 'consumer_org_create'): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    $this->drush_consumerorg_createOrUpdate($content, $event, 'createOrg');
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param string|null $event
   * @param $content
   *
   * @command consumerorg-update
   * @usage drush consumerorg-update [content] [event]
   *   Updates a consumerorg.
   * @aliases uorg
   */
  public function drush_consumerorg_update($content, ?string $event = 'consumer_org_update'): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    $this->drush_consumerorg_createOrUpdate($content, $event, 'updateOrg');
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param string|null $event
   * @param $content
   *
   * @throws \JsonException
   *
   * @command consumerorg-delete
   * @usage drush consumerorg-delete [content] [event]
   *   Deletes a consumerorg.
   * @aliases dorg
   */
  public function drush_consumerorg_delete($content, ?string $event = 'consumer_org_del'): void {
    ibm_apim_entry_trace(__FUNCTION__);
    if ($content !== NULL) {
      // in case moderation is on we need to run as admin
      // save the current user so we can switch back at the end
      $accountSwitcher = \Drupal::service('account_switcher');
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
        \Drupal::logger('consumerorg')->info('Drush DeleteOrg deleted organization @org', ['@org' => $content['consumer_org']['title']]);
      }
      else {
        \Drupal::logger('consumerorg')
          ->warning('Drush DeleteOrg could not find organization @org', ['@org' => $content['consumer_org']['title']]);
      }
      if ($original_user->id() !== 1) {
        $accountSwitcher->switchBack();
      }
    }
    else {
      \Drupal::logger('consumerorg')->error('Drush DeleteOrg no ID provided', []);
    }
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }


  /**
   * @param array $consumerOrgs
   * @param string|null $event
   *
   * @command consumerorg-massupdate
   * @usage drush consumerorg-massupdate [consumerOrgs] [event]
   *   Mass updates a list of Consumerorgs.
   * @aliases morg
   */
  public function drush_consumerorg_massupdate(array $consumerOrgs = [], ?string $event = 'content_refresh'): void {
    ibm_apim_entry_trace(__FUNCTION__, count($consumerOrgs));
    if (!empty($consumerOrgs)) {
      foreach ($consumerOrgs as $consumerOrg) {
        $this->drush_consumerorg_createOrUpdate('MassUpdate', $event, $consumerOrg);
      }
    }
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param array $consumerorgIds
   *
   * @command consumerorg-tidy
   * @usage drush consumerorg-tiday [consumerorgIds]
   *   Tidies the list of Consumerorgs to ensure consistency with APIM.
   * @aliases torg
   */
  public function drush_consumerorg_tidy(array $consumerorgIds = []): void {
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
   * @param string|null $event
   * @param $content
   */
  public function drush_consumerorg_invitation_create($content, ?string $event = 'consumer_org_invitation_create'): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    $this->drush_consumerorg_invitation_createOrUpdate($content, $event);
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param string|null $event
   * @param $content
   */
  public function drush_consumerorg_invitation_update($content, string $event = 'consumer_org_invitation_update'): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    $this->drush_consumerorg_invitation_createOrUpdate($content, $event);
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param array $content
   * @param string|null $event
   */
  public function drush_consumerorg_invitation_delete(array $content = [], ?string $event = 'invitation_delete'): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    if ($content !== NULL) {
      $org_url = $content['consumer_org_url'];
      if (!empty($org_url)) {
        // in case moderation is on we need to run as admin
        // save the current user so we can switch back at the end
        $accountSwitcher = \Drupal::service('account_switcher');
        $original_user = \Drupal::currentUser();
        if ($original_user->id() !== 1) {
          $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
        }

        $consumerOrgService = \Drupal::service('ibm_apim.consumerorg');

        $consumerOrgService->deleteInvitation($content, $event);

        if ($original_user->id() !== 1) {
          $accountSwitcher->switchBack();
        }
        \Drupal::logger('consumerorg')->info('Drush deleted invitation for organization @org', [
          '@org' => $org_url,
        ]);
      }
    }
    else {
      \Drupal::logger('consumerorg')->error('Drush @func No invitation provided', ['@func' => 'drush_consumerorg_invitation_delete']);
    }
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $content
   * @param $event
   */
  public function drush_consumerorg_invitation_createOrUpdate($content, $event): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    if ($content !== NULL) {
      // in case moderation is on we need to run as admin
      // save the current user so we can switch back at the end
      $accountSwitcher = \Drupal::service('account_switcher');
      $original_user = \Drupal::currentUser();
      if ($original_user->id() !== 1) {
        $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
      }

      $consumerOrgService = \Drupal::service('ibm_apim.consumerorg');

      $createdOrUpdated = $consumerOrgService->createOrUpdateInvitation($content, $event);
      $ref = $content['consumer_org_url'];
      if ($createdOrUpdated) {
        \Drupal::logger('consumerorg')->info('Drush @func created invitation for organization @org', [
          '@func' => 'drush_consumerorg_invitation_createOrUpdate',
          '@org' => $ref,
        ]);
      }
      else {
        \Drupal::logger('consumerorg')->info('Drush @func updated existing invitation for organization @org', [
          '@func' => 'drush_consumerorg_invitation_createOrUpdate',
          '@org' => $ref,
        ]);
      }
      if ($original_user->id() !== 1) {
        $accountSwitcher->switchBack();
      }
    }
    else {
      \Drupal::logger('consumerorg')
        ->error('Drush @func No invitation provided', ['@func' => 'drush_consumerorg_invitation_createOrUpdate']);
    }
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $content
   * @param string|null $event
   *
   * @throws \JsonException
   *
   * @command consumerorg-role-create
   * @usage drush consumerorg-role-create [content] [event]
   *   Creates a role (which belongs to an org).
   * @aliases crole
   */
  public function drush_consumerorg_role_create($content, ?string $event = 'role_create'): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);

    $corg_service = \Drupal::service('ibm_apim.consumerorg');
    $role_service = \Drupal::service('consumerorg.role');

    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = \Drupal::service('account_switcher');
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
      \Drupal::logger('consumerorg')->warning('Drush create role could not find org with url @org_url', ['@org_url' => $corg_url]);
    }
    else {
      // update the org with the new roles
      $corg_roles = $corg->getRoles();
      $new_role = $role_service->createFromJSON($content);
      $corg_roles[] = $new_role;
      $corg->setRoles($corg_roles);
      $corg_service->createOrUpdateNode($corg, 'create role');
      \Drupal::logger('consumerorg')->notice('Drush create role added role @role to org @org', [
        '@org' => $corg->getTitle(),
        '@role' => $new_role->getName(),
      ]);
    }

    if ($original_user->id() !== 1) {
      $accountSwitcher->switchBack();
    }

    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $content
   * @param string|null $event
   *
   * @throws \JsonException
   */
  public function drush_consumerorg_role_update($content, ?string $event = 'role_update'): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);

    $corg_service = \Drupal::service('ibm_apim.consumerorg');
    $role_service = \Drupal::service('consumerorg.role');

    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = \Drupal::service('account_switcher');
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
      \Drupal::logger('consumerorg')->warning('Drush update role could not find org with url @org_url', ['@org_url' => $corg_url]);
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
      \Drupal::logger('consumerorg')->notice('Drush update role updated role @role from org @org', [
        '@org' => $corg->getTitle(),
        '@role' => $content['name'],
      ]);
    }

    if ($original_user->id() !== 1) {
      $accountSwitcher->switchBack();
    }

    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $content
   * @param string|null $event
   *
   * @throws \JsonException
   */
  public function drush_consumerorg_role_delete($content, ?string $event = 'role_delete'): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);

    $corg_service = \Drupal::service('ibm_apim.consumerorg');

    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = \Drupal::service('account_switcher');
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
      \Drupal::logger('consumerorg')->warning('Drush delete role could not find org with url @org_url', ['@org_url' => $corg_url]);
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
      \Drupal::logger('consumerorg')->notice('Drush delete role removed role @role from org @org', [
        '@org' => $corg->getTitle(),
        '@role' => $content['name'],
      ]);
    }

    if ($original_user->id() !== 1) {
      $accountSwitcher->switchBack();
    }

    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $payload
   * @param string|null $event
   *
   * @throws \JsonException
   *
   * @command consumerorg-member-create
   * @usage drush consumerorg-member-create [payload] [event]
   *   Create a member in the org.
   * @aliases cmembercreate
   */
  public function drush_consumerorg_member_create($payload, ?string $event = 'member_create'): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);

    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = \Drupal::service('account_switcher');
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
      \Drupal::logger('consumerorg')->warning('Drush member-create could not find org with url @org_url', ['@org_url' => $corg_url]);
    }
    elseif (empty($payload['user'])) {
      \Drupal::logger('consumerorg')->error('Drush member-create no user object provided');
    }
    elseif (empty($payload['role_urls'])) {
      \Drupal::logger('consumerorg')->error('Drush member-create no role_urls provided');
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

      \Drupal::logger('consumerorg')->info('Added member @username to consumer org @id', [
        '@username' => $member->getUser()->getUsername(),
        '@id' => $corg->getId(),
      ]);
    }

    if ($original_user->id() !== 1) {
      $accountSwitcher->switchBack();
    }

    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $payload
   * @param string|null $event
   *
   * @throws \JsonException
   */
  public function drush_consumerorg_member_update($payload, ?string $event = 'member_update'): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);

    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = \Drupal::service('account_switcher');
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
      \Drupal::logger('consumerorg')->warning('Drush member-update could not find org with url @org_url', ['@org_url' => $corg_url]);
    }
    elseif (empty($payload['user'])) {
      \Drupal::logger('consumerorg')->error('Drush member-update no user object provided');
    }
    elseif (empty($payload['role_urls'])) {
      \Drupal::logger('consumerorg')->error('Drush member-update no role_urls provided');
    }
    else {
      $members = $corg->getMembers();
      if ($members !== NULL && !empty($members)) {
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
          }
        }
        $corg->setMembers($members);
        \Drupal::logger('consumerorg')->notice('Updated member @username in consumer org @id', [
          '@username' => $payload['user']['username'],
          '@id' => $corg->getId(),
        ]);
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
        \Drupal::logger('consumerorg')->notice('Added member @username to consumer org @id', [
          '@username' => $member->getUser()->getUsername(),
          '@id' => $corg->getId(),
        ]);
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
   * @param string|null $event
   *
   * @throws \JsonException
   */
  public function drush_consumerorg_member_delete($payload, ?string $event = 'member_delete'): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);

    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = \Drupal::service('account_switcher');
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
      \Drupal::logger('consumerorg')->warning('Drush member-delete could not find org with url @org_url', ['@org_url' => $corg_url]);
    }
    elseif (empty($payload['user'])) {
      \Drupal::logger('consumerorg')->error('Drush member-delete no user object provided');
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

      \Drupal::logger('consumerorg')->notice('Removed member @username from consumer org @id', [
        '@username' => $member->getUser()->getUsername(),
        '@id' => $corg->getId(),
      ]);
    }

    if ($original_user->id() !== 1) {
      $accountSwitcher->switchBack();
    }

    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $content
   * @param string|null $event
   *
   * @throws \JsonException
   *
   * @command consumerorg-payment-method-create
   * @usage drush consumerorg-payment-method-create [content] [event]
   *   Creates a payment method (which belongs to an org).
   * @aliases cpaymentmethod
   */
  public function drush_consumerorg_payment_method_create($content, ?string $event = 'payment_method_create'): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);

    $corg_service = \Drupal::service('ibm_apim.consumerorg');

    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = \Drupal::service('account_switcher');
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
      \Drupal::logger('consumerorg')
        ->warning('Drush create payment method could not find org with url @org_url', ['@org_url' => $corg_url]);
    }
    else {
      // update the org with the new payment method
      $payment_method_service = \Drupal::service('consumerorg.paymentmethod');
      $content['consumer_org_url'] = $corg_url;
      $payment_method_service->createOrUpdate($content);

      \Drupal::logger('consumerorg')->notice('Drush create payment method added payment method @id to org @org', [
        '@org' => $corg->getTitle(),
        '@id' => $content['id'],
      ]);
    }

    if ($original_user->id() !== 1) {
      $accountSwitcher->switchBack();
    }

    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $content
   * @param string|null $event
   *
   * @throws \JsonException
   *
   * @command consumerorg-payment-method-update
   * @usage drush consumerorg-payment-method-update [content] [event]
   *   Updates a payment method (which belongs to an org).
   * @aliases upaymentmethod
   */
  public function drush_consumerorg_payment_method_update($content, ?string $event = 'payment_method_update'): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);

    $corg_service = \Drupal::service('ibm_apim.consumerorg');

    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = \Drupal::service('account_switcher');
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
      \Drupal::logger('consumerorg')
        ->warning('Drush update payment method could not find org with url @org_url', ['@org_url' => $corg_url]);
    }
    else {
      // update the org with the new payment method
      $payment_method_service = \Drupal::service('consumerorg.paymentmethod');
      $content['consumer_org_url'] = $corg_url;
      $payment_method_service->createOrUpdate($content);

      \Drupal::logger('consumerorg')->notice('Drush update payment method updated payment method @id from org @org', [
        '@org' => $corg->getTitle(),
        '@id' => $content['id'],
      ]);
    }

    if ($original_user->id() !== 1) {
      $accountSwitcher->switchBack();
    }

    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $content
   * @param string|null $event
   *
   * @throws \JsonException
   *
   * @command consumerorg-payment-method-delete
   * @usage drush consumerorg-payment-method-delete [content] [event]
   *   Deletes a payment method (which belongs to an org).
   * @aliases dpaymentmethod
   */
  public function drush_consumerorg_payment_method_delete($content, ?string $event = 'payment_method_delete'): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);

    $corg_service = \Drupal::service('ibm_apim.consumerorg');

    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = \Drupal::service('account_switcher');
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
      \Drupal::logger('consumerorg')
        ->warning('Drush delete payment method could not find org with url @org_url', ['@org_url' => $corg_url]);
    }
    else {
      // delete the payment method
      $payment_method_id = $content['id'];
      $payment_method_service = \Drupal::service('consumerorg.paymentmethod');
      $payment_method_service->delete($payment_method_id, $corg_url);

      \Drupal::logger('consumerorg')->notice('Drush delete payment method removed payment method @id from org @org', [
        '@org' => $corg->getTitle(),
        '@id' => $payment_method_id,
      ]);
    }

    if ($original_user->id() !== 1) {
      $accountSwitcher->switchBack();
    }

    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

}
