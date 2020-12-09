<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2020
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\apictest\Context;

use Behat\Gherkin\Node\TableNode;
use Drupal\apictest\ApicTestUtils;
use Drupal\Component\Utility\Html;
use Drupal\consumerorg\ApicType\ConsumerOrg;
use Drupal\consumerorg\ApicType\Role;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\ibm_apim\ApicUser;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

class ConsumerOrgContext extends RawDrupalContext {

  /**
   * @Given consumerorgs:
   */
  public function createConsumerorgs(TableNode $table): void {
    $apimUtils = \Drupal::service('ibm_apim.apim_utils');

    // If we are not using mocks, then we are testing with live data from a management appliance
    // Under those circumstances, we should absolutely not create any consumerorg in the database!
    if ($this->useMockServices === FALSE) {
      print "This test is running with a real management server backend. No consumerorgs will be created in the database.\n";
      return;
    }

    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = \Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(User::load(1));
    }

    foreach ($table as $row) {

      $org = new ConsumerOrg();
      $org->setTitle($row['title']);
      $org->setName($row['name']);
      $org->setId($row['id']);
      if (isset($row['owner_uid'])) {
        $user = User::load($row['owner_uid']);
        $org->setOwnerUrl($user->get('apic_url')->value);
      }
      else {
        $org->setOwnerUrl($apimUtils->removeFullyQualifiedUrl($row['owner']));
      }
      $org->setOrgUrl('/orgs/1234');
      $org->setCatalogUrl('/catalogs/1234/5678');
      $org->setUrl('/consumer-orgs/1234/5678/' . $row['id']);
      if (array_key_exists('tags', $row)) {
        $org->addTag($row['tags']);
      }

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

      if (isset($row['owner_uid'])) {
        $user = User::load($row['owner_uid']);
        $this->linkUserAndOrg($org, $user, $ownerRole);
      }
      else {
        // Need to update the user record with a consumerorg_url as well
        $ids = \Drupal::entityQuery('user')->execute();
        $users = User::loadMultiple($ids);

        foreach ($users as $drupalUser) {
          if ($drupalUser->getAccountName() === $row['owner'] && !$consumerOrgService->isConsumerorgAssociatedWithAccount($org->getUrl(), $drupalUser)) {
            $this->linkUserAndOrg($org, $drupalUser, $ownerRole);
          }
        }
      }
      $consumerOrgService->createOrUpdateNode($org, 'test');
    }
    if ($originalUser !== NULL && (int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }
  }


  /**
   * @Given members:
   */
  public function createMembers(TableNode $table): void {

    // If we are not using mocks, then we are testing with live data from a management appliance
    // Under those circumstances, we should absolutely not create any consumerorg in the database!
    if ($this->useMockServices === FALSE) {
      print "This test is running with a real management server backend. No consumerorg members will be created in the database.\n";
      return;
    }

    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = \Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(User::load(1));
    }

    $consumerOrgService = \Drupal::service('ibm_apim.consumerorg');
    $userService = \Drupal::service('ibm_apim.apicuser');

    foreach ($table as $row) {

      $account = user_load_by_name($row['username']);
      $consumerorgUrl = '/consumer-orgs/1234/5678/' . $row['consumerorgid'];

      if (!$consumerOrgService->isConsumerorgAssociatedWithAccount($consumerorgUrl, $account)) {
        $account->consumerorg_url[] = $consumerorgUrl;
        $account->save();
        print('(member create) Saved user ' . $account->getAccountName() . ' after adding consumerorg field ' . $consumerorgUrl . "\n");
      }

      // get the corg from the consumerorg service
      $corg = $consumerOrgService->get($consumerorgUrl);
      $orgRoles = $corg->getRoles();
      $requiredRoles = \explode(',', $row['roles']);
      $rolesToAdd = [];
      foreach ($requiredRoles as $requiredRole) {
        foreach ($orgRoles as $role) {
          if ($role->getName() === $requiredRole) {
            // this is the role we wanted to add to the user
            print('adding ' . $role->getName() . ' to ' . $account->getAccountName() . "\n");
            $rolesToAdd[] = $role;
            continue 2;
          }
        }
      }
      if (!empty($rolesToAdd)) {
        ApicTestUtils::addMemberToOrg($corg, $userService->parseDrupalAccount($account), $rolesToAdd);
      }
    }

