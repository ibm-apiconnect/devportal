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

namespace Drupal\Tests\mail_subscribers\Unit;

use Drupal\mail_subscribers\Service\MailService;
use Drupal\Tests\UnitTestCase;
use Prophecy\Prophet;

/**
 * mail_subscribers tests.
 *
 * @group mail_subscribers
 */
class MailServiceUnitTest extends UnitTestCase {


  protected $prophet;

  protected $emailValidator;

  protected $userStorageInterface;

  protected $entityStorageBase;

  protected $entityStorageSQL;

  protected $entityNodeStorage;

  protected $entityQuery;

  protected $subscriptionQuery;

  protected $consorgNode1;

  protected $consorgNode2;

  protected $consorgNode3;

  protected $consorgNode4;

  protected $apiNode1;

  protected $prodNode1;

  protected $prodNode2;

  protected $appNode1;

  protected $appNode2;

  protected $sub1;

  protected $sub2;

  protected $sub3;

  protected $sub4;

  protected $consorg1;

  protected $consorg2;

  protected $consorgString1;

  protected $memberString1;

  protected $memberString2;

  protected $ownerString1;

  protected $account1;

  protected $account2;

  protected $emailValidation;

  protected function setup() {
    $this->prophet = new Prophet();
    $this->emailValidator = $this->prophet->prophesize(\Drupal\Component\Utility\EmailValidator::class);

    $this->entityTypeManager = $this->prophet->prophesize(\Drupal\Core\Entity\EntityTypeManagerInterface::class);

    $this->userStorageInterface = $this->prophet->prophesize(\Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface::class);

    $this->entityNodeStorage = $this->prophet->prophesize(\Drupal\Core\Entity\EntityStorageInterface::class);
    $this->entitySubscriptionStorage = $this->prophet->prophesize(\Drupal\Core\Entity\EntityStorageInterface::class);

    $this->entityTypeManager->getStorage('node')->willReturn($this->entityNodeStorage->reveal());
    $this->entityTypeManager->getStorage('apic_app_application_subs')->willReturn($this->entitySubscriptionStorage->reveal());

    $this->entityQuery = $this->prophet->prophesize(\Drupal\Core\Entity\Query\QueryInterface::class);
    $this->subscriptionQuery = $this->prophet->prophesize(\Drupal\Core\Entity\Query\QueryInterface::class);

    $this->consorgNode1 = $this->prophet->prophesize(\Drupal\node\Entity\Node::class);
    $this->consorgNode2 = $this->prophet->prophesize(\Drupal\node\Entity\Node::class);
    $this->consorgNode3 = $this->prophet->prophesize(\Drupal\node\Entity\Node::class);
    $this->consorgNode4 = $this->prophet->prophesize(\Drupal\node\Entity\Node::class);
    $this->apiNode1 = $this->prophet->prophesize(\Drupal\node\Entity\Node::class);
    $this->prodNode1 = $this->prophet->prophesize(\Drupal\node\Entity\Node::class);
    $this->prodNode2 = $this->prophet->prophesize(\Drupal\node\Entity\Node::class);
    $this->appNode1 = $this->prophet->prophesize(\Drupal\node\Entity\Node::class);
    $this->appNode2 = $this->prophet->prophesize(\Drupal\node\Entity\Node::class);

    $this->sub1 = $this->prophet->prophesize(\Drupal\apic_app\Entity\ApplicationSubscription::class);
    $this->sub2 = $this->prophet->prophesize(\Drupal\apic_app\Entity\ApplicationSubscription::class);
    $this->sub3 = $this->prophet->prophesize(\Drupal\apic_app\Entity\ApplicationSubscription::class);
    $this->sub4 = $this->prophet->prophesize(\Drupal\apic_app\Entity\ApplicationSubscription::class);

    $this->consorg1 = $this->prophet->prophesize(\Drupal\Core\TypedData\Plugin\DataType\ItemList::class);
    $this->consorg2 = $this->prophet->prophesize(\Drupal\Core\TypedData\Plugin\DataType\ItemList::class);

    $this->consorgString1 = $this->prophet->prophesize(\Drupal\Core\TypedData\Plugin\DataType\StringData::class);
    $this->memberString1 = $this->prophet->prophesize(\Drupal\Core\TypedData\Plugin\DataType\StringData::class);
    $this->memberString2 = $this->prophet->prophesize(\Drupal\Core\TypedData\Plugin\DataType\StringData::class);

    $this->ownerString1 = $this->prophet->prophesize(\Drupal\Core\TypedData\Plugin\DataType\StringData::class);

    $this->account1 = $this->prophet->prophesize(\Drupal\Core\Session\AccountInterface::class);
    $this->account2 = $this->prophet->prophesize(\Drupal\Core\Session\AccountInterface::class);
    $this->entityNodeStorage->getQuery()->willReturn($this->entityQuery->reveal());
    $this->entitySubscriptionStorage->getQuery()->willReturn($this->subscriptionQuery->reveal());
  }

