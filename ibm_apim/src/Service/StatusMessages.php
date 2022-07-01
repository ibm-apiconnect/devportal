<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2022
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Service;

/**
 * Service allowing status messages to be set or removed from the
 * status message block.
 */
class StatusMessages {

  /**
   * StatusMessages constructor.
   */
  public function __construct() {
    if (!is_array(\Drupal::state()->get('ibm_apim.status_messages'))) {
      \Drupal::state()->set('ibm_apim.status_messages', []);
    }
  }

  /**
   * add status message
   *
   * @param string $component Component heading to insert the message under.
   * @param string $message The message text to be displayed.
   * @param string|null $role 'administrator' by default
   *
   */
  public function add(string $component, string $message, ?string $role = 'administrator'): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, [$component, $message]);

    $current_messages = \Drupal::state()->get('ibm_apim.status_messages');

    if (!isset($current_messages[$component])) {
      $current_messages[$component] = [];
    }

    // We don't want to add the same message multiple times
    $found = FALSE;
    foreach ($current_messages[$component] as $existing_message) {
      if ($existing_message['text'] === $message) {
        $found = TRUE;
      }
    }
    if ($found !== TRUE) {
      $current_messages[$component][] = ['text' => $message, 'role' => $role];
      \Drupal::state()->set('ibm_apim.status_messages', $current_messages);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }


  /**
   * remove a specific status message
   *
   * @param string $component Component heading where the message is located.
   * @param string $message The string of the message to be removed.
   *
   */
  public function remove(string $component, string $message): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, [$component, $message]);

    $current_messages = \Drupal::state()->get('ibm_apim.status_messages');

    if (in_array($message, $current_messages[$component], FALSE)) {
      $replacement_component_messages = [];
      foreach ($current_messages[$component] as $existing_message) {
        if ($existing_message['text'] !== $message) {
          $replacement_component_messages[] = $existing_message;
        }
      }

      $current_messages[$component] = $replacement_component_messages;
      \Drupal::state()->set('ibm_apim.status_messages', $current_messages);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * remove all messages for a given component
   *
   * @param string $component Component for which all messages should be removed.
   *
   */
  public function clearComponent(string $component): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $component);

    $current_messages = \Drupal::state()->get('ibm_apim.status_messages');
    unset($current_messages[$component]);
    \Drupal::state()->set('ibm_apim.status_messages', $current_messages);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * remove all messages
   */
  public function clearAll(): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    \Drupal::state()->set('ibm_apim.status_messages', []);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

}
