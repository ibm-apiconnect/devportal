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

namespace Drupal\apictest\Context;

use Behat\Gherkin\Node\TableNode;
use Drupal\apic_api\Api;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

class ApiContext extends RawDrupalContext {

  private $tempObject = NULL;

  private string $testDataDirectory = __DIR__ . '/../../../../testdata';

  /**
   * @var bool|\stdClass|\stdClass[]
   */
  private bool $useMockServices = TRUE;

  /**
   * @Given apis:
   * @throws \Drupal\Core\Entity\EntityStorageException|\JsonException
   */
  public function createApis(TableNode $table): void {

    // If we are not using mocks, then we are testing with live data from a management appliance
    // Under those circumstances, we should absolutely not create any api in the database!
    if ($this->useMockServices === FALSE) {
      print "This test is running with a real management server backend. No apis will be created in the database.\n";
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
      $object = $this->createApiWithDocument($row['title'], $row['id'], $row['document']);

      $api = new Api();
      $api->create($object);

    }
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }
  }

  /**
   * @Given I publish an api with the name :name, id :id and categories :categories
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   */
  public function iPublishAnApiWithTheNameIdAndCategories($name, $id, $categories): void {
    $object = $this->createApiObject($name, $id, $categories);

    if (!(boolean) \Drupal::config('ibm_apim.settings')->get('categories')['create_taxonomies_from_categories']) {
      print("Setting 'create_taxonomies_from_categories' config to true. ");
      \Drupal::service('config.factory')
        ->getEditable('ibm_apim.settings')
        ->set('categories.create_taxonomies_from_categories', TRUE)
        ->save();
    }

    $api = new Api();
    $nid = $api->create($object);

    print('Saved spi ' . $name . ' as nid ' . $nid);
  }

  /**
   * @Then I should have an api with the name :name, id :id and categories :categories
   * @throws \Exception
   */
  public function iShouldHaveAnApiWithTheNameIdAndCategories($name, $id, $categories): void {
    $results = $this->searchForApiByTitle($name);

    if ($results !== NULL && !empty($results)) {
      $queryNid = array_shift($results);
      $api = Node::load($queryNid);

      if ($api !== NULL && $api->get('api_id')->value === $id && $api->get('api_xibmname')->value === $name) {
        print("The api with the name $name and id $id was created successfully. ");
      }
      else {
        throw new \Exception("The returned api did not have a name of $name or an id of $id");
      }

      // Make sure the parent term was created from the categories
      if ($terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => 'Sport', 'vid' => 'tags'])) {

        $terms = \Drupal::entityTypeManager()
          ->getStorage('taxonomy_term')
          ->loadTree('tags', reset($terms)->get('tid')->value);

        // Make sure the first child in the tree is Ball
        if ($terms[0]->name === 'Ball') {
          //print('Child term Ball was created');
        }
        else {
          throw new \Exception('Failed to find a term in the tree with the name Ball');
        }

        // Make sure the final child in the tree is Rugby
        if ($terms[1]->name === 'Rugby') {

          // Make sure the id of the term was added to the product
          if ($api->get('apic_tags')->getValue()[0]['target_id'] === $terms[1]->tid) {
            print('Categories where successfully created and linked to the product. ');
          }
          else {
            throw new \Exception("Categories where not added to the product with name $name");
          }

        }
        else {
          throw new \Exception('Failed to find a term in the tree with the name Rugby');
        }
      }

    }
    else {
      throw new \Exception("Failed to find a product with the name $name");
    }
  }

  /**
   * @Given I publish an api with the name :name and categories :categories and create_taxonomies_from_categories is false
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   */
  public function iPublishAnApiWithTheNameAndCategoriesAndCreateTaxonomiesFromCategoriesIsFalse($name, $categories): void {

    $object = $this->createApiObject($name, '12345', $categories);

    if ((boolean) \Drupal::config('ibm_apim.settings')->get('categories')['create_taxonomies_from_categories']) {
      print('Setting \'create_taxonomies_from_categories\' config to false. ');
      \Drupal::service('config.factory')
        ->getEditable('ibm_apim.settings')
        ->set('categories.create_taxonomies_from_categories', FALSE)
        ->save();
    }

    // Remove any existing terms within the tags vocabulary
    $tids = \Drupal::entityQuery('taxonomy_term')->condition('vid', 'tags')->execute();
    try {
      $controller = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
      $entities = $controller->loadMultiple($tids);
      $controller->delete($entities);
    } catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
    }

    $api = new Api();
    $nid = $api->create($object);

    print('Saved spi ' . $name . ' as nid ' . $nid);
  }

  /**
   * @Then I should have an api with name :name and no taxonomies for the categories :categories
   * @throws \Exception
   */
  public function iShouldHaveAnApiWithNameAndNoTaxonomiesForTheCategories($name, $categories): void {
    $results = $this->searchForApiByTitle($name);

    if ($results !== NULL && !empty($results)) {
      $queryNid = array_shift($results);
      $api = Node::load($queryNid);

      if ($api !== NULL && $api->get('api_xibmname')->value === $name) {
        print("The api with the name $name was created successfully. ");
      }
      else {
        throw new \Exception("The returned api did not have a name of $name");
      }

      $apicTags = $api->get('apic_tags')->getValue();

      // Make sure the categories where not associated with the product
      if ($apicTags !== NULL && !empty($apicTags)) {
        throw new \Exception('Categories where added to api entity when they should not have been');
      }

    }
    else {
      throw new \Exception("Failed to find an api with the name $name");
    }
  }

  /**
   * @Given I publish an api with the name :name and the phase :phase and autotag_with_phase is true
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   */
  public function iPublishAnApiWithTheNameAndAutotagWithPhaseIsTrue($name, $phase): void {
    $object = $this->createApiObject($name, '12345', NULL, $phase);

    if (!(boolean) \Drupal::config('ibm_apim.settings')->get('autotag_with_phase')) {
      print('Setting \'autotag_with_phase\' config to true. ');
      \Drupal::service('config.factory')->getEditable('ibm_apim.settings')->set('autotag_with_phase', TRUE)->save();
    }

    $api = new Api();
    $nid = $api->create($object);

    print('Saved spi ' . $name . ' as nid ' . $nid);
  }

  /**
   * @Then I should have an api with the name :name tagged with the phase :phase
   * @throws \Exception
   */
  public function iShouldHaveAnApiWithTheNameTaggedWithThePhase($name, $phase): void {
    $results = $this->searchForApiByTitle($name);

    if ($results !== NULL && !empty($results)) {
      $queryNid = array_shift($results);
      $api = Node::load($queryNid);

      if ($api !== NULL && $api->get('api_xibmname')->value === $name) {
        print("The api with the name $name was created successfully. ");
      }
      else {
        throw new \Exception("The returned api did not have a name of $name");
      }

      // Make sure the term 'Realized' was created
      if ($realized = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => $phase, 'vid' => 'tags'])) {
        $realizedId = reset($realized)->id();

        // Make sure it's parent is the term 'Phase'
        $parent = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadParents($realizedId);
        $parent = reset($parent);

        if ($parent === NULL || empty($parent)) {
          throw new \Exception("No parent term was found for the term $phase. ");
        }

        if ($parent->name->value !== 'Phase') {
          throw new \Exception("The parent of the term $phase was not 'Phase', found $parent->name->value instead. ");
        }

        $apicTags = $api->apic_tags->getValue();
        $matchingPhaseTag = FALSE;

        foreach ($apicTags as $tag) {
          if ($tag['target_id'] === $realizedId) {
            $matchingPhaseTag = TRUE;
          }
        }

        if ($matchingPhaseTag) {
          print("The api was successfully tagged with the phase term $phase");
        }
        else {
          throw new \Exception("The api was not tagged with the phase $phase. ");
        }
      }
      else {
        throw new \Exception("The term $phase does not exist");
      }
    }
  }

  /**
   * @Given I publish an api with the name :name and autocreate_apiforum is true
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function iPublishAnApiWithTheNameAndAutocreateApiforumIsTrue($name): void {
    $object = $this->createApiObject($name);

    if (!(boolean) \Drupal::config('ibm_apim.settings')->get('autocreate_apiforum')) {
      print('Setting \'autocreate_apiforum\' config to true. ');
      \Drupal::service('config.factory')->getEditable('ibm_apim.settings')->set('autocreate_apiforum', TRUE)->save();
    }

    $api = new Api();
    $nid = $api->create($object);

    print('Saved spi ' . $name . ' as nid ' . $nid);
  }

  /**
   * @Then I should have an api and a forum both with the name :name
   * @throws \Exception
   */
  public function iShouldHaveAnApiWithTheNameAndAForumWithTheName($name): void {
    $results = $this->searchForApiByTitle($name);

    if ($results !== NULL && !empty($results)) {
      $queryNid = array_shift($results);
      $api = Node::load($queryNid);

      if ($api !== NULL && $api->get('api_xibmname')->value === $name) {
        print("The api with the name $name was created successfully. ");
      }
      else {
        throw new \Exception("The returned api did not have a name of $name");
      }

      // Make sure the forum  was created
      if ($forum = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => $name, 'vid' => 'forums'])) {
        $forumId = reset($forum)->id();

        // Make sure it's parent is the forum container 'APIs'
        $parent = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadParents($forumId);
        $parent = reset($parent);

        if ($parent === NULL || empty($parent)) {
          throw new \Exception("No parent term was found for the term $name. ");
        }

        $parentName = $parent->name->value;

        if ($parentName !== 'APIs') {
          throw new \Exception("The parent of the term $name was not 'APIs', found $parentName instead. ");
        }

        print("A forum with the name $name was successfully created");

      }
      else {
        throw new \Exception("The term $name does not exist");
      }
    }
  }

  /**
   * @Given I publish an api with the name :name and autocreate_apiforum is false
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   */
  public function iPublishAnApiWithTheNameAndAutocreateApiforumIsFalse($name): void {
    $object = $this->createApiObject($name);

    if ((boolean) \Drupal::config('ibm_apim.settings')->get('autocreate_apiforum')) {
      print('Setting \'autocreate_apiforum\' config to false. ');
      \Drupal::service('config.factory')->getEditable('ibm_apim.settings')->set('autocreate_apiforum', FALSE)->save();
    }

    $api = new Api();
    $nid = $api->create($object);

    print('Saved spi ' . $name . ' as nid ' . $nid);
  }

  /**
   * @Then I should have an api name :name and no forum
   * @throws \Exception
   */
  public function iShouldHaveAnApiNameAndNoForum($name): void {
    $results = $this->searchForApiByTitle($name);

    if ($results !== NULL && !empty($results)) {
      $queryNid = array_shift($results);
      $api = Node::load($queryNid);

      if ($api !== NULL && $api->get('api_xibmname')->value === $name) {
        print("The api with the name $name was created successfully. ");
      }
      else {
        throw new \Exception("The returned api did not have a name of $name");
      }

      // Make sure the forum  was created
      if (empty(\Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => $name, 'vid' => 'forums']))) {
        print("The forum for the api with the name $name was successfully NOT created");
      }
      else {
        //var_dump(taxonomy_term_load_multiple_by_name($name, 'forums'));
        throw new \Exception("The forum with the name $name exists");
      }
    }
  }

  /**
   * @Given I publish an api with the name :name and id :id
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function iPublishAnApiWithTheNameAndId($name, $id): void {
    $this->tempObject = $this->createApiObject($name, $id);

    $api = new Api();
    $created = $api->createOrUpdate($this->tempObject, 'internal');

    if ($created) {
      print("Api with the name $name and id $id was created successfully. ");
    }
    else {
      throw new \Exception("Failed to create the api with the name $name");
    }
  }

  /**
   * @When I update the api to have the url :url
   * @throws \Exception
   */
  public function iUpdateTheApiToHaveTheUrl($url): void {
    $object = $this->tempObject;
    $object['url'] = $url;

    $api = new Api();
    $created = $api->createOrUpdate($object, 'internal');

    if ($created) {
      throw new \Exception('A new api was created when the existing api with the name ' . $object['consumer_api']['info']['name'] . ' should have been updated. ');
    }
    else {
      print('The existing api with the name ' . $object['consumer_api']['info']['name'] . ' was updated.  ');
    }
  }

  /**
   * @Then I should have an api with the id :id and url :url
   * @throws \Exception
   */
  public function iShouldHaveAnApiWithTheIdAndUrl($id, $url): void {
    $apiName = $this->tempObject['consumer_api']['info']['name'];

    $results = $this->searchForApiByTitle($apiName);

    if ($results !== NULL && !empty($results)) {
      $queryNid = array_shift($results);
      $api = Node::load($queryNid);

      if ($api !== NULL && $api->api_id->value === $id && $api->apic_url->value === $url) {
        print("The api with the id $id and url $url was found successfully. ");
      }
      else {
        throw new \Exception("The returned api did not have an id of $id or a url of $url");
      }
    }
  }

  /**
   * @Given I publish an api with the name :name
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function iPublishAnApiWithTheName($name): void {
    $object = $this->createApiObject($name);

    $api = new Api();
    $created = $api->createOrUpdate($object, 'internal');

    if ($created) {
      print("Api with the name $name was created successfully. ");
    }
    else {
      throw new \Exception("Failed to create the api with the name $name");
    }
  }

  /**
   * @When I delete the api with the name :name
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function iDeleteTheApiWithTheName($name): void {
    $results = $this->searchForApiByTitle($name);

    if ($results !== NULL && !empty($results)) {
      $nId = array_shift($results);

      print("Deleting the api with the node id $nId. ");

      Api::deleteNode($nId, 'internal');

    }
    else {
      throw new \Exception("Failed to find the api with the name $name to delete");
    }

  }

  /**
   * @Then I should no longer have an api with the name :name
   * @throws \Exception
   */
  public function iShouldNoLongerHaveAnApiWithTheName($name): void {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'api');
    $query->condition('title.value', $name);
    $results = $query->execute();

    if (empty($results)) {
      print("The api with the name $name was deleted successfully. ");
    }
    else {
      throw new \Exception("Failed to delete the api with the name $name");
    }
  }

  /**
   * @Then I should have an api name :name visible by :switchTo
   * @throws \Exception
   */
  public function iShouldHaveAnApiNameVisibleBy($name, $switchTo): void {
    $accountSwitcher = \Drupal::service('account_switcher');
    $original_user = \Drupal::currentUser();
    $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => $switchTo]);
    $user = reset($users);
    if ($user) {
      $accountSwitcher->switchTo(User::load($user->id()));
    }
    else {
      throw new \Exception("Unable to switch to user $switchTo");
    }
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'api');
    $query->condition('title.value', $name);
    $results = $query->execute();

    if ($results !== NULL && !empty($results)) {
      $queryNid = array_shift($results);
      $api = Node::load($queryNid);

      if ($api !== NULL && $api->get('api_xibmname')->value === $name) {
        print("$switchTo is able to see API $name");
      }
      else {
        throw new \Exception("The returned api did not have a name of $name");
      }
    }
    if ($original_user !== NULL) {
      $accountSwitcher->switchBack();
    }
  }

  /**
   * @Then the api named :name should be visible
   * @throws \Exception
   */
  public function iShouldHaveAnApiNameVisible($name): void {

    print('Current user uid: ' . \Drupal::currentUser()->id() . PHP_EOL);

    $query = \Drupal::entityQuery('node')
      ->condition('type', 'api')
      ->condition('title.value', $name)
      ->accessCheck(TRUE);
    $results = $query->execute();

    print('Query results: ' . serialize($results) . PHP_EOL);

    if ($results !== NULL && !empty($results)) {
      $queryNid = array_shift($results);
      $api = Node::load($queryNid);

      if ($api !== NULL && $api->get('api_xibmname')->value === $name) {
        print("API $name exists and is visible");
      }
      else {
        throw new \Exception("The returned api was not visible");
      }
    }
    else {
      throw new \Exception("Failed to find an api with the name $name");
    }
  }

  /**
   * @Then the api named :name should not be visible
   * @throws \Exception
   */
  public function iShouldHaveAnApiNameNotVisible($name): void {

    print('Current user uid: ' . \Drupal::currentUser()->id() . PHP_EOL);

    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'api');
    $query->condition('title.value', $name);
    $results = $query->execute();

    print('Query results: ' . serialize($results) . PHP_EOL);

    if ($results !== NULL && !empty($results)) {
      //      $queryNid = array_shift($results);
      //      $api = Node::load($queryNid);
      //      if ($api !== NULL && $api->get('api_xibmname')->value === $name) {
      throw new \Exception("API $name exists and is visible");
    }
    else {
      print("The returned api was not visible");
    }
  }

  /**
   * Creates an associative array representing an API including the API swagger doc
   *
   * @param $title
   * @param string $id
   * @param null $document
   *
   * @return array
   * @throws \JsonException
   */
  private function createApiWithDocument($title, $id = '12345', $document = NULL): array {

    $object = [];
    $object['id'] = $id;
    $object['url'] = 'https://localhost.com';

    if ($document && file_exists($this->testDataDirectory . '/apis/' . $document)) {
      $string = file_get_contents($this->testDataDirectory . '/apis/' . $document);
      $json = json_decode($string, TRUE, 512, JSON_THROW_ON_ERROR);
      $object['consumer_api'] = $json;
      $object['encoded_consumer_api'] = base64_encode($string);
    }
    // overwrite what was in the document with what we were fed in
    // not currently allowing override of api name since it has to match whats in the product document
    $object['consumer_api']['info']['title'] = $title;

    return $object;
  }

  /**
   * Creates an associative array representing an API
   *
   * @param $name
   * @param string $id
   * @param null $categories
   * @param null $phase
   *
   * @return array
   * @throws \JsonException
   */
  private function createApiObject($name, $id = '12345', $categories = NULL, $phase = NULL): array {

    $object = [];
    $object['consumer_api'] = [];
    $object['consumer_api']['info'] = [];
    $object['consumer_api']['info']['name'] = $name;
    $object['consumer_api']['info']['title'] = $name;
    $object['consumer_api']['info']['x-ibm-name'] = $name;
    $object['consumer_api']['info']['version'] = '1.0.0';
    $object['consumer_api']['info']['description'] = 'This is a test API';
    $object['consumer_api']['info']['x-pathalias'] = $name;
    $object['id'] = $id;
    $object['url'] = 'https://localhost.com';
    $object['created_at'] = '2021-02-26T12:18:58.995Z';
    $object['updated_at'] = '2021-02-26T12:18:58.995Z';
    $object['consumer_api']['x-ibm-configuration'] = [];
    $object['consumer_api']['x-ibm-configuration']['type'] = 'rest';
    $object['consumer_api']['x-ibm-configuration']['enforced'] = TRUE;

    if ($categories) {
      $object['consumer_api']['x-ibm-configuration']['categories'] = [$categories];
    }
    if ($phase) {
      $object['consumer_api']['x-ibm-configuration']['phase'] = $phase;
    }
    $object['encoded_consumer_api'] = base64_encode(json_encode($object['consumer_api'], JSON_THROW_ON_ERROR));

    return $object;
  }

  /**
   * Returns any api type nodes with a matching title
   *
   * @param $name
   *
   * @return array|int
   */
  private function searchForApiByTitle($name) {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'api');
    $query->condition('title.value', $name);

    return $query->execute();
  }

}