  protected function tearDown() {
    $this->prophet->checkPredictions();
  }

  public function testGetProductSubscribingOwnersNoPlan(): void {

    $this->subscriptionQuery->condition('product_url', "prod1url")->willReturn(NULL);
    $this->subscriptionQuery->execute()->willReturn(['sub1']);
    $this->entitySubscriptionStorage->load("sub1")->willReturn($this->sub1);
    $this->sub1->consumerorg_url()->willReturn("consorg1");


    $this->entityQuery->execute()->willReturn(['consorg1']);
    $this->entityNodeStorage->loadMultiple(['consorg1'])->willReturn([$this->consorgNode1]);
    $this->consorgNode1->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e'));
    $this->consorgNode1->id()->willReturn('1a112v76-6cfa-4486-9637-80a8d3e11aa11');
    $this->consorg1->get(0)->willReturn($this->consorgString1);
    $this->consorgNode1->get('application_consumer_org_url')->willReturn($this->createSimpleObject('value', 'consorg1'));
    $this->entityQuery->condition('type', 'consumerorg')->willReturn(NULL);
    $this->entityQuery->condition('consumerorg_url.value', "consorg1")->willReturn(NULL);
    $this->account1->getEmail()->willReturn("fdh@test.com");
    $this->emailValidator->isValid("fdh@test.com")->willReturn(TRUE);
    $this->userStorageInterface->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e')
      ->willReturn($this->account1);

    $this->consorgNode1->reveal();
    $this->consorgNode2->reveal();
    $this->account1->reveal();
    $this->sub1->reveal();

    $expectedRecipients = [
      '1a112v76-6cfa-4486-9637-80a8d3e11aa11' => ['fdh@test.com'],
    ];

    $mailService = new MailService($this->userStorageInterface->reveal(), $this->entityTypeManager->reveal(), $this->emailValidator->reveal());
    $recipients = $mailService->getProductSubscribers('prod1url', 'owners');

    $this->assertEquals($expectedRecipients, $recipients);
  }

  public function testGetProductSubscribingMembersNoPlan(): void {
    $this->subscriptionQuery->condition('product_url', "prod1url")->willReturn(NULL);
    $this->subscriptionQuery->execute()->willReturn(['sub1']);
    $this->entitySubscriptionStorage->load("sub1")->willReturn($this->sub1);
    $this->sub1->consumerorg_url()->willReturn("consorg1");
    
    $this->entityQuery->execute()->willReturn(['consorg1']);
    $this->entityNodeStorage->loadMultiple(['consorg1'])->willReturn([$this->consorgNode1]);
    $this->consorgNode1->get('application_consumer_org_url')->willReturn($this->createSimpleObject('value', 'consorg1'));
    $this->entityQuery->condition('type', 'consumerorg')->willReturn(NULL);
    $this->entityQuery->condition('consumerorg_url.value', "consorg1")->willReturn(NULL);
    $this->memberString1->getValue()->willReturn([['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:12:"fdh@test.com";}}']]);
    $this->consorgNode1->get('consumerorg_members')->willReturn($this->memberString1);
    $this->consorgNode1->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e'));
    $this->consorgNode1->id()->willReturn('1a112v76-6cfa-4486-9637-80a8d3e11aa11');
    $this->consorg1->get(0)->willReturn($this->consorgString1);
    $this->account1->getEmail()->willReturn("fdh@test.com");
    $this->emailValidator->isValid("fdh@test.com")->willReturn(TRUE);
    $this->userStorageInterface->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e')
      ->willReturn($this->account1);

    $this->consorgNode1->reveal();
    $this->consorgNode2->reveal();
    $this->memberString1->reveal();
    $this->account1->reveal();

    $expectedRecipients = [
      '1a112v76-6cfa-4486-9637-80a8d3e11aa11' => ['fdh@test.com'],
    ];

    $mailService = new MailService($this->userStorageInterface->reveal(), $this->entityTypeManager->reveal(), $this->emailValidator->reveal());
    $recipients = $mailService->getProductSubscribers('prod1url', 'members');

    $this->assertEquals($expectedRecipients, $recipients);
  }

