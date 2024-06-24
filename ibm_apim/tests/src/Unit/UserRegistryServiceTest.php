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

namespace Drupal\Tests\ibm_apim\Unit;

use Drupal\Core\State\State;
use Drupal\ibm_apim\ApicType\UserRegistry;
use Drupal\ibm_apim\Service\UserRegistryService;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\Prophet;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Drupal\ibm_apim\Service\UserRegistryService
 *
 * @group ibm_apim
 */
class UserRegistryServiceTest extends UnitTestCase {

  /**
   * @var \Prophecy\Prophet
   */
  private Prophet $prophet;

  /**
   * @var \Prophecy\Prophecy\ObjectProphecy|\Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @var \Drupal\Core\State\State|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $state;

  protected function setup(): void {
    $this->prophet = new Prophet();
    $this->logger = $this->prophet->prophesize(LoggerInterface::class);
    $this->state = $this->prophet->prophesize(State::class);
  }

  protected function tearDown(): void {
    $this->prophet->checkPredictions();
  }

  /**
   * @throws \Exception
   */
  public function testGetDefaultRegistry(): void {

    $this->state->get('ibm_apim.default_user_registry')->willReturn('/user/registry/url')->shouldBeCalled();
    $this->state->set('ibm_apim.default_user_registry', Argument::any())->shouldNotBeCalled();

    $registries = ['/user/registry/url' => $this->createLURReg('/user/registry/url')];
    $this->state->get('ibm_apim.user_registries')->willReturn($registries)->shouldBeCalled();

    $this->logger->debug(Argument::any())->shouldNotBeCalled();
    $this->logger->warning(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = new UserRegistryService($this->state->reveal(), $this->logger->reveal());

    $result = $service->getDefaultRegistry();

    self::assertEquals('/user/registry/url', $result->getUrl(), 'Unexpected default registry url');

  }

  /**
   * @throws \Exception
   */
  public function testGetDefaultFallback(): void {

    $this->state->get('ibm_apim.default_user_registry')->willReturn(NULL)->shouldBeCalled();
    $this->state->set('ibm_apim.default_user_registry', '/fallback/url')->shouldBeCalled();

    $registries = ['/fallback/url' => $this->createLURReg('/fallback/url')];
    $this->state->get('ibm_apim.user_registries')->willReturn($registries)->shouldBeCalled();

    $this->logger->debug('Unexpected result while retrieving default registry - none set.')->shouldBeCalled();
    $this->logger->warning(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = new UserRegistryService($this->state->reveal(), $this->logger->reveal());

    $result = $service->getDefaultRegistry();

    self::assertEquals('/fallback/url', $result->getUrl(), 'Unexpected default registry url');

  }

  /**
   * @throws \Exception
   */
  public function testGetDefaultFallbackUserManaged(): void {

    $this->state->get('ibm_apim.default_user_registry')->willReturn(NULL)->shouldBeCalled();
    $this->state->set('ibm_apim.default_user_registry', '/lur/one')->shouldBeCalled();

    $registries = [
      '/ldap/one' => $this->createLDAPReg('/ldap/one'),
      '/ldap/two' => $this->createLDAPReg('/ldap/two'),
      '/lur/one' => $this->createLURReg('/lur/one'),
    ];
    $this->state->get('ibm_apim.user_registries')->willReturn($registries)->shouldBeCalled();

    $this->logger->debug('Unexpected result while retrieving default registry - none set.')->shouldBeCalled();
    $this->logger->warning(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = new UserRegistryService($this->state->reveal(), $this->logger->reveal());

    $result = $service->getDefaultRegistry();

    self::assertEquals('/lur/one', $result->getUrl(), 'Unexpected default registry url');

  }

  /**
   * @throws \Exception
   */
  public function testGetDefaultFallbackNoUserManaged(): void {

    $this->state->get('ibm_apim.default_user_registry')->willReturn(NULL)->shouldBeCalled();
    $this->state->set('ibm_apim.default_user_registry', '/ldap/one')->shouldBeCalled();

    $registries = [
      '/ldap/one' => $this->createLDAPReg('/ldap/one'),
      '/ldap/two' => $this->createLDAPReg('/ldap/two'),
    ];
    $this->state->get('ibm_apim.user_registries')->willReturn($registries)->shouldBeCalled();

    $this->logger->debug('Unexpected result while retrieving default registry - none set.')->shouldBeCalled();
    $this->logger->warning(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = new UserRegistryService($this->state->reveal(), $this->logger->reveal());

    $result = $service->getDefaultRegistry();

    self::assertEquals('/ldap/one', $result->getUrl(), 'Unexpected default registry url');

  }

  /**
   * @throws \Exception
   */
  public function testGetDefaultNoRegistries(): void {

    $this->state->get('ibm_apim.default_user_registry')->willReturn(NULL)->shouldBeCalled();
    $this->state->set('ibm_apim.default_user_registry', Argument::any())->shouldNotBeCalled();

    $this->state->get('ibm_apim.user_registries')->willReturn(NULL)->shouldBeCalled();

    $this->logger->debug('Unexpected result while retrieving default registry - none set.')->shouldBeCalled();
    $this->logger->warning('No registries available when trying to calculate the default.')->shouldBeCalled();
    $this->logger->warning('Found no user registries in the catalog config. Potentially missing data from APIM.')->shouldBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = new UserRegistryService($this->state->reveal(), $this->logger->reveal());

    $result = $service->getDefaultRegistry();

    self::assertNull($result, 'Unexpected default registry url');

  }

  // updateAll tests - check we handle either an array of arrays (i.e. from webhook/snapshot) or array of objects.
  public function testUpdateAllAsArrays(): void {


    $urs = [
      '/reg/1' => $this->createRegistryAsArray('1'),
      '/reg/2' => $this->createRegistryAsArray('2'),
    ];

    $expected_ur1 = new UserRegistry();
    $expected_ur1->setValues($urs['/reg/1']);

    $expected_ur2 = new UserRegistry();
    $expected_ur2->setValues($urs['/reg/2']);

    $expected = [
      '/reg/1' => $expected_ur1,
      '/reg/2' => $expected_ur2,
    ];

    $this->state->set('ibm_apim.user_registries', $expected)->shouldBeCalled();

    $service = new UserRegistryService($this->state->reveal(), $this->logger->reveal());
    $success = $service->updateAll($urs);
    self::assertTrue($success);

  }


  public function testUpdateAllAsObjects(): void {

    $urs = [
      '/reg/1' => $this->createLURReg('/reg/1'),
      '/reg/2' => $this->createLURReg('/reg/2'),
    ];

    $this->state->set('ibm_apim.user_registries', $urs)->shouldBeCalled();

    $service = new UserRegistryService($this->state->reveal(), $this->logger->reveal());
    $success = $service->updateAll($urs);
    self::assertTrue($success);

  }

  /**
   * @param $url
   *
   * @return \Drupal\ibm_apim\ApicType\UserRegistry
   */
  private function createLURReg($url): UserRegistry {
    $reg = new UserRegistry();
    $reg->setUrl($url);
    $reg->setUserManaged(TRUE);
    return $reg;
  }

  /**
   * @param $url
   *
   * @return \Drupal\ibm_apim\ApicType\UserRegistry
   */
  private function createLDAPReg($url): UserRegistry {
    $reg = new UserRegistry();
    $reg->setUrl($url);
    $reg->setUserManaged(FALSE);
    return $reg;
  }

  /**
   * @param $id
   *
   * @return array
   */
  private function createRegistryAsArray($id): array {
    return [
      'id' => $id,
      'name' => 'lur' . $id,
      'url' => '/reg/' . $id,
      'title' => $id,
      'summary' => $id,
      'registry_type' => 'lur',
      'user_managed' => TRUE,
      'user_registry_managed' => FALSE,
      'onboarding' => TRUE,
      'case_sensitive' => TRUE,
      'identity_providers' => [],
    ];
  }


}
