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

namespace Drupal\apictest;

use Drupal\consumerorg\ApicType\ConsumerOrg;
use Drupal\consumerorg\ApicType\Member;
use Drupal\consumerorg\ApicType\Role;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\user\Entity\User;

class ApicTestUtils {

  /**
   * Generates a "unique" id based on the current system timestamp.
   * (May not be unique if called very quickly in sequence but seems to be)
   *
   * @return array|string|string[]
   */
  public static function makeId() {
    return str_replace('.', '', microtime(1));
  }

  /**
   * Create a new consumer org with the provided properties
   *
   */
  public static function createConsumerOrg(array $properties) {
    $org = new ConsumerOrg();
    $org->setTitle($properties['title']);
    $org->setName($properties['name']);
    $org->setId($properties['id']);
    if (isset($properties['owner_uid'])) {
      $user = User::load($properties['owner_uid']);
      if ($user !== NULL) {
        $org->setOwnerUrl($user->get('apic_url')->value);
      }
    }
    else {
      $org->setOwnerUrl($apimUtils->removeFullyQualifiedUrl($properties['owner']));
    }
    $org->setCreatedAt(strtotime("-1 day"));
    $org->setUpdatedAt(time());
    $org->setOrgUrl('/orgs/1234');
    $org->setCatalogUrl('/catalogs/1234/5678');
    $org->setUrl('/consumer-orgs/1234/5678/' . $properties['id']);

    // TODO: custom roles
    $ownerRole = ApicTestUtils::makeOwnerRole($org);
    $administratorRole = ApicTestUtils::makeAdministratorRole($org);
    $devRole = ApicTestUtils::makeDeveloperRole($org);
    $viewerRole = ApicTestUtils::makeViewerRole($org);
    $org->addRole($ownerRole);
    $org->addRole($administratorRole);
    $org->addRole($devRole);
    $org->addRole($viewerRole);

    $consumerOrgService = \Drupal::service('ibm_apim.consumerorg');
    $consumerOrgService->createOrUpdateNode($org, 'test');

    if (isset($properties['owner_uid'])) {
      $user = User::load($properties['owner_uid']);
      ApicTestUtils::linkUserAndOrg($org, $user, $ownerRole);
    }
    else {
      // Need to update the user record with a consumerorg_url as well
      $ids = \Drupal::entityQuery('user')->accessCheck()->execute();
      $users = User::loadMultiple($ids);

      foreach ($users as $drupalUser) {
        if ($drupalUser->getAccountName() === $properties['owner'] && !$consumerOrgService->isConsumerorgAssociatedWithAccount($org->getUrl(), $drupalUser)) {
          ApicTestUtils::linkUserAndOrg($org, $drupalUser, $ownerRole);
        }
      }
    }
    $consumerOrgService->createOrUpdateNode($org, 'test');
  }

  /**
   * Create a role with no permissions. Provide the corg so that the role's URL can be set.
   * You will need to set a name, title, summary and permissions for this role.
   *
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   *
   * @return \Drupal\consumerorg\ApicType\Role
   */
  public static function makeNoPermissionsRole(ConsumerOrg $org): Role {
    $blank_role = new Role();
    $blank_role->setId('generated-role-' . self::makeId());
    $blank_role->setUrl('/orgs/' . $org->getId() . '/roles/' . $blank_role->getId());
    $blank_role->setScope('org');
    $blank_role->setOrgUrl($org->getOrgUrl());

    $blank_role->setName('blank-test-role');
    $blank_role->setTitle('Blank Test Role');
    $blank_role->setSummary('This role was created during the behat test runs. The title and name should have been overridden by the test that created this role!!');

    return $blank_role;
  }

