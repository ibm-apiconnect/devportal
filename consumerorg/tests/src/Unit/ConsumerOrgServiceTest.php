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


use Drupal\apic_app\Service\ApplicationService;
use Drupal\consumerorg\ApicType\ConsumerOrg;
use Drupal\consumerorg\ApicType\Member;
use Drupal\consumerorg\Service\ConsumerOrgService;
use Drupal\consumerorg\Service\MemberService;
use Drupal\consumerorg\Service\RoleService;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Rest\RestResponse;
use Drupal\ibm_apim\Service\ApicUserService;
use Drupal\ibm_apim\Service\ApimUtils;
use Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface;
use Drupal\ibm_apim\Service\SiteConfig;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\ibm_apim\Service\Utils;
use Drupal\ibm_apim\UserManagement\ApicAccountService;
use Drupal\ibm_apim\Service\EventLogService;
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

  /**
   * @var \Prophecy\Prophet
   */
  private Prophet $prophet;

  // dependencies of ConsumerOrgService

  /**
   * @var \Prophecy\Prophecy\ObjectProphecy|\Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * @var \Drupal\ibm_apim\Service\SiteConfig|\Prophecy\Prophecy\ObjectProphecy
   */
  private $siteConfig;

  /**
   * @var \Drupal\ibm_apim\Service\ApimUtils|\Prophecy\Prophecy\ObjectProphecy
   */
  private $apimUtils;

  /**
   * @var \Prophecy\Prophecy\ObjectProphecy|\Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  private $eventDispatcher;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  private $currentUser;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  private $entityTypeManager;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  private $moduleHandler;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  private $apimServer;

  /**
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory|\Prophecy\Prophecy\ObjectProphecy
   */
  private $session;

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils|\Prophecy\Prophecy\ObjectProphecy
   */
  private $userUtils;

  /**
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  private $cacheTagsInvalidator;

  /**
   * @var \Drupal\consumerorg\Service\MemberService|\Prophecy\Prophecy\ObjectProphecy
   */
  private $memberService;

  /**
   * @var \Drupal\consumerorg\Service\RoleService|\Prophecy\Prophecy\ObjectProphecy
   */
  private $roleService;

  /**
   * @var \Drupal\ibm_apim\UserManagement\ApicAccountService|\Prophecy\Prophecy\ObjectProphecy
   */
  private $apicAccountService;

  /**
   * @var \Drupal\ibm_apim\Service\ApicUserService|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $userService;

  /**
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $userQuery;

  /**
   * @var \Drupal\ibm_apim\Service\EventLogService|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $eventLogService;

  /**
   * @var \Drupal\ibm_apim\Service\Utils|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $utils;

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function setup(): void {
    $this->prophet = new Prophet();
    $this->logger = $this->prophet->prophesize(LoggerInterface::class);

    $this->siteConfig = $this->prophet->prophesize(SiteConfig::class);
    $this->apimUtils = $this->prophet->prophesize(ApimUtils::class);
    $this->eventDispatcher = $this->prophet->prophesize(EventDispatcherInterface::class);
    $this->currentUser = $this->prophet->prophesize(AccountProxyInterface::class);
    $this->entityTypeManager = $this->prophet->prophesize(EntityTypeManagerInterface::class);
    $this->moduleHandler = $this->prophet->prophesize(ModuleHandlerInterface::class);
    $this->apimServer = $this->prophet->prophesize(ManagementServerInterface::class);
    $this->session = $this->prophet->prophesize(PrivateTempStoreFactory::class);
    $this->userUtils = $this->prophet->prophesize(UserUtils::class);
    $this->cacheTagsInvalidator = $this->prophet->prophesize(CacheTagsInvalidatorInterface::class);
    $this->memberService = $this->prophet->prophesize(MemberService::class);
    $this->roleService = $this->prophet->prophesize(RoleService::class);
    $this->apicAccountService = $this->prophet->prophesize(ApicAccountService::class);
    $this->userService = $this->prophet->prophesize(ApicUserService::class);
    $this->userQuery = $this->prophet->prophesize(QueryInterface::class);
    $this->eventLogService = $this->prophet->prophesize(EventLogService::class);
    $this->utils = $this->prophet->prophesize(Utils::class);
    $userStorage = $this->prophet->prophesize(EntityStorageInterface::class);
    $this->entityTypeManager->getStorage('user')->willReturn($userStorage->reveal());
    $userStorage->getQuery()->willReturn($this->userQuery); // TODO: implement per test when needed?
  }

  protected function tearDown(): void {
    $this->prophet->checkPredictions();
  }


  // deleteMember

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   */
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

    self::assertNotNull($response);
    self::assertTrue($response->success());

  }

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \JsonException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
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

    self::assertNotNull($response);
    self::assertFalse($response->success());

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
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function createService(): ConsumerOrgService {
    return new ConsumerOrgService($this->logger->reveal(),
      $this->siteConfig->reveal(),
      $this->apimUtils->reveal(),
      $this->eventDispatcher->reveal(),
      $this->currentUser->reveal(),
      $this->entityTypeManager->reveal(),
      $this->moduleHandler->reveal(),
      $this->apimServer->reveal(),
      $this->session->reveal(),
      $this->userUtils->reveal(),
      $this->cacheTagsInvalidator->reveal(),
      $this->memberService->reveal(),
      $this->roleService->reveal(),
      $this->apicAccountService->reveal(),
      $this->userService->reveal(),
      $this->eventLogService->reveal(),
      $this->utils->reveal());
  }

}

