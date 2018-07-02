<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\Tests\auth_apic\Unit\UserManager;

use Drupal\Tests\UnitTestCase;
use Drupal\auth_apic\Service\ApicUserManager;
use Prophecy\Prophet;

abstract class UserManagerTestBaseClass extends UnitTestCase {

  protected $prophet;

  /*
   Dependencies of ApicUserManager.
   */
  protected $logger;
  protected $database;
  protected $externalAuth;
  protected $mgmtServer;
  protected $consumerorg;
  protected $state;
  protected $config;
  protected $userRegistryService;
  protected $userService;
  protected $userUtils;
  protected $moduleHandler;

  /**
   *
   */
  protected function setup() {
    $this->prophet = new Prophet();
    $this->logger = $this->prophet->prophesize('Psr\Log\LoggerInterface');
    $this->database = $this->prophet->prophesize('Drupal\Core\Database\Connection');
    $this->mgmtServer = $this->prophet->prophesize('Drupal\ibm_apim\Service\APIMServer');
    $this->externalAuth = $this->prophet->prophesize('Drupal\externalauth\ExternalAuth');
    $this->consumerorg = $this->prophet->prophesize('Drupal\consumerorg\Service\ConsumerOrgService');
    $this->state = $this->prophet->prophesize('Drupal\Core\State\State');
    $this->config = $this->prophet->prophesize('Drupal\ibm_apim\Service\SiteConfig');
    $this->userRegistryService = $this->prophet->prophesize('Drupal\ibm_apim\Service\UserRegistryService');
    $this->userService = $this->prophet->prophesize('\Drupal\ibm_apim\Service\ApicUserService');
    $this->userUtils = $this->prophet->prophesize('\Drupal\ibm_apim\Service\UserUtils');
    $this->moduleHandler = $this->prophet->prophesize('\Drupal\Core\Extension\ModuleHandler');
  }

  protected function tearDown() {
    $this->prophet->checkPredictions();
  }

  /**
   * @return \Drupal\auth_apic\Service\ApicUserManager
   */
  protected function createUserManager(): \Drupal\auth_apic\Service\ApicUserManager {
    $userManager = new ApicUserManager($this->logger->reveal(),
      $this->database->reveal(),
      $this->externalAuth->reveal(),
      $this->mgmtServer->reveal(),
      $this->consumerorg->reveal(),
      $this->state->reveal(),
      $this->config->reveal(),
      $this->userRegistryService->reveal(),
      $this->userService->reveal(),
      $this->userUtils->reveal(),
      $this->moduleHandler->reveal()
    );
    return $userManager;
  }

  protected function createAccountStub() {
    $account = $this->prophet->prophesize('Drupal\user\Entity\User');

    $account->get('name')->willReturn($this->createSimpleObject('value', 'andre'));

    $account->get('first_name')->willReturn($this->createSimpleObject('value', 'abc'));
    $account->get('last_name')->willReturn($this->createSimpleObject('value', 'def'));
    $account->get('consumer_organization')->willReturn($this->createConsumerorgArray());
    $account->get('uid')->willReturn($this->createSimpleObject('value', '1'));
    $account->get('mail')->willReturn($this->createSimpleObject('value', 'abc@me.com'));
    $account->get('apic_user_registry_url')->willReturn($this->createSimpleObject('value', 'user/registry/url'));

    $consumerorg_url_field = $this->prophet->prophesize('Drupal\Core\Field\FieldItemList');
    $consumerorg_url_field->getValue()->willReturn(array(array("value" => '/consumer-orgs/1234/5678/9abc')));
    $account->get('consumerorg_url')->willReturn($consumerorg_url_field);

    $account->set('first_name', 'abc')->willReturn(NULL);
    $account->set('last_name', 'def')->willReturn(NULL);
    $account->set('mail', 'abc@me.com')->willReturn(NULL);
    $account->set('consumerorg_url', array(array("value" => "/consumer-orgs/1234/5678/9abc")))->willReturn(NULL);
    $account->set('apic_user_registry_url', 'user/registry/url')->willReturn(NULL);
    $account->set('apic_url', 'user/url')->willReturn(NULL);

    $account->setPassword(NULL)->willReturn(NULL);
    $account->save()->willReturn(NULL);
    $account->id()->willReturn(2);

    return $account->reveal();
  }

  private function createSimpleObject($name, $value) {
    $c = new \stdClass();
    $c->$name = $value;
    return $c;
  }

  private function createConsumerorgArray() {
    $consumerorg = new \stdClass();
    $consumerorg->id = '999';
    $consumerorg->url = '/consumer-orgs/1234/5678/9abc';
    $consumerorg->name = 'org1';
    $consumerorg->roles = NULL;
    $consumerorg->tags = NULL;

    return array($consumerorg);
  }

  protected function createAccountFields($user) {
    $data = array();

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