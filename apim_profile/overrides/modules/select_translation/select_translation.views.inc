<?php

/**
 * @file
 * Views related hooks.
 */

/**
 * Implements hook_views_data_alter().
 */
function select_translation_views_data_alter(array &$data) {
  $data['node_field_data']['select_translation'] = [
    'title' => t('Select translation'),
    'filter' => [
      'title' => t('Select translation'),
      'help' => t('Select which translation of a node should be displayed'),
      'field' => 'nid',
      'id' => 'select_translation_filter',
    ],
  ];
}
