<?php

/**
 * @file
 * Drush integration for the select_translation module.
 */

use Drush\Log\LogLevel;

/**
 * Implements hook_drush_command().
 */
function select_translation_drush_command() {
  $items['select-translation'] = [
    'description' => dt('Select which translation of a node should be displayed.'),
    'arguments' => [
      'nid' => dt('The Node ID.'),
    ],
    'options' => [
      'mode' => dt('The selection mode, it can be: \'default\', \'original\', or a comma separated list of language codes. See the API doc for more details.'),
    ],
    'required-arguments' => 1,
  ];
  return $items;
}

/**
 * Select which translation of a node should be displayed.
 *
 * @param int $nid
 *   The Node ID.
 */
function drush_select_translation($nid) {
  if (\Drupal::moduleHandler()->moduleExists('select_translation') === FALSE) {
    return drush_set_error('INVALID_MODULE', dt("Module 'select_translation' doesn't exist or is uninstalled."));
  }

  $node_id = filter_var($nid, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
  if (!$node_id) {
    return drush_set_error('INVALID_ARGUMENT', dt("The 'nid' argument must be an integer >= 1."));
  }

  $mode = drush_get_option('mode');
  if ($mode) {
    $node = select_translation_of_node($node_id, $mode);
  }
  else {
    $node = select_translation_of_node($node_id);
  }

  if (!$node) {
    return drush_set_error('INVALID_NODE', dt("Node with 'nid' = $nid not available."));
  }

  \Drupal::logger("Selected translation for node $nid: " . $node->language()->getId(), LogLevel::OK);
  return $node;
}
