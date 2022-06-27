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

use Drupal\Core\State\State;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\ApicType\UserRegistry;
use Drupal\ibm_apim\Service\ApicUserService;
use Drupal\ibm_apim\Service\UserRegistryService;
use Drupal\Tests\UnitTestCase;
use Prophecy\Prophet;
use Psr\Log\LoggerInterface;
use Drupal\Core\Messenger\Messenger;


/**
 * @coversDefaultClass \Drupal\ibm_apim\Service\ApicUserService
 *
 * @group ibm_apim
 */
class ApicUserServiceTest extends UnitTestCase {

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

  /**
   * @var \Drupal\ibm_apim\Service\UserRegistryService|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $userRegistryService;

  /**
   * @var \Drupal\Core\Messenger\Messenger|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $messenger;

  protected function setup(): void {
    $this->prophet = new Prophet();
    $this->logger = $this->prophet->prophesize(LoggerInterface::class);
    $this->state = $this->prophet->prophesize(State::class);
    $this->userRegistryService = $this->prophet->prophesize(UserRegistryService::class);
    $this->messenger = $this->prophet->prophesize(Messenger::class);
  }

  protected function tearDown(): void {
    $this->prophet->checkPredictions();
  }

  public function testGetUserAccountFields(): void {

    $user = $this->createUser();
    $registry = $this->createLurRegistry();

    $this->userRegistryService->get('/user/reg/url')->willReturn($registry);

    $service = new ApicUserService($this->logger->reveal(), $this->state->reveal(), $this->userRegistryService->reveal(), $this->messenger->reveal());

    $result = $service->getUserAccountFields($user);

    self::assertEquals('andre', $result['first_name']);
    self::assertEquals('andresson', $result['last_name']);
    self::assertEquals('Qwert123IsBadPassword!', $result['pass']);
    self::assertEquals('andre@example.com', $result['mail']);
    self::assertEquals('andreorg', $result['consumer_organization']);
    self::assertEquals('/user/url', $result['apic_url']);
    self::assertEquals('/user/reg/url', $result['apic_user_registry_url']);
    self::assertEquals('idp1', $result['apic_idp']);
    self::assertEquals('pending', $result['apic_state']);

  }

  public function testGetUserAccountFieldsNotUserManaged(): void {

    $user = $this->createUser('enabled');

    $registry = $this->createLdapRegistry();

    $this->userRegistryService->get('/user/reg/url')->willReturn($registry);

    $service = new ApicUserService($this->logger->reveal(), $this->state->reveal(), $this->userRegistryService->reveal(), $this->messenger->reveal());

    $result = $service->getUserAccountFields($user);

    self::assertEquals('andre', $result['first_name']);
    self::assertEquals('andresson', $result['last_name']);
    self::assertEquals('Qwert123IsBadPassword!', $result['pass']);
    self::assertEquals('andre@example.com', $result['mail']);
    self::assertEquals('andreorg', $result['consumer_organization']);
    self::assertEquals('/user/url', $result['apic_url']);
    self::assertEquals('/user/reg/url', $result['apic_user_registry_url']);
    self::assertEquals('idp1', $result['apic_idp']);
    self::assertEquals('enabled', $result['apic_state']);

  }

  public function testGetUserAccountFieldsUserManagedAndEnabled(): void {

    $user = $this->createUser('enabled');

    $registry = $this->createLurRegistry();

    $this->userRegistryService->get('/user/reg/url')->willReturn($registry);

    $service = new ApicUserService($this->logger->reveal(), $this->state->reveal(), $this->userRegistryService->reveal(), $this->messenger->reveal());

    $result = $service->getUserAccountFields($user);

    self::assertEquals('andre', $result['first_name']);
    self::assertEquals('andresson', $result['last_name']);
    self::assertEquals('Qwert123IsBadPassword!', $result['pass']);
    self::assertEquals('andre@example.com', $result['mail']);
    self::assertEquals('andreorg', $result['consumer_organization']);
    self::assertEquals('/user/url', $result['apic_url']);
    self::assertEquals('/user/reg/url', $result['apic_user_registry_url']);
    self::assertEquals('idp1', $result['apic_idp']);
    self::assertEquals('enabled', $result['apic_state']);

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
    $user->setPassword('Qwert123IsBadPassword!');
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
  private function createLurRegistry(): UserRegistry {
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
  private function createLdapRegistry(): UserRegistry {
    $registry = new UserRegistry();
    $registry->setUrl('/user/reg/url');
    $registry->setRegistryType('ldap');
    $registry->setUserManaged(FALSE);
    $registry->setIdentityProviders([['name' => 'idp2']]);
    return $registry;
  }


}
