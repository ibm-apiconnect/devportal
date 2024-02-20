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

namespace Drupal\Tests\mail_subscribers\Unit;

use Drupal\apic_app\Entity\ApplicationSubscription;
use Drupal\Component\Utility\EmailValidator;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\TypedData\Plugin\DataType\ItemList;
use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\ibm_apim\Service\ApicUserStorage;
use Drupal\mail_subscribers\Service\MailService;
use Drupal\node\Entity\Node;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Entity\User;
use Prophecy\Prophet;

/**
 * mail_subscribers tests.
 *
 * @group mail_subscribers
 */
class MailServiceUnitTest extends UnitTestCase {


  /**
   * @var \Prophecy\Prophet
   */
  protected Prophet $prophet;

  /**
   * @var \Drupal\Component\Utility\EmailValidator|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $emailValidator;

  /**
   * @var \Drupal\ibm_apim\Service\ApicUserStorage|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $userStorage;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityNodeStorage;

  /**
   * @var \Drupal\Core\Entity\Query\QueryInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityQuery;

  /**
   * @var \Drupal\Core\Entity\Query\QueryInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $subscriptionQuery;

  /**
   * @var \Drupal\node\Entity\Node|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $cOrgNode1;

  /**
   * @var \Drupal\node\Entity\Node|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $cOrgNode2;

  /**
   * @var \Drupal\node\Entity\Node|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $cOrgNode3;

  /**
   * @var \Drupal\node\Entity\Node|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $cOrgNode4;

  /**
   * @var \Drupal\node\Entity\Node|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $apiNode1;

  /**
   * @var \Drupal\node\Entity\Node|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $prodNode1;

  /**
   * @var \Drupal\node\Entity\Node|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $prodNode2;

  /**
   * @var \Drupal\node\Entity\Node|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $appNode1;

  /**
   * @var \Drupal\node\Entity\Node|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $appNode2;

  /**
   * @var \Drupal\apic_app\Entity\ApplicationSubscription|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $sub1;

  /**
   * @var \Drupal\apic_app\Entity\ApplicationSubscription|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $sub2;

  /**
   * @var \Drupal\apic_app\Entity\ApplicationSubscription|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $sub3;

  /**
   * @var \Drupal\apic_app\Entity\ApplicationSubscription|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $sub4;

  /**
   * @var \Drupal\Core\TypedData\Plugin\DataType\ItemList|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $cOrg1;

  /**
   * @var \Drupal\Core\TypedData\Plugin\DataType\ItemList|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $cOrg2;

  /**
   * @var \Drupal\Core\TypedData\Plugin\DataType\StringData|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $cOrgString1;

  /**
   * @var \Drupal\Core\TypedData\Plugin\DataType\StringData|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $memberString1;

  /**
   * @var \Drupal\Core\TypedData\Plugin\DataType\StringData|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $memberString2;

  /**
   * @var \Drupal\Core\TypedData\Plugin\DataType\StringData|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $ownerString1;

  /**
   * @var \Drupal\Core\Entity\EntityInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $account1;

  /**
   * @var \Drupal\Core\Entity\EntityInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $account2;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  private $entityTypeManager;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  private $entitySubscriptionStorage;

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function setup(): void {
    $this->prophet = new Prophet();
    $this->emailValidator = $this->prophet->prophesize(EmailValidator::class);

    $this->entityTypeManager = $this->prophet->prophesize(EntityTypeManagerInterface::class);

    $this->userStorage = $this->prophet->prophesize(ApicUserStorage::class);

    $this->entityNodeStorage = $this->prophet->prophesize(EntityStorageInterface::class);
    $this->entitySubscriptionStorage = $this->prophet->prophesize(EntityStorageInterface::class);

    $this->entityTypeManager->getStorage('node')->willReturn($this->entityNodeStorage->reveal());
    $this->entityTypeManager->getStorage('apic_app_application_subs')->willReturn($this->entitySubscriptionStorage->reveal());

    $this->entityTypeManager->getStorage('user')->willReturn($this->userStorage->reveal());

    $this->entityQuery = $this->prophet->prophesize(QueryInterface::class);
    $this->subscriptionQuery = $this->prophet->prophesize(QueryInterface::class);

    $this->cOrgNode1 = $this->prophet->prophesize(Node::class);
    $this->cOrgNode2 = $this->prophet->prophesize(Node::class);
    $this->cOrgNode3 = $this->prophet->prophesize(Node::class);
    $this->cOrgNode4 = $this->prophet->prophesize(Node::class);
    $this->apiNode1 = $this->prophet->prophesize(Node::class);
    $this->prodNode1 = $this->prophet->prophesize(Node::class);
    $this->prodNode2 = $this->prophet->prophesize(Node::class);
    $this->appNode1 = $this->prophet->prophesize(Node::class);
    $this->appNode2 = $this->prophet->prophesize(Node::class);

    $this->sub1 = $this->prophet->prophesize(ApplicationSubscription::class);
    $this->sub2 = $this->prophet->prophesize(ApplicationSubscription::class);
    $this->sub3 = $this->prophet->prophesize(ApplicationSubscription::class);
    $this->sub4 = $this->prophet->prophesize(ApplicationSubscription::class);

    $this->cOrg1 = $this->prophet->prophesize(ItemList::class);
    $this->cOrg2 = $this->prophet->prophesize(ItemList::class);

    $this->cOrgString1 = $this->prophet->prophesize(StringData::class);
    $this->memberString1 = $this->prophet->prophesize(StringData::class);
    $this->memberString2 = $this->prophet->prophesize(StringData::class);

    $this->ownerString1 = $this->prophet->prophesize(StringData::class);

    $this->account1 = $this->prophet->prophesize(User::class);
    $this->account2 = $this->prophet->prophesize(User::class);
    $this->entityNodeStorage->getQuery()->willReturn($this->entityQuery->reveal());
    $this->entitySubscriptionStorage->getQuery()->willReturn($this->subscriptionQuery->reveal());
  }

  protected function tearDown(): void {
    $this->prophet->checkPredictions();
  }

  /**
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  public function testGetProductSubscribingOwnersNoPlan(): void {

    $this->subscriptionQuery->condition('product_url', "prod1url")->willReturn(NULL);
    $this->subscriptionQuery->accessCheck()->willReturn($this->subscriptionQuery);
    $this->subscriptionQuery->execute()->willReturn(['sub1']);
    $this->entitySubscriptionStorage->load("sub1")->willReturn($this->sub1);
    $this->sub1->consumerorg_url()->willReturn("consorg1");

    $this->entityQuery->accessCheck()->willReturn($this->entityQuery);
    $this->entityQuery->execute()->willReturn(['consorg1']);
    $this->entityNodeStorage->loadMultiple(['consorg1'])->willReturn([$this->cOrgNode1]);
    $this->cOrgNode1->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e'));
    $this->cOrgNode1->id()->willReturn('1a112v76-6cfa-4486-9637-80a8d3e11aa11');
    $this->cOrg1->get(0)->willReturn($this->cOrgString1);
    $this->cOrgNode1->get('application_consumer_org_url')->willReturn($this->createSimpleObject('value', 'consorg1'));
    $this->entityQuery->condition('type', 'consumerorg')->willReturn(NULL);
    $this->entityQuery->condition('consumerorg_url.value', "consorg1")->willReturn(NULL);
    $this->account1->getEmail()->willReturn("fdh@test.com");
    $this->emailValidator->isValid("fdh@test.com")->willReturn(TRUE);
    $this->userStorage->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e')
      ->willReturn($this->account1);

    $this->cOrgNode1->reveal();
    $this->cOrgNode2->reveal();
    $this->account1->reveal();
    $this->sub1->reveal();

    $expectedRecipients = [
      '1a112v76-6cfa-4486-9637-80a8d3e11aa11' => ['fdh@test.com'],
    ];

    $mailService = new MailService($this->userStorage->reveal(), $this->entityTypeManager->reveal(), $this->emailValidator->reveal());
    $recipients = $mailService->getProductSubscribers('prod1url', 'owners');

    self::assertEquals($expectedRecipients, $recipients);
  }

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   * @throws \Exception
   */
  public function testGetProductSubscribingMembersNoPlan(): void {
    $this->subscriptionQuery->condition('product_url', "prod1url")->willReturn(NULL);
    $this->subscriptionQuery->accessCheck()->willReturn($this->subscriptionQuery);
    $this->subscriptionQuery->execute()->willReturn(['sub1']);
    $this->entitySubscriptionStorage->load("sub1")->willReturn($this->sub1);
    $this->sub1->consumerorg_url()->willReturn("consorg1");

    $this->entityQuery->accessCheck()->willReturn($this->entityQuery);
    $this->entityQuery->execute()->willReturn(['consorg1']);
    $this->entityNodeStorage->loadMultiple(['consorg1'])->willReturn([$this->cOrgNode1]);
    $this->cOrgNode1->get('application_consumer_org_url')->willReturn($this->createSimpleObject('value', 'consorg1'));
    $this->entityQuery->condition('type', 'consumerorg')->willReturn(NULL);
    $this->entityQuery->condition('consumerorg_url.value', "consorg1")->willReturn(NULL);
    $this->memberString1->getValue()->willReturn([['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:12:"fdh@test.com";}}']]);
    $this->cOrgNode1->get('consumerorg_members')->willReturn($this->memberString1);
    $this->cOrgNode1->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e'));
    $this->cOrgNode1->id()->willReturn('1a112v76-6cfa-4486-9637-80a8d3e11aa11');
    $this->cOrg1->get(0)->willReturn($this->cOrgString1);
    $this->account1->getEmail()->willReturn("fdh@test.com");
    $this->emailValidator->isValid("fdh@test.com")->willReturn(TRUE);
    $this->userStorage->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e')
      ->willReturn($this->account1);

    $this->cOrgNode1->reveal();
    $this->cOrgNode2->reveal();
    $this->memberString1->reveal();
    $this->account1->reveal();

    $expectedRecipients = [
      '1a112v76-6cfa-4486-9637-80a8d3e11aa11' => ['fdh@test.com'],
    ];

    $mailService = new MailService($this->userStorage->reveal(), $this->entityTypeManager->reveal(), $this->emailValidator->reveal());
    $recipients = $mailService->getProductSubscribers('prod1url', 'members');

    self::assertEquals($expectedRecipients, $recipients);
  }

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  public function testGetProductSubscribingOwnersWithPlan(): void {
    $this->subscriptionQuery->condition('product_url', "/consumer-api/products/c025bc00-92ab-413a-af78-34eabdecacac")->willReturn(NULL);
    $this->subscriptionQuery->condition('plan', "default-plan")->willReturn(NULL);
    $this->subscriptionQuery->accessCheck()->willReturn($this->subscriptionQuery);
    $this->subscriptionQuery->execute()->willReturn(['sub1', 'sub2']);
    $this->entitySubscriptionStorage->load("sub1")->willReturn($this->sub1);
    $this->entitySubscriptionStorage->load("sub2")->willReturn($this->sub2);
    $this->sub1->consumerorg_url()->willReturn("consorg1");
    $this->sub2->consumerorg_url()->willReturn("consorg2");

    $this->entityQuery->accessCheck()->willReturn($this->entityQuery);
    $this->entityQuery->execute()->willReturn(["consorg1", "consorg2"]);
    $this->entityNodeStorage->loadMultiple(['consorg1', 'consorg2'])->willReturn([$this->cOrgNode1, $this->cOrgNode2]);
    $this->cOrgNode1->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e'));
    $this->cOrgNode2->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e2'));
    $this->cOrgNode1->get('application_consumer_org_url')->willReturn($this->createSimpleObject('value', 'consorg1'));
    $this->cOrgNode1->id()->willReturn('1a112v76-6cfa-4486-9637-80a8d3e11aa11');
    $this->cOrgNode2->get('application_consumer_org_url')->willReturn($this->createSimpleObject('value', 'consorg2'));
    $this->cOrgNode2->id()->willReturn('2b222v76-6cfa-4486-9637-80a8d3e22bb22');
    $this->entityQuery->condition('type', 'consumerorg')->willReturn(NULL);
    $this->entityQuery->condition('consumerorg_url.value', "consorg1")->willReturn(NULL);
    $this->entityQuery->condition('consumerorg_url.value', "consorg2")->willReturn(NULL);
    $this->account1->getEmail()->willReturn("fdh@test.com");
    $this->account2->getEmail()->willReturn("fdh2@test.com");
    $this->emailValidator->isValid("fdh@test.com")->willReturn(TRUE);
    $this->emailValidator->isValid("fdh2@test.com")->willReturn(TRUE);
    $this->userStorage->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e')
      ->willReturn($this->account1);
    $this->userStorage->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e2')
      ->willReturn($this->account2);

    $this->cOrgNode1->reveal();
    $this->cOrgNode2->reveal();
    $this->cOrgNode3->reveal();
    $this->cOrgNode4->reveal();
    $this->account1->reveal();
    $this->account2->reveal();

    $expectedRecipients = [
      '1a112v76-6cfa-4486-9637-80a8d3e11aa11' => ['fdh@test.com'],
      '2b222v76-6cfa-4486-9637-80a8d3e22bb22' => ['fdh2@test.com'],
    ];

    $mailService = new MailService($this->userStorage->reveal(), $this->entityTypeManager->reveal(), $this->emailValidator->reveal());
    $recipients = $mailService->getProductSubscribers('/consumer-api/products/c025bc00-92ab-413a-af78-34eabdecacac:default-plan', 'owners');

    self::assertEquals($expectedRecipients, $recipients);
  }

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  public function testGetProductSubscribingMembersWithPlan(): void {
    $this->subscriptionQuery->condition('product_url', "/consumer-api/products/c025bc00-92ab-413a-af78-34eabdecacac")->willReturn(NULL);
    $this->subscriptionQuery->condition('plan', "default-plan")->willReturn(NULL);
    $this->subscriptionQuery->accessCheck()->willReturn($this->subscriptionQuery);
    $this->subscriptionQuery->execute()->willReturn(['sub1', 'sub2']);
    $this->entitySubscriptionStorage->load("sub1")->willReturn($this->sub1);
    $this->entitySubscriptionStorage->load("sub2")->willReturn($this->sub2);
    $this->sub1->consumerorg_url()->willReturn("consorg1");
    $this->sub2->consumerorg_url()->willReturn("consorg2");

    $this->entityQuery->accessCheck()->willReturn($this->entityQuery);
    $this->entityQuery->execute()->willReturn(["consorg1", "consorg2"]);
    $this->entityNodeStorage->loadMultiple(['consorg1', 'consorg2'])->willReturn([$this->cOrgNode1, $this->cOrgNode2]);
    $this->memberString1->getValue()->willReturn([
      ['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:12:"fdh@test.com";}}'],
      ['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:13:"fdh2@test.com";}}'],
    ]);
    $this->cOrgNode1->get('consumerorg_members')->willReturn($this->memberString1);
    $this->cOrgNode1->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e'));
    $this->memberString2->getValue()->willReturn([
      ['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:12:"fdh@test.com";}}'],
      ['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:13:"fdh2@test.com";}}'],
    ]);
    $this->cOrgNode2->get('consumerorg_members')->willReturn($this->memberString1);
    $this->cOrgNode2->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e2'));
    $this->cOrgNode1->get('application_consumer_org_url')->willReturn($this->createSimpleObject('value', 'consorg1'));
    $this->cOrgNode1->id()->willReturn('1a112v76-6cfa-4486-9637-80a8d3e11aa11');
    $this->cOrgNode2->get('application_consumer_org_url')->willReturn($this->createSimpleObject('value', 'consorg2'));
    $this->cOrgNode2->id()->willReturn('2b222v76-6cfa-4486-9637-80a8d3e22bb22');
    $this->entityQuery->condition('type', 'consumerorg')->willReturn(NULL);
    $this->entityQuery->condition('consumerorg_url.value', "consorg1")->willReturn(NULL);
    $this->entityQuery->condition('consumerorg_url.value', "consorg2")->willReturn(NULL);
    $this->account1->getEmail()->willReturn("fdh@test.com");
    $this->account2->getEmail()->willReturn("fdh2@test.com");
    $this->emailValidator->isValid("fdh@test.com")->willReturn(TRUE);
    $this->emailValidator->isValid("fdh2@test.com")->willReturn(TRUE);
    $this->userStorage->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e')
      ->willReturn($this->account1);
    $this->userStorage->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e2')
      ->willReturn($this->account2);

    $this->cOrgNode1->reveal();
    $this->cOrgNode2->reveal();
    $this->memberString1->reveal();
    $this->memberString2->reveal();
    $this->account1->reveal();
    $this->account2->reveal();

    $expectedRecipients = [
      '1a112v76-6cfa-4486-9637-80a8d3e11aa11' => ['fdh@test.com', 'fdh2@test.com'],
      '2b222v76-6cfa-4486-9637-80a8d3e22bb22' => ['fdh@test.com', 'fdh2@test.com'],
    ];

    $mailService = new MailService($this->userStorage->reveal(), $this->entityTypeManager->reveal(), $this->emailValidator->reveal());
    $recipients = $mailService->getProductSubscribers('/consumer-api/products/c025bc00-92ab-413a-af78-34eabdecacac:default-plan', 'members');

    self::assertEquals($expectedRecipients, $recipients);
  }

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  public function testGetApiSubscribersMembers(): void {
    $this->entityNodeStorage->load("100")->willReturn($this->apiNode1);

    $this->entityQuery->condition('type', 'product')->willReturn(NULL);
    $this->apiNode1->get('apic_ref')->willReturn($this->createSimpleObject('value', 'api1'));
    $this->entityQuery->condition('product_apis.value', 'api1', 'CONTAINS')->willReturn(NULL);

    $this->entityQuery->accessCheck()->willReturn($this->entityQuery);
    $this->entityQuery->execute()->willReturn(['prodnid1', 'prodnid2'], ['consorg1'], ['consorg2']);

    $this->prodNode1->get('apic_url')->willReturn($this->createSimpleObject('value', 'url1'));
    $this->prodNode2->get('apic_url')->willReturn($this->createSimpleObject('value', 'url2'));

    $this->entityNodeStorage->load("prodnid1")->willReturn($this->prodNode1);
    $this->entityNodeStorage->load("prodnid2")->willReturn($this->prodNode2);

    $this->subscriptionQuery->condition('product_url', 'url1')->willReturn(NULL);
    $this->subscriptionQuery->condition('product_url', 'url2')->willReturn(NULL);

    $this->entityQuery->condition('type', 'application')->willReturn(NULL);

    $this->subscriptionQuery->accessCheck()->willReturn($this->subscriptionQuery);
    $this->subscriptionQuery->execute()->willReturn(['sub1', 'sub3'], ['sub2', 'sub4']);
    $this->entitySubscriptionStorage->load("sub1")->willReturn($this->sub1);
    $this->entitySubscriptionStorage->load("sub2")->willReturn($this->sub2);
    $this->entitySubscriptionStorage->load("sub3")->willReturn($this->sub3);
    $this->entitySubscriptionStorage->load("sub4")->willReturn($this->sub4);
    $this->sub1->consumerorg_url()->willReturn("org1");
    $this->sub2->consumerorg_url()->willReturn("org1");
    $this->sub3->consumerorg_url()->willReturn("org2");
    $this->sub4->consumerorg_url()->willReturn("org2");


    $this->entityQuery->condition('consumerorg_url.value', 'org1')->willReturn(NULL);
    $this->entityNodeStorage->loadMultiple(['consorg1'])->willReturn([$this->cOrgNode1]);
    $this->cOrgNode1->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e'));
    $this->memberString1->getValue()->willReturn([
      ['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:12:"fdh@test.com";}}'],
      ['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:13:"fdh2@test.com";}}'],
    ]);
    $this->cOrgNode1->get('consumerorg_members')->willReturn($this->memberString1);
    $this->cOrgNode1->id()->willReturn('1a112v76-6cfa-4486-9637-80a8d3e11aa11');
    $this->account1->getEmail()->willReturn("fdh@test.com");
    $this->account2->getEmail()->willReturn("fdh2@test.com");
    $this->emailValidator->isValid("fdh@test.com")->willReturn(TRUE);
    $this->emailValidator->isValid("fdh2@test.com")->willReturn(TRUE);
    $this->userStorage->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e')
      ->willReturn($this->account1);
    $this->userStorage->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e2')
      ->willReturn($this->account2);

    $this->entityQuery->condition('type', 'consumerorg')->willReturn(NULL);
    $this->entityQuery->condition('consumerorg_url.value', 'org2')->willReturn(NULL);
    $this->entityNodeStorage->loadMultiple(['consorg2'])->willReturn([$this->cOrgNode2]);
    $this->cOrgNode2->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e2'));
    $this->memberString2->getValue()->willReturn([
      ['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:12:"fdh@test.com";}}'],
      ['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:13:"fdh2@test.com";}}'],
    ]);
    $this->cOrgNode2->get('consumerorg_members')->willReturn($this->memberString2);
    $this->cOrgNode2->id()->willReturn('2b222v76-6cfa-4486-9637-80a8d3e22bb22');
    $this->account1->getEmail()->willReturn("fdh@test.com");
    $this->account2->getEmail()->willReturn("fdh2@test.com");
    $this->emailValidator->isValid("fdh@test.com")->willReturn(TRUE);
    $this->emailValidator->isValid("fdh2@test.com")->willReturn(TRUE);
    $this->userStorage->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e')
      ->willReturn($this->account1);
    $this->userStorage->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e2')
      ->willReturn($this->account2);

    $expectedRecipients = [
      '1a112v76-6cfa-4486-9637-80a8d3e11aa11' => ['fdh@test.com', 'fdh2@test.com'],
      '2b222v76-6cfa-4486-9637-80a8d3e22bb22' => ['fdh@test.com', 'fdh2@test.com'],
    ];

    $mailService = new MailService($this->userStorage->reveal(), $this->entityTypeManager->reveal(), $this->emailValidator->reveal());
    $recipients = $mailService->getAPISubscribers(100, 'members');
    self::assertEquals($expectedRecipients, $recipients);
  }

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  public function testGetApiSubscribersOwners(): void {
    $this->entityNodeStorage->load("100")->willReturn($this->apiNode1);

    $this->entityQuery->condition('type', 'product')->willReturn(NULL);
    $this->apiNode1->get('apic_ref')->willReturn($this->createSimpleObject('value', 'api1'));
    $this->entityQuery->condition('product_apis.value', 'api1', 'CONTAINS')->willReturn(NULL);

    $this->entityQuery->accessCheck()->willReturn($this->entityQuery);
    $this->entityQuery->execute()->willReturn(['prodnid1', 'prodnid2'], ['consorg1'], ['consorg2']);

    $this->prodNode1->get('apic_url')->willReturn($this->createSimpleObject('value', 'url1'));
    $this->prodNode2->get('apic_url')->willReturn($this->createSimpleObject('value', 'url2'));

    $this->entityNodeStorage->load("prodnid1")->willReturn($this->prodNode1);
    $this->entityNodeStorage->load("prodnid2")->willReturn($this->prodNode2);

    $this->subscriptionQuery->condition('product_url', 'url1')->willReturn(NULL);
    $this->subscriptionQuery->condition('product_url', 'url2')->willReturn(NULL);

    $this->entityQuery->condition('type', 'application')->willReturn(NULL);

    $this->subscriptionQuery->accessCheck()->willReturn($this->subscriptionQuery);
    $this->subscriptionQuery->execute()->willReturn(['sub1', 'sub3'], ['sub2', 'sub4']);
    $this->entitySubscriptionStorage->load("sub1")->willReturn($this->sub1);
    $this->entitySubscriptionStorage->load("sub2")->willReturn($this->sub2);
    $this->entitySubscriptionStorage->load("sub3")->willReturn($this->sub3);
    $this->entitySubscriptionStorage->load("sub4")->willReturn($this->sub4);
    $this->sub1->consumerorg_url()->willReturn("org1");
    $this->sub2->consumerorg_url()->willReturn("org1");
    $this->sub3->consumerorg_url()->willReturn("org2");
    $this->sub4->consumerorg_url()->willReturn("org2");


    $this->entityQuery->condition('consumerorg_url.value', 'org1')->willReturn(NULL);
    $this->entityNodeStorage->loadMultiple(['consorg1'])->willReturn([$this->cOrgNode1]);
    $this->cOrgNode1->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e'));
    $this->memberString1->getValue()->willReturn([
      ['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:12:"fdh@test.com";}}'],
      ['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:13:"fdh2@test.com";}}'],
    ]);
    $this->cOrgNode1->get('consumerorg_members')->willReturn($this->memberString1);
    $this->cOrgNode1->id()->willReturn('1a112v76-6cfa-4486-9637-80a8d3e11aa11');
    $this->account1->getEmail()->willReturn("fdh@test.com");
    $this->account2->getEmail()->willReturn("fdh2@test.com");
    $this->emailValidator->isValid("fdh@test.com")->willReturn(TRUE);
    $this->emailValidator->isValid("fdh2@test.com")->willReturn(TRUE);
    $this->userStorage->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e')
      ->willReturn($this->account1);
    $this->userStorage->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e2')
      ->willReturn($this->account2);

    $this->entityQuery->condition('type', 'consumerorg')->willReturn(NULL);
    $this->entityQuery->condition('consumerorg_url.value', 'org2')->willReturn(NULL);
    $this->entityNodeStorage->loadMultiple(['consorg2'])->willReturn([$this->cOrgNode2]);
    $this->cOrgNode2->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e2'));
    $this->memberString2->getValue()->willReturn([
      ['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:12:"fdh@test.com";}}'],
      ['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:13:"fdh2@test.com";}}'],
    ]);
    $this->cOrgNode2->get('consumerorg_members')->willReturn($this->memberString2);
    $this->cOrgNode2->id()->willReturn('2b222v76-6cfa-4486-9637-80a8d3e22bb22');
    $this->account1->getEmail()->willReturn("fdh@test.com");
    $this->account2->getEmail()->willReturn("fdh2@test.com");
    $this->emailValidator->isValid("fdh@test.com")->willReturn(TRUE);
    $this->emailValidator->isValid("fdh2@test.com")->willReturn(TRUE);
    $this->userStorage->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e')
      ->willReturn($this->account1);
    $this->userStorage->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e2')
      ->willReturn($this->account2);

    $expectedRecipients = [
      '1a112v76-6cfa-4486-9637-80a8d3e11aa11' => ['fdh@test.com'],
      '2b222v76-6cfa-4486-9637-80a8d3e22bb22' => ['fdh2@test.com'],
    ];

    $mailService = new MailService($this->userStorage->reveal(), $this->entityTypeManager->reveal(), $this->emailValidator->reveal());
    $recipients = $mailService->getAPISubscribers(100, 'owners');
    self::assertEquals($expectedRecipients, $recipients);
  }

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  public function testGetAllSubscribingOwners(): void {
    $this->entityQuery->condition('type', 'consumerorg')->willReturn(NULL);
    $this->entityQuery->accessCheck()->willReturn($this->entityQuery);
    $this->entityQuery->execute()->willReturn(['consorg1', 'consorg2']);
    $this->entityNodeStorage->loadMultiple(["consorg1", "consorg2"])->willReturn([$this->cOrgNode1, $this->cOrgNode2]);
    $this->cOrgNode1->get('application_consumer_org_url')->willReturn($this->createSimpleObject('value', 'org1'));
    $this->cOrgNode2->get('application_consumer_org_url')->willReturn($this->createSimpleObject('value', 'org2'));
    $this->cOrgNode1->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e'));
    $this->cOrgNode2->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05d'));
    $this->cOrgNode1->id()->willReturn('1a112v76-6cfa-4486-9637-80a8d3e11aa11');
    $this->cOrgNode2->id()->willReturn('2b222v76-6cfa-4486-9637-80a8d3e22bb22');
    $this->account1->getEmail()->willReturn("fdh@test.com");
    $this->account2->getEmail()->willReturn("fdh2@test.com");
    $this->emailValidator->isValid("fdh@test.com")->willReturn(TRUE);
    $this->emailValidator->isValid("fdh2@test.com")->willReturn(TRUE);
    $this->userStorage->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e')
      ->willReturn($this->account1);
    $this->userStorage->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05d')
      ->willReturn($this->account2);

    $this->appNode1->reveal();
    $this->appNode2->reveal();
    $this->account1->reveal();
    $this->account2->reveal();

    $expectedRecipients = [
      '1a112v76-6cfa-4486-9637-80a8d3e11aa11' => ['fdh@test.com'],
      '2b222v76-6cfa-4486-9637-80a8d3e22bb22' => ['fdh2@test.com'],
    ];

    $mailService = new MailService($this->userStorage->reveal(), $this->entityTypeManager->reveal(), $this->emailValidator->reveal());
    $recipients = $mailService->getAllSubscribers('owners');

    self::assertEquals($expectedRecipients, $recipients);
  }

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Exception
   */
  public function testGetAllSubscribingMembers(): void {
    $this->entityQuery->condition('type', 'consumerorg')->willReturn(NULL);
    $this->entityQuery->accessCheck()->willReturn($this->entityQuery);
    $this->entityQuery->execute()->willReturn(["consorg1", "consorg2"]);
    $this->entityNodeStorage->loadMultiple(['consorg1', 'consorg2'])->willReturn([$this->cOrgNode1, $this->cOrgNode2]);
    $this->memberString1->getValue()->willReturn([
      ['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:12:"fdh@test.com";}}'],
      ['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:13:"fdh2@test.com";}}'],
    ]);
    $this->cOrgNode1->get('consumerorg_members')->willReturn($this->memberString1);
    $this->cOrgNode1->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e'));
    $this->memberString2->getValue()->willReturn([
      ['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:12:"fdh@test.com";}}'],
      ['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:13:"fdh2@test.com";}}'],
    ]);
    $this->cOrgNode2->get('consumerorg_members')->willReturn($this->memberString1);
    $this->cOrgNode2->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e2'));
    $this->cOrgNode1->get('application_consumer_org_url')->willReturn($this->createSimpleObject('value', 'consorg1'));
    $this->cOrgNode1->id()->willReturn('1a112v76-6cfa-4486-9637-80a8d3e11aa11');
    $this->cOrgNode2->get('application_consumer_org_url')->willReturn($this->createSimpleObject('value', 'consorg2'));
    $this->cOrgNode2->id()->willReturn('2b222v76-6cfa-4486-9637-80a8d3e22bb22');
    $this->entityQuery->condition('type', 'consumerorg')->willReturn(NULL);
    $this->entityQuery->condition('consumerorg_url.value', "consorg1")->willReturn(NULL);
    $this->entityQuery->condition('consumerorg_url.value', "consorg2")->willReturn(NULL);
    $this->account1->getEmail()->willReturn("fdh@test.com");
    $this->account2->getEmail()->willReturn("fdh2@test.com");
    $this->emailValidator->isValid("fdh@test.com")->willReturn(TRUE);
    $this->emailValidator->isValid("fdh2@test.com")->willReturn(TRUE);
    $this->userStorage->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e')
      ->willReturn($this->account1);
    $this->userStorage->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e2')
      ->willReturn($this->account2);

    $this->cOrgNode1->reveal();
    $this->cOrgNode2->reveal();
    $this->memberString1->reveal();
    $this->memberString2->reveal();
    $this->account1->reveal();
    $this->account2->reveal();

    $expectedRecipients = [
      '1a112v76-6cfa-4486-9637-80a8d3e11aa11' => ['fdh@test.com', 'fdh2@test.com'],
      '2b222v76-6cfa-4486-9637-80a8d3e22bb22' => ['fdh@test.com', 'fdh2@test.com'],
    ];

    $mailService = new MailService($this->userStorage->reveal(), $this->entityTypeManager->reveal(), $this->emailValidator->reveal());
    $recipients = $mailService->getAllSubscribers('members');

    self::assertEquals($expectedRecipients, $recipients);
  }

  /**
   * @param $name
   * @param $value
   *
   * @return \stdClass
   */
  private function createSimpleObject($name, $value): \stdClass {
    $c = new \stdClass();
    $c->$name = $value;
    return $c;
  }

}