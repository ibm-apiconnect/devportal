<?php

/**
 * @file
 *   Drush commands to manage themes.
 */

/**
 * Implementation of hook_drush_command().
 */
function drushadmin_drush_command() {
  $items = [];

  $items['theme-delete'] = [
    'description' => 'Uninstall one or more custom themes.',
    'arguments' => [
      'theme_name' => 'The machine name of the theme (comma separate multiple themes)',
    ],
  ];
  $items['module-delete'] = [
    'description' => 'Uninstall one or more custom modules.',
    'arguments' => [
      'module_name' => 'The machine name of the module (comma separate multiple themes)',
    ],
  ];

  return $items;
}

/**
 * Implementation of hook_drush_help().
 */
function drushadmin_drush_help($section) {
  switch ($section) {
    case 'drush:theme-delete':
      return dt('Delete one or more custom themes. It will fail if the theme is still enabled.');
    case 'drush:module-delete':
      return dt('Delete one or more custom modules. It will fail if the module is still enabled.');
  }
}


/**
 * Implementation of command <code>drush theme-delete theme_name</code>
 *
 * This function deletes theme_name if it is not enabled.
 * A comma separated list of themes can be provided.
 *
 * @param $theme_name - The machine name of the theme
 */
function drush_drushadmin_theme_delete($theme_name) {
  if ($theme_name !== NULL) {
    $themes = \Drupal::service('theme_handler')->listInfo();
    $customThemes = \Drupal::service('ibm_apim.utils')->getCustomThemeDirectories();
    $inputThemes = explode(',', $theme_name);
    foreach ($inputThemes as $inputTheme) {
      // Check if the specified theme is disabled and is a custom theme
      if (!array_key_exists($inputTheme, $themes) && in_array($inputTheme, $customThemes, TRUE)) {
        $item_path = drupal_get_path('theme', $inputTheme);
        if (isset($item_path) && !empty($item_path)) {
          \Drupal::service('ibm_apim.utils')->file_delete_recursive($item_path);
          // clear all caches otherwise reinstalling the same theme will fail
          drupal_flush_all_caches();
          \Drupal::logger("drushadmin")->notice(dt("!theme_name deleted.", ['!theme_name' => $inputTheme]));
        }
      }
      else {
        \Drupal::logger("drushadmin")->error(dt("!theme_name not deleted. It is either still activated or is not a custom theme.", ['!theme_name' => $inputTheme]));
      }
    }
  }
}

/**
 * Implementation of command <code>drush module-delete module_name</code>
 *
 * This function deletes module_name if it is not enabled.
 * A comma separated list of modules can be provided.
 *
 * @param $module_name - The machine name of the module
 */
function drush_drushadmin_module_delete($module_name) {
  if ($module_name !== NULL) {
    $modules = \Drupal::service('ibm_apim.utils')->getDisabledCustomModules();
    $inputModules = explode(',', $module_name);
    foreach ($inputModules as $inputModule) {
      // Check if the specified module is a disabled custom module
      if (array_key_exists($inputModule, $modules)) {
        \Drupal::service('ibm_apim.module')->deleteModulesOnFileSystem([$inputModule], FALSE);
        // clear all caches otherwise reinstalling the same module will fail
        drupal_flush_all_caches();
        \Drupal::logger("drushadmin")->notice(dt("!module_name deleted.", ['!module_name' => $inputModule]));
      }
      else {
        \Drupal::logger("drushadmin")->error(dt("!module_name not deleted. It is either still activated or is not a custom module.", ['!module_name' => $inputModule]));
      }
    }
  }
}


