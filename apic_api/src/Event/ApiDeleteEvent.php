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

use Drupal\core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when an API is deleted.
 *
 * @see Api::deleteNode()
 */
class ApiDeleteEvent extends Event {

  public const EVENT_NAME = 'api_delete';

  /**
   * The API.
   *
   * @var \Drupal\core\Entity\EntityInterface
   */
  public $api;

  /**
   * Constructs the object.
   *
   * @param \Drupal\core\Entity\EntityInterface $api
   *   The API that was deleted.
   */
  public function __construct(EntityInterface $api) {
    $this->api = $api;
  }

}
