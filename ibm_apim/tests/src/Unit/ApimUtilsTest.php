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

namespace Drupal\Tests\ibm_apim\Unit;

use Drupal\ibm_apim\Service\ApimUtils;
use Drupal\Tests\UnitTestCase;
use Prophecy\Prophet;

/**
 * @coversDefaultClass \Drupal\ibm_apim\Service\ApimUtils
 *
 * @group ibm_apim
 */
class ApimUtilsTest extends UnitTestCase {

  private $prophet;

  /*
   Dependencies of ApimUtils.
   */
  protected $logger;

  protected $siteConfig;

  protected function setup() {
    $this->prophet = new Prophet();
    $this->logger = $this->prophet->prophesize(\Psr\Log\LoggerInterface::class);
    $this->siteConfig = $this->prophet->prophesize(\Drupal\ibm_apim\Service\SiteConfig::class);
  }

  protected function tearDown() {
    $this->prophet->checkPredictions();
  }


  /* CREATE */
  public function testCreateFullyQualifiedUrl(): void {

    $this->siteConfig->getApimHost()->willReturn('https://hostname');
    $utils = new ApimUtils($this->logger->reveal(), $this->siteConfig->reveal());

    $result = $utils->createFullyQualifiedUrl('/url/path/only');

    $this->assertEquals('https://hostname/url/path/only', $result, 'Unexpected fully qualified url');

  }

  public function testCreateWithConsumerApiPart(): void {

    $this->siteConfig->getApimHost()->willReturn('https://hostname/consumer-api');
    $utils = new ApimUtils($this->logger->reveal(), $this->siteConfig->reveal());

    $result = $utils->createFullyQualifiedUrl('/consumer-api/url/path/only');

    $this->assertEquals('https://hostname/consumer-api/url/path/only', $result, 'Unexpected fully qualified url');

  }

  public function testCreateWithConsumerApiInHostname(): void {

    $this->siteConfig->getApimHost()->willReturn('https://consumer-api-hostname/consumer-api');
    $utils = new ApimUtils($this->logger->reveal(), $this->siteConfig->reveal());

    $result = $utils->createFullyQualifiedUrl('/url/path/only');

    $this->assertEquals('https://consumer-api-hostname/url/path/only', $result, 'Unexpected fully qualified url');

  }

  public function testCreateWithAlreadyFullyQualified(): void {

    $this->siteConfig->getApimHost()->willReturn('https://hostname');
    $utils = new ApimUtils($this->logger->reveal(), $this->siteConfig->reveal());

    $result = $utils->createFullyQualifiedUrl('https://hostname/url/path/only');

    $this->assertEquals('https://hostname/url/path/only', $result, 'Unexpected fully qualified url');

  }


  /* REMOVE */
  public function testRemoveFullyQualifiedUrl(): void {

    $this->siteConfig->getApimHost()->willReturn('https://hostname');
    $utils = new ApimUtils($this->logger->reveal(), $this->siteConfig->reveal());

    $result = $utils->removeFullyQualifiedUrl('https://hostname/url/path/only');

    $this->assertEquals('/url/path/only', $result, 'Unexpected stripped url');

  }

  public function testRemoveWithConsumerApiSuffix(): void {

    $this->siteConfig->getApimHost()->willReturn('https://hostname/consumer-api');
    $utils = new ApimUtils($this->logger->reveal(), $this->siteConfig->reveal());

    $result = $utils->removeFullyQualifiedUrl('https://hostname/consumer-api/url/path/only');

    $this->assertEquals('/consumer-api/url/path/only', $result, 'Unexpected stripped url');

  }

  public function testValidRemoveWithConsumerApiInUrlOnly(): void {

    $this->siteConfig->getApimHost()->willReturn('https://hostname');
    $utils = new ApimUtils($this->logger->reveal(), $this->siteConfig->reveal());

    $result = $utils->removeFullyQualifiedUrl('https://hostname/consumer-api/url/path/only');

    $this->assertEquals('/consumer-api/url/path/only', $result, 'Unexpected stripped url');

  }

  public function testRemoveWithConsumerApiInHostname(): void {

    $this->siteConfig->getApimHost()->willReturn('https://consumer-apic-hostname');
    $utils = new ApimUtils($this->logger->reveal(), $this->siteConfig->reveal());

    $result = $utils->removeFullyQualifiedUrl('https://consumer-apic-hostname/consumer-api/url/path/only');

    $this->assertEquals('/consumer-api/url/path/only', $result, 'Unexpected stripped url');

  }

  public function testRemoveWithAlreadyStrippedUrl(): void {

    $this->siteConfig->getApimHost()->willReturn('https://hostname');
    $utils = new ApimUtils($this->logger->reveal(), $this->siteConfig->reveal());

    $result = $utils->removeFullyQualifiedUrl('/url/path/only');

    $this->assertEquals('/url/path/only', $result, 'Unexpected stripped url');

  }

}
