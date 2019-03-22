<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2016, 2019
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * @file
 *   mail_subscribers administration page.
 *
 * @ingroup mail_subscribers
 */

/**
 * Callback for admin/settings/mail_subscribers menu item.
 */
function mail_subscribers_settings() {
  $form = array();

  $throttle = drupal_map_assoc(array(1, 10, 20, 30, 50, 100, 200, 500, 1000, 2000, 5000, 10000, 20000));
  $throttle[0] = t('Unlimited');

  $form['mail_subscribers_throttle'] = array(
    '#type' => 'select',
    '#title' => t('Cron throttle'),
    '#options' => $throttle,
    '#default_value' => variable_get('mail_subscribers_throttle', 20),
    '#description' => t('Sets the numbers of messages sent per cron run. Failure to send will also be counted. Cron execution must not exceed the PHP maximum execution time of %max seconds. You find the time spent to send e-mails in the recent logs.', array('%max' => ini_get('max_execution_time'))),
  );
  $form['mail_subscribers_spool_expire'] = array(
    '#type' => 'select',
    '#title' => t('Mail spool expiration'),
    '#options' => array(0 => t('Immediate'), 1 => t('1 day'), 7 => t('1 week'), 14 => t('2 weeks')),
    '#default_value' => variable_get('mail_subscribers_spool_expire', 0),
    '#description' => t('E-mails are spooled. How long must messages be retained in the spool after successful sending.'),
  );
  $form['mail_subscribers_debug'] = array(
    '#type' => 'checkbox',
    '#title' => t('Log e-mails'),
    '#default_value' => variable_get('mail_subscribers_debug', FALSE),
    '#description' => t('When checked all outgoing messages are logged in the system log. A logged e-mail does not guarantee that it is send or will be delivered. It only indicates that a message is send to the PHP mail() function. No status information is available of delivery by the PHP mail() function.'),
  );

  return system_settings_form($form);
}
