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

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\Component\Utility\Random;
use Drupal\product\Product;
use Drupal\node\Entity\Node;
use Drupal\Core\Session\UserSession;
use Drupal\apic_app\Subscription;

class ProductContext extends RawDrupalContext {

  /**
   * @Given I publish a product with the name :arg1, id :arg2 and categories :arg3
   */
  public function iPublishAProductWithNameIdCategories($name, $id, $categories) {
    $random = new Random();
    if (!isset($name) || empty($name)) {
      $name = $random->name(8);
    }
    $object = array();
    $object['catalog_product'] = array();
    $object['catalog_product']['info'] = array();
    $object['catalog_product']['info']['name'] = $name;
    $object['catalog_product']['info']['title'] = $name;
    $object['catalog_product']['info']['version'] = '1.0';
    $object['catalog_product']['info']['categories'] = array($categories);
    $object['state'] = 'published';
    $object['id'] = $id;
    $object['url'] = 'https://localhost.com';
    $object['visibility']['view']['enabled'] = TRUE;
    $object['visibility']['subscribe']['enabled'] = TRUE;
    $object['visibility']['view']['type'] = 'public';

    if (!\Drupal::config('ibm_apim.settings')->get('categories')['create_taxonomies_from_categories']) {
      print("Setting 'create_taxonomies_from_categories' config to true. ");
      \Drupal::service('config.factory')
        ->getEditable('ibm_apim.settings')
        ->set('categories.create_taxonomies_from_categories', TRUE)
        ->save();
    }

    $product = new Product();
    $nid = $product->create($object);

    print("Saved product " . $name . " as nid " . $nid);
  }

  /**
   * @Then I should have a product with the name :arg1, id :arg2 and categories :arg3
   */
  public function iShouldHaveAProductWithNameIdCategories($name, $id, $categories) {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'product');
    $query->condition('title.value', $name);
    $results = $query->execute();

