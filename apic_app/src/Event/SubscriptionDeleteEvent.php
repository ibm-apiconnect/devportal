<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2020
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/
namespace Drupal\apic_app\Event;

use Drupal\core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when a subscription is deleted.
 *
 * @see SubscriptionService::deleteNode()
 */
class SubscriptionDeleteEvent extends Event {

  const EVENT_NAME = 'subscription_delete';

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
   * Constructs the object.
   *
   * @param \Drupal\core\Entity\EntityInterface $application
   *   The application whose subscription was deleted.
   * @param \Drupal\core\Entity\EntityInterface $product
   *   The product that was subscribed to.
   * @param $planName
   *   The plan name
   */
  public function __construct(EntityInterface $application, EntityInterface $product, $planName) {
    $this->application = $application;
    $this->product = $product;
    $this->planName = $planName;
  }

}
