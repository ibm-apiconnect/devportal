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

namespace Drupal\Tests\ibm_apim\Unit;

use Drupal\ibm_apim\Service\ApicUserStorage;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Service\ApicUserService;
use Drupal\ibm_apim\Service\UserRegistryService;
use Drupal\Tests\auth_apic\Unit\Base\AuthApicTestBaseClass;
use Drupal\user\Entity\User;
use Psr\Log\LoggerInterface;

use Prophecy\Argument;

class ApicUserStorageTest extends AuthApicTestBaseClass {

  /*
   Dependencies of ApicUserStorage
   */
  private $entityTypeManager;
  private $registryService;
  private $userService;
  private $logger;

  private $userStorage;


  protected function setup() {
    parent::setup();

    $this->registryService = $this->prophet->prophesize(UserRegistryService::class);
    $this->userService = $this->prophet->prophesize(ApicUserService::class);
    $this->logger = $this->prophet->prophesize(LoggerInterface::class);

    $this->entityTypeManager = $this->prophet->prophesize(EntityTypeManagerInterface::class);
    $this->userStorage = $this->prophet->prophesize(EntityStorageInterface::class);

    $this->entityTypeManager->getStorage('user')->willReturn($this->userStorage->reveal());
  }

  protected function tearDown() {
    parent::tearDown();
  }

  // register() tests
  public function testRegisterNewUser() {

    $user = new ApicUser();
    $user->setApicUserRegistryUrl('/reg/url');
    $user->setUsername('andre');
    $user->setMail('andre@example.com');

    $fields = [];
    $fields['email'] = $user->getMail();
    $fields['mail'] = $user->getMail();
    $fields['apic_user_registry_url'] = $user->getApicUserRegistryUrl();
    $fields['registry_url'] = $user->getApicUserRegistryUrl();

    $this->userStorage->loadByProperties(['name' => 'andre', 'registry_url' => '/reg/url'])->willReturn([]);
    $this->userStorage->loadByProperties(['mail' => 'andre@example.com'])->willReturn([]);
    $this->userService->getUserAccountFields($user)->willReturn([$fields]);
    $new_account = $this->prophet->prophesize(User::class);
    $this->userStorage->create(Argument::type('array'))->willReturn($new_account)->shouldBeCalled();
    $new_account->enforceIsNew()->shouldBeCalled();
    $new_account->save()->shouldBeCalled();

    $this->logger->notice('Registration of apic user %name completed.',['%name' => 'andre'])->shouldBeCalled();

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal());

    $account = $service->register($user);

