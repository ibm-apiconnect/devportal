<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2020
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\Tests\ibm_apim\Unit;

use Drupal\Core\State\State;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\ApicType\UserRegistry;
use Drupal\ibm_apim\Service\ApicUserService;
use Drupal\ibm_apim\Service\UserRegistryService;
use Drupal\Tests\UnitTestCase;
use Prophecy\Prophet;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Drupal\ibm_apim\Service\ApicUserService
 *
 * @group ibm_apim
 */
class ApicUserServiceTest extends UnitTestCase {

  private $prophet;

  /*
   Dependencies of service.
   */
  protected $logger;
  protected $state;
  protected $userRegistryService;

  protected function setup() {
    $this->prophet = new Prophet();
    $this->logger = $this->prophet->prophesize(LoggerInterface::class);
    $this->state = $this->prophet->prophesize(State::class);
    $this->userRegistryService = $this->prophet->prophesize(UserRegistryService::class);
  }

  protected function tearDown() {
    $this->prophet->checkPredictions();
  }

  public function testGetUserAccountFields(): void {

    $user = $this->createUser();
    $registry = $this->createLurRegistry();

    $this->userRegistryService->get('/user/reg/url')->willReturn($registry);

    $service = new ApicUserService($this->logger->reveal(), $this->state->reveal(), $this->userRegistryService->reveal());

    $result = $service->getUserAccountFields($user);

    $this->assertEquals('andre', $result['first_name']);
    $this->assertEquals('andresson', $result['last_name']);
    $this->assertEquals('Qwert123', $result['pass']);
    $this->assertEquals('andre@example.com', $result['mail']);
    $this->assertEquals('andreorg', $result['consumer_organization']);
    $this->assertEquals('/user/url', $result['apic_url']);
    $this->assertEquals('/user/reg/url', $result['apic_user_registry_url']);
    $this->assertEquals('idp1', $result['apic_idp']);
    $this->assertEquals('pending', $result['apic_state']);

  }

  public function testGetUserAccountFieldsNotUserManaged(): void {

    $user = $this->createUser('enabled');

    $registry = $this->createLdapRegistry();

    $this->userRegistryService->get('/user/reg/url')->willReturn($registry);

    $service = new ApicUserService($this->logger->reveal(), $this->state->reveal(), $this->userRegistryService->reveal());

    $result = $service->getUserAccountFields($user);

    $this->assertEquals('andre', $result['first_name']);
    $this->assertEquals('andresson', $result['last_name']);
    $this->assertEquals('Qwert123', $result['pass']);
    $this->assertEquals('andre@example.com', $result['mail']);
    $this->assertEquals('andreorg', $result['consumer_organization']);
    $this->assertEquals('/user/url', $result['apic_url']);
    $this->assertEquals('/user/reg/url', $result['apic_user_registry_url']);
    $this->assertEquals('idp1', $result['apic_idp']);
    $this->assertEquals('enabled', $result['apic_state']);

  }

  public function testGetUserAccountFieldsUserManagedAndEnabled(): void {

    $user = $this->createUser('enabled');

    $registry = $this->createLurRegistry();

    $this->userRegistryService->get('/user/reg/url')->willReturn($registry);

    $service = new ApicUserService($this->logger->reveal(), $this->state->reveal(), $this->userRegistryService->reveal());

    $result = $service->getUserAccountFields($user);

    $this->assertEquals('andre', $result['first_name']);
    $this->assertEquals('andresson', $result['last_name']);
    $this->assertEquals('Qwert123', $result['pass']);
    $this->assertEquals('andre@example.com', $result['mail']);
    $this->assertEquals('andreorg', $result['consumer_organization']);
    $this->assertEquals('/user/url', $result['apic_url']);
    $this->assertEquals('/user/reg/url', $result['apic_user_registry_url']);
    $this->assertEquals('idp1', $result['apic_idp']);
    $this->assertEquals('enabled', $result['apic_state']);

  }


  /**
   * @param string $state
   *
   * @return \Drupal\ibm_apim\ApicType\ApicUser
   */
  private function createUser(string $state = 'pending'): ApicUser {

    $user = new ApicUser();
    $user->setApicUserRegistryUrl('/user/reg/url');
    $user->setFirstname('andre');
    $user->setLastname('andresson');
    $user->setUsername('andre');
    $user->setPassword('Qwert123');
    $user->setMail('andre@example.com');
    $user->setOrganization('andreorg');
    $user->setUrl('/user/url');
    $user->setState($state);
    $user->setApicIdp('idp1');
    return $user;
  }

  /**
   * @return \Drupal\ibm_apim\ApicType\UserRegistry
   */
  private function createLurRegistry(): \Drupal\ibm_apim\ApicType\UserRegistry {
    $registry = new UserRegistry();
    $registry->setUrl('/user/reg/url');
    $registry->setRegistryType('lur');
    $registry->setUserManaged(TRUE);
    $registry->setIdentityProviders([['name' => 'idp1']]);
    return $registry;
  }

  /**
   * @return \Drupal\ibm_apim\ApicType\UserRegistry
   */
  private function createLdapRegistry(): \Drupal\ibm_apim\ApicType\UserRegistry {
    $registry = new UserRegistry();
    $registry->setUrl('/user/reg/url');
    $registry->setRegistryType('ldap');
    $registry->setUserManaged(FALSE);
    $registry->setIdentityProviders([['name' => 'idp2']]);
    return $registry;
  }


}
