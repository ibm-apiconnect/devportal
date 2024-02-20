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

namespace Drupal\consumerorg\Entity;

use Drupal\consumerorg\PaymentMethodInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines a Payment Method entity class.
 *
 * @ContentEntityType(
 *   id = "consumerorg_payment_method",
 *   label = @Translation("Consumer Organization Payment Method"),
 *   label_singular = @Translation("Consumer Organization Payment Methods"),
 *   label_plural = @Translation("Consumer Organization Payment Methods"),
 *   label_count = @PluralTranslation(
 *     singular = @Translation("consumer organization payment methods"),
 *     plural = @Translation("consumer organization payment methods"),
 *   ),
 *   handlers = {
 *     "storage_schema" = "Drupal\consumerorg\PaymentMethodSchema",
 *   },
 *   fieldable = FALSE,
 *   translatable = FALSE,
 *   base_table = "consumerorg_payment_methods",
 *   entity_keys = {
 *     "id" = "id"
 *   }
 * )
 */
class PaymentMethod extends ContentEntityBase implements PaymentMethodInterface {

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
   * @return string|null
   */
  public function title() {
    return $this->get('title')->value;
  }

  /**
   * @return string|null
   */
  public function payment_method_type_url() {
    return $this->get('payment_method_type_url')->value;
  }

  /**
   * @return string|null
   */
  public function billing_url() {
    return $this->get('billing_url')->value;
  }

  /**
   * @return string|null
   */
  public function configuration() {
    return $this->get('configuration')->value;
  }

  /**
   * @return string|null
   */
  public function consumerorg_url() {
    return $this->get('consumerorg_url')->value;
  }

  /**
   * @return int|null
   */
  public function created_at() {
    return $this->get('created_at')->value;
  }

  /**
   * @return int|null
   */
  public function updated_at() {
    return $this->get('updated_at')->value;
  }

  /**
   * @return string|null
   */
  public function created_by() {
    return $this->get('created_by')->value;
  }

  /**
   * @return string|null
   */
  public function updated_by() {
    return $this->get('updated_by')->value;
  }

  /**
   * @return array
   */
  public function toArray(): array {
    return [
      'id' => $this->get('id')->value,
      'uuid' => $this->get('uuid')->value,
      'title' => $this->get('title')->value,
      'configuration' => $this->get('configuration')->value,
      'consumerorg_url' => $this->get('consumerorg_url')->value,
      'billing_url' => $this->get('billing_url')->value,
      'payment_method_type_url' => $this->get('payment_method_type_url')->value,
      'created_at' => $this->get('created_at')->value,
      'updated_at' => $this->get('updated_at')->value,
      'created_by' => $this->get('created_by')->value,
      'updated_by' => $this->get('updated_by')->value,
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
      ->setDescription(t('The ID of the Payment Method entity.'))
      ->setSetting('unsigned', TRUE)
      ->setReadOnly(TRUE);

    $fields['uuid'] = BaseFieldDefinition::create('string')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Payment Method entity.'))
      ->setReadOnly(TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the Payment Method entity.'))
      ->setReadOnly(TRUE);

    $fields['payment_method_type_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment method schema type URL'))
      ->setDescription(t('The URL of the payment method schema integration for this entity'))
      ->setReadOnly(TRUE);

    $fields['billing_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Configured Billing URL'))
      ->setDescription(t('The URL of the configured billing integation for this payment method'))
      ->setReadOnly(TRUE);

    $fields['configuration'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Configuration'))
      ->setDescription(t('The configuration of this payment method'))
      ->setReadOnly(TRUE);

    $fields['consumerorg_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Consumer Organization URL'))
      ->setDescription(t('The URL of the consumer organization which owns the payment method'))
      ->setReadOnly(TRUE);

    $fields['created_at'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Creation time'))
      ->setDescription(t('The APIC creation timestamp'))
      ->setSetting('unsigned', TRUE)
      ->setReadOnly(TRUE);

    $fields['updated_at'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Modification time'))
      ->setDescription(t('The APIC modification timestamp'))
      ->setSetting('unsigned', TRUE)
      ->setReadOnly(TRUE);

    $fields['created_by'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Created by'))
      ->setDescription(t('The APIC created by user url'))
      ->setReadOnly(TRUE);

    $fields['updated_by'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Updated by'))
      ->setDescription(t('The APIC updated by user url'))
      ->setReadOnly(TRUE);
    return $fields;
  }

}
