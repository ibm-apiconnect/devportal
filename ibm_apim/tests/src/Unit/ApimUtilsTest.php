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
use Drupal\ibm_apim\Service\SiteConfig;
use Drupal\Tests\UnitTestCase;
use Prophecy\Prophet;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Drupal\ibm_apim\Service\ApimUtils
 *
 * @group ibm_apim
 */
class ApimUtilsTest extends UnitTestCase {

  /**
   * @var \Prophecy\Prophet
   */
  private Prophet $prophet;

  /*
   Dependencies of ApimUtils.
   */
  /**
   * @var \Psr\Log\LoggerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $logger;

  /**
   * @var \Drupal\ibm_apim\Service\SiteConfig|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $siteConfig;

  protected function setup(): void {
    $this->prophet = new Prophet();
    $this->logger = $this->prophet->prophesize(LoggerInterface::class);
    $this->siteConfig = $this->prophet->prophesize(SiteConfig::class);
  }

  protected function tearDown(): void {
    $this->prophet->checkPredictions();
  }


  /* CREATE */
  public function testCreateFullyQualifiedUrl(): void {

    $this->siteConfig->getApimHost()->willReturn('https://hostname');
    $utils = new ApimUtils($this->logger->reveal(), $this->siteConfig->reveal());

    $result = $utils->createFullyQualifiedUrl('/url/path/only');

    self::assertEquals('https://hostname/url/path/only', $result, 'Unexpected fully qualified url');

  }

  public function testCreateWithConsumerApiPart(): void {

    $this->siteConfig->getApimHost()->willReturn('https://hostname/consumer-api');
    $utils = new ApimUtils($this->logger->reveal(), $this->siteConfig->reveal());

    $result = $utils->createFullyQualifiedUrl('/consumer-api/url/path/only');

    self::assertEquals('https://hostname/consumer-api/url/path/only', $result, 'Unexpected fully qualified url');

  }

  public function testCreateWithConsumerApiInHostname(): void {

    $this->siteConfig->getApimHost()->willReturn('https://consumer-api-hostname/consumer-api');
    $utils = new ApimUtils($this->logger->reveal(), $this->siteConfig->reveal());

    $result = $utils->createFullyQualifiedUrl('/url/path/only');

    self::assertEquals('https://consumer-api-hostname/url/path/only', $result, 'Unexpected fully qualified url');

  }

  public function testCreateWithAlreadyFullyQualified(): void {

    $this->siteConfig->getApimHost()->willReturn('https://hostname');
    $utils = new ApimUtils($this->logger->reveal(), $this->siteConfig->reveal());

    $result = $utils->createFullyQualifiedUrl('https://hostname/url/path/only');

    self::assertEquals('https://hostname/url/path/only', $result, 'Unexpected fully qualified url');

  }


  /* REMOVE */
  public function testRemoveFullyQualifiedUrl(): void {

    $this->siteConfig->getApimHost()->willReturn('https://hostname');
    $utils = new ApimUtils($this->logger->reveal(), $this->siteConfig->reveal());

    $result = $utils->removeFullyQualifiedUrl('https://hostname/url/path/only');

    self::assertEquals('/url/path/only', $result, 'Unexpected stripped url');

  }

  public function testRemoveWithConsumerApiSuffix(): void {

    $this->siteConfig->getApimHost()->willReturn('https://hostname/consumer-api');
    $utils = new ApimUtils($this->logger->reveal(), $this->siteConfig->reveal());

    $result = $utils->removeFullyQualifiedUrl('https://hostname/consumer-api/url/path/only');

    self::assertEquals('/consumer-api/url/path/only', $result, 'Unexpected stripped url');

  }

  public function testValidRemoveWithConsumerApiInUrlOnly(): void {

    $this->siteConfig->getApimHost()->willReturn('https://hostname');
    $utils = new ApimUtils($this->logger->reveal(), $this->siteConfig->reveal());

    $result = $utils->removeFullyQualifiedUrl('https://hostname/consumer-api/url/path/only');

    self::assertEquals('/consumer-api/url/path/only', $result, 'Unexpected stripped url');

  }

  public function testRemoveWithConsumerApiInHostname(): void {

    $this->siteConfig->getApimHost()->willReturn('https://consumer-apic-hostname');
    $utils = new ApimUtils($this->logger->reveal(), $this->siteConfig->reveal());

    $result = $utils->removeFullyQualifiedUrl('https://consumer-apic-hostname/consumer-api/url/path/only');

    self::assertEquals('/consumer-api/url/path/only', $result, 'Unexpected stripped url');

  }

  public function testRemoveWithAlreadyStrippedUrl(): void {

    $this->siteConfig->getApimHost()->willReturn('https://hostname');
    $utils = new ApimUtils($this->logger->reveal(), $this->siteConfig->reveal());

    $result = $utils->removeFullyQualifiedUrl('/url/path/only');

    self::assertEquals('/url/path/only', $result, 'Unexpected stripped url');

  }

}