  /**
   * Create an org owner role with all relevant permissions.
   *
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   *
   * @return \Drupal\consumerorg\ApicType\Role
   */
  public static function makeOwnerRole(ConsumerOrg $org): Role {
    $owner = self::makeNoPermissionsRole($org);
    $owner->setName('owner');
    $owner->setTitle('Owner');
    $owner->setSummary('Owns and administers the app developer organization');

    // Owner gets every permission under the sun
    $perms = self::getAllPermissionsFromMock();

    $permURLs = [];
    if ($perms !== NULL && !empty($perms)) {
      foreach ($perms as $permission) {
        $permURLs[] = $permission['url'];
      }
    }
    $owner->setPermissions($permURLs);
    $org->addRole($owner);

    // Update the org in the database
    \Drupal::service('ibm_apim.consumerorg')->createOrUpdateNode($org, 'ApicTestUtils::makeOwnerRole');

    return $owner;
  }

  /**
   * Create an administrator role with all relevant permissions.
   *
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   *
   * @return \Drupal\consumerorg\ApicType\Role
   */
  public static function makeAdministratorRole(ConsumerOrg $org): Role {
    $owner = self::makeNoPermissionsRole($org);
    $owner->setName('administrator');
    $owner->setTitle('Administrator');
    $owner->setSummary('Administers the app developer organization');

    // Owner gets every permission under the sun
    $perms = self::getAllPermissionsFromMock();
    $permURLs = [];
    if ($perms !== NULL && !empty($perms)) {
      foreach ($perms as $permission) {
        $permURLs[] = $permission['url'];
      }
    }
    $owner->setPermissions($permURLs);
    $org->addRole($owner);

    // Update the org in the database
    \Drupal::service('ibm_apim.consumerorg')->createOrUpdateNode($org, 'ApicTestUtils::makeAdministratorRole');

    return $owner;
  }

  /**
   * Create a developer role for the given org.
   *
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   *
   * @return \Drupal\consumerorg\ApicType\Role
   */
  public static function makeDeveloperRole(ConsumerOrg $org): Role {
    $developer = self::makeNoPermissionsRole($org);
    $developer->setName('developer');
    $developer->setTitle('Developer');
    $developer->setSummary('A developer inside an org owned by another user');

    // Developers have a handful of view and manage permissions
    $developer->setPermissions([
      '/consumer-api/consumer/permissions/org/3e1652f1-e2f5-4d4f-9f58-2e11c312a148', //member:view
      '/consumer-api/consumer/permissions/org/e120f488-5921-45a8-a936-d34d4686ab8c', //view
      '/consumer-api/consumer/permissions/consumer/2c9d4ee3-9348-4159-972f-69ea1ffda5ab', //product:view
      '/consumer-api/consumer/permissions/consumer/e20ca0b3-a2f0-419f-9a99-177c6050c98d', //app:view
      '/consumer-api/consumer/permissions/consumer/e63e4ebc-8787-4901-8f42-16dba9aa8edd', //app-dev:manage
      '/consumer-api/consumer/permissions/consumer/47d90d81-5080-4882-b270-778334fdbf50', //app:manage
      '/consumer-api/consumer/permissions/consumer/81c86a24-c9e1-4fb5-a4e2-a137eef079c7', //app-analytics:view
    ]);
    $org->addRole($developer);

    // Update the org in the database
    \Drupal::service('ibm_apim.consumerorg')->createOrUpdateNode($org, 'ApicTestUtils::makeDeveloperRole');

    return $developer;
  }

  /**
   * Create a viewer role for the given org.
   *
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   *
   * @return \Drupal\consumerorg\ApicType\Role
   */
  public static function makeViewerRole(ConsumerOrg $org): Role {
    $viewer = self::makeNoPermissionsRole($org);
    $viewer->setName('viewer');
    $viewer->setTitle('Viewer');
    $viewer->setSummary('A viewer inside an org owned by another user');

    // Viewers only have a set of view permissions and can't manage / change stuff
    $viewer->setPermissions([
      '/consumer-api/consumer/permissions/org/3e1652f1-e2f5-4d4f-9f58-2e11c312a148', //member:view
      '/consumer-api/consumer/permissions/org/b0e7b96f-f49b-4c3b-a0c5-d72042eb4372', //settings:view
      '/consumer-api/consumer/permissions/org/e120f488-5921-45a8-a936-d34d4686ab8c', //view
      '/consumer-api/consumer/permissions/consumer/2c9d4ee3-9348-4159-972f-69ea1ffda5ab', //product:view
      '/consumer-api/consumer/permissions/consumer/e20ca0b3-a2f0-419f-9a99-177c6050c98d', //app:view
      '/consumer-api/consumer/permissions/consumer/04ee1b04-4f3a-4061-8b99-5fce9a4f346c', //subscription:view
      '/consumer-api/consumer/permissions/consumer/81c86a24-c9e1-4fb5-a4e2-a137eef079c7', //app-analytics:view
    ]);
    $org->addRole($viewer);

    // Update the org in the database
    \Drupal::service('ibm_apim.consumerorg')->createOrUpdateNode($org, 'ApicTestUtils::makeViewerRole');

    return $viewer;
  }

