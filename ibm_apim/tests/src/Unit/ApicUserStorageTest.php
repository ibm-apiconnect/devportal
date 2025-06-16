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

namespace Drupal\Tests\ibm_apim\Unit;

use Drupal\ibm_apim\Service\ApicUserStorage;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Service\ApicUserService;
use Drupal\ibm_apim\Service\UserRegistryService;
use Drupal\ibm_apim\Service\Utils;
use Drupal\Tests\auth_apic\Unit\Base\AuthApicTestBaseClass;
use Drupal\user\Entity\User;
use Psr\Log\LoggerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\DependencyInjection\ContainerBuilder;

use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\ibm_apim\Service\ApicUserStorage
 *
 * @group ibm_apim
 */
class ApicUserStorageTest extends AuthApicTestBaseClass {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  private $entityTypeManager;

  /**
   * @var \Drupal\ibm_apim\Service\UserRegistryService|\Prophecy\Prophecy\ObjectProphecy
   */
  private $registryService;

  /**
   * @var \Drupal\ibm_apim\Service\ApicUserService|\Prophecy\Prophecy\ObjectProphecy
   */
  private $userService;

  /**
   * @var \Prophecy\Prophecy\ObjectProphecy|\Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  private $userStorage;

  /**
   * @var \Drupal\ibm_apim\Service\Utils|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $utils;

  /**
   * @var ConfigFactoryInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  private  $config;


  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function setup(): void {
    parent::setup();

    $this->registryService = $this->prophet->prophesize(UserRegistryService::class);
    $this->userService = $this->prophet->prophesize(ApicUserService::class);
    $this->logger = $this->prophet->prophesize(LoggerInterface::class);

    $this->entityTypeManager = $this->prophet->prophesize(EntityTypeManagerInterface::class);
    $this->userStorage = $this->prophet->prophesize(EntityStorageInterface::class);

    $this->entityTypeManager->getStorage('user')->willReturn($this->userStorage->reveal());

    $this->config = $this->prophet->prophesize(ConfigFactoryInterface::class);
    $ibm_config = $this->prophet->prophesize(ImmutableConfig::class);
    $ibm_config->get('snapshot_debug')->willReturn(TRUE);
    $this->config->get('ibm_apim.devel_settings')->willReturn($ibm_config->reveal());
    $this->utils = new Utils($this->logger->reveal(), $this->config->reveal());

    // $container = new ContainerBuilder();
    // $container->set('config.factory', $this->config->reveal());
    // \Drupal::setContainer($container);
  }

  // register() tests

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testRegisterNewUser(): void {

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

    $this->logger->notice('Registration of apic user %name completed.', ['%name' => 'andre'])->shouldBeCalled();

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal(),
      $this->utils,
    );

    $account = $service->register($user);

    self::assertNotNull($account);

  }

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testRegisterWithExistingUser(): void {
    $this->expectExceptionMessage("User could not be registered. There is already an account with username \"andre\"");
    $this->expectException(\Exception::class);

    $user = new ApicUser();
    $user->setApicUserRegistryUrl('/reg/url');
    $user->setUsername('andre');
    $user->setMail('andre@example.com');

    $this->userStorage->loadByProperties(['name' => 'andre', 'registry_url' => '/reg/url'])
      ->willReturn([$this->prophet->prophesize(User::class)]);
    $this->userStorage->loadByProperties(['mail' => 'andre@example.com'])->willReturn([]);
    $this->userService->getUserAccountFields(Argument::any())->shouldNotBeCalled();
    $this->userStorage->create(Argument::any())->shouldNotBeCalled();

    $this->logger->notice(Argument::any())->shouldNotBeCalled();

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal(),
      $this->utils);

    $service->register($user);
  }

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testRegisterWithExistingEmail(): void {
    $this->expectExceptionMessage("User could not be registered. There is already an account with email \"andre@example.com\"");
    $this->expectException(\Exception::class);

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
      $this->logger->reveal(),
      $this->utils);

    $service->register($user);
  }

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testRegisterWithNoUserName(): void {
    $this->expectExceptionMessage("User could not be registered both a username and registry_url are required.");
    $this->expectException(\Exception::class);

    $user = new ApicUser();
    $user->setApicUserRegistryUrl('/reg/url');
    $user->setMail('andre@example.com');

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal(),
      $this->utils);

    $service->register($user);
  }

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testRegisterWithNoRegistryUrl(): void {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("User could not be registered both a username and registry_url are required.");

    $user = new ApicUser();
    $user->setUsername('andre');
    $user->setMail('andre@example.com');

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal(),
      $this->utils);

    $service->register($user);
  }

  // load() tests

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testLoadValid(): void {

    $user = new ApicUser();
    $user->setApicUserRegistryUrl('/reg/url');
    $user->setUsername('andre');

    $this->userStorage->loadByProperties([
      'name' => 'andre',
      'registry_url' => '/reg/url',
    ])->willReturn([$this->prophet->prophesize(User::class)]);

    $this->logger->debug('loading %name in registry %registry', ['%name' => 'andre', '%registry' => '/reg/url'])->shouldBeCalled();
    $this->logger->debug('loaded %num users', ['%num' => 1])->shouldBeCalled();
    $this->logger->notice(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal(),
      $this->utils);

    $user = $service->load($user);

    self::assertNotNull($user);

  }

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testLoadFailMultiple(): void {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Multiple users (2) returned matching username \"andre\" in registry_url \"/reg/url\"");

    $user = new ApicUser();
    $user->setApicUserRegistryUrl('/reg/url');
    $user->setUsername('andre');

    $this->userStorage->loadByProperties([
      'name' => 'andre',
      'registry_url' => '/reg/url',
    ])->willReturn([$this->prophet->prophesize(User::class), $this->prophet->prophesize(User::class)]);

    $this->logger->debug('loading %name in registry %registry', ['%name' => 'andre', '%registry' => '/reg/url'])->shouldBeCalled();
    $this->logger->debug('loaded %num users', ['%num' => 2])->shouldBeCalled();
    $this->logger->notice(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal(),
      $this->utils);

    $service->load($user);
  }

  /**
   * @throws \Exception
   */
  public function testLoadNone(): void {
    $user = new ApicUser();
    $user->setApicUserRegistryUrl('/reg/url');
    $user->setUsername('andre');

    $this->userStorage->loadByProperties([
      'name' => 'andre',
      'registry_url' => '/reg/url',
    ])->willReturn([]);

    $this->logger->debug('loading %name in registry %registry', ['%name' => 'andre', '%registry' => '/reg/url'])->shouldBeCalled();
    $this->logger->debug('loaded %num users', ['%num' => 0])->shouldBeCalled();
    $this->logger->notice(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal(),
      $this->utils);

    $user = $service->load($user);
    self::assertNull($user);

  }

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testLoadNoRegistryUrl(): void {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Registry url is missing, unable to load user.");

    $user = new ApicUser();
    $user->setUsername('andre');

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal(),
      $this->utils);

