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

  public function id() {
    return $this->get('id')->value;
  }

  /**
   * Get the entity ID
   */
  public function getId() {
    return $this->get('id')->value;
  }

  /**
   * Return the artifact type (e.g. application)
   */
  public function getArtifactType() {
    return $this->get('artifact_type')->value;
  }

  /**
   * Return the artifact URL
   */
  public function getArtifactUrl() {
    return $this->get('artifact_url')->value;
  }

  /**
   * Return the application URL
   */
  public function getAppUrl() {
    return $this->get('app_url')->value;
  }

  /**
   * Return the event type (e.g. create)
   */
  public function getEvent() {
    return $this->get('event')->value;
  }

  /**
   * Return the timestamp
   */
  public function getTimestamp() {
    return $this->get('timestamp')->value;
  }

  /**
   * Return the event data
   */
  public function getData() {
    return $this->get('data')->value;
  }

  /**
   * Return the consumer org url
   */
  public function getConsumerorgUrl() {
    return $this->get('consumerorg_url')->value;
  }

  /**
   * Return the user URL
   */
  public function getUserUrl() {
    return $this->get('user_url')->value;
  }

  /**
   * Convert the entity to an array
   *
   * @return array
   */
  public function toArray(): array {
    return [
      'id' => $this->get('id')->value,
      'timestamp' => $this->get('timestamp')->value,
      'artifact_type' => $this->get('artifact_type')->value,
      'data' => $this->get('data')->value,
      'consumerorg_url' => $this->get('consumerorg_url')->value,
      'artifact_url' => $this->get('artifact_url')->value,
      'app_url' => $this->get('app_url')->value,
      'user_url' => $this->get('user_url')->value,
      'event' => $this->get('event')->value,
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
