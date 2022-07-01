<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2022
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_event_log\Entity;

use Drupal\ibm_event_log\EventLogInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines an Event log entity class.
 *
 * @ContentEntityType(
 *   id = "event_log",
 *   label = @Translation("Event Log"),
 *   label_singular = @Translation("Event Log"),
 *   label_plural = @Translation("Event Logs"),
 *   label_count = @PluralTranslation(
 *     singular = @Translation("event log"),
 *     plural = @Translation("event logs"),
 *   ),
 *   fieldable = FALSE,
 *   translatable = FALSE,
 *   base_table = "event_logs",
 *   handlers = {
 *     "access" = "Drupal\ibm_event_log\EventLogAccessControlHandler",
 *     "list_builder" = "Drupal\ibm_event_log\EventLogListBuilder",
 *     "view_builder" = "Drupal\ibm_event_log\EventLogViewBuilder",
 *     "views_data" = "Drupal\ibm_event_log\Views\EventLogViewsData",
 *     "storage_schema" = "Drupal\ibm_event_log\EventLogSchema",
 *   },
 *   entity_keys = {
 *     "id" = "id",
 *   }
 * )
 */
class EventLog extends ContentEntityBase implements EventLogInterface {

  /**
   * Drupal entity ID
   *
   * @var ?int
   */
  protected ?int $id = NULL;

  /**
   * The artifact type
   *
   * @var ?string
   */
  protected ?string $artifact_type = NULL;

  /**
   * The time the event happened
   *
   * @var ?int
   */
  protected ?int $timestamp = NULL;

  /**
   * The type of event.
   *
   * @var ?string
   */
  protected ?string $event = NULL;

  /**
   * The URL of the artifact.
   *
   * @var ?string
   */
  protected ?string $artifact_url = NULL;

  /**
   * The URL of the user who performed the event
   *
   * @var ?string
   */
  protected ?string $user_url = NULL;

  /**
   * The payment method integration (in JSON form).
   *
   * @var ?string
   */
  protected ?string $data = NULL;

  /**
   * The owning consumer organization URL.
   *
   * @var ?string
   */
  protected ?string $consumerorg_url = NULL;

  /**
   * The application URL.
   * This will only be set for events for apps, creds and subs
   *
   * @var ?string
   */
  protected ?string $app_url = NULL;

  public function id(): ?int {
    return $this->getId();
  }

  /**
   * Get the entity ID
   */
  public function getId(): ?int {
    return $this->id;
  }

  /**
   * Return the artifact type (e.g. application)
   */
  public function getArtifactType(): ?string {
    return $this->artifact_type;
  }

  /**
   * Return the artifact URL
   */
  public function getArtifactUrl(): ?string {
    return $this->artifact_url;
  }

  /**
   * Return the application URL
   */
  public function getAppUrl(): ?string {
    return $this->app_url;
  }

  /**
   * Return the event type (e.g. create)
   */
  public function getEvent(): ?string {
    return $this->event;
  }

  /**
   * Return the timestamp
   */
  public function getTimestamp(): ?int {
    return $this->timestamp;
  }

  /**
   * Return the event data
   */
  public function getData(): ?string {
    return $this->data;
  }

  /**
   * Return the consumer org url
   */
  public function getConsumerorgUrl(): ?string {
    return $this->consumerorg_url;
  }

  /**
   * Return the user URL
   */
  public function getUserUrl(): ?string {
    return $this->user_url;
  }

  /**
   * Convert the entity to an array
   *
   * @return array
   */
  public function toArray(): array {
    return [
      'id' => $this->id,
      'timestamp' => $this->timestamp,
      'artifact_type' => $this->artifact_type,
      'data' => $this->data,
      'consumerorg_url' => $this->consumerorg_url,
      'artifact_url' => $this->artifact_url,
      'app_url' => $this->app_url,
      'user_url' => $this->user_url,
      'event' => $this->event,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = [];

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Event Log entity'))
      ->setSetting('unsigned', TRUE)
      ->setReadOnly(TRUE);

    $fields['timestamp'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Timestamp'))
      ->setDescription(t('The timestamp the event happened'))
      ->setDefaultValue(0)
      ->setReadOnly(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'settings' => [
          'date_format' => 'medium',
          'custom_date_format' => '',
          'timezone' => '',
        ],
        'weight' => 10,
      ]);

    $fields['artifact_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Artifact Type'))
      ->setDescription(t('The type of artifact for this event'))
      ->setReadOnly(TRUE);

    $fields['artifact_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Artifact URL'))
      ->setDescription(t('The URL of the artifact for this event'))
      ->setReadOnly(TRUE);

    $fields['app_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Application URL'))
      ->setDescription(t('The URL of the application for this event'))
      ->setReadOnly(TRUE);

    $fields['user_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('User URL'))
      ->setDescription(t('The URL of the user triggering this event'))
      ->setReadOnly(TRUE);

    $fields['data'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Data'))
      ->setDescription(t('The additional data for this event'))
      ->setReadOnly(TRUE);

    $fields['consumerorg_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Consumer Organization URL'))
      ->setDescription(t('The URL of the consumer organization which owns the event'))
      ->setReadOnly(TRUE);

    $fields['event'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Event'))
      ->setDescription(t('The event type'))
      ->setReadOnly(TRUE);

    return $fields;
  }

}