  public function testGetProductSubscribingOwnersWithPlan(): void {
    $this->subscriptionQuery->condition('product_url', "/consumer-api/products/c025bc00-92ab-413a-af78-34eabdecacac")->willReturn(NULL);
    $this->subscriptionQuery->condition('plan', "default-plan")->willReturn(NULL);
    $this->subscriptionQuery->execute()->willReturn(['sub1','sub2']);
    $this->entitySubscriptionStorage->load("sub1")->willReturn($this->sub1);
    $this->entitySubscriptionStorage->load("sub2")->willReturn($this->sub2);
    $this->sub1->consumerorg_url()->willReturn("consorg1");
    $this->sub2->consumerorg_url()->willReturn("consorg2");
    
    $this->entityQuery->execute()->willReturn(["consorg1","consorg2"]);
    $this->entityNodeStorage->loadMultiple(['consorg1', 'consorg2'])->willReturn([$this->consorgNode1, $this->consorgNode2]);
    $this->consorgNode1->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e'));
    $this->consorgNode2->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e2'));
    $this->consorgNode1->get('application_consumer_org_url')->willReturn($this->createSimpleObject('value', 'consorg1'));
    $this->consorgNode1->id()->willReturn('1a112v76-6cfa-4486-9637-80a8d3e11aa11');
    $this->consorgNode2->get('application_consumer_org_url')->willReturn($this->createSimpleObject('value', 'consorg2'));
    $this->consorgNode2->id()->willReturn('2b222v76-6cfa-4486-9637-80a8d3e22bb22');
    $this->entityQuery->condition('type', 'consumerorg')->willReturn(NULL);
    $this->entityQuery->condition('consumerorg_url.value', "consorg1")->willReturn(NULL);
    $this->entityQuery->condition('consumerorg_url.value', "consorg2")->willReturn(NULL);
    $this->account1->getEmail()->willReturn("fdh@test.com");
    $this->account2->getEmail()->willReturn("fdh2@test.com");
    $this->emailValidator->isValid("fdh@test.com")->willReturn(TRUE);
    $this->emailValidator->isValid("fdh2@test.com")->willReturn(TRUE);
    $this->userStorageInterface->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e')
      ->willReturn($this->account1);
    $this->userStorageInterface->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e2')
      ->willReturn($this->account2);

    $this->consorgNode1->reveal();
    $this->consorgNode2->reveal();
    $this->consorgNode3->reveal();
    $this->consorgNode4->reveal();
    $this->account1->reveal();
    $this->account2->reveal();

    $expectedRecipients = [
      '1a112v76-6cfa-4486-9637-80a8d3e11aa11' => ['fdh@test.com'],
      '2b222v76-6cfa-4486-9637-80a8d3e22bb22' => ['fdh2@test.com'],
    ];

    $mailService = new MailService($this->userStorageInterface->reveal(), $this->entityTypeManager->reveal(), $this->emailValidator->reveal());
    $recipients = $mailService->getProductSubscribers('/consumer-api/products/c025bc00-92ab-413a-af78-34eabdecacac:default-plan', 'owners');

    $this->assertEquals($expectedRecipients, $recipients);
  }

