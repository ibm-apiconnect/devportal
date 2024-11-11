<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2024
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\mail_subscribers\Entity;

use Drupal\mail_subscribers\EmailListInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines a Email List entity class.
 *
 * @ContentEntityType(
 *   id = "mail_subscribers_email_list",
 *   label = @Translation("Email list"),
 *   label_singular = @Translation("Email List"),
 *   label_plural = @Translation("Email lists"),
 *   label_count = @PluralTranslation(
 *     singular = @Translation("email list"),
 *     plural = @Translation("email lists"),
 *   ),
 *   fieldable = FALSE,
 *   translatable = FALSE,
 *   base_table = "mail_subscribers_email_list",
 *   entity_keys = {
 *     "id" = "id",
 *   }
 * )
 */
class EmailList extends ContentEntityBase implements EmailListInterface {

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
  public function title() {
    return $this->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function description() {
    return $this->get('description')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function data() {
    return $this->get('data')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = [];

    // Standard field, used as unique if primary index.
    $fields['id'] = BaseFieldDefinition::create('integer')
    ->setLabel(t('ID'))
    ->setDescription(t('The ID of the Subscription entity.'))
    ->setSetting('unsigned', TRUE)
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('string')
    ->setLabel(t('UUID'))
    ->setDescription(t('The UUID of the Subscription entity.'))
    ->setReadOnly(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
    ->setLabel(t('title'))
    ->setDescription(t('The title of the email list.'))
    ->setReadOnly(TRUE);

    $fields['description'] = BaseFieldDefinition::create('string')
    ->setLabel(t('Description'))
    ->setDescription(t('The description of the email list.'))
    ->setReadOnly(TRUE);

    $fields['data'] = BaseFieldDefinition::create('map')
    ->setLabel(t('Data'))
    ->setDescription(t('The email list data.'))
    ->setReadOnly(TRUE);

    return $fields;
  }

}
