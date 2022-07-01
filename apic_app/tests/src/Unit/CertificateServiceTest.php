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

namespace Drupal\Tests\apic_app\Unit;

use Drupal\apic_app\Service\CertificateService;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\ibm_apim\Service\Utils;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\Prophet;

/**
 * @coversDefaultClass \Drupal\apic_app\Service\CertificateService
 *
 * @group apic_app
 */
class CertificateServiceTest extends UnitTestCase {

  /**
   * @var \Prophecy\Prophet
   */
  private Prophet $prophet;

  /**
   * @var \Drupal\ibm_apim\Service\Utils
   */
  protected $utils;

  /**
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $ibm_apim_config;

  /**
   * @var \Drupal\Core\Config\ConfigFactory|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $configFactory;

  protected function setup(): void {
    $this->prophet = new Prophet();
    $this->utils = $this->prophet->prophesize(Utils::class);
    $this->configFactory = $this->prophet->prophesize(ConfigFactory::class);
    $this->ibm_apim_config = $this->prophet->prophesize(ImmutableConfig::class);
    $this->ibm_apim_config->get('certificate_strip_prefix')->willReturn(TRUE);
    $this->ibm_apim_config->get('certificate_strip_newlines')->willReturn(TRUE);
    $this->configFactory->get('ibm_apim.settings')->willReturn($this->ibm_apim_config->reveal());
    $this->utils->startsWith(Argument::not(Argument::containingString('-----BEGIN CERTIFICATE-----')), '-----BEGIN CERTIFICATE-----')
      ->willReturn(FALSE);
    $this->utils->startsWith(Argument::containingString('-----BEGIN CERTIFICATE-----'), '-----BEGIN CERTIFICATE-----')->willReturn(TRUE);
  }

  protected function tearDown(): void {
    $this->prophet->checkPredictions();
  }


  public function testCleanCertificate(): void {
    $certificate = '-----BEGIN CERTIFICATE-----MIIDOjCCAiICCQChLr4xILm0LzANBgkqhkiG9w0BAQsFADBfMQswCQYDVQQGEwJWTjEMMAoGA1UECAwDSENNMQwwCgYDVQQHDANIQ00xEDAOBgNVBAoMB1NlYXRlY2gxEDAOBgNVBAsMB1NlYXRlY2gxEDAOBgNVBAMMB1NlYXRlY2gwHhcNMTkwNTI5MTEzOTU3WhcNMjIwMjIzMTEzOTU3WjBfMQswCQYDVQQGEwJWTjEMMAoGA1UECAwDSENNMQwwCgYDVQQHDANIQ00xEDAOBgNVBAoMB1NlYXRlY2gxEDAOBgNVBAsMB1NlYXRlY2gxEDAOBgNVBAMMB1NlYXRlY2gwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQC+W9uXsjetI4M+PIQioNmh29u1zLifSX+NoTySVktfC/uvt9n2JCjkqZRTSktYR6YCP6ojIONezDTkFGOrgQsIByOZrg+vS1sxwIfINbfErdCeA9MQJ9zKL6wl1fDo6YRMatp21q5rIJ+k2gH+MQtXIq5axg05Msal45KnBF0ZPfVLw7p2hwRWd5+0VKJ6JhntSnyt8R1nW2fuArHXLaWdYlXul+S4Yow3pIjZ73FPaf4YcmwZgeSvHi4FhHzKrlAE1qOM0WIWUdfJvixCBVkRv0lXCgVfBozAH4K6o3laVIMoHu3Vu8V5GKPYr7p5b1bk+oYtaNyl+iTeyXU358MPfjLqaBpLiHwiHTy1cDSGJMOfJ7mUOj+ul417V+ApiwK3BcpXFhL2EM4owRvXT4sHuaYITqhTfVh4ArVUySSCoG7ClT9AyLJS+cXimM+l0UPLehVC5MflVa7BG5x2mgzbGsYzJ72HHPFHXpGDrhkPoGFrnqiBmmnAhvKcRZ/xk8hlMZO6KxJmIkZPG5BEmRM6SQoUVSpZtp7b+nBZNUnJaWEGEzuqJoVZIbdBpNqcpeKT3cY=-----END CERTIFICATE-----';
    $service = new CertificateService($this->utils->reveal(), $this->configFactory->reveal());
    $result = $service->cleanup($certificate);
    $desiredResult = 'MIIDOjCCAiICCQChLr4xILm0LzANBgkqhkiG9w0BAQsFADBfMQswCQYDVQQGEwJWTjEMMAoGA1UECAwDSENNMQwwCgYDVQQHDANIQ00xEDAOBgNVBAoMB1NlYXRlY2gxEDAOBgNVBAsMB1NlYXRlY2gxEDAOBgNVBAMMB1NlYXRlY2gwHhcNMTkwNTI5MTEzOTU3WhcNMjIwMjIzMTEzOTU3WjBfMQswCQYDVQQGEwJWTjEMMAoGA1UECAwDSENNMQwwCgYDVQQHDANIQ00xEDAOBgNVBAoMB1NlYXRlY2gxEDAOBgNVBAsMB1NlYXRlY2gxEDAOBgNVBAMMB1NlYXRlY2gwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQC+W9uXsjetI4M+PIQioNmh29u1zLifSX+NoTySVktfC/uvt9n2JCjkqZRTSktYR6YCP6ojIONezDTkFGOrgQsIByOZrg+vS1sxwIfINbfErdCeA9MQJ9zKL6wl1fDo6YRMatp21q5rIJ+k2gH+MQtXIq5axg05Msal45KnBF0ZPfVLw7p2hwRWd5+0VKJ6JhntSnyt8R1nW2fuArHXLaWdYlXul+S4Yow3pIjZ73FPaf4YcmwZgeSvHi4FhHzKrlAE1qOM0WIWUdfJvixCBVkRv0lXCgVfBozAH4K6o3laVIMoHu3Vu8V5GKPYr7p5b1bk+oYtaNyl+iTeyXU358MPfjLqaBpLiHwiHTy1cDSGJMOfJ7mUOj+ul417V+ApiwK3BcpXFhL2EM4owRvXT4sHuaYITqhTfVh4ArVUySSCoG7ClT9AyLJS+cXimM+l0UPLehVC5MflVa7BG5x2mgzbGsYzJ72HHPFHXpGDrhkPoGFrnqiBmmnAhvKcRZ/xk8hlMZO6KxJmIkZPG5BEmRM6SQoUVSpZtp7b+nBZNUnJaWEGEzuqJoVZIbdBpNqcpeKT3cY=';
    self::assertEquals($desiredResult, $result);
  }

  public function testStripNewlines(): void {
    $certificate = '-----BEGIN CERTIFICATE-----MIsfs3453453IDO\n\n \r\n-----END CERTIFICATE-----';
    $service = new CertificateService($this->utils->reveal(), $this->configFactory->reveal());
    $result = $service->stripNewlines($certificate);
    $desiredResult = '-----BEGIN CERTIFICATE-----MIsfs3453453IDO-----END CERTIFICATE-----';
    self::assertEquals($desiredResult, $result);
  }

  public function testStripNewlinesClean(): void {
    $certificate = '-----BEGIN CERTIFICATE-----MIsfs3453453IDO-----END CERTIFICATE-----';
    $service = new CertificateService($this->utils->reveal(), $this->configFactory->reveal());
    $result = $service->stripNewlines($certificate);
    $desiredResult = '-----BEGIN CERTIFICATE-----MIsfs3453453IDO-----END CERTIFICATE-----';
    self::assertEquals($desiredResult, $result);
  }

  public function testStripNewlinesNoPrefix(): void {
    $certificate = 'MIsfs345 \n\r\n3453IDO';
    $service = new CertificateService($this->utils->reveal(), $this->configFactory->reveal());
    $result = $service->stripNewlines($certificate);
    $desiredResult = 'MIsfs3453453IDO';
    self::assertEquals($desiredResult, $result);
  }

  public function testStripNewlinesCleanNoPrefix(): void {
    $certificate = 'MIsfs3453453IDO';
    $service = new CertificateService($this->utils->reveal(), $this->configFactory->reveal());
    $result = $service->stripNewlines($certificate);
    $desiredResult = 'MIsfs3453453IDO';
    self::assertEquals($desiredResult, $result);
  }

  public function testStripNewlinesTrailingNewlineNoPrefix(): void {
    $certificate = 'MIsfs3453453IDO\n';
    $service = new CertificateService($this->utils->reveal(), $this->configFactory->reveal());
    $result = $service->stripNewlines($certificate);
    $desiredResult = 'MIsfs3453453IDO';
    self::assertEquals($desiredResult, $result);
  }

  public function testStripPrefix(): void {
    $certificate = '-----BEGIN CERTIFICATE-----MIsfs3453453IDO-----END CERTIFICATE-----';
    $service = new CertificateService($this->utils->reveal(), $this->configFactory->reveal());
    $result = $service->stripPrefix($certificate);
    $desiredResult = 'MIsfs3453453IDO';
    self::assertEquals($desiredResult, $result);
  }

  public function testStripPrefixNewlines(): void {
    $certificate = '-----BEGIN CERTIFICATE-----MIsfs3\n \r\n453453IDO-----END CERTIFICATE-----';
    $service = new CertificateService($this->utils->reveal(), $this->configFactory->reveal());
    $result = $service->stripPrefix($certificate);
    $desiredResult = 'MIsfs3\n \r\n453453IDO';
    self::assertEquals($desiredResult, $result);
  }

  public function testStripPrefixTrailingNewLine(): void {
    $certificate = '-----BEGIN CERTIFICATE-----MIsfs3\n \r\n453453IDO-----END CERTIFICATE-----\n';
    $service = new CertificateService($this->utils->reveal(), $this->configFactory->reveal());
    $result = $service->stripPrefix($certificate);
    $desiredResult = 'MIsfs3\n \r\n453453IDO\n';
    self::assertEquals($desiredResult, $result);
  }

}
