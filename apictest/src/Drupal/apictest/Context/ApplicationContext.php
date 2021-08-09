<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2021
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\apictest\Context;

use Behat\Gherkin\Node\TableNode;
use Drupal\apic_app\Application;
use Drupal\apic_app\SubscriptionService;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\Random;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Defines application features from the specific context.
 */
class ApplicationContext extends RawDrupalContext {

  private $createOrUpdateResult;

  /**
   * @var bool|\stdClass|\stdClass[]
   */
  private $useMockServices = TRUE;

  /**
   * @Given applications:
   */
  public function createApps(TableNode $table): void {

    // If we are not using mocks, then we are testing with live data from a management appliance
    // Under those circumstances, we should absolutely not create any application in the database!
    if ($this->useMockServices === FALSE) {
      print "This test is running with a real management server backend. No applications will be created in the database.\n";
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
      $this->createApplication($row['title'], $row['id'], '/consumer-orgs/1234/5678/' . $row['org_id']);
    }
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }
  }

  /**
   * @Given subscriptions:
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createSubs(TableNode $table): void {

    // If we are not using mocks, then we are testing with live data from a management appliance
    // Under those circumstances, we should absolutely not create any subscription in the database!
    if ($this->useMockServices === FALSE) {
      print "This test is running with a real management server backend. No subscriptions will be created in the database.\n";
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
      $this->createSubscription($row['org_id'], $row['app_id'], $row['sub_id'], $row['product'], $row['plan']);
    }
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }
  }

  /**
   * @param $org_id
   * @param $app_id
   * @param $sub_id
   * @param $product
   * @param $plan
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createSubscription($org_id, $app_id, $sub_id, $product, $plan): void {
    SubscriptionService::create('/apps/1234/5678/' . $org_id . '/apps/' . $app_id, $sub_id, '/catalogs/1234/5678/products/' . $product, $plan, '/apps/1234/5678/' . $org_id, 'enabled', NULL, '2021-02-26T12:18:58.995Z', '2021-02-26T12:18:58.995Z');
  }

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
    $object['url'] = str_replace('consumer-orgs', 'apps', $consumerorgurl) . '/apps/' . $id;
    $object['state'] = 'published';
    $object['created_at'] = '2021-02-26T12:18:58.995Z';
    $object['updated_at'] = '2021-02-26T12:18:58.995Z';
    $object['app_credentials'] = [
      [
        'client_id' => '11111111-78f0-48d1-a015-6a803fd64e8f',
        'client_secret' => 'fkvO2qWJQbtNB8zqcOMs2p1DPqhI0EuRB7Gfi1/tMrQ=',
        'id' => $id . 'cred-1234567',
        'url' => $object['url'] . '/credentials/' . $id . 'cred-1234567',
        'title' => 'cred-1234567',
        'summary' => 'cred-1234567',
        'name' => 'cred-1234567',
        'app_url' => $object['url'],
        'created_at' => '2021-02-26T12:18:58.995Z',
        'updated_at' => '2021-02-26T12:18:58.995Z',
      ],
      [
        'client_id' => '22222222-78f0-48d1-a015-6a803fd64e8f',
        'client_secret' => 'fkvO2qWJQbtNB8zqcOMs2p1DPqhI0EuRB7Gfi1/tMrQ=',
        'id' => $id . 'cred-2345678',
        'url' => $object['url'] . '/credentials/' . $id . 'cred-2345678',
        'title' => 'cred-2345678',
        'summary' => 'cred-2345678',
        'name' => 'cred-2345678',
        'app_url' => $object['url'],
        'created_at' => '2021-02-26T12:18:58.995Z',
        'updated_at' => '2021-02-26T12:18:58.995Z',
      ],
    ];

    try {
      $nid = Application::create($object);
    } catch (InvalidPluginDefinitionException | PluginNotFoundException | EntityStorageException $e) {
    }

    print('Saved application ' . $name . ' (url=' . $object['url'] . ') as nid ' . $nid);

  }

  /**
   * @Then I should have an application named :name id :id
   * @throws \Exception
   */
  public function iHaveApplication($name, $id): void {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'application');
    //$query->condition('title', $name);
    $results = $query->execute();
    $queryNid = NULL;
    $query2Nid = NULL;
    print('Query results: ' . serialize($results));
    if ($results !== NULL && !empty($results)) {
      $queryNid = array_shift($results);
    }

    $userUtils = \Drupal::service('ibm_apim.user_utils');
    $org = $userUtils->getCurrentConsumerOrg();
    print('Current org: ' . serialize($org));

    if ($queryNid === NULL || empty($queryNid)) {
      throw new \Exception("An application with name $name was not found!");
    }

    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'application');
    $query->condition('application_id.value', $id);
    $results = $query->execute();
    if ($results !== NULL && !empty($results)) {
      $query2Nid = array_shift($results);
    }

    if ($query2Nid === NULL || empty($query2Nid)) {
      throw new \Exception("An application with id $id was not found!");
    }
  }

  /**
   * @Then The application with the name :name and id :id should not be visible to :switchTo
   * @throws \Exception
   */
  public function ApplicationShouldNotBeVisibleTo($name, $id, $switchTo): void {

    // Switch to the userid provided
    $accountSwitcher = \Drupal::service('account_switcher');
    $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => $switchTo]);
    $user = reset($users);
    if ($user) {
      $accountSwitcher->switchTo(\Drupal\user\Entity\User::load($user->id()));
    }
    else {
      throw new \Exception("Unable to switch to user $switchTo");
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
      throw new \Exception("User $switchTo was able to view application with id $id!");
    }
    else {
      // User has some apps visible but could not see the one specified
      print('User ' . $switchTo . ' was unable to see application ' . $name);
    }
  }

  /**
   * @When I update the application named :oldname to be called :name
   * @throws \Exception
   */
  public function iUpdateApplication($oldName, $name): void {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'application');
    $query->condition('title', $oldName);
    $results = $query->execute();
    $queryNid = NULL;
    if ($results !== NULL && !empty($results)) {
      $queryNid = array_shift($results);
    }

    if ($queryNid === NULL || empty($queryNid)) {
      throw new \Exception("An application with name $oldName was not found!");
    }
    $node = Node::load($queryNid);
    if ($node !== NULL) {
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
      $object['created_at'] = '2021-02-26T12:18:58.995Z';
      $object['updated_at'] = '2021-03-01T12:18:58.995Z';

      $returned_node = Application::update($node, $object);
    }
    if ($returned_node === NULL || empty($returned_node)) {
      throw new \Exception("Application update for name $name did not return a node!");
    }
  }

  /**
   * @Given I do not have an application named :name
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function iDoNotHaveApplication($name): void {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'application');
    $query->condition('title', $name);
    $results = $query->execute();
    if ($results !== NULL && !empty($results)) {
      $nid = array_shift($results);
      $node = Node::load($nid);
      if ($node !== NULL) {
        $node->delete();
        unset($node);
      }
    }
  }

  /**
   * @Then I should not have an application named :name
   * @throws \Exception
   */
  public function iShouldNotHaveApplication($name): void {
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
   * @throws \Exception
   */
  public function iDeleteApplication($name): void {
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
      if ($node !== NULL) {
        $node->delete();
        unset($node);
      }
    }
    else {
      throw new \Exception("Application named $name not found!");
    }
  }

  /**
   * @Then I delete all applications from the site
   * @Given I do not have any applications
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function iDoNotHaveAnyApplications(): void {
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = \Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(User::load(1));
    }

    try {
      $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'application']);
      foreach ($nodes as $node) {
        $node->delete();
      }
    } catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
    }

    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }
  }


  /**
   * @Given I createOrUpdate an application named :name id :id consumerorgurl :consumerorgurl
   */
  public function iCreateOrUpdateApplication($name, $id, $consumerorg): void {
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
    $object['created_at'] = '2021-02-26T12:18:58.995Z';
    $object['updated_at'] = '2021-03-01T12:18:58.995Z';

    try {
      $this->createOrUpdateResult = Application::createOrUpdate($object, 'internal');
    } catch (InvalidPluginDefinitionException | PluginNotFoundException | EntityStorageException $e) {
    }
    print("createOrUpdateResult: $this->createOrUpdateResult");
  }

  /**
   * @Then The createOrUpdate output should be :value
   * @throws \Exception
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
