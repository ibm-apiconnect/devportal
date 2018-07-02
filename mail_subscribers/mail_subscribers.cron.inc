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

/**
 * @file
 *   Mail subscribers cron routines.
 *
 * @ingroup mail_subscribers
 */

use Drupal\Component\Utility\Timer;
use Drupal\mail_subscribers\Event\MailSentEvent;
 
/**
 * Process the spool queue at cron run.
 */
function mail_subscribers_send_from_spool() {
  module_load_include('helpers.inc', 'mail_subscribers');

  $limit = \Drupal::config('mail_subscribers.settings')->get('throttle');
  $ok = $fail = $check = 0;

  // Get PHP maximum execution time. 30 seconds default.
  $max_execution_time = ini_get('max_execution_time') ? ini_get('max_execution_time') : 30;

  // Reset a Drupal timer.
  Timer::start('mail_subscribers');

  // Retrieve messages to be send.
  $query = "SELECT * FROM {mail_subscribers_spool} WHERE status = :status ORDER BY tentatives ASC, timestamp ASC";
  $result = $limit ? db_query_range($query, 0, $limit, array(':status' => 0)) : db_query($query, array(':status' => 0));
  foreach ($result as $message) {
    // Send the message.
    $status = mail_subscribers_deliver($message);

    if ($status) {
      // Update the spool status.
      db_query("UPDATE {mail_subscribers_spool} SET status = :status WHERE eid = :eid", array(':status' => 1, ':eid' => $message->eid));
      if (\Drupal::config('mail_subscribers.settings')->get('debug')) {
        \Drupal::logger('mail_subscribers')->notice('Message sent to %mail.', array('%mail' => $message->to_mail));
      }
      if (\Drupal::moduleHandler()->moduleExists('rules')) {
        $event = new MailSentEvent($message);
        $event_dispatcher = \Drupal::service('event_dispatcher');
        $event_dispatcher->dispatch(MailSentEvent::EVENT_NAME, $event);
      }
      $ok++;
    }
    else {
      // Increment tentatives so that next time this message
      // will be scheduled with low priority.
      db_query("UPDATE {mail_subscribers_spool} SET tentatives = tentatives + 1 WHERE eid = :eid", array(':eid' => $message->eid));
      $fail++;
    }

    // Check the elapsed time and break if we've spent more than 80%.
    // We check every 50 messages.
    if (++$check >= 50) {
      // Reset the counter.
      $check = 0;

      // Break if exceeded.
      if (Timer::read('mail_subscribers') / 1000 > .8 * $max_execution_time) {
        \Drupal::logger('mail_subscribers')->notice('PHP maximum execution time almost exceeded. Remaining e-mail messages will be sent during the next cron run. If this warning occurs regularly you should reduce the cron throttle setting.');
        break;
      }
    }
  }

  if ($ok + $fail > 0) {
    // Log results and exit.
    \Drupal::logger('mail_subscribers')->notice('%ok messages sent in %sec seconds, %fail failed sending.',
      array('%ok' => $ok, '%sec' => Timer::read('mail_subscribers') / 1000, '%fail' => $fail)
    );
  }
}

/**
 * Clear the expired items from spool.
 */
function mail_subscribers_clear_spool() {
  // TODO: Drupal 7: replace time() with REQUEST_TIME.
  $expiration_time = time() - \Drupal::config('mail_subscribers.settings')->get('spool_expire') * 86400;
  db_query("DELETE FROM {mail_subscribers_spool} WHERE status = :status AND timestamp <= :expiry", array(':status' => 1, 'expiry' => $expiration_time));
}
