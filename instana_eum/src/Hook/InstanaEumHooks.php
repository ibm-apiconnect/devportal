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
 * Configures Instana End User Monitoring (EUM) .
 */
namespace Drupal\instana_eum\Hook;

use Drupal\Core\Hook\Attribute\Hook;

class InstanaEumHooks {
  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$attachments) {
    $attachments['#attached']['library'][] = 'instana_eum/instana_eum_config';
    $config = \Drupal::config('instana_eum.settings');
    $attachments['#attached']['drupalSettings']['instana_eum']['api_key'] = $config->get('api_key');
    $attachments['#attached']['drupalSettings']['instana_eum']['reporting_url'] = $config->get('reporting_url');
    $attachments['#attached']['drupalSettings']['instana_eum']['track_pages'] = $config->get('track_pages');
    $attachments['#attached']['drupalSettings']['instana_eum']['track_admin'] = $config->get('track_admin');
    $attachments['#attached']['drupalSettings']['instana_eum']['advanced_settings'] = $config->get('advanced_settings');
  }
 }
