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

namespace Drupal\ibm_event_log;

use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\ibm_apim\Service\EventLogService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of EventLog entities.
 *
 * @see \Drupal\ibm_event_log\Entity\EventLog
 */
class EventLogListBuilder extends EntityListBuilder {

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected DateFormatter $dateService;

  /**
   * @var \Drupal\ibm_apim\Service\EventLogService
   */
  protected EventLogService $eventLogService;

  /**
   * Constructs a new NodeListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Datetime\DateFormatter $date_service
   *   The date service.
   * @param \Drupal\ibm_apim\Service\EventLogService $event_log_service
   *   The event log service
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, DateFormatter $date_service, EventLogService $event_log_service) {
    parent::__construct($entity_type, $storage);

    $this->dateService = $date_service;
    $this->eventLogService = $event_log_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter'),
      $container->get('ibm_apim.event_log'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    return [
      'timestamp' => [
        'data' => $this->t('Timestamp'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'text' => $this->t('Text'),
      'user' => [
        'data' => $this->t('User'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\ibm_event_log\Entity\EventLog $entity */
    $text = $this->eventLogService->createOutputMessage($entity);
    return [
      'timestamp' => $this->dateService->format($entity->getTimestamp(), 'medium'),
      'text' => [
        'data' => [
          '#markup' => $text,
        ],
      ],
      'user' => (!empty($entity->getUserUrl())) ? $entity->getUserUrl() : $this->t('API Provider'),
    ];
  }

}
