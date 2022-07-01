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

namespace Drupal\Tests\ibm_apim\Unit;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\State\State;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\Tests\auth_apic\Unit\Base\AuthApicTestBaseClass;
use Drupal\user\UserStorageInterface;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \Drupal\ibm_apim\Service\Utils
 *
 * @group ibm_apim
 */
class UserUtilsTest extends AuthApicTestBaseClass {

  /*
    Dependencies of UserUtils.
  */

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $current_user;

  /**
   * @var \Drupal\Core\State\State|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $state;

  /**
   * @var \Prophecy\Prophecy\ObjectProphecy|\Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entity_type_manager;

  /**
   * @var \Drupal\Core\TempStore\PrivateTempStore|\Prophecy\Prophecy\ObjectProphecy
   */
  private $sessionStore;

  /**
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $tempStore;

  /**
   * @var \Drupal\user\UserStorageInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  private $userStorage;

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function setup(): void {

    parent::setup();
    $this->current_user = $this->prophet->prophesize(AccountProxyInterface::class);
    $this->tempStore = $this->prophet->prophesize(PrivateTempStoreFactory::class);
    $this->state = $this->prophet->prophesize(State::class);
    $this->logger = $this->prophet->prophesize(LoggerInterface::class);
    $this->entity_type_manager = $this->prophet->prophesize(EntityTypeManagerInterface::class);
    $this->sessionStore = $this->prophet->prophesize(PrivateTempStore::class);
    $this->userStorage = $this->prophet->prophesize(UserStorageInterface::class);
    $this->tempStore->get('ibm_apim')->willReturn($this->sessionStore);
    $this->entity_type_manager->getStorage('user')->willReturn($this->userStorage->reveal());
  }

  protected function tearDown(): void {
    $this->prophet->checkPredictions();
  }

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testCheckHasPermission(): void {
    $utils = new UserUtils($this->current_user->reveal(),
      $this->tempStore->reveal(),
      $this->state->reveal(),
      $this->logger->reveal(),
      $this->entity_type_manager->reveal());
    $result = $utils->checkHasPermission();
    self::assertEquals(FALSE, $result);
  }


  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testAddConsumerOrgToUser(): void {

    $orgUrl = "/consumer-orgs/1234/5678/9abc";
    $userAccount = $this->createAccountBase();

    $this->current_user->isAnonymous()->willReturn(FALSE);
    $this->current_user->id()->willReturn(2);
    $this->logger->debug('updating consumerorg urls list set on the user object')->shouldBeCalled();
    $this->logger->debug('adding org to consumerorg urls list %orgUrl', ['%orgUrl' => $orgUrl])->shouldNotBeCalled();
    $utils = new UserUtils($this->current_user->reveal(),
      $this->tempStore->reveal(),
      $this->state->reveal(),
      $this->logger->reveal(),
      $this->entity_type_manager->reveal());

    $result = $utils->addConsumerOrgToUser($orgUrl, $userAccount->reveal());

    self::assertTrue($result);

  }

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testAddConsumerOrgToNewUser(): void {

    $orgUrl = "/consumer-orgs/1234/5678/9abc";
    $userAccount = $this->createAccountBase();

    $consumerorg_url_field = $this->prophet->prophesize(\Drupal\Core\Field\FieldItemList::class);
    $consumerorg_url_field->getValue()->willReturn([]);
    $userAccount->get('consumerorg_url')->willReturn($consumerorg_url_field);

    $this->current_user->isAnonymous()->willReturn(FALSE);
    $this->current_user->id()->willReturn(2);
    $this->logger->debug('updating consumerorg urls list set on the user object')->shouldNotBeCalled();
    $this->logger->debug('adding org to consumerorg urls list %orgUrl', ['%orgUrl' => $orgUrl])->shouldBeCalled();
    $utils = new UserUtils($this->current_user->reveal(),
      $this->tempStore->reveal(),
      $this->state->reveal(),
      $this->logger->reveal(),
      $this->entity_type_manager->reveal());

    $result = $utils->addConsumerOrgToUser($orgUrl, $userAccount->reveal());

    self::assertTrue($result);
  }


}
