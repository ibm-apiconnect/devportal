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

  private $prophet;

  /*
   Dependencies of OidcRegistryService.
   */
  protected $state;
  protected $logger;
  protected $utils;
  protected $apimUtils;
  protected $oidcStateService;

  protected function setup() {
    $this->prophet = new Prophet();
    $this->state = $this->prophet->prophesize('Drupal\Core\State\StateInterface');
    $this->logger = $this->prophet->prophesize('Psr\Log\LoggerInterface');
    $this->utils = $this->prophet->prophesize('Drupal\ibm_apim\Service\Utils');
    $this->apimUtils = $this->prophet->prophesize('Drupal\ibm_apim\Service\ApimUtils');
    $this->oidcStateService = $this->prophet->prophesize('Drupal\auth_apic\Service\Interfaces\OidcStateServiceInterface');
  }

  protected function tearDown() {
    $this->prophet->checkPredictions();
  }

  /**
   * Positive test to get oidc metadata.
   */
  public function testValidOidcMetadata() {

    $oidc_registry = new UserRegistry();
    $oidc_registry->setRegistryType('oidc');
    $oidc_registry->setProviderType('google');
    // TODO: need to add identity provider to get realm
    // see https://github.ibm.com/apimesh/devportal/issues/3726
    // $oidc_registry->setIdentityProviders(array(array('name'=>'idp1')));

    $this->state->get('ibm_apim.site_client_id')->willReturn('iamaclientid');
    $this->utils->base64_url_encode(Argument::any())->willReturn('base64encodedstate');
    $this->apimUtils->getHostUrl()->willReturn('https://portal.example.com');
    $this->apimUtils->createFullyQualifiedUrl('/consumer-api/oauth2/authorize')->willReturn('https://mgmt.example.com/consumer-api/oauth2/authorize');

    $service = $this->getServiceUnderTest();
    $response = $service->getOidcMetadata($oidc_registry);

    $this->logger->warning(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();
    $this->oidcStateService->store(Argument::any())->shouldBeCalled();

    $this->assertNotNull($response, 'unexpected NULL response when gathering oidc metadata.');
    $this->assertNotNull($response['az_url'], 'unexpected NULL az_url when gathering oidc metadata.');
    $this->assertNotNull($response['image'], 'unexpected NULL image when gathering oidc metadata.');


    $this->assertRegExp('/^https:\/\/mgmt.example.com\/consumer-api\/oauth2\/authorize?/', $response['az_url'], 'Expected start not found in authorization url.');
    $this->assertRegExp('/client_id=iamaclientid/', $response['az_url'], 'Expected client_id query parameter not found in authorization url.');
    $this->assertRegExp('/state=base64encodedstate/', $response['az_url'], 'Expected state query parameter not found in authorization url.');
    $this->assertRegExp('/redirect_uri=https:\/\/portal.example.com\/test\/env/', $response['az_url'], 'Expected redirect_uri query parameter not found in authorization url.');
    // TODO: note no realm ... see comment above.
    $this->assertRegExp('/realm=/', $response['az_url'], 'Expected realm query parameter not found in authorization url.');
    $this->assertRegExp('/esponse_type=code/', $response['az_url'], 'Expected response_code query parameter not found in authorization url.');

    //$this->assertEquals($response['az_url'], 'https://mgmt.example.com/consumer-api/oauth2/authorize?client_id=iamaclientid&state=base64encodedstate&redirect_uri=http://portal.example.com/test/env&realm=&response_type=code', 'Unexpected authorization url.');
    $this->assertStringStartsWith('<svg ', $response['image'], 'Unexpected image.');

  }

  /**
   * Valid - test with invitation object
   */
  public function testValidOidcMetadataWithInvitationObject() {

    $oidc_registry = new UserRegistry();
    $oidc_registry->setRegistryType('oidc');
    $oidc_registry->setProviderType('google');

    $invitation_object = new JWTToken();
    $invitation_object->setDecodedJwt('blahdeblah');

    $this->state->get('ibm_apim.site_client_id')->willReturn('iamaclientid');
    $this->utils->base64_url_encode(Argument::any())->willReturn('base64encodedstate');
    $this->apimUtils->getHostUrl()->willReturn('http://portal.example.com');
    $this->apimUtils->createFullyQualifiedUrl('/consumer-api/oauth2/authorize')->willReturn('http://mgmt.example.com/consumer-api/oauth2/authorize');

    $service = $this->getServiceUnderTest();
    $response = $service->getOidcMetadata($oidc_registry, $invitation_object);

    $this->logger->warning(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();
    $this->oidcStateService->store(Argument::any())->shouldBeCalled();

    $this->assertNotNull($response, 'unexpected NULL response when gathering oidc metadata.');
    $this->assertNotNull($response['az_url'], 'unexpected NULL az_url when gathering oidc metadata.');
    $this->assertNotNull($response['image'], 'unexpected NULL image when gathering oidc metadata.');

    $this->assertRegExp('/&token=blahdeblah$/', $response['az_url'], 'Expected token query parameter not found in authorization url.');
    $this->assertStringStartsWith('<svg ', $response['image'], 'Unexpected image.');

  }


  /**
   * Invalid - non oidc registry.
   */
  public function testNonOidcRegistry() {
    $non_oidc_registry = new UserRegistry();
    $non_oidc_registry->setRegistryType('lur');

    $this->logger->warning('attempt to get metadata from non-oidc registry')->shouldBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = $this->getServiceUnderTest();
    $response = $service->getOidcMetadata($non_oidc_registry);

    $this->assertNull($response, 'Excepted null response when not oidc registry.');

  }

  /**
   * Invalid - no client id
   */
  public function testNoClientId() {
    $registry = new UserRegistry();
    $registry->setRegistryType('oidc');

    $this->logger->warning('unable to retrieve site client id to build oidc authentication url')->shouldBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $this->state->get('ibm_apim.site_client_id')->willReturn(NULL);

    $service = $this->getServiceUnderTest();
    $response = $service->getOidcMetadata($registry);

    $this->assertNull($response['az_url'], 'Excepted null az_url when not oidc registry.');

  }

  /**
   * @return \Drupal\auth_apic\Service\OidcRegistryService
   */
  private function getServiceUnderTest(): \Drupal\auth_apic\Service\OidcRegistryService {
    $service = new OidcRegistryService($this->state->reveal(),
      $this->logger->reveal(),
      $this->utils->reveal(),
      $this->apimUtils->reveal(),
      $this->oidcStateService->reveal());
    return $service;
  }


}
