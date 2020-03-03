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

namespace Drupal\apic_api\Event;

use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when an API is updated.
 *
 * @see Api::update()
 */
class ApiUpdateEvent extends Event {

  public const EVENT_NAME = 'api_update';

  /**
   * The API.
   *
   * @var \Drupal\node\NodeInterface
   */
  public $api;

  /**
   * Constructs the object.
   *
   * @param \Drupal\node\NodeInterface $api
   *   The API that was updated.
   */
  public function __construct(NodeInterface $api) {
    $this->api = $api;
  }

}
