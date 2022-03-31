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

namespace Drupal\mail_subscribers\Event;

use Drupal\Core\TypedData\Plugin\DataType\Map;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event that is fired when an e-mail has been sent.
 */
class MailSentEvent extends Event {

  public const EVENT_NAME = 'mail_subscribers_email_sent';

  /**
   * The message.
   *
   * @var \Drupal\Core\TypedData\Plugin\DataType\Map
   */
  public Map $message;

  /**
   * Constructs the object.
   *
   * @param array $message
   *   The message.
   */
  public function __construct(array $message) {
    /*
    FIXME
    $this->message = new Map();
    $this->message->setValue($message);
    */
  }

}
