<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2019
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\apictest\Context;

use Behat\Gherkin\Node\TableNode;
use Drupal\apictest\ApicTestUtils;
use Drupal\consumerorg\ApicType\ConsumerOrg;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\ibm_apim\ApicUser;
use Drupal\user\Entity\User;

class ConsumerOrgContext extends RawDrupalContext {

  /**
   * @Given consumerorgs:
   */
  public function createConsumerorgs(TableNode $table): void {

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
      $org->setOwnerUrl($row['owner']);
      $org->setOrgUrl('/orgs/1234');
      $org->setCatalogUrl('/catalogs/1234/5678');
      $org->setUrl('/consumer-orgs/1234/5678/' . $row['id']);
      if (array_key_exists('tags', $row)) {
        $org->addTag($row['tags']);
      }

      $ownerRole = ApicTestUtils::makeOwnerRole($org);
      $devRole = ApicTestUtils::makeDeveloperRole($org);
      $viewerRole = ApicTestUtils::makeViewerRole($org);
      $org->addRole($ownerRole);
      $org->addRole($devRole);
      $org->addRole($viewerRole);

      $consumerOrgService = \Drupal::service('ibm_apim.consumerorg');
      $consumerOrgService->createOrUpdateNode($org, 'test');
      $userService = \Drupal::service('ibm_apim.apicuser');

      // Need to update the user record with a consumerorg_url as well
      $ids = \Drupal::entityQuery('user')->execute();
      $users = User::loadMultiple($ids);

      foreach ($users as $drupalUser) {
        if ($drupalUser->getUsername() === $row['owner'] && !$consumerOrgService->isConsumerorgAssociatedWithAccount($org->getUrl(), $drupalUser)) {
          $drupalUser->consumer_organization[] = $org->getUrl();
          $drupalUser->consumerorg_url[] = $org->getUrl();
          $drupalUser->save();

          $user = $userService->parseDrupalAccount($drupalUser);
          ApicTestUtils::addMemberToOrg($org, $user, [$ownerRole]);

          print('Saved user ' . $drupalUser->getUsername() . ' after adding consumerorg field ' . $org->getUrl() . "\n");
        }
      }
      $consumerOrgService->createOrUpdateNode($org, 'test');
    }
    if ($originalUser !== NULL && (int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }
  }

  /**
   * @Given consumerorgroles:
   *
   * Set up consumer organization role membership
   *
   * @param \Behat\Gherkin\Node\TableNode $table
   */
  public function assignConsumerorgRoles(TableNode $table): void {

    // If we are not using mocks, then we are testing with live data from a management appliance
    // Under those circumstances, we should absolutely not create any consumerorg in the database!
    if ($this->useMockServices === FALSE) {
      print "This test is running with a real management server backend. No consumerorgs will be modified in the database.\n";
      return;
    }

    $consumerOrgService = \Drupal::service('ibm_apim.consumerorg');
    $userService = \Drupal::service('ibm_apim.apicuser');

    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = \Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(User::load(1));
    }

    foreach ($table as $row) {
      $account = user_load_by_name($row['name']);
      $consumerorgUrl = '/consumer-orgs/1234/5678/' . $row['consumerorgid'];

      if (!$consumerOrgService->isConsumerorgAssociatedWithAccount($consumerorgUrl, $account)) {
        $account->consumerorg_url[] = $consumerorgUrl;
        $account->save();
        print('Saved user ' . $account->getUsername() . ' after adding consumerorg field ' . $consumerorgUrl . "\n");
      }
      $email = '';
      if ($account !== NULL) {
        $email = $account->get('mail')->value;
      }

      // get the corg from the consumerorg service
      $corg = \Drupal::service('ibm_apim.consumerorg')->get($consumerorgUrl);
      $orgRoles = $corg->getRoles();
      foreach ($orgRoles as $role) {
        print($row['role']);
        print($role->getName());
        if ($role->getName() === $row['role']) {
          // this is the role we wanted to add to the user
          ApicTestUtils::addMemberToOrg($corg, $userService->parseDrupalAccount($account), [$role]);
        }
      }
    }

    if ($originalUser !== NULL && (int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }
  }

}