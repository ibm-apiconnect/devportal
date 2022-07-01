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

namespace Drupal\Tests\product\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\product\Product;
use Drupal\Tests\ibm_apim\Unit\mocks\MockProductNodeBuilder;

/**
 * product tests.
 *
 * @group product
 */
class ProductUnitTest extends UnitTestCase {

  private $apiName;
  private $apiVersion;
  private $apiRef;
  private $unrelatedApiRef;
  private $planName;
  private $anotherPlanName;

  public function setup() : void {
    $this->apiName = 'swagger-petstore';
    $this->apiVersion = '1.0.0';
    $this->apiRef = $this->apiName.':'.$this->apiVersion;
    $this->planName="default plan";
    $this->anotherPlanName="golden plan";
    $this->unrelatedApiRef = "someOtherApi:1.0.0";
  }

  /**
   * A Product without any apis should no return any plans
   */
  public function testProductWithoutAnyApiReturnsEmptyArray(): void {
    $productBuilder = new MockProductNodeBuilder($this);
    
    $mockProduct = $productBuilder->build();

    $result = Product::getPlansThatContainApi($mockProduct, $this->apiRef);
    self::assertEquals([], $result);
  }

  /**
   * A product without any plans should not return any plans
   */
  public function testProductWithoutAPlanReturnsEmptyArray(): void {
    $productBuilder = new MockProductNodeBuilder($this);

    $productBuilder->setApis([array(
      'name' => $this->apiRef
    )]);

    $yaml = <<<EOT
      apis:
        $this->apiRef: 
          name: $this->apiRef
    EOT;
    $productBuilder->setData($yaml);
    
    $mockProduct = $productBuilder->build();

    $result = Product::getPlansThatContainApi($mockProduct, $this->apiRef);
    self::assertEquals([], $result);
  }

  /**
   * If a product doesn't contain the requested api, the function should not return any plans
   */
  public function testProductWithoutThisApiReturnsEmptyArray(): void {
    $productBuilder = new MockProductNodeBuilder($this);

    $productBuilder->setApis([array(
      'name' => $this->unrelatedApiRef
    )]);
    $productBuilder->setPlans([array(
      'name' => $this->planName
    )]);

    $yaml = <<<EOT
    apis:
      $this->unrelatedApiRef: 
        name: $this->unrelatedApiRef
    EOT;
    $productBuilder->setData($yaml);
    
    $mockProduct = $productBuilder->build();

    $result = Product::getPlansThatContainApi($mockProduct, $this->apiRef);
    self::assertEquals([], $result);
  }

  /**
   * A product with a single plan that contain the requested api should return that plan
   */
  public function testProductWhereSinglePlanHasAllApisReturnsThePlan(): void {
    $productBuilder = new MockProductNodeBuilder($this);
    $plan = array(
      'name' => $this->planName
    );

    $productBuilder->setApis([array(
      'name' => $this->apiRef
    )]);
    $productBuilder->setPlans([$plan]);

    $yaml = <<<EOT
      apis:
        $this->apiRef: 
          name: $this->apiRef
    EOT;
    $productBuilder->setData($yaml);
    
    $mockProduct = $productBuilder->build();
    $result = Product::getPlansThatContainApi($mockProduct, $this->apiRef);

    self::assertEquals(array($this->planName => $plan), $result);
  }

  /**
   * A product that contains a plan that does not contain the api should not return the plan
   */
  public function testProductWhereSinglePlanHasApiOmmittedReturnsEmptyArray(): void {
    $productBuilder = new MockProductNodeBuilder($this);
    $plan = array(
      'name' => $this->planName,
      'apis' => [$this->unrelatedApiRef => NULL]
    );

    $productBuilder->setApis([array(
      'name' => $this->apiRef
    ), array(
      'name' => $this->unrelatedApiRef
    )]);

    $productBuilder->setPlans([$plan]);

    $yaml = <<<EOT
      apis:
        $this->apiRef: 
          name: $this->apiRef
        $this->unrelatedApiRef: 
          name: $this->unrelatedApiRef
    EOT;
    $productBuilder->setData($yaml);
    
    $mockProduct = $productBuilder->build();

    $result = Product::getPlansThatContainApi($mockProduct, $this->apiRef);
    self::assertEquals([], $result);
  }

  /**
   * A product that contains multiple plans that don't contain the api should not return a plan
   */
  public function testProductWhereEveryPlanHasApiOmmittedReturnsEmptyArray(): void {
    $productBuilder = new MockProductNodeBuilder($this);
    $plan = array(
      'name' => $this->planName,
      'apis' => [$this->unrelatedApiRef => NULL]
    );

    $anotherPlan = array(
      'name' => $this->anotherPlanName,
      'apis' => [$this->unrelatedApiRef => NULL]
    );

    $productBuilder->setApis([array(
      'name' => $this->apiRef
    ),array(
      'name' => $this->unrelatedApiRef
    )]);

    $productBuilder->setPlans([$plan]);

    $yaml = <<<EOT
      apis:
        $this->apiRef: 
          name: $this->apiRef
        $this->unrelatedApiRef: 
          name: $this->unrelatedApiRef
    EOT;
    $productBuilder->setData($yaml);
    
    $mockProduct = $productBuilder->build();

    $result = Product::getPlansThatContainApi($mockProduct, $this->apiRef);
    self::assertEquals([], $result);
  }

  /**
   * A product that has multiple plans that all contain the api should return all plans
   */
  public function testProductWhereEveryPlanHasAllApisReturnsRelevantPlans(): void {
    $productBuilder = new MockProductNodeBuilder($this);
    $plan = array(
      'name' => $this->planName
    );
    $otherPlan = array(
      'name' => $this->anotherPlanName
    );

    $productBuilder->setApis([array(
      'name' => $this->apiRef
    )]);
    $productBuilder->setPlans([$plan, $otherPlan]);

    $yaml = <<<EOT
      apis:
        $this->apiRef: 
          name: $this->apiRef
    EOT;
    $productBuilder->setData($yaml);
    
    $mockProduct = $productBuilder->build();

    $result = Product::getPlansThatContainApi($mockProduct, $this->apiRef);
    self::assertEquals(array($this->planName => $plan, $this->anotherPlanName => $otherPlan), $result);
  }

  /**
   * A product that has multiple plans where only one contains the api should return that one plan
   */
  public function testProductWhereSomePlansHaveApiOmmittedReturnsRelevantPlans(): void {
    $productBuilder = new MockProductNodeBuilder($this);
    $plan = array(
      'name' => $this->planName
    );
    $otherPlan = array(
      'name' => $this->anotherPlanName,
      'apis' => [$this->unrelatedApiRef => NULL]
    );

    $productBuilder->setApis([array(
      'name' => $this->apiRef
    )]);
    $productBuilder->setPlans([$plan, $otherPlan]);

    $yaml = <<<EOT
      apis:
        $this->unrelatedApiRef: 
          name: $this->unrelatedApiRef
    EOT;
    $productBuilder->setData($yaml);
    
    $mockProduct = $productBuilder->build();

    $result = Product::getPlansThatContainApi($mockProduct, $this->apiRef);
    self::assertEquals(array($this->planName => $plan), $result);
  }
}