  /**
   * Adds the user specified to the given org with all of the roles that are passed in.
   *
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   * @param \Drupal\ibm_apim\ApicType\ApicUser $user
   * @param array $roles
   */
  public static function addMemberToOrg(ConsumerOrg $org, ApicUser $user, array $roles): void {
    $member = new Member();
    if ($user->getApicUserRegistryUrl() === NULL) {
      $user->setApicUserRegistryUrl('/registry/test');
    }
    $member->setUser($user);
    $member->setUserUrl($user->getUrl());
    $member->setUrl('/generated-member/' . self::makeId());
    $member->setState('active');

    $roleUrls = [];
    foreach ($roles as $role) {
      $roleUrls[] = $role->getUrl();
    }
    $member->setRoleUrls($roleUrls);

    $org->addMember($member);

    // Update the org in the database
    \Drupal::service('ibm_apim.consumerorg')->createOrUpdateNode($org, 'ApicTestUtils::addMember');
  }

  public static function addInvitationToOrg(ConsumerOrg $org, string $email, array $roles): void {


    $invitations = $org->getInvites();

    $id = self::makeId();
    $invitation = [
      'type' => 'member_invitation',
      'api_version' => '2.0.0',
      'email' => $email,
      'id' => $id,
      'url' => $org->getUrl() . '/member-invitations/' . $id,
    ];

    $roleUrls = [];
    foreach ($roles as $role) {
      $roleUrls[] = $role->getUrl();
    }
    //$member->setRoleUrls($roleUrls);
    $invitation['role_urls'] = $roleUrls;

    $invitations[] = $invitation;
    $org->setInvites($invitations);

    // Update the org in the database
    \Drupal::service('ibm_apim.consumerorg')->createOrUpdateNode($org, 'ApicTestUtils::setInvites');
  }

  /**
   * When running with mocks we read all of the data from disk. This is done in the mock permissions service so we need to do likewise.
   * TODO: check for mocks parameter - is this code even called if not using mocks?
   *
   * @return array
   * @throws \JsonException
   */
  private static function getAllPermissionsFromMock(): array {
    $all_perms = json_decode(file_get_contents(\Drupal::service('extension.list.module')->getPath('ibm_apim') . '/src/Service/Mocks/MockData/permissions.json'), TRUE, 512, JSON_THROW_ON_ERROR);
    $permissions = [];
    foreach ($all_perms as $perm) {
      $permissions[$perm['url']] = $perm;
    }

    //\Drupal::logger('PERMISSIONS DEBUG')->debug('getAllPermissionsFromMock returning: ' . \serialize($permissions));
    return $permissions;
  }

    /**
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   * @param $user
   * @param \Drupal\consumerorg\ApicType\Role $ownerRole
   *
   * @return void
   */
  private static function linkUserAndOrg(ConsumerOrg $org, $user, Role $ownerRole): void {

    $userService = \Drupal::service('ibm_apim.apicuser');

    $user->consumer_organization[] = $org->getUrl();
    $user->consumerorg_url[] = $org->getUrl();
    $user->save();

    $apic_user = $userService->parseDrupalAccount($user);
    ApicTestUtils::addMemberToOrg($org, $apic_user, [$ownerRole]);

    print('Saved user ' . $user->getAccountName() . '(uid=' . $user->id() . ') after adding consumerorg field ' . $org->getUrl() . "\n");
  }


}
