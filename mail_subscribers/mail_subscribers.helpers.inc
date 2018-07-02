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
use Drupal\Component\Utility\Unicode;

/**
 * Build header array with priority and receipt confirmation settings.
 *
 * @param $receipt
 *   Boolean: If a receipt is requested.
 * @param $priority
 *   Integer: The message priority.
 * @param $from
 *   String: The sender's e-mail address.
 * @param $additional_headers
 *
 * @return array Header array with priority and receipt confirmation info
 */
function _mail_subscribers_headers($receipt, $priority, $from, $additional_headers) {
  $headers = array();

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
  $additional_headers = trim($additional_headers);
  $additional_headers = str_replace("\r", "\n", $additional_headers);
  $additional_headers = explode("\n", $additional_headers);
  foreach ($additional_headers as $header) {
    $header = trim($header);
    if (!empty($header)) {
      list($key, $value) = explode(': ', $header, 2);
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
 * @return string
 */
function _mail_subscribers_format_address($mail, $name, $encode = TRUE) {

  // Do not format address on Windows based PHP systems or when $name is empty.
  if ((substr(PHP_OS, 0, 3) == 'WIN') || empty($name)) {
    return $mail;
  }
  else {
    $name = ($encode ? Unicode::mimeHeaderEncode($name) : $name);
    return sprintf('"%s" <%s>', $name, $mail);
  }
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
function _mail_subscribers_prepare_mail(&$message, $plain_format = TRUE) {
  // Extract all variables/keys from the message.
  extract($message);

  /**
   * TODO: In the future, this module will be able to send an existing node.
   * $key will have to make the difference. A value when we pickup a node, other
   * when user inputs the subject & body of the message.
   */
  $key = 'direct';

  // Build message parameters.
  $params = array();

  $params['from_name'] = $from_name;
  $params['from_mail'] = $from_mail;
  $params['from_formatted'] = _mail_subscribers_format_address($from_mail, $from_name);

  $params['to_name'] = $to_name;
  $params['to_mail'] = $to_mail;
  $to_mail_formatted = array();
  foreach (explode(',', $to_mail) as $addr) {
    $to_mail_formatted[] = _mail_subscribers_format_address($addr, $to_name);
  }
  $params['to_formatted'] = implode(', ', $to_mail_formatted);
  $params['subject'] = $subject;
  $params['body'] = $body;
  $params['headers'] = $headers;
  $params['format'] = $headers['Content-Type'];

  $mimemail = \Drupal::moduleHandler()->moduleExists('mimemail');

  // Call Drupal standard mail function, but without sending.
  $mail = \Drupal::service('plugin.manager.mail')
    ->mail('mail_subscribers', $key, $params['to_formatted'], \Drupal::languageManager()
      ->getDefaultLanguage()
      ->getId(), $params, $params['from_formatted'], FALSE);

  // Add additional Mime Mail post processing.
  if ($mimemail) {
    // We want to spool the Subject decoded.
    $mail['subject'] = Unicode::mimeHeaderDecode($mail['subject']);
  }

  // Updating message with data from generated mail
  $message['to_mail'] = $mail['to'];
  $message['from_mail'] = $mail['from'];
  $message['subject'] = $mail['subject'];
  $message['body'] = $mail['body'];
  $message['headers'] = serialize($mail['headers']);
}

/**
 * Sending a prepared message.
 *
 * @param $message
 * @return mixed
 *   Boolean indicating if the message was sent successfully.
 */
function mail_subscribers_deliver($message) {
  if (is_array($message)) {
    $message = (object) $message;
  }

  $key = 'direct';
  $headers = unserialize($message->headers);

  $mail = array(
    'to' => $message->to_mail,
    'from' => $message->from_mail,
    'subject' => Unicode::mimeHeaderEncode($message->subject),
    'body' => $message->body,
    'headers' => $headers,
  );

  $result = \Drupal::service('plugin.manager.mail')
    ->mail('mail_subscribers', $key, $message->to_mail, \Drupal::currentUser()
      ->getPreferredLangcode(), $mail, NULL, TRUE);

  return $result;
}

/**
 * Returns property info for the Email Message
 */
function _mail_subscribers_email_message_property_info() {
  $propertyinfo = array(
    'uid' => array(
      'type' => 'integer',
      'label' => t('User ID'),
    ),
    'timestamp' => array(
      'type' => 'integer',
      'label' => t('Timestamp'),
    ),
    'from_name' => array(
      'type' => 'text',
      'label' => t('Sender\'s name'),
    ),
    'from_mail' => array(
      'type' => 'text',
      'label' => t('Sender\'s e-mail'),
    ),
    'to_name' => array(
      'type' => 'text',
      'label' => t('Recipient\'s name'),
    ),
    'to_mail' => array(
      'type' => 'text',
      'label' => t('Recipient\'s e-mail'),
    ),
    'subject' => array(
      'type' => 'text',
      'label' => t('E-mail subject'),
    ),
    'body' => array(
      'type' => 'text',
      'label' => t('E-mail body'),
    ),
    'headers' => array(
      'type' => 'text',
      'label' => t('E-mail headers (serialized)'),
    ),
  );
  return $propertyinfo;
}
