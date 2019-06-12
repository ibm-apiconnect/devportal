<?php

use Drupal\Component\Utility\Html;
use Drupal\Core\Session\UserSession;
use Drupal\node\NodeInterface;
use Drupal\node_type_count\Controller\NodeTypeCountController;

/**
 * Implements hook_drush_command
 *
 * @return mixed
 */
function node_type_count_drush_command() {

  $items['node_type_count-totals'] = [
    'description' => 'Count nodes of each type',
    'aliases' => ['nodecount'],
    'outputformat' => array(
      'default' => 'table'
    ),
  ];
  return $items;
}

/**
 * This will output a list of nodes per node type
 *
 * @return array
 */
function drush_node_type_count_totals() {
  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = Drupal::service('account_switcher');
  $originalUser = \Drupal::currentUser();
  if ((int) $originalUser->id() !== 1) {
    $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
  }
  $result_final = [];
  $result = node_type_get_names();
  if (is_array($result)) {
    foreach ($result as $node_type_machine_name => $content_type_title) {
      // Get the value as key and value pair.
      $result_arr['title'] = Html::escape($content_type_title);
      $result_arr['machine_name'] = $node_type_machine_name;
      $result_arr['published'] = NodeTypeCountController::nodeCountState(NodeInterface::PUBLISHED, $node_type_machine_name);
      $result_arr['unpublished'] = NodeTypeCountController::nodeCountState(NodeInterface::NOT_PUBLISHED, $node_type_machine_name);
      $result_final[$node_type_machine_name] = $result_arr;
    }
  }
  if (isset($originalUser) && (int) $originalUser->id() !== 1) {
    $accountSwitcher->switchBack();
  }

  return $result_final;
}