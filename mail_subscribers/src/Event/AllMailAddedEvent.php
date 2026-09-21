<?php

namespace Drupal\mail_subscribers\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event that is fired when all e-mails have been added to the spool.
 */
class AllMailAddedEvent extends Event {

  public const EVENT_NAME = 'mail_subscribers_all_email_added_to_spool';

  /**
   * The message account.
   *
   * @var int
   */
  public int $count;

  /**
   * Constructs the object.
   *
   * @param int $count
   *   The message count.
   */
  public function __construct(int $count) {
    $this->count = $count;
  }

}
