<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2024
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\Tests\auth_apic\Unit;

use Drupal\auth_apic\JWTToken;
use Drupal\auth_apic\Service\OidcRegistryService;
use Drupal\ibm_apim\ApicType\UserRegistry;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\Prophet;

/**
 * @coversDefaultClass \Drupal\auth_apic\Service\OidcRegistryService
 *
 * @group auth_apic
 */
class OidcRegistryServiceTest extends UnitTestCase {

  /**
   * @var \Prophecy\Prophet
   */
  private Prophet $prophet;

  /**
   * @var \Drupal\Core\State\StateInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $state;

  /**
   * @var \Prophecy\Prophecy\ObjectProphecy|\Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @var \Drupal\ibm_apim\Service\Utils|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $utils;

  /**
   * @var \Drupal\ibm_apim\Service\ApimUtils|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $apimUtils;


  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $moduleHandler;

  /**
   * @var Drupal\encrypt\EncryptServiceInterface
   */
  protected $encryption;

  /**
   * @var \Drupal\encrypt\EncryptionProfileManagerInterface
   */
  protected $profileManager;
  protected $encryptionProfile;
  /**
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $storeFactory;
  protected $time;

  /**
   * @var \Drupal\Core\TempStore\PrivateTempStore|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $store;

  protected function setup(): void {
    $this->prophet = new Prophet();
    $this->state = $this->prophet->prophesize('Drupal\Core\State\StateInterface');
    $this->logger = $this->prophet->prophesize('Psr\Log\LoggerInterface');
    $this->utils = $this->prophet->prophesize('Drupal\ibm_apim\Service\Utils');
    $this->apimUtils = $this->prophet->prophesize('Drupal\ibm_apim\Service\ApimUtils');
    $this->moduleHandler = $this->prophet->prophesize('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->time = $this->prophet->prophesize('\Drupal\Component\Datetime\Time');
    $this->encryption = $this->prophet->prophesize('\Drupal\encrypt\EncryptServiceInterface');
    $this->profileManager = $this->prophet->prophesize('\Drupal\encrypt\EncryptionProfileManagerInterface');
    $this->encryptionProfile = $this->prophet->prophesize('\Drupal\encrypt\EncryptionProfileInterface');
    $this->storeFactory = $this->prophet->prophesize('Drupal\Core\TempStore\PrivateTempStoreFactory');
    $this->store = $this->prophet->prophesize('Drupal\Core\TempStore\PrivateTempStore');
    $this->storeFactory->get('auth_apic_storage')->willReturn($this->store);
  }

  protected function tearDown(): void {
    $this->prophet->checkPredictions();
  }

  /**
   * Positive test to get oidc metadata.
   */
  public function testValidOidcMetadata(): void {

    $oidc_registry = new UserRegistry();
    $oidc_registry->setRegistryType('oidc');
    $oidc_registry->setProviderType('google');
    // TODO: need to add identity provider to get realm
    // see https://github.ibm.com/velox/devportal/issues/3726
    // $oidc_registry->setIdentityProviders(array(array('name'=>'idp1')));

    $this->state->get('ibm_apim.site_client_id')->willReturn('iamaclientid');
    $this->utils->base64_url_encode(Argument::any())->willReturn('base64encodedstate');
    $this->apimUtils->getHostUrl()->willReturn('https://portal.example.com');
    $this->apimUtils->createFullyQualifiedUrl('/consumer-api/oauth2/authorize')
      ->willReturn('https://mgmt.example.com/consumer-api/oauth2/authorize');

    $service = $this->getServiceUnderTest();
    $this->profileManager->getEncryptionProfile(Argument::any())->willReturn($this->encryptionProfile->reveal());
    $response = $service->getOidcMetadata($oidc_registry);


    $this->logger->warning(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    self::assertNotNull($response, 'unexpected NULL response when gathering oidc metadata.');
    self::assertNotNull($response['az_url'], 'unexpected NULL az_url when gathering oidc metadata.');
    self::assertNotNull($response['image'], 'unexpected NULL image when gathering oidc metadata.');


    self::assertMatchesRegularExpression('/^https:\/\/mgmt.example.com\/consumer-api\/oauth2\/authorize?/', $response['az_url'], 'Expected start not found in authorization url.');
    self::assertMatchesRegularExpression('/client_id=iamaclientid/', $response['az_url'], 'Expected client_id query parameter not found in authorization url.');
    self::assertMatchesRegularExpression('/state=base64encodedstate/', $response['az_url'], 'Expected state query parameter not found in authorization url.');
    self::assertMatchesRegularExpression('/redirect_uri=https:\/\/portal.example.com\/test\/env/', $response['az_url'], 'Expected redirect_uri query parameter not found in authorization url.');
    // TODO: note no realm ... see comment above.
    self::assertMatchesRegularExpression('/realm=/', $response['az_url'], 'Expected realm query parameter not found in authorization url.');
    self::assertMatchesRegularExpression('/esponse_type=code/', $response['az_url'], 'Expected response_code query parameter not found in authorization url.');

    //self::assertEquals($response['az_url'], 'https://mgmt.example.com/consumer-api/oauth2/authorize?client_id=iamaclientid&state=base64encodedstate&redirect_uri=http://portal.example.com/test/env&realm=&response_type=code', 'Unexpected authorization url.');
    self::assertStringStartsWith('<svg ', $response['image']['html'], 'Unexpected image.');

  }

  /**
   * Valid - test with invitation object
   */
  public function testValidOidcMetadataWithInvitationObject(): void {

    $oidc_registry = new UserRegistry();
    $oidc_registry->setRegistryType('oidc');
    $oidc_registry->setProviderType('google');

    $invitation_object = new JWTToken();
    $invitation_object->setDecodedJwt('blahdeblah');
    $this->profileManager->getEncryptionProfile(Argument::any())->willReturn($this->encryptionProfile->reveal());

    $this->state->get('ibm_apim.site_client_id')->willReturn('iamaclientid');
    $this->utils->base64_url_encode(Argument::any())->willReturn('base64encodedstate');
    $this->apimUtils->getHostUrl()->willReturn('http://portal.example.com');
    $this->apimUtils->createFullyQualifiedUrl('/consumer-api/oauth2/authorize')
      ->willReturn('http://mgmt.example.com/consumer-api/oauth2/authorize');

    $service = $this->getServiceUnderTest();
    $response = $service->getOidcMetadata($oidc_registry, $invitation_object);

    $this->logger->warning(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    self::assertNotNull($response, 'unexpected NULL response when gathering oidc metadata.');
    self::assertNotNull($response['az_url'], 'unexpected NULL az_url when gathering oidc metadata.');
    self::assertNotNull($response['image'], 'unexpected NULL image when gathering oidc metadata.');

    self::assertMatchesRegularExpression('/&token=blahdeblah$/', $response['az_url'], 'Expected token query parameter not found in authorization url.');

  }


  /**
   * Invalid - non oidc registry.
   */
  public function testNonOidcRegistry(): void {
    $non_oidc_registry = new UserRegistry();
    $non_oidc_registry->setRegistryType('lur');

    $this->logger->warning('attempt to get metadata from non-oidc registry')->shouldBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = $this->getServiceUnderTest();
    $response = $service->getOidcMetadata($non_oidc_registry);

    self::assertNull($response, 'Excepted null response when not oidc registry.');

  }

  /**
   * Invalid - no client id
   */
  public function testNoClientId(): void {
    $registry = new UserRegistry();
    $registry->setRegistryType('oidc');

    $this->logger->warning('unable to retrieve site client id to build oidc authentication url')->shouldBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $this->state->get('ibm_apim.site_client_id')->willReturn(NULL);

    $service = $this->getServiceUnderTest();
    $response = $service->getOidcMetadata($registry);

    self::assertNull($response['az_url'], 'Excepted null az_url when not oidc registry.');

  }

  /**
   * @return \Drupal\auth_apic\Service\OidcRegistryService
   */
  private function getServiceUnderTest(): \Drupal\auth_apic\Service\OidcRegistryService {
    return new OidcRegistryService($this->state->reveal(),
      $this->logger->reveal(),
      $this->utils->reveal(),
      $this->apimUtils->reveal(),
      $this->moduleHandler->reveal(),
      $this->storeFactory->reveal(),
      $this->time->reveal(),
      $this->encryption->reveal(),
      $this->profileManager->reveal()
    );
  }

}
