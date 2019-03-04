<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2019
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

  private $prophet;

  /*
   Dependencies of service.
   */
  protected $logger;

  protected $state;

  protected function setup() {
    $this->prophet = new Prophet();
    $this->logger = $this->prophet->prophesize(LoggerInterface::class);
    $this->state = $this->prophet->prophesize(State::class);
  }

  protected function tearDown() {
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

    $this->assertEquals('/user/registry/url', $result->getUrl(), 'Unexpected default registry url');

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

    $this->assertEquals('/fallback/url', $result->getUrl(), 'Unexpected default registry url');

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

    $this->assertEquals('/lur/one', $result->getUrl(), 'Unexpected default registry url');

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

    $this->assertEquals('/ldap/one', $result->getUrl(), 'Unexpected default registry url');

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

    $this->assertNull($result, 'Unexpected default registry url');

  }

  private function createLURReg($url): UserRegistry {
    $reg = new UserRegistry();
    $reg->setUrl($url);
    $reg->setUserManaged(TRUE);
    return $reg;
  }

  private function createLDAPReg($url): UserRegistry {
    $reg = new UserRegistry();
    $reg->setUrl($url);
    $reg->setUserManaged(FALSE);
    return $reg;
  }


}