  public function testGetProductSubscribingMembersWithPlan(): void {
    $this->subscriptionQuery->condition('product_url', "/consumer-api/products/c025bc00-92ab-413a-af78-34eabdecacac")->willReturn(NULL);
    $this->subscriptionQuery->condition('plan', "default-plan")->willReturn(NULL);
    $this->subscriptionQuery->execute()->willReturn(['sub1', 'sub2']);
    $this->entitySubscriptionStorage->load("sub1")->willReturn($this->sub1);
    $this->entitySubscriptionStorage->load("sub2")->willReturn($this->sub2);
    $this->sub1->consumerorg_url()->willReturn("consorg1");
    $this->sub2->consumerorg_url()->willReturn("consorg2");

    $this->entityQuery->execute()->willReturn(["consorg1","consorg2"]);
    $this->entityNodeStorage->loadMultiple(['consorg1', 'consorg2'])->willReturn([$this->consorgNode1, $this->consorgNode2]);
    $this->memberString1->getValue()->willReturn([['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:12:"fdh@test.com";}}'], ['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:13:"fdh2@test.com";}}']]);
    $this->consorgNode1->get('consumerorg_members')->willReturn($this->memberString1);
    $this->consorgNode1->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e'));
    $this->memberString2->getValue()->willReturn([['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:12:"fdh@test.com";}}'], ['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:13:"fdh2@test.com";}}']]);
    $this->consorgNode2->get('consumerorg_members')->willReturn($this->memberString1);
    $this->consorgNode2->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e2'));
    $this->consorgNode1->get('application_consumer_org_url')->willReturn($this->createSimpleObject('value', 'consorg1'));
    $this->consorgNode1->id()->willReturn('1a112v76-6cfa-4486-9637-80a8d3e11aa11');
    $this->consorgNode2->get('application_consumer_org_url')->willReturn($this->createSimpleObject('value', 'consorg2'));
    $this->consorgNode2->id()->willReturn('2b222v76-6cfa-4486-9637-80a8d3e22bb22');
    $this->entityQuery->condition('type', 'consumerorg')->willReturn(NULL);
    $this->entityQuery->condition('consumerorg_url.value', "consorg1")->willReturn(NULL);
    $this->entityQuery->condition('consumerorg_url.value', "consorg2")->willReturn(NULL);
    $this->account1->getEmail()->willReturn("fdh@test.com");
    $this->account2->getEmail()->willReturn("fdh2@test.com");
    $this->emailValidator->isValid("fdh@test.com")->willReturn(TRUE);
    $this->emailValidator->isValid("fdh2@test.com")->willReturn(TRUE);
    $this->userStorageInterface->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e')
      ->willReturn($this->account1);
    $this->userStorageInterface->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e2')
      ->willReturn($this->account2);

    $this->consorgNode1->reveal();
    $this->consorgNode2->reveal();
    $this->memberString1->reveal();
    $this->memberString2->reveal();
    $this->account1->reveal();
    $this->account2->reveal();

    $expectedRecipients = [
      '1a112v76-6cfa-4486-9637-80a8d3e11aa11' => ['fdh@test.com', 'fdh2@test.com'],
      '2b222v76-6cfa-4486-9637-80a8d3e22bb22' => ['fdh@test.com', 'fdh2@test.com'],
    ];

    $mailService = new MailService($this->userStorageInterface->reveal(), $this->entityTypeManager->reveal(), $this->emailValidator->reveal());
    $recipients = $mailService->getProductSubscribers('/consumer-api/products/c025bc00-92ab-413a-af78-34eabdecacac:default-plan', 'members');

    $this->assertEquals($expectedRecipients, $recipients);
  }

