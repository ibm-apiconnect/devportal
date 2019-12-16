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

  protected $consorgNode1;

  protected $consorgNode2;

  protected $consorgNode3;

  protected $consorgNode4;

  protected $apiNode1;

  protected $prodNode1;

  protected $appNode1;

  protected $appNode2;

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

    $this->entityTypeManager->getStorage('node')->willReturn($this->entityNodeStorage->reveal());

    $this->entityQuery = $this->prophet->prophesize(\Drupal\Core\Entity\Query\QueryInterface::class);

    $this->consorgNode1 = $this->prophet->prophesize(\Drupal\node\Entity\Node::class);
    $this->consorgNode2 = $this->prophet->prophesize(\Drupal\node\Entity\Node::class);
    $this->consorgNode3 = $this->prophet->prophesize(\Drupal\node\Entity\Node::class);
    $this->consorgNode4 = $this->prophet->prophesize(\Drupal\node\Entity\Node::class);
    $this->apiNode1 = $this->prophet->prophesize(\Drupal\node\Entity\Node::class);
    $this->prodNode1 = $this->prophet->prophesize(\Drupal\node\Entity\Node::class);
    $this->appNode1 = $this->prophet->prophesize(\Drupal\node\Entity\Node::class);
    $this->appNode2 = $this->prophet->prophesize(\Drupal\node\Entity\Node::class);

    $this->consorg1 = $this->prophet->prophesize(\Drupal\Core\TypedData\Plugin\DataType\ItemList::class);
    $this->consorg2 = $this->prophet->prophesize(\Drupal\Core\TypedData\Plugin\DataType\ItemList::class);

    $this->consorgString1 = $this->prophet->prophesize(\Drupal\Core\TypedData\Plugin\DataType\StringData::class);
    $this->memberString1 = $this->prophet->prophesize(\Drupal\Core\TypedData\Plugin\DataType\StringData::class);
    $this->memberString2 = $this->prophet->prophesize(\Drupal\Core\TypedData\Plugin\DataType\StringData::class);

    $this->ownerString1 = $this->prophet->prophesize(\Drupal\Core\TypedData\Plugin\DataType\StringData::class);

    $this->account1 = $this->prophet->prophesize(\Drupal\Core\Session\AccountInterface::class);
    $this->account2 = $this->prophet->prophesize(\Drupal\Core\Session\AccountInterface::class);
    $this->entityNodeStorage->getQuery()->willReturn($this->entityQuery->reveal());
  }

  protected function tearDown() {
    $this->prophet->checkPredictions();
  }

  public function testGetProductSubscribingOwnersNoPlan(): void {
    $this->entityQuery->execute()->willReturn(['id1'], ['app1']);
    $this->entityQuery->condition('type', 'application')->willReturn(NULL);
    $this->entityQuery->condition('application_subscriptions.value', "", 'CONTAINS')->willReturn(NULL);
    $this->entityNodeStorage->loadMultiple(['id1'])->willReturn([$this->consorgNode1]);
    $this->entityNodeStorage->loadMultiple(['app1'])->willReturn([$this->consorgNode2]);
    $this->consorgNode2->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e'));
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

    $mailService = new MailService($this->userStorageInterface->reveal(), $this->entityTypeManager->reveal(), $this->emailValidator->reveal());
    $recipients = $mailService->getProductSubscribers('', 'owners');

    $this->assertEquals($recipients, ['fdh@test.com']);
  }

  public function testGetProductSubscribingMembersNoPlan(): void {
    $this->entityQuery->condition('type', 'application')->willReturn(NULL);
    $this->entityQuery->condition('application_subscriptions.value', "", 'CONTAINS')->willReturn(NULL);
    $this->entityQuery->execute()->willReturn(['id1'], ['consorg1']);
    $this->entityNodeStorage->loadMultiple(['id1'])->willReturn([$this->consorgNode1]);
    $this->consorgNode1->get('application_consumer_org_url')->willReturn($this->createSimpleObject('value', 'consorg1'));
    $this->entityQuery->condition('type', 'consumerorg')->willReturn(NULL);
    $this->entityQuery->condition('consumerorg_url.value', "consorg1")->willReturn(NULL);
    $this->entityNodeStorage->loadMultiple(['consorg1'])->willReturn([$this->consorgNode2]);
    $this->memberString1->getValue()->willReturn([['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:12:"fdh@test.com";}}']]);
    $this->consorgNode2->get('consumerorg_members')->willReturn($this->memberString1);
    $this->consorgNode2->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e'));
    $this->consorg1->get(0)->willReturn($this->consorgString1);
    $this->account1->getEmail()->willReturn("fdh@test.com");
    $this->emailValidator->isValid("fdh@test.com")->willReturn(TRUE);
    $this->userStorageInterface->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e')
      ->willReturn($this->account1);

    $this->consorgNode1->reveal();
    $this->consorgNode2->reveal();
    $this->memberString1->reveal();
    $this->account1->reveal();

    $mailService = new MailService($this->userStorageInterface->reveal(), $this->entityTypeManager->reveal(), $this->emailValidator->reveal());
    $recipients = $mailService->getProductSubscribers('', 'members');

    $this->assertEquals($recipients, ['fdh@test.com']);
  }

  public function testGetProductSubscribingOwnersWithPlan(): void {
    $this->entityQuery->condition('type', 'application')->willReturn(NULL);
    $this->entityQuery->condition('application_subscriptions.value', '/consumer-api/products/c025bc00-92ab-413a-af78-34eabdecacac";s:4:"plan";s:12:"default-plan"', 'CONTAINS')
      ->willReturn(NULL);
    $this->entityQuery->execute()->willReturn(['id1', 'id2'], ['app1', 'app2']);
    $this->entityQuery->condition('application_subscriptions.value', "", 'CONTAINS')->willReturn(NULL);
    $this->entityNodeStorage->loadMultiple(['id1', 'id2'])->willReturn([$this->consorgNode1, $this->consorgNode2]);
    $this->entityNodeStorage->loadMultiple(['app1', 'app2'])->willReturn([$this->consorgNode3, $this->consorgNode4]);
    $this->consorgNode3->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e'));
    $this->consorgNode4->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e2'));
    $this->consorgNode1->get('application_consumer_org_url')->willReturn($this->createSimpleObject('value', 'consorg1'));
    $this->consorgNode2->get('application_consumer_org_url')->willReturn($this->createSimpleObject('value', 'consorg2'));
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

    $mailService = new MailService($this->userStorageInterface->reveal(), $this->entityTypeManager->reveal(), $this->emailValidator->reveal());
    $recipients = $mailService->getProductSubscribers('/consumer-api/products/c025bc00-92ab-413a-af78-34eabdecacac:default-plan', 'owners');

    $this->assertEquals($recipients, ['fdh@test.com', 'fdh2@test.com']);
  }

  public function testGetProductSubscribingMembersWithPlan(): void {
    $this->entityQuery->condition('type', 'application')->willReturn(NULL);
    $this->entityQuery->condition('application_subscriptions.value', '/consumer-api/products/c025bc00-92ab-413a-af78-34eabdecacac";s:4:"plan";s:12:"default-plan"', 'CONTAINS')
      ->willReturn(NULL);
    $this->entityQuery->execute()->willReturn(['id1'], ['app1']);
    $this->entityQuery->condition('type', 'application')->willReturn(NULL);
    $this->entityQuery->condition('application_subscriptions.value', "", 'CONTAINS')->willReturn(NULL);
    $this->entityNodeStorage->loadMultiple(['id1'])->willReturn([$this->consorgNode1]);
    $this->entityNodeStorage->loadMultiple(['app1'])->willReturn([$this->consorgNode2]);
    $this->memberString1->getValue()->willReturn([['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:12:"fdh@test.com";}}']]);
    $this->consorgNode2->get('consumerorg_members')->willReturn($this->memberString1);
    $this->consorgNode2->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e'));
    $this->consorg1->get(0)->willReturn($this->consorgString1);
    $this->consorgNode1->get('application_consumer_org_url')->willReturn($this->createSimpleObject('value', 'consorg1'));
    $this->consorgNode2->get('application_consumer_org_url')->willReturn($this->createSimpleObject('value', 'consorg2'));
    $this->entityQuery->condition('type', 'consumerorg')->willReturn(NULL);
    $this->entityQuery->condition('consumerorg_url.value', "consorg1")->willReturn(NULL);
    $this->account1->getEmail()->willReturn("fdh@test.com");
    $this->emailValidator->isValid("fdh@test.com")->willReturn(TRUE);
    $this->userStorageInterface->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e')
      ->willReturn($this->account1);

    $this->consorgNode1->reveal();
    $this->consorgNode2->reveal();
    $this->memberString1->reveal();
    $this->account1->reveal();

    $mailService = new MailService($this->userStorageInterface->reveal(), $this->entityTypeManager->reveal(), $this->emailValidator->reveal());
    $recipients = $mailService->getProductSubscribers('/consumer-api/products/c025bc00-92ab-413a-af78-34eabdecacac:default-plan', 'members');

    $this->assertEquals($recipients, ['fdh@test.com']);
  }

  public function testGetApiSubscribersMembers(): void {
    $this->entityNodeStorage->load("100")->willReturn($this->apiNode1);
    $this->entityNodeStorage->load("prodnid1")->willReturn($this->prodNode1);
    $this->entityQuery->execute()->willReturn(['prodnid1'], ['appnid1'], ['consorg1'], ['consorg3']);
    $this->entityQuery->condition('type', 'product')->willReturn(NULL);
    $this->entityQuery->condition('type', 'application')->willReturn(NULL);
    $this->apiNode1->get('apic_ref')->willReturn($this->createSimpleObject('value', 'api1'));
    $this->entityQuery->condition('product_apis.value', 'api1', 'CONTAINS')->willReturn(NULL);
    $this->prodNode1->get('apic_url')->willReturn($this->createSimpleObject('value', 'url1'));
    $this->entityQuery->condition('application_subscriptions.value', 'url1', 'CONTAINS')->willReturn(NULL);
    $this->entityNodeStorage->loadMultiple(['appnid1'])->willReturn([$this->appNode1]);
    $this->entityNodeStorage->loadMultiple(["consorg1"])->willReturn([$this->appNode2]);
    $this->appNode1->get('application_consumer_org_url')->willReturn($this->createSimpleObject('value', 'org1'));
    $this->appNode2->get('application_consumer_org_url')->willReturn($this->createSimpleObject('value', 'org1'));
    $this->appNode2->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e'));
    $this->entityQuery->condition('consumerorg_url.value', 'org1')->willReturn(NULL);
    $this->memberString1->getValue()->willReturn([['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:12:"fdh@test.com";}}']]);
    $this->appNode2->get('consumerorg_members')->willReturn($this->memberString1);
    $this->account1->getEmail()->willReturn("fdh@test.com");
    $this->emailValidator->isValid("fdh@test.com")->willReturn(TRUE);
    $this->userStorageInterface->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e')
      ->willReturn($this->account1);
    $this->entityQuery->condition('type', 'consumerorg')->willReturn(NULL);
    $this->entityQuery->condition('consumerorg_url.value', "consorg1")->willReturn(NULL);

    $this->apiNode1->reveal();
    $this->prodNode1->reveal();
    $this->appNode1->reveal();
    $this->appNode2->reveal();
    $this->memberString1->reveal();
    $this->account1->reveal();

    $mailService = new MailService($this->userStorageInterface->reveal(), $this->entityTypeManager->reveal(), $this->emailValidator->reveal());
    $recipients = $mailService->getAPISubscribers(100, 'members');

    $this->assertEquals($recipients, ['fdh@test.com']);
  }

  public function testGetAllSubscribingOwners(): void {
    $this->entityQuery->condition('type', 'consumerorg')->willReturn(NULL);
    $this->entityQuery->execute()->willReturn(['consorg1', 'consorg2']);
    $this->entityNodeStorage->loadMultiple(["consorg1", "consorg2"])->willReturn([$this->appNode1, $this->appNode2]);
    $this->appNode1->get('application_consumer_org_url')->willReturn($this->createSimpleObject('value', 'org1'));
    $this->appNode2->get('application_consumer_org_url')->willReturn($this->createSimpleObject('value', 'org2'));
    $this->appNode1->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e'));
    $this->appNode2->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05d'));
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

    $mailService = new MailService($this->userStorageInterface->reveal(), $this->entityTypeManager->reveal(), $this->emailValidator->reveal());
    $recipients = $mailService->getAllSubscribers('owners');

    $this->assertEquals($recipients, ['fdh@test.com', 'fdh2@test.com']);
  }

  public function testGetAllSubscribingMembers(): void {
    $this->entityQuery->condition('type', 'consumerorg')->willReturn(NULL);
    $this->entityQuery->execute()->willReturn(['consorg1']);
    $this->entityNodeStorage->loadMultiple(["consorg1"])->willReturn([$this->consorgNode1]);
    $this->consorgNode1->get('consumerorg_owner')
      ->willReturn($this->createSimpleObject('value', '/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e'));
    $this->memberString1->getValue()->willReturn([['value' => 'a:1:{s:4:"user";a:1:{s:4:"mail";s:12:"fdh@test.com";}}']]);
    $this->consorgNode1->get('consumerorg_members')->willReturn($this->memberString1);
    $this->account1->getEmail()->willReturn("fdh@test.com");
    $this->emailValidator->isValid("fdh@test.com")->willReturn(TRUE);
    $this->userStorageInterface->loadUserByUrl('/consumer-api/user-registries/3b115f76-6cfa-4486-9637-80a8d3e50c58/users/8294239d-3301-4cf6-b012-7aab7efbf05e')
      ->willReturn($this->account1);

    $this->consorgNode1->reveal();
    $this->memberString1->reveal();
    $this->account1->reveal();

    $mailService = new MailService($this->userStorageInterface->reveal(), $this->entityTypeManager->reveal(), $this->emailValidator->reveal());
    $recipients = $mailService->getAllSubscribers('members');

    $this->assertEquals($recipients, ['fdh@test.com']);
  }

  private function createSimpleObject($name, $value): \stdClass {
    $c = new \stdClass();
    $c->$name = $value;
    return $c;
  }

}