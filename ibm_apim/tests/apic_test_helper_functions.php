<?php

trait ApicTestHelperFunctions {

  public function __construct($test_id = NULL) {

    parent::__construct($test_id);

    $this->skipClasses[__CLASS__] = TRUE;

  }

  /**
   * Function for login
   *
   * @param $var
   */
  protected function login(&$var) {
    $username = variable_get('ibm_apim_test_username');
    $user = user_load_by_name($username);
    $user->pass_raw = variable_get('ibm_apim_test_password');
    $var->drupalLogin($user);
  }

  /**
   * Public functions - Get first product page
   *
   * @param $var
   * @param $arrayPosition
   * @param $visit
   * @return mixed
   */
  public function getProductPage(&$var, $arrayPosition, $visit) {


     //visit = true navigates the test browser to the product page retrieved
     //visit = false leaves the test browser on the product list page
     //arrayPosition allows choice of product from list

    // Get product page
    $var->drupalGet('product');

    // Check for the presence of nodes
    $nodes = $var->findElementByCss('.node.node-product');
    if ($nodes !== FALSE) {
      $var->pass('There is at least one node on the products page');
      // Select the product selected and get the product_id (selection in func call)
      $node1 = $this->findElementByCss('.apimTitle a');
      $productPathArray = explode('/', $node1[$arrayPosition]['href'][0]);
      $prodId = $productPathArray[(count($productPathArray) - 1)];

      // Visit the specific product page and extract the node object from the db
      if ($visit) {
        $var->drupalGet('product/' . $prodId);
      }
      $query = new EntityFieldQuery();
      $query->entityCondition('entity_type', 'node')
        ->entityCondition('bundle', 'product')
        ->propertyCondition('status', 1)
        ->fieldCondition('product_id', 'value', check_plain($prodId));
      $results = $query->execute();
      if (isset($results['node'])) {
        $keys = array_keys($results['node']);
        $node = node_load($keys[0]);

        //debug('Checking the node has been successfully saved, node ID:' . $node->nid);
        return ($node);
      }
      else {
        debug('Something went wrong - node not extracted from DB correctly');
        $var->drupalLogout();
        return FALSE;
      }
    }
    else {
      $var->pass('There are no products on this page, further tests require at least one product');
      $var->assertText('No products have been found.', 'No products message displays correctly');
      $var->drupalLogout();
      return FALSE;
    }
  }

  /**
   * Public functions - Get first API page
   *
   * @param $var
   * @param $arrayPosition
   * @return mixed
   */
  public function getApiPage(&$var, $arrayPosition) {

    /**
     * Takes the node of the product page you're working with as second arg
     * Assumption: that you're already on the page of a product
     * Search for the href link to the API specified by arrayPosition-> extract api reference
     */

    $apiElement = $this->findElementByCss('.tocItem.tocApi a');
    $apiElementArray = explode('/', $apiElement[$arrayPosition]['href']);

    // Decode the api reference and query the database for that api node
    $apiRef = ibm_apim_base64_url_decode(urldecode($apiElementArray[(count($apiElementArray) - 1)]));
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'node')
      ->entityCondition('bundle', 'api')
      ->propertyCondition('status', 1)
      ->fieldCondition('api_ref', 'value', $apiRef);
    $results = $query->execute();
    if (isset($results['node'])) {
      $first = array_shift($results['node']);
      $nid = $first->nid;
      $node1 = node_load($nid);
      // Visit the API page
      $var->drupalGet('node/' . $node1->nid);
      debug('Checking that the API node has been successfully saved, node ID:' . $node1->nid);
      return ($node1);
    }
    else {
      $var->drupalLogout();
      return FALSE;
    }
  }

  /**
   * Public functions - Get first API page if there is at least one product
   *
   * @param $var
   * @return mixed
   */
  public function getApiPageOneLine(&$var1) {

    // Login
    $var1->login($this);

    // Visit product page
    $node1 = $var1->getProductPage($this, 0, TRUE);

    // Visit an API page if there was one or more products
    if ($node1 == FALSE) {
      debug('getProducts call returns FALSE. Skipping the rest of this test due to lack of products');
      $var1->drupalLogout();
      return FALSE;
    }
    else {
      $node2 = $this->getApiPage($this, 0);
      return ($node2);
    }
  }

}
