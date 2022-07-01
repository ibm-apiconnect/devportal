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
   * Drupal entity ID
   *
   * @var int|NULL
   */
  protected ?int $id = NULL;

  /**
   * APIM UUID
   *
   * @var string|NULL
   */
  protected ?string $uuid = NULL;

  /**
   * The payment method title
   *
   * @var string|NULL
   */
  protected ?string $title = NULL;

  /**
   * The payment method schema type url.
   *
   * @var string|NULL
   */
  protected ?string $payment_method_type_url = NULL;

  /**
   * The configured billing integration URL.
   *
   * @var string|NULL
   */
  protected ?string $billing_url = NULL;

  /**
   * The payment method integration (in JSON form).
   *
   * @var string|NULL
   */
  protected ?string $configuration = NULL;

  /**
   * The owning consumer organization URL.
   *
   * @var string|NULL
   */
  protected ?string $consumerorg_url = NULL;

  /**
   * APIC Creation timestamp
   *
   * @var int|NULL
   */
  protected ?int $created_at = NULL;

  /**
   * APIC Modification timestamp
   *
   * @var int|NULL
   */
  protected ?int $updated_at = NULL;

  /**
   * APIC Creation user url
   *
   * @var string|NULL
   */
  protected ?string $created_by = NULL;

  /**
   * APIC Modification user url
   *
   * @var string|NULL
   */
  protected ?string $updated_by = NULL;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function uuid() {
    return $this->uuid;
  }

  /**
   * @return string|null
   */
  public function title(): ?string {
    return $this->title;
  }

  /**
   * @return string|null
   */
  public function payment_method_type_url(): ?string {
    return $this->payment_method_type_url;
  }

  /**
   * @return string|null
   */
  public function billing_url(): ?string {
    return $this->billing_url;
  }

  /**
   * @return string|null
   */
  public function configuration(): ?string {
    return $this->configuration;
  }

  /**
   * @return string|null
   */
  public function consumerorg_url(): ?string {
    return $this->consumerorg_url;
  }

  /**
   * @return int|null
   */
  public function created_at(): ?int {
    return $this->created_at;
  }

  /**
   * @return int|null
   */
  public function updated_at(): ?int {
    return $this->updated_at;
  }

  /**
   * @return string|null
   */
  public function created_by(): ?string {
    return $this->created_by;
  }

  /**
   * @return string|null
   */
  public function updated_by(): ?string {
    return $this->updated_by;
  }

  /**
   * @return array
   */
  public function toArray(): array {
    return [
      'id' => $this->id,
      'uuid' => $this->uuid,
      'title' => $this->title,
      'configuration' => $this->configuration,
      'consumerorg_url' => $this->consumerorg_url,
      'billing_url' => $this->billing_url,
      'payment_method_type_url' => $this->payment_method_type_url,
      'created_at' => $this->created_at,
      'updated_at' => $this->updated_at,
      'created_by' => $this->created_by,
      'updated_by' => $this->updated_by,
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
