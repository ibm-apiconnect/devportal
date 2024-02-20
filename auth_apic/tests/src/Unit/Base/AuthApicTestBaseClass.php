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

namespace Drupal\Tests\auth_apic\Unit\Base;

use Drupal\Tests\UnitTestCase;
use Prophecy\Prophet;
use Prophecy\Argument;

abstract class AuthApicTestBaseClass extends UnitTestCase {

  protected Prophet $prophet;

  protected function setup(): void {
    $this->prophet = new Prophet();
  }

  protected function tearDown(): void {
    $this->prophet->checkPredictions();
  }

  /**
   * Common base for createAccountStub and createBlockedAccountStub
   *
   * @return \Drupal\user\Entity\User|\Prophecy\Prophecy\ObjectProphecy
   */
  protected function createAccountBase() {
    $account = $this->prophet->prophesize(\Drupal\user\Entity\User::class);
    $account->set('apic_state', Argument::any())->willReturn(NULL);

    $account->get('name')->willReturn($this->createSimpleObject('value', 'andre'));

    $account->get('first_name')->willReturn($this->createSimpleObject('value', 'abc'));
    $account->get('last_name')->willReturn($this->createSimpleObject('value', 'def'));
    $account->get('consumer_organization')->willReturn($this->createConsumerorgArray());
    $account->get('uid')->willReturn($this->createSimpleObject('value', '1'));
    $account->get('mail')->willReturn($this->createSimpleObject('value', 'abc@me.com'));
    $account->get('apic_user_registry_url')->willReturn($this->createSimpleObject('value', '/registry/idp1'));
    $account->get('registry_url')->willReturn($this->createSimpleObject('value', '/registry/idp1'));
    $account->get('apic_state')->willReturn($this->createSimpleObject('value', 'enabled'));
    $first_time_field = $this->prophet->prophesize(\Drupal\Core\Field\FieldItemList::class);
    $first_time_field->getString()->willReturn('0');
    $account->get('first_time_login')->willReturn($first_time_field);

    $consumerorg_url_field = $this->prophet->prophesize(\Drupal\Core\Field\FieldItemList::class);
    $consumerorg_url_field->getValue()->willReturn([['value' => '/consumer-orgs/1234/5678/9abc']]);
    $account->get('consumerorg_url')->willReturn($consumerorg_url_field);

    $account->set('first_name', 'abc')->willReturn(NULL);
    $account->set('last_name', 'def')->willReturn(NULL);
    $account->set('mail', 'abc@me.com')->willReturn(NULL);
    $account->set('consumerorg_url', [['value' => '/consumer-orgs/1234/5678/9abc']])->willReturn(NULL);
    $account->set('apic_user_registry_url', '/registry/idp1')->willReturn(NULL);
    $account->set('registry_url', '/registry/idp1')->willReturn(NULL);
    $account->set('apic_url', 'user/url')->willReturn(NULL);

    $account->hasField('apic_user_registry_url')->willReturn(TRUE);
    $account->hasField('registry_url')->willReturn(TRUE);
    $account->hasField('apic_state')->willReturn(TRUE);

    $account->setPassword(NULL)->willReturn(NULL);
    $account->save()->willReturn(NULL);
    $account->id()->willReturn(2);

    return $account;
  }

  /**
   * Create a valid unblocked user
   *
   * @return mixed
   */
  protected function createAccountStub() {
    $account = $this->createAccountBase();
    $account->get('status')->willReturn($this->createSimpleObject('value', 1));
    $account->isBlocked()->willReturn(FALSE);
    $account->delete()->willReturn(null);
    return $account->reveal();
  }

  /**
   * Create a blocked user
   *
   * @return mixed
   */
  protected function createBlockedAccountStub() {
    $account = $this->createAccountBase();
    $account->get('status')->willReturn($this->createSimpleObject('value', 0));
    $account->isBlocked()->willReturn(TRUE);

    return $account->reveal();
  }

  protected function createAccountStubFromDifferentRegistry() {
    $account = $this->createAccountBase();
    $account->set('apic_user_registry_url', '/registry/different')->willReturn(NULL);
    $account->get('apic_user_registry_url')->willReturn($this->createSimpleObject('value', '/registry/different'));
    $account->isBlocked()->willReturn(FALSE);
    return $account->reveal();
  }

  protected function createAccountStubNoConsumerOrgs() {
    $account = $this->createAccountBase();
    $account->set('consumerorg_url', NULL)->willReturn(NULL)->shouldBeCalled();
    $account->isBlocked()->willReturn(FALSE);
    return $account->reveal();
  }

  protected function createAccountStubForAdmin() {
    $account = $this->createAccountBase();
    $account->id()->willReturn(1)->shouldBeCalled();
    $account->isBlocked()->willReturn(FALSE);
    return $account->reveal();
  }

  protected function createAccountStubPending() {
    $account = $this->createAccountBase();
    $account->get('status')->willReturn($this->createSimpleObject('value', 1));
    $account->isBlocked()->willReturn(FALSE);
    $account->get('apic_state')->willReturn($this->createSimpleObject('value', 'pending'));

    return $account->reveal();
  }

  protected function createAccountStubNoState() {
    $account = $this->createAccountBase();
    $account->get('status')->willReturn($this->createSimpleObject('value', 1));
    $account->isBlocked()->willReturn(FALSE);
    $account->hasField('apic_state')->willReturn(FALSE);

    return $account->reveal();
  }

  private function createSimpleObject($name, $value): \stdClass {
    $c = new \stdClass();
    $c->$name = $value;
    return $c;
  }

  private function createConsumerorgArray(): array {
    $consumerorg = new \stdClass();
    $consumerorg->id = '999';
    $consumerorg->url = '/consumer-orgs/1234/5678/9abc';
    $consumerorg->name = 'org1';
    $consumerorg->title = 'org1';
    $consumerorg->roles = NULL;
    $consumerorg->tags = NULL;

    return [$consumerorg];
  }

  protected function createAccountFields($user): array {
    $data = [];

    $data['first_name'] = $user->getFirstname();
    $data['last_name'] = $user->getLastname();
    $data['pass'] = $user->getPassword();
    $data['email'] = $user->getMail();
    $data['mail'] = $user->getMail();
    $data['consumer_organization'] = $user->getOrganization();
    //$data['realm'] = $this->userRegistryService->get($user->getApicUserRegistryURL())->getRealm();
    $data['apic_url'] = $user->getUrl();
    $data['apic_user_registry_url'] = $user->getApicUserRegistryURL();
    $data['apic_idp'] = $user->getApicIDP();

    $data['status'] = 1;

    return $data;
  }


}
