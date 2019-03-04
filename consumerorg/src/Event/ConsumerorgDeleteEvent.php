<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2019
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/
namespace Drupal\consumerorg\Event;

use Drupal\core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when a consumerorg is deleted.
 *
 * @see Consumerorg::deleteNode()
 */
class ConsumerorgDeleteEvent extends Event {

  const EVENT_NAME = 'consumerorg_delete';

  /**
   * The consumer organization.
   *
   * @var \Drupal\core\Entity\EntityInterface
   */
  public $consumerorg;

  /**
   * Constructs the object.
   *
   * @param \Drupal\core\Entity\EntityInterface $consumerorg
   *   The consumer organization that was deleted.
   */
  public function __construct(EntityInterface $consumerorg) {
    $this->consumerorg = $consumerorg;
  }

}
