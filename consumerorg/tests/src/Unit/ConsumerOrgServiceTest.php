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

namespace Drupal\Tests\consumerorg\Unit;


use Drupal\ibm_apim\UserManagement\ApicAccountService;
use Drupal\consumerorg\ApicType\ConsumerOrg;
use Drupal\consumerorg\ApicType\Member;
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
 * @coversDefaultClass \Drupal\consumerorg\Service\ConsumerOrgService
 *
 * @group consumerorg
 */
class ConsumerOrgServiceTest extends UnitTestCase {

  private $prophet;

  // dependencies of ConsumerOrgService
  private $logger;

  private $siteconfig;

  private $apimUtils;

  private $eventDispatcher;

  private $currentUser;

  private $userQuery;

  private $moduleHandler;

  private $apimServer;

  private $session;

  private $userUtils;

  private $cacheTagsInvalidator;

  private $memberService;

  private $roleService;

  private $apicAccountService;

  protected function setup() {
    $this->prophet = new Prophet();
    $this->logger = $this->prophet->prophesize(LoggerInterface::class);

    $this->siteconfig = $this->prophet->prophesize(SiteConfig::class);
    $this->apimUtils = $this->prophet->prophesize(ApimUtils::class);
    $this->eventDispatcher = $this->prophet->prophesize(EventDispatcherInterface::class);
    $this->currentUser = $this->prophet->prophesize(AccountProxyInterface::class);
    $this->userQuery = $this->prophet->prophesize(EntityTypeManagerInterface::class);
    $this->moduleHandler = $this->prophet->prophesize(ModuleHandlerInterface::class);
    $this->apimServer = $this->prophet->prophesize(ManagementServerInterface::class);
    $this->session = $this->prophet->prophesize(PrivateTempStoreFactory::class);
    $this->userUtils = $this->prophet->prophesize(UserUtils::class);
    $this->cacheTagsInvalidator = $this->prophet->prophesize(CacheTagsInvalidatorInterface::class);
    $this->memberService = $this->prophet->prophesize(MemberService::class);
    $this->roleService = $this->prophet->prophesize(RoleService::class);
    $this->apicAccountService = $this->prophet->prophesize(ApicAccountService::class);

    $userStorage = $this->prophet->prophesize(EntityStorageInterface::class);
    $this->userQuery->getStorage('user')->willReturn($userStorage->reveal());
    $userStorage->getQuery()->willReturn(NULL); // TODO: implement per test when needed?


  }

  protected function tearDown() {
    $this->prophet->checkPredictions();
  }


  // deleteMember
  public function testDeleteMemberValid(): void {

    $org = new ConsumerOrg();
    $org->setName('testorg');
    $org->setUrl('/org/url');
    $member1 = $this->createMember('user1');
    $member2 = $this->createMember('user2');
    $org->setMembers([$member1, $member2]);

    $apim_response = new RestResponse();
    $apim_response->setCode(200);

    $this->apimServer->deleteMember(Argument::any())->willReturn($apim_response);
    $this->cacheTagsInvalidator->invalidateTags(['myorg:url:/org/url'])->shouldBeCalled();

    $this->logger->notice('Deleted @member (id = @id) from @org consumer org.', [
      '@member' => 'user2',
      '@id' => 'user2-id',
      '@org' => 'testorg',
    ])->shouldBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();
    $this->logger->debug('unit test environment, createOrUpdateNode skipped')->shouldBeCalled();

    $service = $this->createService();
    $response = $service->deleteMember($org, $member2);

    $this->assertNotNull($response);
    $this->assertTrue($response->success());

  }

  public function testDeleteMemberErrorResponseFromApim(): void {

    $org = new ConsumerOrg();
    $org->setName('testorg');
    $org->setUrl('/org/url');
    $member1 = $this->createMember('user1');
    $member2 = $this->createMember('user2');
    $org->setMembers([$member1, $member2]);

    $apim_response = new RestResponse();
    $apim_response->setCode(500);

    $this->apimServer->deleteMember(Argument::any())->willReturn($apim_response);
    $this->cacheTagsInvalidator->invalidateTags(Argument::any())->shouldNotBeCalled();

    $this->logger->notice(Argument::any())->shouldNotBeCalled();
    $this->logger->debug(Argument::any())->shouldNotBeCalled();
    $this->logger->error('Error deleting @member (id = @id)', [
      '@member' => 'user2',
      '@id' => 'user2-id',
    ])->shouldBeCalled();

    $service = $this->createService();
    $response = $service->deleteMember($org, $member2);

    $this->assertNotNull($response);
    $this->assertFalse($response->success());

  }

  /**
   * @param $username
   *
   * @return \Drupal\consumerorg\ApicType\Member
   */
  private function createMember($username): Member {
    $user = new ApicUser();
    $user->setUsername($username);
    $member = new Member();
    $member->setUrl('/id/of/' . $username . '-id');
    $member->setUser($user);
    return $member;
  }

  /**
   * @return \Drupal\consumerorg\Service\ConsumerOrgService
   */
  private function createService(): ConsumerOrgService {
    $service = new ConsumerOrgService($this->logger->reveal(),
      $this->siteconfig->reveal(),
      $this->apimUtils->reveal(),
      $this->eventDispatcher->reveal(),
      $this->currentUser->reveal(),
      $this->userQuery->reveal(),
      $this->moduleHandler->reveal(),
      $this->apimServer->reveal(),
      $this->session->reveal(),
      $this->userUtils->reveal(),
      $this->cacheTagsInvalidator->reveal(),
      $this->memberService->reveal(),
      $this->roleService->reveal(),
      $this->apicAccountService->reveal());
    return $service;
  }


}

