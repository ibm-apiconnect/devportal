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

namespace Drupal\Tests\consumerorg\Unit;


use Drupal\consumerorg\ApicType\ConsumerOrg;
use Drupal\consumerorg\ApicType\Member;
use Drupal\consumerorg\Service\ConsumerOrgLoginService;
use Drupal\consumerorg\Service\ConsumerOrgService;
use Drupal\consumerorg\Service\MemberService;
use Drupal\consumerorg\Service\RoleService;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Rest\RestResponse;
use Drupal\ibm_apim\Service\ApimUtils;
use Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface;
use Drupal\ibm_apim\Service\SiteConfig;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\Prophet;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @coversDefaultClass \Drupal\consumerorg\Service\ConsumerOrgLoginService
 *
 * @group consumerorg
 */
class ConsumerOrgLoginServiceTest extends UnitTestCase {

  private $prophet;

  // dependencies of ConsumerOrgLoginService
  private $consumerOrgService;
  private $logger;

  protected function setup(): void {
    $this->prophet = new Prophet();
    $this->consumerOrgService = $this->prophet->prophesize(ConsumerOrgService::class);
    $this->logger = $this->prophet->prophesize(LoggerInterface::class);
  }

  protected function tearDown(): void {
    $this->prophet->checkPredictions();
  }

  public function testCreateOrUpdateLoginOrgMemberExists() {

    $existing_members = [];
    $existing_member = new Member();
    $existing_member->setUrl('/member/1');
    $existing_member->setUserUrl('/user/1');
    $existing_members[] = $existing_member;
    $existing_member2 = new Member();
    $existing_member2->setUrl('/member/2');
    $existing_member2->setUserUrl('/user/2');
    $existing_members[] = $existing_member2;


    $stored_org = new ConsumerOrg();
    $stored_org->setUrl('/org/1');
    $stored_org->setMembers($existing_members);


    $user = new ApicUser();
    $user->setUrl('/user/2');

    $members = [];
    $member = new Member();
    $member->setUrl('/member/2');
    $member->setUserUrl('/user/2');
    $members[] = $member;

    $new_org = new ConsumerOrg();
    $new_org->setUrl('/org/1');
    $new_org->setName('org1');
    $new_org->setMembers($members);

    $this->consumerOrgService->get('/org/1')->willReturn($stored_org)->shouldBeCalled();
    $this->consumerOrgService->createNode(Argument::any())->shouldNotBeCalled();
    $this->consumerOrgService->createOrUpdateNode(Argument::any())->shouldNotBeCalled();
    $this->logger->notice(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();
    $this->logger->warning(Argument::any())->shouldNotBeCalled();

    $service = $this->createService();
    $result = $service->createOrUpdateLoginOrg($new_org, $user);

    $this->assertNotNull($result);
    $this->assertEquals('/org/1', $result->getUrl());
    $this->assertEquals(2, \sizeof($result->getMembers()));

  }

  public function testCreateOrUpdateLoginOrgAddNewMember() {

    $existing_members = [];
    $existing_member = new Member();
    $existing_member->setUrl('/member/1');
    $existing_member->setUserUrl('/user/1');
    $existing_members[] = $existing_member;

    $stored_org = new ConsumerOrg();
    $stored_org->setUrl('/org/1');
    $stored_org->setMembers($existing_members);


    $user = new ApicUser();
    $user->setUrl('/user/2');

    $members = [];
    $member = new Member();
    $member->setUrl('/member/2');
    $member->setUserUrl('/user/2');
    $members[] = $member;

    $new_org = new ConsumerOrg();
    $new_org->setUrl('/org/1');
    $new_org->setName('org1');
    $new_org->setMembers($members);

    $this->consumerOrgService->get('/org/1')->willReturn($stored_org)->shouldBeCalled();
    $this->consumerOrgService->createNode(Argument::any())->shouldNotBeCalled();
    $this->consumerOrgService->createOrUpdateNode($new_org, 'login-update-members')->shouldBeCalled();
    $this->logger->notice(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();
    $this->logger->warning(Argument::any())->shouldNotBeCalled();

    $service = $this->createService();
    $result = $service->createOrUpdateLoginOrg($new_org, $user);

    $this->assertNotNull($result);
    $this->assertEquals('/org/1', $result->getUrl());
    $this->assertEquals(2, \sizeof($result->getMembers()));

  }

  public function testCreateOrUpdateLoginOrgNoExistingOrg() {

    $user = new ApicUser();
    $user->setUrl('/user/2');

    $members = [];
    $member = new Member();
    $member->setUrl('/member/2');
    $member->setUserUrl('/user/2');
    $members[] = $member;

    $new_org = new ConsumerOrg();
    $new_org->setUrl('/org/1');
    $new_org->setName('org1');
    $new_org->setMembers($members);

    $this->consumerOrgService->get('/org/1')->willReturn(NULL)->shouldBeCalled();
    $org_service = $this->consumerOrgService;
    $this->consumerOrgService->createNode($new_org)->shouldBeCalled()->will(function ($args) use ($org_service, $new_org) {
      $org_service->get('/org/1')->willReturn($new_org)->shouldBeCalled();
    });;
    $this->consumerOrgService->createOrUpdateNode(Argument::any())->shouldNotBeCalled();
    $this->logger->notice('Consumerorg @consumerorgname (url=@consumerorgurl) was not found in drupal database during login. It will be created.', [
      '@consumerorgurl' => '/org/1',
      '@consumerorgname' => 'org1',
    ])->shouldBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();
    $this->logger->warning(Argument::any())->shouldNotBeCalled();

    $service = $this->createService();
    $result = $service->createOrUpdateLoginOrg($new_org, $user);

    $this->assertNotNull($result);
    $this->assertEquals('/org/1', $result->getUrl());
    $this->assertEquals('org1', $result->getTitle());
    $this->assertEquals('/user/2', $result->getOwnerUrl());
    $this->assertEquals(1, \sizeof($result->getMembers()));

  }


  /**
   * @return \Drupal\consumerorg\Service\ConsumerOrgLoginService
   */
  private function createService(): ConsumerOrgLoginService {
    $service = new ConsumerOrgLoginService(
      $this->consumerOrgService->reveal(),
      $this->logger->reveal());
    return $service;
  }


}

