<?php

namespace Drupal\apic_app\Entity;

@trigger_error('The ' . __NAMESPACE__ . '\Subscription is deprecated in 2018.4.1.10 
Instead, use \Drupal\apic_app\Entity\ApplicationSubscription.', E_USER_DEPRECATED);

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\apic_app\SubscriptionInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * @deprecated in 8.x-2.0.39 (APIC v2018.4.1.10)
 * 
 * Defines a Subscription entity class.
 *
 * @ConfigEntityType(
 *   id = "apic_app_subscription",
 *   label = @Translation("Subscription"),
 *   label_singular = @Translation("Subscription"),
 *   label_plural = @Translation("Subscriptions"),
 *   label_count = @PluralTranslation(
 *     singular = @Translation("subscription"),
 *     plural = @Translation("subscriptions"),
 *   ),
 *   fieldable = FALSE,
 *   translatable = FALSE,
 *   base_table = "apic_app_subscription",
 *   entity_keys = {
 *     "id" = "id",
 *     "app_url" = "app_url",
 *     "product_url" = "product_url",
 *     "plan" = "plan",
 *     "state" = "state",
 *     "billing_url" = "billing_url",
 *     "consumerorg_url" = "consumerorg_url"
 *   }
 * )
 */
class Subscription extends ConfigEntityBase implements SubscriptionInterface {

  /**
   * Drupal entity ID
   *
   * @var string
   */
  protected $id;

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
    $fields['id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Subscription entity.'))
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
