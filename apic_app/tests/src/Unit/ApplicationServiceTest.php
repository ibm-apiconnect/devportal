<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2021, 2024
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\Tests\apic_app\Unit;

use Drupal\apic_app\Service\ApplicationService;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\ibm_apim\Unit\mocks\MockApplicationNodeBuilder;
use Drupal\Tests\ibm_apim\Unit\mocks\MockProductNodeBuilder;

class ApplicationServiceTest extends UnitTestCase {

  private $mockUserUtils;
  private $mockApimUtils;
  private $mockUtils;
  private $mockEventLogService;
  private $mockModuleHandler;
  private $mockCredentialsService;
  private $mockSerializer;
  private $mockSiteConfig;
  private $mockProductPlan;
  private $mockEntityTypeManager;
  private $dependencyInjection;

  private $applicationService;

  private $applicationBuilder;

  protected function setup(): void {
    $this->mockUserUtils = $this->createStub(\Drupal\ibm_apim\Service\UserUtils::class);
    $this->mockApimUtils = $this->createStub(\Drupal\ibm_apim\Service\ApimUtils::class);
    $this->mockUtils = $this->createStub(\Drupal\ibm_apim\Service\Utils::class);
    $this->mockEventLogService = $this->createStub(\Drupal\ibm_apim\Service\EventLogService::class);
    $this->mockModuleHandler = $this->createStub(\Drupal\Core\Extension\ModuleHandlerInterface::class);
    $this->mockCredentialsService = $this->createStub(\Drupal\apic_app\Service\CredentialsService::class);
    $this->mockSerializer = $this->createStub(\Symfony\Component\Serializer\Serializer::class);
    $this->mockSiteConfig = $this->createStub(\Drupal\ibm_apim\Service\SiteConfig::class);
    $this->mockProductPlan = $this->createStub(\Drupal\ibm_apim\Service\ProductPlan::class);
    $this->mockEntityTypeManager = $this->createStub(\Drupal\Core\Entity\EntityTypeManager::class);
    $this->dependencyInjection = [$this->mockUserUtils, $this->mockApimUtils, $this->mockUtils, $this->mockEventLogService, $this->mockModuleHandler, $this->mockCredentialsService, $this->mockSerializer, $this->mockSiteConfig, $this->mockProductPlan, $this->mockEntityTypeManager];

    $this->applicationService = new ApplicationService(...$this->dependencyInjection);

    $this->applicationBuilder = new MockApplicationNodeBuilder($this);
  }

  /**
   * Queries are hard to mock so this test at least verify that the result of the query is respected
   */
  public function testIsApplicationSubscribedTrue(): void {
    $application = $this->applicationBuilder->setApicUrl('url')->build();
    $productId = "5";
    $planName = "default plan";

    $subscriptions = [1, 2, 3];
    $this->mockEntityTypeManager->method('getStorage')->willReturn(new MockEntityTypeManager($this, $subscriptions));

    $result = $this->applicationService->isApplicationSubscribed($application, $productId, $planName);

    self::assertTrue($result);
  }

  /**
   * Queries are hard to mock so this test at least verify that the result of the query is respected
   */
  public function testIsApplicationSubscribedFalse(): void {
    $application = $this->applicationBuilder->setApicUrl('url')->build();
    $productId = "5";
    $planName = "default plan";

    $subscriptions = [];
    $this->mockEntityTypeManager->method('getStorage')->willReturn(new MockEntityTypeManager($this, $subscriptions));

    $result = $this->applicationService->isApplicationSubscribed($application, $productId, $planName);

    self::assertFalse($result);
  }
}

class MockQuery {
  private $queryResult;

  public function __construct($queryResult) {
    $this->queryResult = $queryResult;
  }

  public function condition(){}
  public function accessCheck(){
    return new MockQuery($this->queryResult);
  }
  public function execute() {
    return $this->queryResult;
  }
}

class MockEntityTypeManager {
  private $phpUnitRef;
  private $queryResult;

  public function __construct($phpUnitRef, $queryResult) {
    $this->phpUnitRef = $phpUnitRef;
    $this->queryResult = $queryResult;
  }

  public function getQuery(): MockQuery {
    return new MockQuery($this->queryResult);
  }

  public function load(){
    $productBuilder = new MockProductNodeBuilder($this->phpUnitRef);
    return $productBuilder->setApicUrl('url')->build();
  }
}