  public function testGetApiSubscribersMembers(): void {
    $this->entityNodeStorage->load("100")->willReturn($this->apiNode1);

    $this->entityQuery->condition('type', 'product')->willReturn(NULL);
    $this->apiNode1->get('apic_ref')->willReturn($this->createSimpleObject('value', 'api1'));
    $this->entityQuery->condition('product_apis.value', 'api1', 'CONTAINS')->willReturn(NULL);

    $this->entityQuery->execute()->willReturn(['prodnid1', 'prodnid2'], ['consorg1'], ['consorg2']);

    $this->prodNode1->get('apic_url')->willReturn($this->createSimpleObject('value', 'url1'));
    $this->prodNode2->get('apic_url')->willReturn($this->createSimpleObject('value', 'url2'));

    $this->entityNodeStorage->load("prodnid1")->willReturn($this->prodNode1);
    $this->entityNodeStorage->load("prodnid2")->willReturn($this->prodNode2);

    $this->subscriptionQuery->condition('product_url','url1')->willReturn(NULL);
    $this->subscriptionQuery->condition('product_url','url2')->willReturn(NULL);

    $this->entityQuery->condition('type', 'application')->willReturn(NULL);


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
    $this->entityNodeStorage->loadMultiple(['consorg1'])->willReturn([$this->consorgNode1]);
    $this->consorgNode1->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e'));
    $this->memberString1->getValue()->willReturn([['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:12:"fdh@test.com";}}'], ['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:13:"fdh2@test.com";}}']]);
    $this->consorgNode1->get('consumerorg_members')->willReturn($this->memberString1);
    $this->consorgNode1->id()->willReturn('1a112v76-6cfa-4486-9637-80a8d3e11aa11');
    $this->account1->getEmail()->willReturn("fdh@test.com");
    $this->account2->getEmail()->willReturn("fdh2@test.com");
    $this->emailValidator->isValid("fdh@test.com")->willReturn(TRUE);
    $this->emailValidator->isValid("fdh2@test.com")->willReturn(TRUE);
    $this->userStorageInterface->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e')
      ->willReturn($this->account1);
    $this->userStorageInterface->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e2')
      ->willReturn($this->account2);

    $this->entityQuery->condition('type', 'consumerorg')->willReturn(NULL);
    $this->entityQuery->condition('consumerorg_url.value', 'org2')->willReturn(NULL);
    $this->entityNodeStorage->loadMultiple(['consorg2'])->willReturn([$this->consorgNode2]);
    $this->consorgNode2->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e2'));
    $this->memberString2->getValue()->willReturn([['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:12:"fdh@test.com";}}'], ['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:13:"fdh2@test.com";}}']]);
    $this->consorgNode2->get('consumerorg_members')->willReturn($this->memberString2);
    $this->consorgNode2->id()->willReturn('2b222v76-6cfa-4486-9637-80a8d3e22bb22');
    $this->account1->getEmail()->willReturn("fdh@test.com");
    $this->account2->getEmail()->willReturn("fdh2@test.com");
    $this->emailValidator->isValid("fdh@test.com")->willReturn(TRUE);
    $this->emailValidator->isValid("fdh2@test.com")->willReturn(TRUE);
    $this->userStorageInterface->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e')
      ->willReturn($this->account1);
    $this->userStorageInterface->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e2')
      ->willReturn($this->account2);

    $expectedRecipients = [
      '1a112v76-6cfa-4486-9637-80a8d3e11aa11' => ['fdh@test.com', 'fdh2@test.com'],
      '2b222v76-6cfa-4486-9637-80a8d3e22bb22' => ['fdh@test.com', 'fdh2@test.com'],
    ];

    $mailService = new MailService($this->userStorageInterface->reveal(), $this->entityTypeManager->reveal(), $this->emailValidator->reveal());
    $recipients = $mailService->getAPISubscribers(100, 'members');
    $this->assertEquals($expectedRecipients, $recipients);
  }

