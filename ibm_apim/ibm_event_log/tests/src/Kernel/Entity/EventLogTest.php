<?php

namespace Drupal\Tests\ibm_event_log\Kernel\Entity;

use Drupal\ibm_event_log\Entity\EventLog;
use Drupal\KernelTests\KernelTestBase;

/**
 * Class EventLogTest.
 *
 * Tests getters and setters for the EventLog entity.
 *
 * @group ibm_event_log
 * @coversDefaultClass \Drupal\ibm_event_log\Entity\EventLog
 */
class EventLogTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['ibm_apim', 'ibm_event_log'];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('event_log');
  }

  /**
   * Tests for artifact getters.
   *
   * @covers ::getArtifactType
   * @covers ::getArtifactUrl
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function testArtifact(): void {
    $timestamp = time();
    $entity = EventLog::create([
      'timestamp' => $timestamp,
      'event' => 'create',
      'consumerorg_url' => '/org',
      'artifact_url' => '/foo',
      'app_url' => '/foo',
      'artifact_type' => 'Application',
      'data' => serialize([]),
      'user_url' => '/user',
    ]);
    $entity->enforceIsNew();
    $entity->save();
    $query = \Drupal::entityQuery('event_log');
    $query->condition('timestamp', $timestamp);
    $query->condition('event', 'create');
    $query->condition('consumerorg_url', '/org');
    $query->condition('artifact_url', '/foo');
    $query->condition('artifact_type', 'Application');
    $entityIds = $query->accessCheck()->execute();
    if (isset($entityIds) && !empty($entityIds)) {
      // an existing event has been found, no need to do anything more
      $entity = EventLog::load(array_shift($entityIds));
    }
    self::assertEquals('Application', $entity->getArtifactType());
    self::assertEquals('/foo', $entity->getArtifactUrl());
  }

}