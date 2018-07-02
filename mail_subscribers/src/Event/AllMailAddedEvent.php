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
namespace Drupal\mail_subscribers\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when all e-mails have been added to the spool.
 */
class AllMailAddedEvent extends Event {

  const EVENT_NAME = 'mail_subscribers_all_email_added_to_spool';

  /**
   * The message account.
   *
   * @var int
   */
  public $count;

  /**
   * Constructs the object.
   *
   * @param int $count
   *   The message count.
   */
  public function __construct($count) {
    $this->count = $count;
  }

}
