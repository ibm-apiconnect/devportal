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

namespace Drupal\apictest\Context;

use Drupal\user\Entity\User;

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Behat\Gherkin\Node\TableNode;

use Drupal\apictest\ApicTestUtils;
use Drupal\consumerorg\ApicType\ConsumerOrg;
use Drupal\ibm_apim\ApicUser;

class ConsumerOrgContext extends RawDrupalContext {

  /**
   * @Given consumerorgs:
   */
  public function createConsumerorgs(TableNode $table) {

    // If we are not using mocks, then we are testing with live data from a management appliance
    // Under those circumstances, we should absolutely not create any consumerorg in the database!
    if ($this->useMockServices === FALSE) {
      print "This test is running with a real management server backend. No consumerorgs will be created in the database.\n";
      return;
    }

    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = \Drupal::service('account_switcher');
    $original_user = \Drupal::currentUser();
    if ($original_user->id() != 1) {
      $accountSwitcher->switchTo(\Drupal\user\Entity\User::load(1));
    }

    foreach ($table as $row) {

      $org = new ConsumerOrg();
      $org->setTitle($row['title']);
      $org->setName($row['name']);
      $org->setId($row['id']);
      $org->setOwnerUrl($row['owner']);
      $org->setUrl('/consumer-orgs/1234/5678/' . $row['id']);

      $owner_role = ApicTestUtils::makeOwnerRole($org);
      $dev_role = ApicTestUtils::makeDeveloperRole($org);
      $viewer_role = ApicTestUtils::makeViewerRole($org);
      $org->addRole($owner_role);
      $org->addRole($dev_role);
      $org->addRole($viewer_role);

      $consumerOrgService = \Drupal::service('ibm_apim.consumerorg');
      $consumerOrgService->createOrUpdateNode($org, 'test');
      $user_service = \Drupal::service('ibm_apim.apicuser');

      // Need to update the user record with a consumerorg_url as well
      $ids = \Drupal::entityQuery('user')->execute();
      $users = User::loadMultiple($ids);

      foreach ($users as $drupal_user) {
        if ($drupal_user->getUsername() == $row['owner']) {
          if (!$consumerOrgService->isConsumerorgAssociatedWithAccount($org->getUrl(), $drupal_user)) {
            $drupal_user->consumer_organization[] = $org->getUrl();
            $drupal_user->consumerorg_url[] = $org->getUrl();
            $drupal_user->save();

            $user = $user_service->parseDrupalAccount($drupal_user);
            ApicTestUtils::addMemberToOrg($org, $user, array($owner_role));

            print("Saved user " . $drupal_user->getUsername() . " after adding consumerorg field " . $org->getUrl() . "\n");
          }
        }
      }
      $consumerOrgService->createOrUpdateNode($org, 'test');
    }
    if (isset($original_user) && $original_user->id() != 1) {
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
  public function assignConsumerorgRoles(TableNode $table) {

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
    $original_user = \Drupal::currentUser();
    if ($original_user->id() != 1) {
      $accountSwitcher->switchTo(\Drupal\user\Entity\User::load(1));
    }

    foreach ($table as $row) {
      $account = user_load_by_name($row['name']);
      $consumerorg_url = '/consumer-orgs/1234/5678/' . $row['consumerorgid'];

      if (!$consumerOrgService->isConsumerorgAssociatedWithAccount($consumerorg_url, $account)) {
        $account->consumerorg_url[] = $consumerorg_url;
        $account->save();
        print("Saved user " . $account->getUsername() . " after adding consumerorg field " . $consumerorg_url . "\n");
      }
      $email = '';
      if (isset($account)) {
        $email = $account->get('mail')->value;
      }

      // get the corg from the consumerorg service
      $corg = \Drupal::service('ibm_apim.consumerorg')->get($consumerorg_url);
      $org_roles = $corg->getRoles();
      foreach($org_roles as $role) {
        print($row['role']);
        print($role->getName());
        if($role->getName() == $row['role']){
          // this is the role we wanted to add to the user
          ApicTestUtils::addMemberToOrg($corg, $userService->parseDrupalAccount($account), array($role));
        }
      }
    }

    if (isset($original_user) && $original_user->id() != 1) {
      $accountSwitcher->switchBack();
    }
  }

}