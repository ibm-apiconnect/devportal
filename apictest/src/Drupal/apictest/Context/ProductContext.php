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
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\Random;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\node\Entity\Node;
use Drupal\product\Product;
use Drupal\user\Entity\User;

class ProductContext extends RawDrupalContext {

  private string $testDataDirectory = __DIR__ . '/../../../../testdata';

  /**
   * @var bool|\stdClass|\stdClass[]
   */
  private $useMockServices = TRUE;

  /**
   * @Given products:
   *
   * @param \Behat\Gherkin\Node\TableNode $table
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   */
  public function createProducts(TableNode $table): void {

    // If we are not using mocks, then we are testing with live data from a management appliance
    // Under those circumstances, we should absolutely not create any product in the database!
    if ($this->useMockServices === FALSE) {
      print "This test is running with a real management server backend. No products will be created in the database.\n";
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
      $object = $this->createProductWithDocument($row['name'], $row['title'], $row['id'], $row['document']);

      $product = new Product();
      $product->create($object);

    }
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }
  }

  /**
   * @Given I have no products or apis
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function iHaveNoProductsOrApis():void {
    $query = \Drupal::entityQuery('node');
    $group = $query->orConditionGroup()
      ->condition('type', 'product')
      ->condition('type', 'api');
    $query->condition($group);
    $results = $query->execute();
    foreach ($results as $nid) {
      $node = Node::load($nid);
      if ($node !== NULL) {
        $node->delete();
      }
    }
  }

  /**
   * @Given I publish a product with the name :arg1, id :arg2 and categories :arg3
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
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
    $object['url'] = '/catalogs/1234/5678/products/' . $id;
    $object['catalog_product']['visibility']['view']['enabled'] = TRUE;
    $object['catalog_product']['visibility']['subscribe']['enabled'] = TRUE;
    $object['catalog_product']['visibility']['view']['type'] = 'public';
    $object['created_at'] = '2021-02-26T12:18:58.995Z';
    $object['updated_at'] = '2021-02-26T12:18:58.995Z';

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
    if ((int) $nid >= 0) {
      print('Saved product ' . $name . ' as nid ' . $nid . PHP_EOL);
    }
    else {
      throw new \Exception("Failed to create product with the name $name");
    }
  }

  /**
   * @Then I should have a product with the name :arg1, id :arg2 and categories :arg3
   * @throws \Exception
   */
  public function iShouldHaveAProductWithNameIdCategories($name, $id, $categories): void {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'product');
    $query->condition('title.value', $name);
    $results = $query->execute();

    if ($results !== NULL && !empty($results)) {
      $queryNid = array_shift($results);
      $product = Node::load($queryNid);

      if ($product !== NULL && $product->get('product_id')->value === $id && $product->get('product_name')->value === $name) {
        print("The product with the name $name and id $id was created successfully. ");
      }
      else {
        throw new \Exception("The returned product did not have a name of $name or an id of $id");
      }

      // Make sure the parent term was created from the categories
      if ($terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadByProperties(['name' => 'Sport', 'vid' => 'tags'])) {

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
          if ($product->get('apic_tags')->getValue()[0]['target_id'] === $terms[1]->tid) {
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
   * @throws \Exception
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
    $object['created_at'] = '2021-02-26T12:18:58.995Z';
    $object['updated_at'] = '2021-02-26T12:18:58.995Z';

    if ((boolean) \Drupal::config('ibm_apim.settings')->get('categories')['create_taxonomies_from_categories']) {
      print("Setting 'create_taxonomies_from_categories' config to false. ");
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

    $product = new Product();
    $nid = $product->create($object);

    if ((int) $nid >= 0) {
      print('Saved product ' . $name . ' as nid ' . $nid . PHP_EOL);
    }
    else {
      throw new \Exception("Failed to create product with the name $name");
    }
  }

  /**
   * @Then I should have a product with name :name and no taxonomies for the categories :categories
   * @throws \Exception
   */
  public function iShouldHaveAProductWithNameAndNoTaxonomiesForTheCategories($name, $categories): void {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'product');
    $query->condition('title.value', $name);
    $results = $query->execute();

    if ($results !== NULL && !empty($results)) {
      $queryNid = array_shift($results);
      $product = Node::load($queryNid);

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
   * @Given I publish a product with the name :arg1, id :arg2 and visibility :arg3 :arg4 :arg5
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function iPublishAProductWithNameIdVisibility($name, $id, $visi, $data, $enabled): void {
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
    $object['catalog_product']['info']['x-pathalias'] = $name;
    $object['created_at'] = '2021-02-26T12:18:58.995Z';
    $object['updated_at'] = '2021-02-26T12:18:58.995Z';
    $object['state'] = 'published';
    $object['id'] = $id;
    $object['url'] = 'https://localhost.com';
    if ($enabled === "false" || $enabled === "FALSE") {
      $object['catalog_product']['visibility']['view']['enabled'] = 0;
    } else {
      $object['catalog_product']['visibility']['view']['enabled'] = true;
    }
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
    if ((int) $nid >= 0) {
      print('Saved product ' . $name . ' as nid ' . $nid . PHP_EOL);
    }
    else {
      throw new \Exception("Failed to create product with the name $name");
    }
  }

  /**
   * @Given I publish a product with the name :arg1, id :arg2, apis :arg3 and subscribility :arg4 :arg5 :arg6
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function iPublishAProductWithNameIdAPISubscribability($name, $id, $api, $sub, $data, $enabled): void {
    $random = new Random();
    if ($name === NULL || empty($name)) {
      $name = $random->name(8);
    }
    $incApi = $api . ':1.0.0';
    $object = [];
    $object['created_at'] = '2021-02-26T12:18:58.995Z';
    $object['updated_at'] = '2021-02-26T12:18:58.995Z';
    $object['catalog_product'] = [];
    $object['catalog_product']['info'] = [];
    $object['catalog_product']['info']['name'] = $name;
    $object['catalog_product']['info']['title'] = $name;
    $object['catalog_product']['info']['x-pathalias'] = $name;
    $object['catalog_product']['info']['version'] = '1.0.0';
    $object['state'] = 'published';
    $object['id'] = $id;
    $object['url'] = 'https://localhost.com';
    $object['product_plans'] = [["apis" => []]];
    $object['catalog_product']['plans'] = [
      "default-plan" => [
              "rate-limits" => [
                      "default" =>[
                              "value" => "100/1hour"
                              ]

                      ],

              "title" => "Default Plan",
              "description" => "Default Plan",
              "approval" => null,
              "apis" => [
                      $incApi => []

                      ]

      ]
    ];
    $object['catalog_product']['apis'] = [$incApi => ['name' => $incApi]];
    $object['catalog_product']['visibility']['view']['enabled'] = true;
    $object['catalog_product']['visibility']['view']['type'] = 'public';
    if ($enabled === "false" || $enabled === "FALSE") {
      $object['catalog_product']['visibility']['subscribe']['enabled'] = 0;
    } else {
      $object['catalog_product']['visibility']['subscribe']['enabled'] = true;
    }
    switch ($sub) {
      case 'auth':
        // authenticated: any authenticated user can view
        $object['catalog_product']['visibility']['subscribe']['type'] = 'authenticated';
        break;
      case 'org_urls':
        // organization: only people in the given orgs can view
        $object['catalog_product']['visibility']['subscribe']['type'] = 'custom';
        $object['catalog_product']['visibility']['subscribe']['org_urls'] = [$data];
        break;
      case 'tags':
        // category: only people in an org with the right community string can view
        $object['catalog_product']['visibility']['subscribe']['type'] = 'custom';
        $object['catalog_product']['visibility']['subscribe']['group_urls'] = [$data];
        break;
      default:
    }


    $product = new Product();
    $nid = $product->create($object);
    // Make sure that the call returns a number
    if ((int) $nid >= 0) {
      print('Saved product ' . $name . ' as nid ' . $nid . PHP_EOL);
    }
    else {
      throw new \Exception("Failed to create product with the name $name");
    }
  }

  /**
   * @Given I publish a product with the name :arg1, id :arg2, apis :arg3 and visibility :arg4 :arg5 :arg6
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function iPublishAProductWithNameIdAPIVisibility($name, $id, $api, $visi, $data, $enabled): void {
    $random = new Random();
    if ($name === NULL || empty($name)) {
      $name = $random->name(8);
    }
    $incApi = $api . ':1.0.0';
    $object = [];
    $object['created_at'] = '2021-02-26T12:18:58.995Z';
    $object['updated_at'] = '2021-02-26T12:18:58.995Z';
    $object['catalog_product'] = [];
    $object['catalog_product']['info'] = [];
    $object['catalog_product']['info']['name'] = $name;
    $object['catalog_product']['info']['title'] = $name;
    $object['catalog_product']['info']['version'] = '1.0.0';
    $object['catalog_product']['info']['x-pathalias'] = $name;
    $object['state'] = 'published';
    $object['id'] = $id;
    $object['url'] = 'https://localhost.com';
    $object['product_plans'] = [["apis" => []]];
    $object['catalog_product']['plans'] = [
      "default-plan" => [
              "rate-limits" => [
                      "default" =>[
                              "value" => "100/1hour"
                              ]

                      ],

              "title" => "Default Plan",
              "description" => "Default Plan",
              "approval" => null,
              "apis" => [
                      $incApi => []

                      ]

      ]
    ];
    $object['catalog_product']['apis'] = [$incApi => ['name' => $incApi]];
    if ($enabled === "false" || $enabled === "FALSE") {
      $object['catalog_product']['visibility']['view']['enabled'] = 0;
    } else {
      $object['catalog_product']['visibility']['view']['enabled'] = true;
    }
    $object['catalog_product']['visibility']['subscribe']['enabled'] = TRUE;
    switch ($visi) {
      case 'pub':
        // public; anyone can view
        $object['catalog_product']['visibility']['subscribe']['type'] = 'authenticated';
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
    if ((int) $nid >= 0) {
      print('Saved product ' . $name . ' as nid ' . $nid . PHP_EOL);
    }
    else {
      throw new \Exception("Failed to create product with the name $name");
    }
  }

    /**
   * @Given I publish a product with the name :arg1, id :arg2, apis :arg3 and a paid plan
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function iPublishAProductWithNameIdAPIBilling($name, $id, $api): void {
    $random = new Random();
    if ($name === NULL || empty($name)) {
      $name = $random->name(8);
    }
    $incApi = $api . ':1.0.0';
    $object = [];
    $object['created_at'] = '2021-02-26T12:18:58.995Z';
    $object['updated_at'] = '2021-02-26T12:18:58.995Z';
    $object['catalog_product'] = [];
    $object['catalog_product']['info'] = [];
    $object['catalog_product']['info']['name'] = $name;
    $object['catalog_product']['info']['title'] = $name;
    $object['catalog_product']['info']['version'] = '1.0.0';
    $object['catalog_product']['info']['x-pathalias'] = $name;
    $object['state'] = 'published';
    $object['id'] = $id;
    $object['url'] = 'https://localhost.com';
    $object['product_plans'] = [["apis" => []]];
    $object['catalog_product']['plans'] = [
      "default-plan" => [
        "rate-limits" => [
                "default" =>[
                        "value" => "100/1hour"
                        ]

                ],
        "title" => "Default Plan",
        "description" => "Default Plan",
        "approval" => null,
        "apis" => [
                $incApi => []
        ],
        "billing" => [
          "billing" => "account",
          "currency" => "USD",
          "price" => 10,
          "period" => 1,
          "period-unit" => "month", 
          "trial-period" => 0,
          "trial-period-unit" => "day"
        ]
      ]
    ];
    $object['catalog_product']['apis'] = [$incApi => ['name' => $incApi]];

    $object['catalog_product']['visibility']['view']['enabled'] = true;
    $object['catalog_product']['visibility']['subscribe']['enabled'] = TRUE;
    $object['catalog_product']['visibility']['subscribe']['type'] = 'authenticated';
    $object['catalog_product']['visibility']['view']['type'] = 'public';

    $product = new Product();
    $nid = $product->create($object);
    // Make sure that the call returns a number
    if ((int) $nid >= 0) {
      print('Saved product ' . $name . ' as nid ' . $nid . PHP_EOL);
    }
    else {
      throw new \Exception("Failed to create product with the name $name");
    }
  }


  /**
   * @Given I publish a product name :arg1, id :arg2, apis :arg3 and visible to org :arg4
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Exception
   */
  public function iPublishAProductWithNameIdAPIsAndOrgVisibility($name, $id, $api, $org): void {
    $random = new Random();
    if ($name === NULL || empty($name)) {
      $name = $random->name(8);
    }
    $object = [];
    $object['created_at'] = '2021-02-26T12:18:58.995Z';
    $object['updated_at'] = '2021-02-26T12:18:58.995Z';
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
    if ((int) $nid >= 0) {
      print('Saved product ' . $name . ' as nid ' . $nid . PHP_EOL);
    }
    else {
      throw new \Exception("Failed to create product with the name $name");
    }
  }

  /**
   * @Then The product with the name :arg1 and id :arg2 should be visible
   * @throws \Exception
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
      if ($product !== NULL) {
        if ($product->get('product_id')->value === $id && $product->get('product_name')->value === $name) {
          print("Product with the name $name and id $id exists and was viewable." . PHP_EOL);
        }
        else {
          throw new \Exception("The returned product did not have a name of $name or an id of $id");
        }
      }
      else {
        throw new \Exception("NULL product returned");
      }
    }
    else {
      throw new \Exception("Failed to find a product with the name $name");
    }
  }

  /**
   * @Then The product with the name :arg1 and id :arg2 should not be visible
   * @throws \Exception
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
      // $queryNid = array_shift($results);
      // $product = Node::load($queryNid);
      // print('Product: ' . serialize($product) . PHP_EOL);
      //if ($product->get('product_id')->value === $id && $product->get('product_name')->value === $name) {
      throw new \Exception("Product with the name $name and id $id was viewable");
    }
    else {
      print("Product with the name $name and id $id was not viewable." . PHP_EOL);
    }
  }

  /**
   * Creates an associative array representing a Product including the product doc
   *
   * @param $name
   * @param $title
   * @param string $id
   * @param null $document
   *
   * @return array
   * @throws \JsonException
   */
  private function createProductWithDocument($name, $title, $id = '12345', $document = NULL): array {

    $object = [];
    $object['id'] = $id;
    $object['url'] = '/catalogs/1234/5678/products/' . $id;
    $object['state'] = 'published';
    $object['created_at'] = '2021-02-26T12:18:58.995Z';
    $object['updated_at'] = '2021-02-26T12:18:58.995Z';

    if ($document && file_exists($this->testDataDirectory . '/products/' . $document)) {
      $string = file_get_contents($this->testDataDirectory . '/products/' . $document);
      $json = json_decode($string, TRUE, 512, JSON_THROW_ON_ERROR);
      $object['catalog_product'] = $json;
    }
    // overwrite what was in the document with what we were fed in
    $object['catalog_product']['info']['name'] = $name;
    $object['catalog_product']['info']['title'] = $title;

    return $object;
  }

}
