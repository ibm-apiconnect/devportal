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

namespace Drupal\Tests\auth_apic\Unit\UserManager;

use Drupal\auth_apic\Service\ApicUserManager;
use Drupal\Tests\UnitTestCase;
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

  protected $config;

  protected $userRegistryService;

  protected $userService;

  protected $userUtils;

  protected $moduleHandler;

  protected $languageManager;

  protected $tempStore;

  /**
   *
   */
  protected function setup() {
    $this->prophet = new Prophet();
    $this->logger = $this->prophet->prophesize(\Psr\Log\LoggerInterface::class);
    $this->database = $this->prophet->prophesize(\Drupal\Core\Database\Connection::class);
    $this->mgmtServer = $this->prophet->prophesize(\Drupal\ibm_apim\Service\APIMServer::class);
    $this->externalAuth = $this->prophet->prophesize(\Drupal\externalauth\ExternalAuth::class);
    $this->consumerorg = $this->prophet->prophesize(\Drupal\consumerorg\Service\ConsumerOrgService::class);
    $this->config = $this->prophet->prophesize(\Drupal\ibm_apim\Service\SiteConfig::class);
    $this->userRegistryService = $this->prophet->prophesize(\Drupal\ibm_apim\Service\UserRegistryService::class);
    $this->userService = $this->prophet->prophesize(\Drupal\ibm_apim\Service\ApicUserService::class);
    $this->userUtils = $this->prophet->prophesize(\Drupal\ibm_apim\Service\UserUtils::class);
    $this->moduleHandler = $this->prophet->prophesize(\Drupal\Core\Extension\ModuleHandler::class);
    $this->languageManager = $this->prophet->prophesize(\Drupal\Core\Language\LanguageManager::class);
    $this->tempStore = $this->prophet->prophesize(\Drupal\Core\TempStore\PrivateTempStoreFactory::class);
  }

  protected function tearDown() {
    $this->prophet->checkPredictions();
  }

  /**
   * @return \Drupal\auth_apic\Service\ApicUserManager
   */
  protected function createUserManager(): ApicUserManager {
    $userManager = new ApicUserManager($this->logger->reveal(),
      $this->database->reveal(),
      $this->externalAuth->reveal(),
      $this->mgmtServer->reveal(),
      $this->consumerorg->reveal(),
      $this->config->reveal(),
      $this->userRegistryService->reveal(),
      $this->userService->reveal(),
      $this->userUtils->reveal(),
      $this->moduleHandler->reveal(),
      $this->languageManager->reveal(),
      $this->tempStore->reveal()
    );
    return $userManager;
  }

  /**
   * Common base for createAccountStub and createBlockedAccountStub
   *
   * @return mixed
   */
  protected function createAccountBase() {
    $account = $this->prophet->prophesize(\Drupal\user\Entity\User::class);

    $account->get('name')->willReturn($this->createSimpleObject('value', 'andre'));

    $account->get('first_name')->willReturn($this->createSimpleObject('value', 'abc'));
    $account->get('last_name')->willReturn($this->createSimpleObject('value', 'def'));
    $account->get('consumer_organization')->willReturn($this->createConsumerorgArray());
    $account->get('uid')->willReturn($this->createSimpleObject('value', '1'));
    $account->get('mail')->willReturn($this->createSimpleObject('value', 'abc@me.com'));
    $account->get('apic_user_registry_url')->willReturn($this->createSimpleObject('value', '/registry/idp1'));

    $consumerorg_url_field = $this->prophet->prophesize(\Drupal\Core\Field\FieldItemList::class);
    $consumerorg_url_field->getValue()->willReturn([['value' => '/consumer-orgs/1234/5678/9abc']]);
    $account->get('consumerorg_url')->willReturn($consumerorg_url_field);

    $account->set('first_name', 'abc')->willReturn(NULL);
    $account->set('last_name', 'def')->willReturn(NULL);
    $account->set('mail', 'abc@me.com')->willReturn(NULL);
    $account->set('consumerorg_url', [['value' => '/consumer-orgs/1234/5678/9abc']])->willReturn(NULL);
    $account->set('apic_user_registry_url', '/registry/idp1')->willReturn(NULL);
    $account->set('apic_url', 'user/url')->willReturn(NULL);

    $account->hasField('apic_user_registry_url')->willReturn(TRUE);

    $account->setPassword(NULL)->willReturn(NULL);
    $account->save()->willReturn(NULL);
    $account->id()->willReturn(2);

    return $account;
  }

  /**
   * Create a valid unblocked user
   * @return mixed
   */
  protected function createAccountStub() {
    $account = $this->createAccountBase();
    $account->get('status')->willReturn($this->createSimpleObject('value', 1));
    $account->isBlocked()->willReturn(FALSE);

    return $account->reveal();
  }

  /**
   * Create a blocked user
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
