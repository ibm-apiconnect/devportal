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

use Drupal\apic_api\Api;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\node\Entity\Node;

class ApiContext extends RawDrupalContext {

  private $tempObject = NULL;

  /**
   * @Given I publish an api with the name :name, id :id and categories :categories
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
   */
  public function iShouldHaveAnApiWithTheNameIdAndCategories($name, $id, $categories): void {
    $results = $this->searchForApiByTitle($name);

    if ($results !== NULL && !empty($results)) {
      $querynid = array_shift($results);
      $api = Node::load($querynid);

      if ($api !== NULL && $api->get('api_id')->value === $id && $api->get('api_xibmname')->value === $name) {
        print("The api with the name $name and id $id was created successfully. ");
      }
      else {
        throw new \Exception("The returned api did not have a name of $name or an id of $id");
      }

      // Make sure the parent term was created from the categories
      if ($terms = taxonomy_term_load_multiple_by_name('Sport', 'tags')) {

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
    $controller = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $entities = $controller->loadMultiple($tids);
    $controller->delete($entities);

    $api = new Api();
    $nid = $api->create($object);

    print('Saved spi ' . $name . ' as nid ' . $nid);
  }

  /**
   * @Then I should have an api with name :name and no taxonomies for the categories :categories
   */
  public function iShouldHaveAnApiWithNameAndNoTaxonomiesForTheCategories($name, $categories): void {
    $results = $this->searchForApiByTitle($name);

    if ($results !== NULL && !empty($results)) {
      $querynid = array_shift($results);
      $api = Node::load($querynid);

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
   */
  public function iShouldHaveAnApiWithTheNameTaggedWithThePhase($name, $phase): void {
    $results = $this->searchForApiByTitle($name);

    if ($results !== NULL && !empty($results)) {
      $querynid = array_shift($results);
      $api = Node::load($querynid);

      if ($api !== NULL && $api->get('api_xibmname')->value === $name) {
        print("The api with the name $name was created successfully. ");
      }
      else {
        throw new \Exception("The returned api did not have a name of $name");
      }

      // Make sure the term 'Realized' was created
      if ($realized = taxonomy_term_load_multiple_by_name($phase, 'tags')) {
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
   */
  public function iShouldHaveAnApiWithTheNameAndAForumWithTheName($name): void {
    $results = $this->searchForApiByTitle($name);

    if ($results !== NULL && !empty($results)) {
      $querynid = array_shift($results);
      $api = Node::load($querynid);

      if ($api !== NULL && $api->get('api_xibmname')->value === $name) {
        print("The api with the name $name was created successfully. ");
      }
      else {
        throw new \Exception("The returned api did not have a name of $name");
      }

      // Make sure the forum  was created
      if ($forum = taxonomy_term_load_multiple_by_name($name, 'forums')) {
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
   */
  public function iShouldHaveAnApiNameAndNoForum($name): void {
    $results = $this->searchForApiByTitle($name);

    if ($results !== NULL && !empty($results)) {
      $querynid = array_shift($results);
      $api = Node::load($querynid);

      if ($api !== NULL && $api->get('api_xibmname')->value === $name) {
        print("The api with the name $name was created successfully. ");
      }
      else {
        throw new \Exception("The returned api did not have a name of $name");
      }

      // Make sure the forum  was created
      if (empty(taxonomy_term_load_multiple_by_name($name, 'forums'))) {
        print("The forum for the api with the name $name was successfully NOT created");
      }
      else {
        var_dump(taxonomy_term_load_multiple_by_name($name, 'forums'));
        throw new \Exception("The forum with the name $name exists");
      }
    }
  }

  /**
   * @Given I publish an api with the name :name and id :id
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
   */
  public function iShouldHaveAnApiWithTheIdAndUrl($id, $url): void {
    $apiName = $this->tempObject['consumer_api']['info']['name'];

    $results = $this->searchForApiByTitle($apiName);

    if ($results !== NULL && !empty($results)) {
      $querynid = array_shift($results);
      $api = Node::load($querynid);

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
   * @Then I should have an api name :name visible by :switchto
   */
  public function iShouldHaveAnApiNameVisibleBy($name, $switchto): void {
    $accountSwitcher = \Drupal::service('account_switcher');
    $users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['name' => $switchto]);
    $user = reset($users);
    if ($user) {
      $accountSwitcher->switchTo(\Drupal\user\Entity\User::load($user->id()));
    }
    else {
      throw new \Exception("Unable to switch to user $switchto");
    }
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'api');
    $query->condition('title.value', $name);
    $results = $query->execute();

    if ($results !== NULL && !empty($results)) {
      $querynid = array_shift($results);
      $api = Node::load($querynid);

      if ($api !== NULL && $api->get('api_xibmname')->value === $name) {
        print("$switchto is able to see API $name");
      }
      else {
        throw new \Exception("The returned api did not have a name of $name");
      }
    }
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
   */
  private function createApiObject($name, $id = '12345', $categories = NULL, $phase = NULL): array {

    $object = [];
    $object['consumer_api'] = [];
    $object['consumer_api']['info'] = [];
    $object['consumer_api']['info']['name'] = $name;
    $object['consumer_api']['info']['title'] = $name;
    $object['consumer_api']['info']['x-ibm-name'] = $name;
    $object['consumer_api']['info']['version'] = '1.0';
    $object['consumer_api']['info']['description'] = 'This is a test API';
    $object['id'] = $id;
    $object['url'] = 'https://localhost.com';
    $object['consumer_api']['x-ibm-configuration'] = [];
    $object['consumer_api']['x-ibm-configuration']['type'] = 'rest';

    if ($categories) {
      $object['consumer_api']['x-ibm-configuration']['categories'] = [$categories];
    }
    if ($phase) {
      $object['consumer_api']['x-ibm-configuration']['phase'] = $phase;
    }

    return $object;
  }

  /**
   * Retruns any api type nodes with a matching title
   *
   * @param $name
   *
   * @return mixed
   */
  private function searchForApiByTitle($name) {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'api');
    $query->condition('title.value', $name);

    return $query->execute();
  }
}