    if (isset($results) && !empty($results)) {
      $querynid = array_shift($results);
      $product = Node::load($querynid);

      if ($product->get('product_id')->value === $id && $product->get('product_name')->value === $name) {
        print("The product with the name $name and id $id was created successfully. ");
      }
      else {
        throw new \Exception("The returned product did not have a name of $name or an id of $id");
      }

      // Make sure the parent term was created from the categories
      if ($terms = taxonomy_term_load_multiple_by_name("Sport", "tags")) {

        $terms = \Drupal::entityTypeManager()
          ->getStorage('taxonomy_term')
          ->loadTree("tags", reset($terms)->get('tid')->value);

        // Make sure the first child in the tree is Ball
        if ($terms[0]->name === "Ball") {
          //print("Child term Ball was created");
        }
        else {
          throw new \Exception("Failed to find a term in the tree with the name Ball");
        }

        // Make sure the final child in the tree is Rugby
        if ($terms[1]->name === "Rugby") {

          // Make sure the id of the term was added to the product
          if ($product->get('apic_tags')->getValue()[0]['target_id'] == $terms[1]->tid) {
            print("Categories where successfully created and linked to the product. ");
          }
          else {
            throw new \Exception("Categories where not added to the product with name $name");
          }

        }
        else {
          throw new \Exception("Failed to find a term in the tree with the name Rugby");
        }
      }

    }
    else {
      throw new \Exception("Failed to find a product with the name $name");
    }

  }

  /**
   * @Given I publish a product with the name :name and categories :categories and create_taxonomies_from_categories is false
   */
  public function iPublishAProductWithTheNameAndCategoriesAndCreateTaxonomiesFromCategoriesIsFalse($name, $categories) {
    $random = new Random();
    if (!isset($name) || empty($name)) {
      $name = $random->name(8);
    }
    $object = array();
    $object['catalog_product'] = array();
    $object['catalog_product']['info'] = array();
    $object['catalog_product']['info']['name'] = $name;
    $object['catalog_product']['info']['title'] = $name;
    $object['catalog_product']['info']['version'] = '1.0';
    $object['catalog_product']['info']['categories'] = array($categories);
    $object['state'] = 'published';
    $object['id'] = "12345678";
    $object['url'] = 'https://localhost.com';
    $object['visibility']['view']['enabled'] = TRUE;
    $object['visibility']['subscribe']['enabled'] = TRUE;
    $object['visibility']['view']['type'] = 'public';

    if (\Drupal::config('ibm_apim.settings')->get('categories')['create_taxonomies_from_categories']) {
      print("Setting 'create_taxonomies_from_categories' config to false. ");
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

    $product = new Product();
    $nid = $product->create($object);

    print("Saved product " . $name . " as nid " . $nid);
  }

  /**
   * @Then I should have a product with name :name and no taxonomies for the categories :categories
   */
  public function iShouldHaveAProductWithNameAndNoTaxonomiesForTheCategories($name, $categories) {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'product');
    $query->condition('title.value', $name);
    $results = $query->execute();

    if (isset($results) && !empty($results)) {
      $querynid = array_shift($results);
      $product = Node::load($querynid);

      if ($product->get('product_name')->value === $name) {
        print("The product with the name $name was created successfully. ");
      }
      else {
        throw new \Exception("The returned product did not have a name of $name");
      }

      $apicTags = $product->get('apic_tags')->getValue();

      // Make sure the categories where not associated with the product
      if (isset($apicTags) && !empty($apicTags)) {
        throw new \Exception("Categories where added to product entity when they should not have been");
      }

    }
    else {
      throw new \Exception("Failed to find a product with the name $name");
    }

  }

  /**
   * @Given I publish a product with the name :arg1, id :arg2 and visibility :arg3 :arg4
   */
  public function iPublishAProductWithNameIdVisibility($name, $id, $visi, $data) {
    $random = new Random();
    if (!isset($name) || empty($name)) {
      $name = $random->name(8);
    }
    $object = array();
    $object['catalog_product'] = array();
    $object['catalog_product']['info'] = array();
    $object['catalog_product']['info']['name'] = $name;
    $object['catalog_product']['info']['title'] = $name;
    $object['catalog_product']['info']['version'] = '1.0';
    $object['state'] = 'published';
    $object['id'] = $id;
    $object['url'] = 'https://localhost.com';
    $object['visibility']['view']['enabled'] = TRUE;
    switch ($visi) {
      case 'pub':
        // public; anyone can view
        $object['visibility']['view']['type'] = 'public';
        break;
      case 'auth':
        // authenticated: any authenticated user can view
        $object['visibility']['view']['type'] = 'authenticated';
        break;
      case 'org_urls':
        // organization: only people in the given orgs can view
        $object['visibility']['view']['type'] = 'custom';
        $object['visibility']['view']['org_urls'] = array($data);
        break;
      case 'tags':
        // category: only people in an org with the right community string can view
        $object['visibility']['view']['type'] = 'custom';
        $object['visibility']['view']['tags'] = array($data);
        break;
      case 'subs':
        // subscription: only people with an app that is subscribed to the portal can view
        // so don't add any extra visibility entries
        break;
      default:
    }

    $product = new Product();
    $nid = $product->create($object);
    print("Saved product " . $name . " as nid " . $nid . PHP_EOL);
  }

  /**
   * @Then The product with the name :arg1 and id :arg2 should be visible to :arg3
   */
  public function iShouldHaveAProductWithNameIdVisisbleAs($name, $id, $switchto) {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'product');
    $query->condition('title.value', $name);
    $results = $query->execute();

    print("Query results: " . serialize($results) . PHP_EOL);
    if (isset($results) && !empty($results)) {
      $product = Node::load(array_shift($results));
      if ($product->get('product_id')->value === $id && $product->get('product_name')->value === $name) {
        print("Product with the name $name and id $id exists and was viewable by $switchto." . PHP_EOL);
      }
      else {
        throw new \Exception("The returned product did not have a name of $name or an id of $id");
      }
    }
    else {
      throw new \Exception("Failed to find a product with the name $name");
    }
  }

  /**
   * @Then The product with the name :arg1 and id :arg2 should not be visible to :arg3
   */
  public function iShouldHaveAProductWithNameIdNotVisisbleAs($name, $id, $switchto) {

    $original_user = \Drupal::currentUser();
    print("Current user uid: " . $original_user->id() . PHP_EOL);

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

    $new_user = \Drupal::currentUser();
    print("Current user uid: " . $new_user->id() . PHP_EOL);


    $query = \Drupal::entityQuery('node')
      ->condition('type', 'product')
      ->condition('title.value', $name)
      ->accessCheck(TRUE);
    $results = $query->execute();
    // print("Query results: " . serialize($results) . PHP_EOL);
    if (isset($results) && !empty($results)) {
      $querynid = array_shift($results);
      $product = Node::load($querynid);
      // print("Product: " . serialize($product) . PHP_EOL);
      //if ($product->get('product_id')->value === $id && $product->get('product_name')->value === $name) {
      throw new \Exception("Product with the name $name and id $id was viewable by $switchto");
    }
    else {
      print("Product with the name $name and id $id was not viewable by $switchto." . PHP_EOL);
    }
  }

  /**
   * @Given I publish a product name :arg1, id :arg2, apis :arg3 and visible to org :arg4
   */
  public function iPublishAProductWithNameIdAPIsAndOrgVisibility($name, $id, $api, $org) {
    $random = new Random();
    if (!isset($name) || empty($name)) {
      $name = $random->name(8);
    }
    $object = array();
    $object['catalog_product'] = array();
    $object['catalog_product']['info'] = array();
    $object['catalog_product']['info']['name'] = $name;
    $object['catalog_product']['info']['title'] = $name;
    $object['catalog_product']['info']['version'] = '1.0';
    $object['state'] = 'published';
    $object['id'] = $id;
    $object['url'] = 'https://localhost.com';
    $object['visibility']['view']['enabled'] = TRUE;
    $object['visibility']['view']['type'] = 'custom';
    $object['visibility']['view']['orgs'] = array($org);
    $object['catalog_product']['apis'] = array($api => array('name' => $api));

    $product = new Product();
    $nid = $product->create($object);
    print("Saved product " . $name . " as nid " . $nid . PHP_EOL);
  }
}
