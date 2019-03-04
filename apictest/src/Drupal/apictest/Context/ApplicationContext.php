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

use Drupal\apic_app\Application;
use Drupal\Component\Utility\Random;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\node\Entity\Node;

/**
 * Defines application features from the specific context.
 */
class ApplicationContext extends RawDrupalContext {

  private $createOrUpdateResult;

  /**
   * @Given I create an application named :name id :id consumerorgurl :consumerorgurl
   */
  public function createApplication($name, $id, $consumerorgurl): void {
    $random = new Random();
    if ($name === NULL || empty($name)) {
      $name = $random->name(8);
    }
    $object = [];
    $object['title'] = $name;
    $object['name'] = $name;
    $object['consumer_org_url'] = $consumerorgurl;
    $object['redirect_urls'] = [$name];
    $object['enabled'] = TRUE;
    $object['id'] = $id;
    $object['url'] = 'https://localhost.com';
    $object['state'] = 'published';
    $object['app_credentials'] = [
      [
        'client_id' => '11111111-78f0-48d1-a015-6a803fd64e8f',
        'client_secret' => 'fkvO2qWJQbtNB8zqcOMs2p1DPqhI0EuRB7Gfi1/tMrQ=',
        'id' => 'cred-1234567',
      ],
      [
        'client_id' => '22222222-78f0-48d1-a015-6a803fd64e8f',
        'client_secret' => 'fkvO2qWJQbtNB8zqcOMs2p1DPqhI0EuRB7Gfi1/tMrQ=',
        'id' => 'cred-2345678',
      ],
    ];

    $nid = Application::create($object);

    print('Saved application ' . $name . ' as nid ' . $nid);

  }

  /**
   * @Then I should have an application named :name id :id
   */
  public function iHaveApplication($name, $id): void {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'application');
    //$query->condition('title', $name);
    $results = $query->execute();
    $querynid = NULL;
    $query2nid = NULL;
    print('Query results: ' . serialize($results));
    if ($results !== NULL && !empty($results)) {
      $querynid = array_shift($results);
    }

    $userUtils = \Drupal::service('ibm_apim.user_utils');
    $org = $userUtils->getCurrentConsumerOrg();
    print('Current org: ' . serialize($org));

