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

use Drupal\ibm_apim\Service\UserUtils;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\State\State;
use Drupal\Tests\auth_apic\Unit\Base\AuthApicTestBaseClass;
use Psr\Log\LoggerInterface;
use Drupal\Tests\UnitTestCase;
use Prophecy\Prophet;

/**
 * @coversDefaultClass \Drupal\ibm_apim\Service\Utils
 *
 * @group ibm_apim
 */
class UserUtilsTest extends AuthApicTestBaseClass {

  protected $prophet;

  /*
    Dependencies of UserUtils.
  */


  protected $current_user;

  protected $temp_store_factory;

  protected $state;

  protected $logger;

  protected $entity_type_manager;



  protected function setup() {

    parent::setup();
    //$this->prophet = new Prophet();

    $this->current_user = $this->prophet->prophesize(AccountProxyInterface::class);
    $this->temp_store_factory = $this->prophet->prophesize(PrivateTempStoreFactory::class);
    $this->state = $this->prophet->prophesize(State::class);
    $this->logger = $this->prophet->prophesize(\Psr\Log\LoggerInterface::class);
    $this->entity_type_manager = $this->prophet->prophesize(EntityTypeManagerInterface::class);

  }

  protected function tearDown() {
    $this->prophet->checkPredictions();
  }


  public function testCheckHasPermission(): void {
    $utils = new UserUtils( $this->current_user->reveal(),
      $this->temp_store_factory->reveal(),
      $this->state->reveal(),
      $this->logger->reveal(),
      $this->entity_type_manager->reveal());
    $result = $utils->checkHasPermission();
    $this->assertEquals(false, $result);
  }


  public function testAddConsumerOrgToUser(): void {

    $orgUrl="/consumer-orgs/1234/5678/9abc";
    $userAccount = $this->createAccountBase();

    $this->current_user->isAnonymous()->willReturn(false);
    $this->current_user->id()->willReturn(2);
    $this->logger->debug('updating consumerorg urls list set on the user object')->shouldBeCalled();
    $this->logger->debug('adding org to consumerorg urls list '.$orgUrl)->shouldNotBeCalled();
    $utils = new UserUtils( $this->current_user->reveal(),
      $this->temp_store_factory->reveal(),
      $this->state->reveal(),
      $this->logger->reveal(),
      $this->entity_type_manager->reveal());

    $result = $utils->addConsumerOrgToUser($orgUrl, $userAccount->reveal());

    $this->assertTrue($result);

  }

  public function testAddConsumerOrgToNewUser(): void {

    $orgUrl="/consumer-orgs/1234/5678/9abc";
    $userAccount = $this->createAccountBase();

    $consumerorg_url_field = $this->prophet->prophesize(\Drupal\Core\Field\FieldItemList::class);
    $consumerorg_url_field->getValue()->willReturn([]);
    $userAccount->get('consumerorg_url')->willReturn($consumerorg_url_field);

    $this->current_user->isAnonymous()->willReturn(false);
    $this->current_user->id()->willReturn(2);
    $this->logger->debug('updating consumerorg urls list set on the user object')->shouldNotBeCalled();
    $this->logger->debug('adding org to consumerorg urls list '.$orgUrl)->shouldBeCalled();
    $utils = new UserUtils( $this->current_user->reveal(),
      $this->temp_store_factory->reveal(),
      $this->state->reveal(),
      $this->logger->reveal(),
      $this->entity_type_manager->reveal());

    $result = $utils->addConsumerOrgToUser($orgUrl, $userAccount->reveal());

    $this->assertTrue($result);
  }


}