    $service->load($user);
  }

  // loadByUsername tests

  /**
   * @throws \Exception
   */
  public function testLoadByUsernameValid(): void {

    $this->userStorage->loadByProperties([
      'name' => 'andre',
    ])->willReturn([$this->prophet->prophesize(User::class)]);

    $this->logger->debug('loading %name', ['%name' => 'andre'])->shouldBeCalled();
    $this->logger->debug('loaded %num users', ['%num' => 1])->shouldBeCalled();
    $this->logger->notice(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal(),
      $this->utils);

    $user = $service->loadByUsername('andre');

    self::assertNotNull($user);
  }

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testLoadByUsernameFailMultiple(): void {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Multiple users (2) returned matching username \"andre\" unable to continue.");

    $this->userStorage->loadByProperties([
      'name' => 'andre',
    ])->willReturn([$this->prophet->prophesize(User::class), $this->prophet->prophesize(User::class)]);

    $this->logger->debug('loading %name', ['%name' => 'andre'])->shouldBeCalled();
    $this->logger->notice(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal(),
      $this->utils);

    $service->loadByUsername('andre');
  }

  /**
   * @throws \Exception
   */
  public function testLoadByUsernameNoResults(): void {

    $this->userStorage->loadByProperties([
      'name' => 'andre',
    ])->willReturn([]);

    $this->logger->debug('loading %name', ['%name' => 'andre'])->shouldBeCalled();
    $this->logger->debug('loaded %num users', ['%num' => 0])->shouldBeCalled();
    $this->logger->notice(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal(),
      $this->utils);

    $user = $service->loadByUsername('andre');

    self::assertNull($user);
  }

  // loadByEmailAddress() tests

  /**
   * @throws \Exception
   */
  public function testLoadByEmailAddressValid(): void {

    $this->userStorage->loadByProperties([
      'mail' => 'andre@example.com',
    ])->willReturn([$this->prophet->prophesize(User::class)]);

    $this->logger->debug('loading by email: %mail', ['%mail' => 'andre@example.com'])->shouldBeCalled();
    $this->logger->debug('loaded by email %num users', ['%num' => 1])->shouldBeCalled();
    $this->logger->notice(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal(),
      $this->utils);

    $user = $service->loadUserByEmailAddress('andre@example.com');

    self::assertNotNull($user);
  }

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testLoadByEmailAddressFailMultiple(): void {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Multiple users (2) returned matching email \"andre@example.com\" unable to continue.");

    $this->userStorage->loadByProperties([
      'mail' => 'andre@example.com',
    ])->willReturn([$this->prophet->prophesize(User::class), $this->prophet->prophesize(User::class)]);

    $this->logger->debug('loading by email: %mail', ['%mail' => 'andre@example.com'])->shouldBeCalled();
    $this->logger->notice(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal(),
      $this->utils);

    $service->loadUserByEmailAddress('andre@example.com');
  }

  /**
   * @throws \Exception
   */
  public function testLoadByEmailAddressNoResults(): void {

    $this->userStorage->loadByProperties([
      'mail' => 'andre@example.com',
    ])->willReturn([]);

    $this->logger->debug('loading by email: %mail', ['%mail' => 'andre@example.com'])->shouldBeCalled();
    $this->logger->debug('loaded by email %num users', ['%num' => 0])->shouldBeCalled();
    $this->logger->notice(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal(),
      $this->utils);

    $user = $service->loadUserByEmailAddress('andre@example.com');

    self::assertNull($user);
  }

  // loadUserByUrl tests

  /**
   * @throws \Exception
   */
  public function testLoadUserByUrlValid(): void {

    $this->userStorage->loadByProperties([
      'apic_url' => '/abc/1234',
    ])->willReturn([$this->prophet->prophesize(User::class)]);

    $this->logger->debug('loading user by url %url', ['%url' => '/abc/1234'])->shouldBeCalled();
    $this->logger->debug('loaded %num users', ['%num' => 1])->shouldBeCalled();
    $this->logger->notice(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal(),
      $this->utils);

    $user = $service->loadUserByUrl('/abc/1234');

    self::assertNotNull($user);
  }

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testLoadUserByUrlFailMultiple(): void {
    $this->expectException(\Exception::class);
    $this->expectExceptionMessage("Multiple users (2) with url \"/abc/1234\" unable to continue.");

    $this->userStorage->loadByProperties([
      'apic_url' => '/abc/1234',
    ])->willReturn([$this->prophet->prophesize(User::class), $this->prophet->prophesize(User::class)]);

    $this->logger->debug('loading user by url %url', ['%url' => '/abc/1234'])->shouldBeCalled();
    $this->logger->notice(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal(),
      $this->utils);

    $service->loadUserByUrl('/abc/1234');

  }

  /**
   * @throws \Exception
   */
  public function testLoadUserByUrlNoResults(): void {

    $this->userStorage->loadByProperties([
      'apic_url' => '/abc/1234',
    ])->willReturn([]);

    $this->logger->debug('loading user by url %url', ['%url' => '/abc/1234'])->shouldBeCalled();
    $this->logger->debug('loaded %num users', ['%num' => 0])->shouldBeCalled();
    $this->logger->notice(Argument::any())->shouldNotBeCalled();
    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = new ApicUserStorage($this->entityTypeManager->reveal(),
      $this->registryService->reveal(),
      $this->userService->reveal(),
      $this->logger->reveal(),
      $this->utils);

    $user = $service->loadUserByUrl('/abc/1234');

    self::assertNull($user);
  }


}
