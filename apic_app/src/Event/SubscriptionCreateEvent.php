<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/
namespace Drupal\apic_app\Event;

use Drupal\core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when a subscription is created.
 *
 * @see Subscription::create()
 */
class SubscriptionCreateEvent extends Event {

  const EVENT_NAME = 'subscription_create';

  /**
   * The application.
   *
   * @var \Drupal\core\Entity\EntityInterface
   */
  public $application;

  /**
   * The product.
   *
   * @var \Drupal\core\Entity\EntityInterface
   */
  public $product;

  /**
   * The plan name.
   *
   * @var string
   */
  public $planName;

  /**
   * The state.
   * @var string
   */
  public $state;

  /**
   * Constructs the object.
   *
   * @param \Drupal\core\Entity\EntityInterface $application
   *   The application that was subscribed.
   * @param \Drupal\core\Entity\EntityInterface $product
   *   The product that was subscribed to.
   * @param $planName
   *   The plan name
   * @param $state
   *   The subscription state, e.g. enabled
   */
  public function __construct(EntityInterface $application, EntityInterface $product, $planName, $state) {
    $this->application = $application;
    $this->product = $product;
    $this->planName = $planName;
    $this->state = $state;
  }

}
