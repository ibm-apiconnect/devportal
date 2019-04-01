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

use Drupal\Component\Utility\Random;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\node\Entity\Node;
use Drupal\product\Product;

class ProductContext extends RawDrupalContext {

  /**
   * @Given I publish a product with the name :arg1, id :arg2 and categories :arg3
   */
  public function iPublishAProductWithNameIdCategories($name, $id, $categories): void {
    $random = new Random();
    if ($name === NULL || empty($name)) {
      $name = $random->name(8);
    }
    $object = [];
    $object['catalog_product'] = [];
    $object['catalog_product']['info'] = [];
    $object['catalog_product']['info']['name'] = $name;
    $object['catalog_product']['info']['title'] = $name;
    $object['catalog_product']['info']['version'] = '1.0';
    $object['catalog_product']['info']['categories'] = [$categories];
    $object['state'] = 'published';
    $object['id'] = $id;
    $object['url'] = 'https://localhost.com';
    $object['catalog_product']['visibility']['view']['enabled'] = TRUE;
    $object['catalog_product']['visibility']['subscribe']['enabled'] = TRUE;
    $object['catalog_product']['visibility']['view']['type'] = 'public';

    if (!(boolean) \Drupal::config('ibm_apim.settings')->get('categories')['create_taxonomies_from_categories']) {
      print("Setting 'create_taxonomies_from_categories' config to true. ");
      \Drupal::service('config.factory')
        ->getEditable('ibm_apim.settings')
        ->set('categories.create_taxonomies_from_categories', TRUE)
        ->save();
    }

    $product = new Product();
    $nid = $product->create($object);

    // Make sure that the call returns a number
    if ((int)$nid >= 0) {
      print('Saved product ' . $name . ' as nid ' . $nid . PHP_EOL);
    }
    else {
      throw new \Exception("Failed to create product with the name $name");
    }
  }

  /**
   * @Then I should have a product with the name :arg1, id :arg2 and categories :arg3
   */
  public function iShouldHaveAProductWithNameIdCategories($name, $id, $categories): void {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'product');
    $query->condition('title.value', $name);
    $results = $query->execute();