    $this->assertNotNull($account);

  }

  /**
   * @expectedException \Exception
   * @expectedExceptionMessage User could not be registered. There is already an account with username "andre"
   */
  public function testRegisterWithExistingUser() {

    $user = new ApicUser();
    $user->setApicUserRegistryUrl('/reg/url');
    $user->setUsername('andre');
    $user->setMail('andre@example.com');

    $this->userStorage->loadByProperties(['name' => 'andre', 'registry_url' => '/reg/url'])->willReturn([$this->prophet->prophesize(User::class)]);
    $this->userStorage->loadByProperties(['mail' => 'andre@example.com'])->willReturn([]);
    $this->userService->getUserAccountFields(Argument::any())->shouldNotBeCalled();
    $this->userStorage->create(Argument::any())->shouldNotBeCalled();

    $this->logger->notice(Argument::any())->shouldNotBeCalled();

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal());

    $service->register($user);
  }

    /**
   * @expectedException \Exception
   * @expectedExceptionMessage User could not be registered. There is already an account with email "andre@example.com"
   */
  public function testRegisterWithExistingEmail() {

    $user = new ApicUser();
    $user->setApicUserRegistryUrl('/reg/url');
    $user->setUsername('andre');
    $user->setMail('andre@example.com');

    $this->userStorage->loadByProperties(['name' => 'andre', 'registry_url' => '/reg/url'])->willReturn([]);
    $this->userStorage->loadByProperties(['mail' => 'andre@example.com'])->willReturn([$this->prophet->prophesize(User::class)]);
    $this->userService->getUserAccountFields(Argument::any())->shouldNotBeCalled();
    $this->userStorage->create(Argument::any())->shouldNotBeCalled();

    $this->logger->notice(Argument::any())->shouldNotBeCalled();

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal());

    $service->register($user);
  }

  /**
   * @expectedException \Exception
   * @expectedExceptionMessage User could not be registered both a username and registry_url are required.
   */
  public function testRegisterWithNoUserName() {

    $user = new ApicUser();
    $user->setApicUserRegistryUrl('/reg/url');
    $user->setMail('andre@example.com');

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal());

    $service->register($user);
  }

  /**
   * @expectedException \Exception
   * @expectedExceptionMessage User could not be registered both a username and registry_url are required.
   */
  public function testRegisterWithNoRegistryUrl() {

    $user = new ApicUser();
    $user->setUsername('andre');
    $user->setMail('andre@example.com');

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal());

    $service->register($user);
  }

  // load() tests
  public function testLoadValid() {

    $user = new ApicUser();
    $user->setApicUserRegistryUrl('/reg/url');
    $user->setUsername('andre');

    $this->userStorage->loadByProperties([
      'name' => 'andre',
      'registry_url' => '/reg/url'
    ])->willReturn([$this->prophet->prophesize(User::class)]);

    $this->logger->debug('loading %name in registry %registry', ['%name'=> 'andre', '%registry' => '/reg/url'])->shouldBeCalled();
    $this->logger->debug('loaded %num users', ['%num'=> 1])->shouldBeCalled();
    $this->logger->notice(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal());

    $user = $service->load($user);

    $this->assertNotNull($user);

  }

  /**
   * @expectedException \Exception
   * @expectedExceptionMessage Multiple users (2) returned matching username "andre" in registry_url "/reg/url"
   */
  public function testLoadFailMultiple() {

    $user = new ApicUser();
    $user->setApicUserRegistryUrl('/reg/url');
    $user->setUsername('andre');

    $this->userStorage->loadByProperties([
      'name' => 'andre',
      'registry_url' => '/reg/url'
    ])->willReturn([$this->prophet->prophesize(User::class), $this->prophet->prophesize(User::class)]);

    $this->logger->debug('loading %name in registry %registry', ['%name'=> 'andre', '%registry' => '/reg/url'])->shouldBeCalled();
    $this->logger->debug('loaded %num users', ['%num'=> 2])->shouldBeCalled();
    $this->logger->notice(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal());

    $service->load($user);

  }

  public function testLoadNone() {

    $user = new ApicUser();
    $user->setApicUserRegistryUrl('/reg/url');
    $user->setUsername('andre');

    $this->userStorage->loadByProperties([
      'name' => 'andre',
      'registry_url' => '/reg/url'
    ])->willReturn([]);

    $this->logger->debug('loading %name in registry %registry', ['%name'=> 'andre', '%registry' => '/reg/url'])->shouldBeCalled();
    $this->logger->debug('loaded %num users', ['%num'=> 0])->shouldBeCalled();
    $this->logger->notice(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal());

    $user = $service->load($user);

    $this->assertNull($user);

  }

  /**
   * @expectedException \Exception
   * @expectedExceptionMessage Registry url is missing, unable to load user.
   */
  public function testLoadNoRegistryUrl() {

    $user = new ApicUser();
    $user->setUsername('andre');

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
                                   $this->registryService->reveal(),
                                   $this->userService->reveal(),
                                   $this->logger->reveal());

    $service->load($user);

  }

  // loadByUsername tests
  public function testLoadByUsernameValid() {

    $this->userStorage->loadByProperties([
      'name' => 'andre'
    ])->willReturn([$this->prophet->prophesize(User::class)]);

    $this->logger->debug('loading %name', ['%name'=> 'andre'])->shouldBeCalled();
    $this->logger->debug('loaded %num users', ['%num'=> 1])->shouldBeCalled();
    $this->logger->notice(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal());

    $user = $service->loadByUsername('andre');

    $this->assertNotNull($user);
  }

  /**
   * @expectedException \Exception
   * @expectedExceptionMessage Multiple users (2) returned matching username "andre" unable to continue.
   */
  public function testLoadByUsernameFailMultiple() {

    $this->userStorage->loadByProperties([
      'name' => 'andre'
    ])->willReturn([$this->prophet->prophesize(User::class), $this->prophet->prophesize(User::class)]);

    $this->logger->debug('loading %name', ['%name'=> 'andre'])->shouldBeCalled();
    $this->logger->notice(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal());

    $service->loadByUsername('andre');

  }

  public function testLoadByUsernameNoResults() {

    $this->userStorage->loadByProperties([
      'name' => 'andre'
    ])->willReturn([]);

    $this->logger->debug('loading %name', ['%name'=> 'andre'])->shouldBeCalled();
    $this->logger->debug('loaded %num users', ['%num'=> 0])->shouldBeCalled();
    $this->logger->notice(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal());

    $user = $service->loadByUsername('andre');

    $this->assertNull($user);
  }

  // loadByEmailAddress() tests
  public function testLoadByEmailAddressValid() {

    $this->userStorage->loadByProperties([
      'mail' => 'andre@example.com'
    ])->willReturn([$this->prophet->prophesize(User::class)]);

    $this->logger->debug('loading by email: %mail', ['%mail'=> 'andre@example.com'])->shouldBeCalled();
    $this->logger->debug('loaded by email %num users', ['%num'=> 1])->shouldBeCalled();
    $this->logger->notice(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal());

    $user = $service->loadUserByEmailAddress('andre@example.com');

    $this->assertNotNull($user);
  }

  /**
   * @expectedException \Exception
   * @expectedExceptionMessage Multiple users (2) returned matching email "andre@example.com" unable to continue.
   */
  public function testLoadByEmailAddressFailMultiple() {

    $this->userStorage->loadByProperties([
      'mail' => 'andre@example.com'
    ])->willReturn([$this->prophet->prophesize(User::class), $this->prophet->prophesize(User::class)]);

    $this->logger->debug('loading by email: %mail', ['%mail'=> 'andre@example.com'])->shouldBeCalled();
    $this->logger->notice(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal());

    $service->loadUserByEmailAddress('andre@example.com');

  }

  public function testLoadByEmailAddressNoResults() {

    $this->userStorage->loadByProperties([
      'mail' => 'andre@example.com'
    ])->willReturn([]);

    $this->logger->debug('loading by email: %mail', ['%mail'=> 'andre@example.com'])->shouldBeCalled();
    $this->logger->debug('loaded by email %num users', ['%num'=> 0])->shouldBeCalled();
    $this->logger->notice(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal());

    $user = $service->loadUserByEmailAddress('andre@example.com');

    $this->assertNull($user);
  }

  // loadUserByUrl tests
  public function testLoadUserByUrlValid() {

    $this->userStorage->loadByProperties([
      'apic_url' => '/abc/1234'
    ])->willReturn([$this->prophet->prophesize(User::class)]);

    $this->logger->debug('loading user by url %url', ['%url'=> '/abc/1234'])->shouldBeCalled();
    $this->logger->debug('loaded %num users', ['%num'=> 1])->shouldBeCalled();
    $this->logger->notice(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal());

    $user = $service->loadUserByUrl('/abc/1234');

    $this->assertNotNull($user);
  }

  /**
   * @expectedException \Exception
   * @expectedExceptionMessage Multiple users (2) with url "/abc/1234" unable to continue.
   */
  public function testLoadUserByUrlFailMultiple() {

    $this->userStorage->loadByProperties([
      'apic_url' => '/abc/1234'
    ])->willReturn([$this->prophet->prophesize(User::class), $this->prophet->prophesize(User::class)]);

    $this->logger->debug('loading user by url %url', ['%url'=> '/abc/1234'])->shouldBeCalled();
    $this->logger->notice(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal());

    $service->loadUserByUrl('/abc/1234');

  }

  public function testLoadUserByUrlNoResults() {

    $this->userStorage->loadByProperties([
      'apic_url' => '/abc/1234'
    ])->willReturn([]);

    $this->logger->debug('loading user by url %url', ['%url'=> '/abc/1234'])->shouldBeCalled();
    $this->logger->debug('loaded %num users', ['%num'=> 0])->shouldBeCalled();
    $this->logger->notice(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal());

    $user = $service->loadUserByUrl('/abc/1234');

    $this->assertNull($user);
  }



}

