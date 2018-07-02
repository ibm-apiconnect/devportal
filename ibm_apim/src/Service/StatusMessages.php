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

namespace Drupal\ibm_apim\Service;

/**
 * Service allowing status messages to be set or removed from the
 * status message block.
 */
class StatusMessages {

  private $logger;

  public function __construct() {
    if(!is_array(\Drupal::state()->get('ibm_apim.status_messages'))) {
      \Drupal::state()->set('ibm_apim.status_messages', array());
    }
  }

  /**
   * add status message
   *
   * @param $component   Component heading to insert the message under.
   * @param $message     The message text to be displayed.
   * @param $role        'administrator' by default
   *
   * @return NULL       This function returns nothing.
   */
  public function add($component, $message, $role = 'administrator') {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, array($component, $message));

    $current_messages = \Drupal::state()->get('ibm_apim.status_messages');

    if(!isset($current_messages[$component])) {
      $current_messages[$component] = array();
    }

    // We don't want to add the same message multiple times
    $found = false;
    foreach($current_messages[$component] as $existing_message) {
      if($existing_message['text'] == $message) {
        $found = true;
      }
    }
    if($found != true) {
      array_push($current_messages[$component], array('text'=> $message, 'role' => $role));
      \Drupal::state()->set('ibm_apim.status_messages', $current_messages);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }


  /**
   * remove a specific status message
   *
   * @param $component  Component heading where the message is located.
   * @param $message    The string of the message to be removed.
   *
   * @return NULL       This function returns nothing.
   */
  public function remove($component, $message) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, array($component, $message));

    $current_messages = \Drupal::state()->get('ibm_apim.status_messages');

    if(in_array($message, $current_messages[$component])) {
      $replacement_component_messages = array();
      foreach($current_messages[$component] as $existing_message) {
        if($existing_message['text'] !== $message) {
          array_push($replacement_component_messages, $existing_message);
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
   * @param $component  Component for which all messages should be removed.
   *
   * @return NULL       This function returns nothing.
   */
  public function clearComponent($component) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $component);

    $current_messages = \Drupal::state()->get('ibm_apim.status_messages');
    unset($current_messages[$component]);
    \Drupal::state()->set('ibm_apim.status_messages', $current_messages);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * remove all messages
   *
   * @return NULL       This function returns nothing.
   */
  public function clearAll() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    \Drupal::state()->set('ibm_apim.status_messages', array());

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

}
