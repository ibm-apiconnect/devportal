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
   * @var int
   */
  protected $id;

  /**
   * APIM UUID
   *
   * @var string
   */
  protected $uuid;

  /**
   * The payment method title
   *
   * @var string
   */
  protected $title;

  /**
   * The payment method schema type url.
   *
   * @var string
   */
  protected $payment_method_type_url;

  /**
   * The configured billing integration URL.
   *
   * @var string
   */
  protected $billing_url;

  /**
   * The payment method integration (in JSON form).
   *
   * @var string
   */
  protected $configuration;

  /**
   * The owning consumer organization URL.
   *
   * @var string
   */
  protected $consumerorg_url;

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
   * {@inheritdoc}
   */
  public function title() {
    return $this->title;
  }

  /**
   * {@inheritdoc}
   */
  public function payment_method_type_url() {
    return $this->payment_method_type_url;
  }

  /**
   * {@inheritdoc}
   */
  public function billing_url() {
    return $this->billing_url;
  }

  /**
   * {@inheritdoc}
   */
  public function configuration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function consumerorg_url() {
    return $this->consumerorg_url;
  }

  public function toArray(): array {
    return [
      'id' => $this->id,
      'uuid' => $this->uuid,
      'title' => $this->title,
      'configuration' => $this->configuration,
      'consumerorg_url' => $this->consumerorg_url,
      'billing_url' => $this->billing_url,
      'payment_method_type_url' => $this->payment_method_type_url,
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

    return $fields;
  }
}