  public function testGetApiSubscribersOwners(): void {
    $this->entityNodeStorage->load("100")->willReturn($this->apiNode1);

    $this->entityQuery->condition('type', 'product')->willReturn(NULL);
    $this->apiNode1->get('apic_ref')->willReturn($this->createSimpleObject('value', 'api1'));
    $this->entityQuery->condition('product_apis.value', 'api1', 'CONTAINS')->willReturn(NULL);

    $this->entityQuery->execute()->willReturn(['prodnid1', 'prodnid2'], ['consorg1'], ['consorg2']);

    $this->prodNode1->get('apic_url')->willReturn($this->createSimpleObject('value', 'url1'));
    $this->prodNode2->get('apic_url')->willReturn($this->createSimpleObject('value', 'url2'));

    $this->entityNodeStorage->load("prodnid1")->willReturn($this->prodNode1);
    $this->entityNodeStorage->load("prodnid2")->willReturn($this->prodNode2);

    $this->subscriptionQuery->condition('product_url','url1')->willReturn(NULL);
    $this->subscriptionQuery->condition('product_url','url2')->willReturn(NULL);

    $this->entityQuery->condition('type', 'application')->willReturn(NULL);


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
    $this->entityNodeStorage->loadMultiple(['consorg1'])->willReturn([$this->consorgNode1]);
    $this->consorgNode1->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e'));
    $this->memberString1->getValue()->willReturn([['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:12:"fdh@test.com";}}'], ['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:13:"fdh2@test.com";}}']]);
    $this->consorgNode1->get('consumerorg_members')->willReturn($this->memberString1);
    $this->consorgNode1->id()->willReturn('1a112v76-6cfa-4486-9637-80a8d3e11aa11');
    $this->account1->getEmail()->willReturn("fdh@test.com");
    $this->account2->getEmail()->willReturn("fdh2@test.com");
    $this->emailValidator->isValid("fdh@test.com")->willReturn(TRUE);
    $this->emailValidator->isValid("fdh2@test.com")->willReturn(TRUE);
    $this->userStorageInterface->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e')
      ->willReturn($this->account1);
    $this->userStorageInterface->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e2')
      ->willReturn($this->account2);

    $this->entityQuery->condition('type', 'consumerorg')->willReturn(NULL);
    $this->entityQuery->condition('consumerorg_url.value', 'org2')->willReturn(NULL);
    $this->entityNodeStorage->loadMultiple(['consorg2'])->willReturn([$this->consorgNode2]);
    $this->consorgNode2->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e2'));
    $this->memberString2->getValue()->willReturn([['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:12:"fdh@test.com";}}'], ['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:13:"fdh2@test.com";}}']]);
    $this->consorgNode2->get('consumerorg_members')->willReturn($this->memberString2);
    $this->consorgNode2->id()->willReturn('2b222v76-6cfa-4486-9637-80a8d3e22bb22');
    $this->account1->getEmail()->willReturn("fdh@test.com");
    $this->account2->getEmail()->willReturn("fdh2@test.com");
    $this->emailValidator->isValid("fdh@test.com")->willReturn(TRUE);
    $this->emailValidator->isValid("fdh2@test.com")->willReturn(TRUE);
    $this->userStorageInterface->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e')
      ->willReturn($this->account1);
    $this->userStorageInterface->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e2')
      ->willReturn($this->account2);

    $expectedRecipients = [
      '1a112v76-6cfa-4486-9637-80a8d3e11aa11' => ['fdh@test.com'],
      '2b222v76-6cfa-4486-9637-80a8d3e22bb22' => ['fdh2@test.com'],
    ];

    $mailService = new MailService($this->userStorageInterface->reveal(), $this->entityTypeManager->reveal(), $this->emailValidator->reveal());
    $recipients = $mailService->getAPISubscribers(100, 'owners');
    $this->assertEquals($expectedRecipients, $recipients);
  }

  public function testGetAllSubscribingOwners(): void {
    $this->entityQuery->condition('type', 'consumerorg')->willReturn(NULL);
    $this->entityQuery->execute()->willReturn(['consorg1', 'consorg2']);
    $this->entityNodeStorage->loadMultiple(["consorg1", "consorg2"])->willReturn([$this->consorgNode1, $this->consorgNode2]);
    $this->consorgNode1->get('application_consumer_org_url')->willReturn($this->createSimpleObject('value', 'org1'));
    $this->consorgNode2->get('application_consumer_org_url')->willReturn($this->createSimpleObject('value', 'org2'));
    $this->consorgNode1->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e'));
    $this->consorgNode2->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05d'));
    $this->consorgNode1->id()->willReturn('1a112v76-6cfa-4486-9637-80a8d3e11aa11');
    $this->consorgNode2->id()->willReturn('2b222v76-6cfa-4486-9637-80a8d3e22bb22');
    $this->account1->getEmail()->willReturn("fdh@test.com");
    $this->account2->getEmail()->willReturn("fdh2@test.com");
    $this->emailValidator->isValid("fdh@test.com")->willReturn(TRUE);
    $this->emailValidator->isValid("fdh2@test.com")->willReturn(TRUE);
    $this->userStorageInterface->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e')
      ->willReturn($this->account1);
    $this->userStorageInterface->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05d')
      ->willReturn($this->account2);

    $this->appNode1->reveal();
    $this->appNode2->reveal();
    $this->account1->reveal();
    $this->account2->reveal();

    $expectedRecipients = [
      '1a112v76-6cfa-4486-9637-80a8d3e11aa11' => ['fdh@test.com'],
      '2b222v76-6cfa-4486-9637-80a8d3e22bb22' => ['fdh2@test.com'],
    ];

    $mailService = new MailService($this->userStorageInterface->reveal(), $this->entityTypeManager->reveal(), $this->emailValidator->reveal());
    $recipients = $mailService->getAllSubscribers('owners');

    $this->assertEquals($expectedRecipients, $recipients);
  }

