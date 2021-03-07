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

namespace Drupal\apic_app\Entity;

use Drupal\apic_app\ApplicationSubscriptionInterface;
use Drupal\Core\Entity\ContentEntity;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines a Subscription entity class.
 *
 * @ContentEntityType(
 *   id = "apic_app_application_subs",
 *   label = @Translation("Application Subscription"),
 *   label_singular = @Translation("Application Subscription"),
 *   label_plural = @Translation("Application Subscriptions"),
 *   label_count = @PluralTranslation(
 *     singular = @Translation("application subscription"),
 *     plural = @Translation("application subscriptions"),
 *   ),
 *   fieldable = FALSE,
 *   translatable = FALSE,
 *   base_table = "apic_app_application_subs",
 *   entity_keys = {
 *     "id" = "id",
 *   }
 * )
 */
class ApplicationSubscription extends ContentEntityBase implements ApplicationSubscriptionInterface {

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
   * The application URL.
   *
   * @var string
   */
  protected $app_url;

  /**
   * The product URL.
   *
   * @var string
   */
  protected $product_url;

  /**
   * The owning consumer organization URL.
   *
   * @var string
   */
  protected $consumerorg_url;

  /**
   * The plan name.
   *
   * @var string
   */
  protected $plan;

  /**
   * The subscription state.
   *
   * @var string
   */
  protected $state;

  /**
   * The billing URL.
   *
   * @var string
   */
  protected $billing_url;

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
  public function app_url() {
    return $this->app_url;
  }

  /**
   * {@inheritdoc}
   */
  public function product_url() {
    return $this->product_url;
  }


  /**
   * {@inheritdoc}
   */
  public function consumerorg_url() {
    return $this->consumerorg_url;
  }

  /**
   * {@inheritdoc}
   */
  public function plan() {
    return $this->plan;
  }

  /**
   * {@inheritdoc}
   */
  public function state() {
    return $this->state;
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

    $fields['app_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Application URL'))
      ->setDescription(t('The URL of the Application this Subscription belongs to'))
      ->setReadOnly(TRUE);

    $fields['product_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Product URL'))
      ->setDescription(t('The URL of the product this Subscription applies to'))
      ->setReadOnly(TRUE);

    $fields['plan'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Plan name'))
      ->setDescription(t('The name of the plan this Subscription applies to'))
      ->setReadOnly(TRUE);

    $fields['state'] = BaseFieldDefinition::create('string')
      ->setLabel(t('State'))
      ->setDescription(t('Whether the Subscription is enabled or not'))
      ->setReadOnly(TRUE);

    $fields['billing_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Billing URL'))
      ->setDescription(t('The URL of the billing object'))
      ->setReadOnly(TRUE);

    $fields['consumerorg_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Consumer Organization URL'))
      ->setDescription(t('The URL of the consumer organization which owns the Application'))
      ->setReadOnly(TRUE);

    return $fields;
  }
}
