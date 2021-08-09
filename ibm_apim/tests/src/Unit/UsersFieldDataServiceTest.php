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

namespace Drupal\Tests\ibm_apim\Unit;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Schema;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface;
use Drupal\ibm_apim\Service\UsersFieldDataService;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Prophecy\Prophet;
use Psr\Log\LoggerInterface;

class UsersFieldDataServiceTest extends UnitTestCase {

  private $prophet;

  protected $database;
  protected $logger;
  protected $schema;
  protected $entityTypeManager;
  protected $userRegistryService;

  protected function setup(): void {
    $this->prophet = new Prophet();
    $this->database = $this->prophet->prophesize(Connection::class);
    $this->logger = $this->prophet->prophesize(LoggerInterface::class);
    $this->schema = $this->prophet->prophesize(Schema::class);
    $this->entityTypeManager = $this->prophet->prophesize(EntityTypeManagerInterface::class);
    $this->userRegistryService = $this->prophet->prophesize(UserRegistryServiceInterface::class);

    $this->database->schema()->willReturn($this->schema->reveal());
  }

  protected function tearDown(): void {
    $this->prophet->checkPredictions();
  }

  public function testAddNameAndRegistryUniqueKey(): void {

    $schema = $this->schema;

    $this->schema->fieldExists('users_field_data', 'registry_url')->willReturn(TRUE);
    $this->schema->indexExists('users_field_data', 'user__name')->willReturn(TRUE);
    $this->schema->indexExists('users_field_data', 'user__name__registry')->willReturn(FALSE);
    $this->schema->dropUniqueKey('users_field_data', 'user__name')->shouldBeCalled();
    $this->logger->notice('Dropping %index from %table.', ['%index' => 'user__name', '%table' => 'users_field_data'])->shouldBeCalled();
    $this->schema->addUniqueKey('users_field_data', 'user__name__registry', ['name', 'registry_url', 'langcode'])
      ->shouldBeCalled()
      ->will(function ($args) use ($schema) {
        $schema->indexExists('users_field_data', 'user__name__registry')->willReturn(TRUE);
      });
    $this->logger->notice('Creating %index in %table.', ['%index' => 'user__name__registry', '%table' => 'users_field_data'])->shouldBeCalled();

    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = new UsersFieldDataService($this->database->reveal(),
      $this->logger->reveal(),
      $this->entityTypeManager->reveal(),
      $this->userRegistryService->reveal());

    $this->assertTrue($service->addNameAndRegistryUniqueKey());

  }

  public function testAddUniqueKeyNoField(): void {

    $this->schema->fieldExists('users_field_data', 'registry_url')->willReturn(FALSE);
    $this->schema->indexExists(Argument::any())->shouldNotBeCalled();
    $this->schema->indexExists('users_field_data', 'user__name__registry')->willReturn(FALSE);
    $this->schema->dropUniqueKey(Argument::any())->shouldNotBeCalled();
    $this->schema->addUniqueKey(Argument::any())->shouldNotBeCalled();

    $service = new UsersFieldDataService($this->database->reveal(),
      $this->logger->reveal(),
      $this->entityTypeManager->reveal(),
      $this->userRegistryService->reveal());

    $this->logger->error('%field is not available in %table. Unable to create unique key.', ['%field' => 'registry_url', '%table' => 'users_field_data'])->shouldBeCalled();

    $this->assertFalse($service->addNameAndRegistryUniqueKey());

  }

  public function testAddUniqueKeyNameIndexMissing(): void {

    $schema = $this->schema;

    $this->schema->fieldExists('users_field_data', 'registry_url')->willReturn(TRUE);
    $this->schema->indexExists('users_field_data', 'user__name')->willReturn(FALSE);
    $this->schema->dropUniqueKey(Argument::any())->shouldNotBeCalled();
    $this->schema->indexExists('users_field_data', 'user__name__registry')->willReturn(FALSE);
    $this->schema->addUniqueKey('users_field_data', 'user__name__registry', ['name', 'registry_url', 'langcode'])
      ->shouldBeCalled()
      ->will(function ($args) use ($schema) {
        $schema->indexExists('users_field_data', 'user__name__registry')->willReturn(TRUE);
      });
    $this->logger->notice('Creating %index in %table.', ['%index' => 'user__name__registry', '%table' => 'users_field_data'])->shouldBeCalled();

    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = new UsersFieldDataService($this->database->reveal(),
      $this->logger->reveal(),
      $this->entityTypeManager->reveal(),
      $this->userRegistryService->reveal());

    $this->assertTrue($service->addNameAndRegistryUniqueKey());

  }

  public function testAddUniqueKeyNameRegistryIndexExists(): void {

    $this->schema->fieldExists('users_field_data', 'registry_url')->willReturn(TRUE);
    $this->schema->indexExists('users_field_data', 'user__name')->willReturn(TRUE);
    $this->schema->dropUniqueKey('users_field_data', 'user__name')->shouldBeCalled();
    $this->logger->notice('Dropping %index from %table.', ['%index' => 'user__name', '%table' => 'users_field_data'])->shouldBeCalled();
    $this->schema->indexExists('users_field_data', 'user__name__registry')->willReturn(TRUE);
    $this->schema->addUniqueKey(Argument::any())->shouldNotBeCalled();

    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = new UsersFieldDataService($this->database->reveal(),
      $this->logger->reveal(),
      $this->entityTypeManager->reveal(),
      $this->userRegistryService->reveal());

    $this->assertTrue($service->addNameAndRegistryUniqueKey());

  }

  public function testAddUniqueKeyAlreadyRun(): void {

    $this->schema->fieldExists('users_field_data', 'registry_url')->willReturn(TRUE);
    $this->schema->indexExists('users_field_data', 'user__name')->willReturn(FALSE);
    $this->schema->dropUniqueKey(Argument::any())->shouldNotBeCalled();

    $this->schema->indexExists('users_field_data', 'user__name__registry')->willReturn(TRUE);
    $this->schema->addUniqueKey(Argument::any())->shouldNotBeCalled();

    $this->logger->error(Argument::any())->shouldNotBeCalled();

    $service = new UsersFieldDataService($this->database->reveal(),
      $this->logger->reveal(),
      $this->entityTypeManager->reveal(),
      $this->userRegistryService->reveal());

    $this->assertTrue($service->addNameAndRegistryUniqueKey());

  }




}