  public function testGetAllSubscribingMembers(): void {
    $this->entityQuery->condition('type', 'consumerorg')->willReturn(NULL);
    $this->entityQuery->execute()->willReturn(["consorg1","consorg2"]);
    $this->entityNodeStorage->loadMultiple(['consorg1', 'consorg2'])->willReturn([$this->consorgNode1, $this->consorgNode2]);
    $this->memberString1->getValue()->willReturn([['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:12:"fdh@test.com";}}'], ['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:13:"fdh2@test.com";}}']]);
    $this->consorgNode1->get('consumerorg_members')->willReturn($this->memberString1);
    $this->consorgNode1->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e'));
    $this->memberString2->getValue()->willReturn([['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:12:"fdh@test.com";}}'], ['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:13:"fdh2@test.com";}}']]);
    $this->consorgNode2->get('consumerorg_members')->willReturn($this->memberString1);
    $this->consorgNode2->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e2'));
    $this->consorgNode1->get('application_consumer_org_url')->willReturn($this->createSimpleObject('value', 'consorg1'));
    $this->consorgNode1->id()->willReturn('1a112v76-6cfa-4486-9637-80a8d3e11aa11');
    $this->consorgNode2->get('application_consumer_org_url')->willReturn($this->createSimpleObject('value', 'consorg2'));
    $this->consorgNode2->id()->willReturn('2b222v76-6cfa-4486-9637-80a8d3e22bb22');
    $this->entityQuery->condition('type', 'consumerorg')->willReturn(NULL);
    $this->entityQuery->condition('consumerorg_url.value', "consorg1")->willReturn(NULL);
    $this->entityQuery->condition('consumerorg_url.value', "consorg2")->willReturn(NULL);
    $this->account1->getEmail()->willReturn("fdh@test.com");
    $this->account2->getEmail()->willReturn("fdh2@test.com");
    $this->emailValidator->isValid("fdh@test.com")->willReturn(TRUE);
    $this->emailValidator->isValid("fdh2@test.com")->willReturn(TRUE);
    $this->userStorageInterface->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e')
      ->willReturn($this->account1);
    $this->userStorageInterface->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e2')
      ->willReturn($this->account2);

    $this->consorgNode1->reveal();
    $this->consorgNode2->reveal();
    $this->memberString1->reveal();
    $this->memberString2->reveal();
    $this->account1->reveal();
    $this->account2->reveal();

    $expectedRecipients = [
      '1a112v76-6cfa-4486-9637-80a8d3e11aa11' => ['fdh@test.com', 'fdh2@test.com'],
      '2b222v76-6cfa-4486-9637-80a8d3e22bb22' => ['fdh@test.com', 'fdh2@test.com'],
    ];

    $mailService = new MailService($this->userStorageInterface->reveal(), $this->entityTypeManager->reveal(), $this->emailValidator->reveal());
    $recipients = $mailService->getAllSubscribers('members');

    $this->assertEquals($expectedRecipients, $recipients);
  }

  private function createSimpleObject($name, $value): \stdClass {
    $c = new \stdClass();
    $c->$name = $value;
    return $c;
  }

}