    if ($querynid === NULL || empty($querynid)) {
      throw new \Exception("An application with name $name was not found!");
    }

    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'application');
    $query->condition('application_id.value', $id);
    $results = $query->execute();
    if ($results !== NULL && !empty($results)) {
      $query2nid = array_shift($results);
    }

    if ($query2nid === NULL || empty($query2nid)) {
      throw new \Exception("An application with id $id was not found!");
    }
  }

  /**
   * @Then The application with the name :name and id :id should not be visible to :switchto
   */
  public function ApplicationShouldNotBeVisisbleTo($name, $id, $switchto): void {

    // Switch to the userid provided
    $accountSwitcher = \Drupal::service('account_switcher');
    $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => $switchto]);
    $user = reset($users);
    if ($user) {
      $accountSwitcher->switchTo(\Drupal\user\Entity\User::load($user->id()));
    }
    else {
      throw new \Exception("Unable to switch to user $switchto");
    }
    $query2nid = NULL;
    $userUtils = \Drupal::service('ibm_apim.user_utils');
    $org = $userUtils->getCurrentConsumerOrg();
    print('Current org: ' . serialize($org) . PHP_EOL);

    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'application');
    $query->condition('application_id.value', $id);
    $results = $query->execute();
    print('Search for ' . $id . ': ' . serialize($results) . PHP_EOL);
    if ($results !== NULL && !empty($results)) {
      $query2nid = array_shift($results);
    }
    if ($query2nid !== NULL && !empty($query2nid) && $query2nid === $id) {
      // User could see the application; fail
      throw new \Exception("User $switchto was able to view application with id $id!");
    }
    else {
      // User has some apps visible but could not see the one specified
      print('User ' . $switchto . ' was unable to see application ' . $name);
    }
  }

  /**
   * @When I update the application named :oldname to be called :name
   */
  public function iUpdateApplication($oldname, $name) {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'application');
    $query->condition('title', $oldname);
    $results = $query->execute();
    $querynid = NULL;
    if ($results !== NULL && !empty($results)) {
      $querynid = array_shift($results);
    }

    if ($querynid === NULL || empty($querynid)) {
      throw new \Exception("An application with name $oldname was not found!");
    }
    $node = Node::load($querynid);
    $random = new Random();
    if ($name === NULL || empty($name)) {
      $name = $random->name(8);
    }
    $object['name'] = $name;
    $object['title'] = $name;
    $object['id'] = $node->application_id->value;
    $object['consumer_org_url'] = $node->application_consumer_org_url->value;
    $object['redirect_urls'] = [$name];
    $object['enabled'] = TRUE;
    $object['url'] = 'https://localhost.com';
    $object['state'] = 'published';

    $returned_node = Application::update($node, $object);
    if ($returned_node === NULL || empty($returned_node)) {
      throw new \Exception("Application update for name $name did not return a node!");
    }
  }

  /**
   * @Given I do not have an application named :name
   */
  public function iDoNotHaveApplication($name) {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'application');
    $query->condition('title', $name);
    $results = $query->execute();
    if ($results !== NULL && !empty($results)) {
      $nid = array_shift($results);
      $node = Node::load($nid);
      $node->delete();
      unset($node);
    }
  }

  /**
   * @Then I should not have an application named :name
   */
  public function iShouldNotHaveApplication($name) {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'application');
    $query->condition('title', $name);
    $results = $query->execute();
    if ($results !== NULL && !empty($results)) {
      $nid = array_shift($results);
      if ($nid === NULL || empty($nid)) {
        throw new \Exception("Application named $name still present!");
      }
    }
  }

  /**
   * @When I delete the application named :name
   */
  public function iDeleteApplication($name) {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'application');
    $query->condition('title', $name);
    $results = $query->execute();
    if ($results !== NULL && !empty($results)) {
      $nid = array_shift($results);
      if ($nid === NULL || empty($nid)) {
        throw new \Exception("Application named $name not found!");
      }
      $node = Node::load($nid);
      $node->delete();
      unset($node);
    }
    else {
      throw new \Exception("Application named $name not found!");
    }
  }

  /**
   * @Given I do not have any applications
   */
  public function iDoNotHaveAnyApplications() {
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'application']);

    foreach ($nodes as $node) {
      $node->delete();
    }
  }

  /**
   * @Given I createOrUpdate an application named :name id :id consumerorgurl :consumerorgurl
   */
  public function iCreateOrUpdateApplication($name, $id, $consumerorg) {
    $random = new Random();
    if ($name === NULL || empty($name)) {
      $name = $random->name(8);
    }
    $object = [];
    $object['title'] = $name;
    $object['name'] = $name;
    $object['consumer_org_url'] = $consumerorg;
    $object['redirect_urls'] = [$name];
    $object['enabled'] = TRUE;
    $object['id'] = $id;
    $object['url'] = 'https://localhost.com';
    $object['state'] = 'published';

    $this->createOrUpdateResult = Application::createOrUpdate($object, 'internal');
    print("createOrUpdateResult: $this->createOrUpdateResult");
  }

  /**
   * @Then The createOrUpdate output should be :value
   */
  public function theCreateOrUpdateValueShouldBe($value): void {
    if (!property_exists($this, 'createOrUpdateResult')) {
      throw new \Exception('createOrUpdateResult is not set!');
    }

    if ((bool) $this->createOrUpdateResult !== (bool) $value) {
      throw new \Exception("createOrUpdateResult is not set to $value! Currently set to: $this->createOrUpdateResult");
    }
  }
}