    if ($results !== NULL && !empty($results)) {
      $querynid = array_shift($results);
      $product = Node::load($querynid);

      if ($product !== NULL && $product->get('product_id')->value === $id && $product->get('product_name')->value === $name) {
        print("The product with the name $name and id $id was created successfully. ");
      }
      else {
        throw new \Exception("The returned product did not have a name of $name or an id of $id");
      }

      // Make sure the parent term was created from the categories
      if ($terms = taxonomy_term_load_multiple_by_name('Sport', 'tags')) {

        $terms = \Drupal::entityTypeManager()
          ->getStorage('taxonomy_term')
          ->loadTree('tags', reset($terms)->get('tid')->value);

        // Make sure the first child in the tree is Ball
        if ($terms[0]->name !== 'Ball') {
          throw new \Exception('Failed to find a term in the tree with the name Ball');
        }

        // Make sure the final child in the tree is Rugby
        if ($terms[1]->name === 'Rugby') {

          // Make sure the id of the term was added to the product
          if ($product->get('apic_tags')->getValue()[0]['target_id'] == $terms[1]->tid) {
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
   * @Given I publish a product with the name :name and categories :categories and create_taxonomies_from_categories is false
   */
  public function iPublishAProductWithTheNameAndCategoriesAndCreateTaxonomiesFromCategoriesIsFalse($name, $categories): void {
    $random = new Random();
    if ($name === NULL || empty($name)) {
      $name = $random->name(8);
    }
    $object = [];
    $object['catalog_product'] = [];
    $object['catalog_product']['info'] = [];
    $object['catalog_product']['info']['name'] = $name;
    $object['catalog_product']['info']['title'] = $name;
    $object['catalog_product']['info']['version'] = '1.0';
    $object['catalog_product']['info']['categories'] = [$categories];
    $object['state'] = 'published';
    $object['id'] = '12345678';
    $object['url'] = 'https://localhost.com';
    $object['catalog_product']['visibility']['view']['enabled'] = TRUE;
    $object['catalog_product']['visibility']['subscribe']['enabled'] = TRUE;
    $object['catalog_product']['visibility']['view']['type'] = 'public';

    if ((boolean) \Drupal::config('ibm_apim.settings')->get('categories')['create_taxonomies_from_categories']) {
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

    if ((int)$nid >= 0) {
      print('Saved product ' . $name . ' as nid ' . $nid . PHP_EOL);
    }
    else {
      throw new \Exception("Failed to create product with the name $name");
    }
  }

  /**
   * @Then I should have a product with name :name and no taxonomies for the categories :categories
   */
  public function iShouldHaveAProductWithNameAndNoTaxonomiesForTheCategories($name, $categories): void {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'product');
    $query->condition('title.value', $name);
    $results = $query->execute();

    if ($results !== NULL && !empty($results)) {
      $querynid = array_shift($results);
      $product = Node::load($querynid);

      if ($product !== NULL && $product->get('product_name')->value === $name) {
        print("The product with the name $name was created successfully. ");
      }
      else {
        throw new \Exception("The returned product did not have a name of $name");
      }

      $apicTags = $product->get('apic_tags')->getValue();

      // Make sure the categories where not associated with the product
      if ($apicTags !== NULL && !empty($apicTags)) {
        throw new \Exception('Categories where added to product entity when they should not have been');
      }

    }
    else {
      throw new \Exception("Failed to find a product with the name $name");
    }

  }

  /**
   * @Given I publish a product with the name :arg1, id :arg2 and visibility :arg3 :arg4
   */
  public function iPublishAProductWithNameIdVisibility($name, $id, $visi, $data): void {
    $random = new Random();
    if ($name === NULL || empty($name)) {
      $name = $random->name(8);
    }
    $object = [];
    $object['catalog_product'] = [];
    $object['catalog_product']['info'] = [];
    $object['catalog_product']['info']['name'] = $name;
    $object['catalog_product']['info']['title'] = $name;
    $object['catalog_product']['info']['version'] = '1.0';
    $object['state'] = 'published';
    $object['id'] = $id;
    $object['url'] = 'https://localhost.com';
    $object['catalog_product']['visibility']['view']['enabled'] = TRUE;
    switch ($visi) {
      case 'pub':
        // public; anyone can view
        $object['catalog_product']['visibility']['view']['type'] = 'public';
        break;
      case 'auth':
        // authenticated: any authenticated user can view
        $object['catalog_product']['visibility']['view']['type'] = 'authenticated';
        break;
      case 'org_urls':
        // organization: only people in the given orgs can view
        $object['catalog_product']['visibility']['view']['type'] = 'custom';
        $object['catalog_product']['visibility']['view']['org_urls'] = [$data];
        break;
      case 'tags':
        // category: only people in an org with the right community string can view
        $object['catalog_product']['visibility']['view']['type'] = 'custom';
        $object['catalog_product']['visibility']['view']['group_urls'] = [$data];
        break;
      case 'subs':
        // subscription: only people with an app that is subscribed to the portal can view
        // so don't add any extra visibility entries
        break;
      default:
    }

    $product = new Product();
    $nid = $product->create($object);
    // Make sure that the call returns a number
    if ((int)$nid >= 0) {
      print('Saved product ' . $name . ' as nid ' . $nid . PHP_EOL);
    }
    else {
      throw new \Exception("Failed to create product with the name $name");
    }
  }

  /**
   * @Given I publish a product with the name :arg1, id :arg2, apis :arg3 and visibility :arg4 :arg5
   */
  public function iPublishAProductWithNameIdAPIVisibility($name, $id, $api, $visi, $data): void {
    $random = new Random();
    if ($name === NULL || empty($name)) {
      $name = $random->name(8);
    }
    $incapi = $api . ':1.0.0';
    $object = [];
    $object['catalog_product'] = [];
    $object['catalog_product']['info'] = [];
    $object['catalog_product']['info']['name'] = $name;
    $object['catalog_product']['info']['title'] = $name;
    $object['catalog_product']['info']['version'] = '1.0.0';
    $object['state'] = 'published';
    $object['id'] = $id;
    $object['url'] = 'https://localhost.com';
    $object['catalog_product']['apis'] = [$incapi => ['name' => $incapi]];
    $object['catalog_product']['visibility']['view']['enabled'] = TRUE;
    switch ($visi) {
      case 'pub':
        // public; anyone can view
        $object['catalog_product']['visibility']['view']['type'] = 'public';
        break;
      case 'auth':
        // authenticated: any authenticated user can view
        $object['catalog_product']['visibility']['view']['type'] = 'authenticated';
        break;
      case 'org_urls':
        // organization: only people in the given orgs can view
        $object['catalog_product']['visibility']['view']['type'] = 'custom';
        $object['catalog_product']['visibility']['view']['org_urls'] = [$data];
        break;
      case 'tags':
        // category: only people in an org with the right community string can view
        $object['catalog_product']['visibility']['view']['type'] = 'custom';
        $object['catalog_product']['visibility']['view']['group_urls'] = [$data];
        break;
      case 'subs':
        // subscription: only people with an app that is subscribed to the portal can view
        // so don't add any extra visibility entries
        break;
      default:
    }

    $product = new Product();
    $nid = $product->create($object);
    // Make sure that the call returns a number
    if ((int)$nid >= 0) {
      print('Saved product ' . $name . ' as nid ' . $nid . PHP_EOL);
    }
    else {
      throw new \Exception("Failed to create product with the name $name");
    }
  }


  /**
   * @Given I publish a product name :arg1, id :arg2, apis :arg3 and visible to org :arg4
   */
  public function iPublishAProductWithNameIdAPIsAndOrgVisibility($name, $id, $api, $org): void {
    $random = new Random();
    if ($name === NULL || empty($name)) {
      $name = $random->name(8);
    }
    $object = [];
    $object['catalog_product'] = [];
    $object['catalog_product']['info'] = [];
    $object['catalog_product']['info']['name'] = $name;
    $object['catalog_product']['info']['title'] = $name;
    $object['catalog_product']['info']['version'] = '1.0';
    $object['state'] = 'published';
    $object['id'] = $id;
    $object['url'] = 'https://localhost.com';
    $object['catalog_product']['visibility']['view']['enabled'] = TRUE;
    $object['catalog_product']['visibility']['view']['type'] = 'custom';
    $object['catalog_product']['visibility']['view']['orgs'] = [$org];
    $object['catalog_product']['apis'] = [$api => ['name' => $api]];

    $product = new Product();
    $nid = $product->create($object);
    // Make sure that the call returns a number
    if ((int)$nid >= 0) {
      print('Saved product ' . $name . ' as nid ' . $nid . PHP_EOL);
    }
    else {
      throw new \Exception("Failed to create product with the name $name");
    }
  }

  /**
   * @Then The product with the name :arg1 and id :arg2 should be visible
   */
  public function iShouldHaveAProductWithNameIdVisible($name, $id): void {

    print('Current user uid: ' . \Drupal::currentUser()->id() . PHP_EOL);

    $query = \Drupal::entityQuery('node')
      ->condition('type', 'product')
      ->condition('title.value', $name)
      ->accessCheck(TRUE);
    $results = $query->execute();

    print('Query results: ' . serialize($results) . PHP_EOL);

    if ($results !== NULL && !empty($results)) {
      $product = Node::load(array_shift($results));
      if ($product->get('product_id')->value === $id && $product->get('product_name')->value === $name) {
        print("Product with the name $name and id $id exists and was viewable." . PHP_EOL);
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
   * @Then The product with the name :arg1 and id :arg2 should not be visible
   */
  public function iShouldHaveAProductWithNameIdNotVisible($name, $id): void {

    print('Current user uid: ' . \Drupal::currentUser()->id() . PHP_EOL);

    $query = \Drupal::entityQuery('node')
      ->condition('type', 'product')
      ->condition('title.value', $name)
      ->accessCheck(TRUE);
    $results = $query->execute();

    print('Query results: ' . serialize($results) . PHP_EOL);

    if ($results !== NULL && !empty($results)) {
      $querynid = array_shift($results);
      // $product = Node::load($querynid);
      // print('Product: ' . serialize($product) . PHP_EOL);
      //if ($product->get('product_id')->value === $id && $product->get('product_name')->value === $name) {
      throw new \Exception("Product with the name $name and id $id was viewable");
    }
    else {
      print("Product with the name $name and id $id was not viewable." . PHP_EOL);
    }
  }

}