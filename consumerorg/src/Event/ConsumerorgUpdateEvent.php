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

use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when a consumerorg is updated.
 *
 * @see Consumerorg::update()
 */
class ConsumerorgUpdateEvent extends Event {

  const EVENT_NAME = 'consumerorg_update';

  /**
   * The consumer organization.
   *
   * @var \Drupal\node\NodeInterface
   */
  public $consumerorg;

  /**
   * Constructs the object.
   *
   * @param \Drupal\node\NodeInterface $consumerorg
   *   The consumer organization that was updated.
   */
  public function __construct(NodeInterface $consumerorg) {
    $this->consumerorg = $consumerorg;
  }

}
