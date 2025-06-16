<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2025
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * @file
 * Provides email subscribers of a given API Management Plan, Product or API.
 */
namespace Drupal\mail_subscribers\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Database\Database;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

class MailSubscribersHooks {

  /**
   * Implements hook_cron().
   */
  #[Hook('cron')]
  public function cron() {
    ibm_apim_entry_trace(__FUNCTION__, NULL);

    // Load cron functions.
    \Drupal::moduleHandler()->loadInclude('mail_subscribers', 'cron.inc');

    // Send pending messages from spool.
    mail_subscribers_send_from_spool();

    // Clear successful sent messages.
    mail_subscribers_clear_spool();

    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * Implements hook_mail().
   *
   * @param $key
   * @param $message
   * @param $params
   */
  #[Hook('mail')]
  public function mail($key, &$message, $params) {
    // This is a simple message send. User inputs the content directly.
    if ($key === 'direct') {

      // Set the subject.
      $message['subject'] = $params['subject'];

      // Set the body.
      $message['body'] = $params['body'];

      // Add additional headers.
      if (!array_key_exists('headers', $message)) {
        $message['headers'] = [];
      }
      $message['headers'] = array_merge($message['headers'], $params['headers']);
    }
  }

  /**
   * Implements hook_token_info().
   */
  #[Hook('token_info')]
  public function token_info(): array {
    \Drupal::moduleHandler()->loadInclude('mail_subscribers', 'helpers.inc');
    $data = [];
    foreach (_mail_subscribers_email_message_property_info() as $key => $info) {
      $data[$key] = [
        'name' => $info['label'],
        'description' => '',
      ];
    }
    $type = [
      'name' => t('Mail Subscribers e-mail message'),
      'description' => t('Tokens for Mail Subscribers e-mail message.'),
      'needs-data' => 'mail_subscribers_email_message',
    ];
    return [
      'types' => ['mail_subscribers_email_message' => $type],
      'tokens' => ['mail_subscribers_email_message' => $data],
    ];
  }

  /**
   * Implementation hook_tokens().
   *
   * These token replacements are used by Rules.
   *
   * @param $type
   * @param $tokens
   * @param array $data
   * @param array $options
   *
   * @return array
   */
  #[Hook('tokens')]
  public function tokens($type, $tokens, array $data = [], array $options = []): array {
    $replacements = [];
    if ($type === 'mail_subscribers_email_message' && !empty($data['mail_subscribers_email_message'])) {
      foreach ($tokens as $name => $original) {
        $replacements[$original] = $data['mail_subscribers_email_message']->{$name};
      }
    }
    return $replacements;
  }

  /**
   * hook_theme impl
   *
   * @return array
   */
  #[Hook('theme')]
  public function theme(): array {
    $themeTemplates = [];
    $themeTemplates['mail_subscribers_ctools_wizard_trail'] = [
      'template' => 'mail-subscribers-ctools-wizard-trail',
      'base hook' => 'ctools_wizard_trail',
    ];
    return $themeTemplates;
  }
 }