    if ($originalUser !== NULL && (int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }
  }


  /**
   * @Given invitations:
   */
  public function createInvitation(TableNode $table): void {

    // If we are not using mocks, then we are testing with live data from a management appliance
    // Under those circumstances, we should absolutely not create any consumerorg in the database!
    if ($this->useMockServices === FALSE) {
      print "This test is running with a real management server backend. No consumerorg members will be created in the database.\n";
      return;
    }

    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = \Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(User::load(1));
    }

    $consumerOrgService = \Drupal::service('ibm_apim.consumerorg');

    foreach ($table as $row) {

      $consumerorgUrl = '/consumer-orgs/1234/5678/' . $row['consumerorgid'];


      // get the corg from the consumerorg service
      $corg = $consumerOrgService->get($consumerorgUrl);
      $orgRoles = $corg->getRoles();
      $requiredRoles = \explode(',', $row['roles']);
      $rolesToAdd = [];
      foreach ($requiredRoles as $requiredRole) {
        foreach ($orgRoles as $role) {
          if ($role->getName() === $requiredRole) {
            // this is the role we wanted to add to the user
            print('adding ' . $role->getName() . ' to invitation for ' . $row['mail'] . "\n");
            $rolesToAdd[] = $role;
            continue 2;
          }
        }
      }
      if (!empty($rolesToAdd)) {
        ApicTestUtils::addInvitationToOrg($corg, $row['mail'], $rolesToAdd);
      }
    }

    if ($originalUser !== NULL && (int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }
  }


  /**
   * @Then I should see that :arg1 is a(n) :arg2
   */
  public function iShouldSeeThatUserIsARole($username, $role) {

    $num = $this->getNumberOfUserRoleElements($username, $role);
    if ($num !== 1) {
      throw new \Exception("Unexpected response from finding " . $role . " role elements for " . $username . " user. Found " . $num . " elements, expected 1.");
    }

  }


  /**
   * @Then I should not see that :arg1 is a(n) :arg2
   */
  public function iShouldSeeThatUserIsNotARole($username, $role) {

    $num = $this->getNumberOfUserRoleElements($username, $role);
    if ($num !== 0) {
      throw new \Exception("Unexpected response from finding " . $role . " role elements for " . $username . " user. Found " . $num . " elements, expected 0.");
    }

  }

  /**
   * @param $username
   * @param $role
   *
   * @return int
   */
  private function getNumberOfUserRoleElements($username, $role): int {
    $page = $this->getSession()->getPage();
    $css_selector = "." . Html::getClass('apicmyorgmemberrole-' . $username . '-' . $role);
    $enabled = $page->findAll('css', $css_selector);

    print 'searched for ' . $css_selector . "\n";
    print 'enabled ' . \sizeof($enabled);

    $num = \sizeof($enabled);
    return $num;
  }

  /**
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   * @param $user
   * @param $userService
   * @param \Drupal\consumerorg\ApicType\Role $ownerRole
   *
   * @return mixed
   */
  private function linkUserAndOrg(ConsumerOrg $org, $user, Role $ownerRole) {

    $userService = \Drupal::service('ibm_apim.apicuser');

    $user->consumer_organization[] = $org->getUrl();
    $user->consumerorg_url[] = $org->getUrl();
    $user->save();

    $apic_user = $userService->parseDrupalAccount($user);
    ApicTestUtils::addMemberToOrg($org, $apic_user, [$ownerRole]);

    print('Saved user ' . $user->getAccountName() . '(uid=' . $user->id() .') after adding consumerorg field ' . $org->getUrl() . "\n");
  }


}
