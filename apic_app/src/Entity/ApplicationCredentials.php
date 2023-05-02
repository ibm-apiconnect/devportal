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

namespace Drupal\apic_app\Entity;

use Drupal\apic_app\ApplicationCredentialsInterface;
use Drupal\Core\Entity\ContentEntity;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines an Application Credentials entity class.
 *
 * @ContentEntityType(
 *   id = "apic_app_application_creds",
 *   label = @Translation("Application Credentials"),
 *   label_singular = @Translation("Application Credentials"),
 *   label_plural = @Translation("Application Credentials"),
 *   label_count = @PluralTranslation(
 *     singular = @Translation("application credentials"),
 *     plural = @Translation("application credentials"),
 *   ),
 *   handlers = {
 *     "storage_schema" = "Drupal\apic_app\ApplicationCredentialsSchema",
 *   },
 *   fieldable = FALSE,
 *   translatable = FALSE,
 *   base_table = "apic_app_application_creds",
 *   entity_keys = {
 *     "id" = "id"
 *   }
 * )
 */
class ApplicationCredentials extends ContentEntityBase implements ApplicationCredentialsInterface {

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->get('id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function uuid() {
    return $this->get('uuid')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function app_url() {
    return $this->get('app_url')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function client_id() {
    return $this->get('client_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function name() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function title() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->get('summary')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function consumerorg_url() {
    return $this->get('consumerorg_url')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function cred_url() {
    return $this->get('cred_url')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function created_at() {
    return $this->get('created_at')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function updated_at() {
    return $this->get('updated_at')->value;
  }

  public function toArray(): array {
    return [
      'id' => $this->get('id')->value,
      'uuid' => $this->get('uuid')->value,
      'cred_url' => $this->get('cred_url')->value,
      'title' => $this->get('title')->value,
      'summary' => $this->get('summary')->value,
      'name' => $this->get('name')->value,
      'consumerorg_url' => $this->get('consumerorg_url')->value,
      'client_id' => $this->get('client_id')->value,
      'app_url' => $this->get('app_url')->value,
      'created_at' => $this->get('created_at')->value,
      'updated_at' => $this->get('updated_at')->value,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = [];

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Application Credentials entity.'))
      ->setSetting('unsigned', TRUE)
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Application Credentials entity.'))
      ->setReadOnly(TRUE);

    $fields['app_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Application URL'))
      ->setDescription(t('The URL of the Application these credentials belongs to'))
      ->setReadOnly(TRUE);

    $fields['client_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Client ID'))
      ->setDescription(t('The client ID for these credentials'))
      ->setReadOnly(TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The credentials name'))
      ->setReadOnly(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The credentials title'))
      ->setReadOnly(TRUE);

    $fields['summary'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Summary'))
      ->setDescription(t('The credentials summary'))
      ->setReadOnly(TRUE);

    $fields['consumerorg_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Consumer Organization URL'))
      ->setDescription(t('The URL of the consumer organization which owns the Application'))
      ->setReadOnly(TRUE);

    $fields['cred_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Credentials URL'))
      ->setDescription(t('The URL of the credentials'))
      ->setReadOnly(TRUE);

    $fields['created_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Creation time'))
      ->setDescription(t('The APIC creation timestamp'))
      ->setDefaultValue(0)
      ->setReadOnly(TRUE);

    $fields['updated_at'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Modification time'))
      ->setDescription(t('The APIC modification timestamp'))
      ->setDefaultValue(0)
      ->setReadOnly(TRUE);

    return $fields;
  }

}
