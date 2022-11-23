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

use Drupal\Component\Utility\Unicode;
use Symfony\Component\Mime\Header\UnstructuredHeader;

/**
 * Build header array with priority and receipt confirmation settings.
 *
 * @param $receipt
 *   Boolean: If a receipt is requested.
 * @param $priority
 *   Integer: The message priority.
 * @param $from
 *   String: The sender's e-mail address.
 * @param $additionalHeaders
 *
 * @return array Header array with priority and receipt confirmation info
 */
function _mail_subscribers_headers($receipt, $priority, $from, $additionalHeaders): array {
  $headers = [];

  // If receipt is requested, add headers.
  if ($receipt) {
    $headers['Disposition-Notification-To'] = $from;
    $headers['X-Confirm-Reading-To'] = $from;
  }

  // Add priority if set.
  switch ($priority) {
    case 1:
      $headers['Priority'] = 'High';
      $headers['X-Priority'] = '1';
      $headers['X-MSMail-Priority'] = 'Highest';
      break;
    case 2:
      $headers['Priority'] = 'urgent';
      $headers['X-Priority'] = '2';
      $headers['X-MSMail-Priority'] = 'High';
      break;
    case 3:
      $headers['Priority'] = 'normal';
      $headers['X-Priority'] = '3';
      $headers['X-MSMail-Priority'] = 'Normal';
      break;
    case 4:
      $headers['Priority'] = 'non-urgent';
      $headers['X-Priority'] = '4';
      $headers['X-MSMail-Priority'] = 'Low';
      break;
    case 5:
      $headers['Priority'] = 'non-urgent';
      $headers['X-Priority'] = '5';
      $headers['X-MSMail-Priority'] = 'Lowest';
      break;
  }

  // Add general headers.
  $headers['Precedence'] = 'bulk';

  // Add additional headers.
  $additionalHeaders = trim($additionalHeaders);
  $additionalHeaders = str_replace("\r", "\n", $additionalHeaders);
  $additionalHeaders = explode("\n", $additionalHeaders);
  foreach ($additionalHeaders as $header) {
    $header = trim($header);
    if (!empty($header)) {
      [$key, $value] = explode(': ', $header, 2);
      $headers[$key] = trim($value);
    }
  }

  return $headers;
}

/**
 * Build a formatted e-mail address.
 *
 * @param $mail
 * @param $name
 * @param bool $encode
 *
 * @return string
 */
function _mail_subscribers_format_address($mail, $name, $encode = TRUE): string {

  // Do not format address on Windows based PHP systems or when $name is empty.
  if (empty($name) || (strpos(PHP_OS, 'WIN') === 0)) {
    $returnValue = $mail;
  }
  else {
    $name = ($encode ? $mail_subject = (new UnstructuredHeader('subject', $name))->getBodyAsString() : $name);
    $returnValue = sprintf('"%s" <%s>', $name, $mail);
  }
  return $returnValue;
}

/**
 * Prepare the mail message before sending or spooling.
 *
 * @param array $message
 *   which contains the following keys:
 *   from_name
 *     String holding the Sender's name.
 *   from_mail
 *     String holding the Sender's e-mail.
 *   to_name
 *     String holding the Recipient's name.
 *   to_mail
 *     String holding the Recipient's e-mail.
 *   subject
 *     String with the e-mail subject. This argument can be altered here.
 *   body
 *     Text with the e-mail body. This argument can be altered here.
 *   headers
 *     Associative array with e-mail headers. This argument can be altered here.
 * @param boolean $plain_format
 *   Whether the e-mail should be sent in plain format.
 */
function _mail_subscribers_prepare_mail(array &$message, $plain_format = TRUE) {
  // Extract all variables/keys from the message.
  extract($message, EXTR_OVERWRITE);

  /**
   * TODO: In the future, this module will be able to send an existing node.
   * $key will have to make the difference. A value when we pickup a node, other
   * when user inputs the subject & body of the message.
   */
  $key = 'direct';

  // Updating message with data from generated mail
  $message['to_mail'] = $to_mail;
  $message['from_mail'] = $from_mail;
  $message['subject'] = $subject;
  $message['body'] = $body;
  $message['headers'] = $headers;
}

/**
 * Sending a prepared message.
 *
 * @param $message
 *
 * @return mixed
 *   Boolean indicating if the message was sent successfully.
 */
function mail_subscribers_deliver($message) {
  if (is_array($message)) {
    $message = (object) $message;
  }

  $key = 'direct';
  $headers = $message->headers;
  $subject = (new UnstructuredHeader('subject', $message->subject))->getBodyAsString();
  $mail = [
    'to' => $message->to_mail,
    'from' => $message->from_mail,
    'subject' => $subject,
    'body' => $message->body,
    'headers' => $headers,
  ];

  return \Drupal::service('plugin.manager.mail')
    ->mail('mail_subscribers', $key, $message->to_mail, \Drupal::currentUser()
      ->getPreferredLangcode(), $mail, NULL, TRUE);
}

/**
 * Returns property info for the Email Message
 */
function _mail_subscribers_email_message_property_info(): array {
  return [
    'uid' => [
      'type' => 'integer',
      'label' => t('User ID'),
    ],
    'timestamp' => [
      'type' => 'integer',
      'label' => t('Timestamp'),
    ],
    'from_name' => [
      'type' => 'text',
      'label' => t('Sender\'s name'),
    ],
    'from_mail' => [
      'type' => 'text',
      'label' => t('Sender\'s e-mail'),
    ],
    'to_name' => [
      'type' => 'text',
      'label' => t('Recipient\'s name'),
    ],
    'to_mail' => [
      'type' => 'text',
      'label' => t('Recipient\'s e-mail'),
    ],
    'subject' => [
      'type' => 'text',
      'label' => t('E-mail subject'),
    ],
    'body' => [
      'type' => 'text',
      'label' => t('E-mail body'),
    ],
    'headers' => [
      'type' => 'text',
      'label' => t('E-mail headers (serialized)'),
    ],
  ];
}
