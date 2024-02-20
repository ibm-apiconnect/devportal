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

namespace Drupal\apic_app\Entity;

use Drupal\apic_app\ApplicationSubscriptionInterface;
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
 *   handlers = {
 *     "storage_schema" = "Drupal\apic_app\ApplicationSubscriptionSchema",
 *   },
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
  public function product_url() {
    return $this->get('product_url')->value;
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
  public function plan() {
    return $this->get('plan')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function state() {
    return $this->get('state')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function billing_url() {
    return $this->get('billing_url')->value;
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
