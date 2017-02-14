<?php
// connect_theme
// based on sky by Adaptivethemes.com

/**
 * Override or insert variables into the html template.
 *
 * @param $vars
 */
function connect_theme_preprocess_html(&$vars) {
  global $theme_key;
  $theme_name = $theme_key;

  // Add a class for the active color scheme
  if (module_exists('color')) {
    $class = check_plain(get_color_scheme_name($theme_name));
    $vars['classes_array'][] = 'color-scheme-' . drupal_html_class($class);
  }

  // Add class for the active theme
  $vars['classes_array'][] = drupal_html_class($theme_name);

  // Add theme settings classes
  $settings_array = array(
    'box_shadows',
    'body_background',
    'menu_bullets',
    'menu_bar_position',
    'content_corner_radius',
    'tabs_corner_radius'
  );
  foreach ($settings_array as $setting) {
    $vars['classes_array'][] = at_get_setting($setting);
  }
  // add class for role
  if (isset($vars['user']) && isset($vars['user']->roles)) {
    foreach ($vars['user']->roles as $role) {
      $vars['classes_array'][] = 'role-' . drupal_html_class($role);
    }
  }
}

/**
 * Override or insert variables into the html template.
 *
 * @param $vars
 */
function connect_theme_process_html(&$vars) {
  // Hook into the color module.
  if (module_exists('color')) {
    _color_html_alter($vars);
  }
}

/**
 * Override or insert variables into the page template.
 *
 * @param $vars
 * @param $hook
 */
function connect_theme_preprocess_page(&$vars, $hook) {
  if ($vars['page']['footer'] || $vars['page']['four_first'] || $vars['page']['four_second'] || $vars['page']['four_third'] || $vars['page']['four_fourth']) {
    $vars['classes_array'][] = 'with-footer';
  }

  if (isset($vars['node']->type)) {
    // If the content type's machine name is "my_machine_name" the file
    // name will be "page--my-machine-name.tpl.php".
    $vars['theme_hook_suggestions'][] = 'page__' . $vars['node']->type;
    if (isset($vars['view_mode'])) {
      // If the content type's machine name is "my_machine_name" and the view mode is "teaser" the file
      // name will be "page--my-machine-name--teaser.tpl.php".
      $vars['theme_hook_suggestions'][] = 'page__' . $vars['node']->type . '__' . $vars['view_mode'];
    }
  }
}

/**
 * Override or insert variables into the page template.
 *
 * @param $vars
 */
function connect_theme_process_page(&$vars) {
  // Hook into the color module.
  if (module_exists('color')) {
    _color_page_alter($vars);
  }
}

/**
 * Override or insert variables into the block template.
 *
 * @param $vars
 */
function connect_theme_preprocess_block(&$vars) {
  if ($vars['block']->module == 'superfish' || $vars['block']->module == 'nice_menu') {
    $vars['content_attributes_array']['class'][] = 'clearfix';
  }
  if (!$vars['block']->subject) {
    $vars['content_attributes_array']['class'][] = 'no-title';
  }
  if ($vars['block']->region == 'menu_bar' || $vars['block']->region == 'top_menu') {
    $vars['title_attributes_array']['class'][] = 'element-invisible';
  }
}

/**
 * Override or insert variables into the node template.
 *
 * @param $vars
 */
function connect_theme_preprocess_node(&$vars) {
  // Add class if user picture exists
  if (!empty($vars['submitted']) && $vars['display_submitted']) {
    if ($vars['user_picture']) {
      $vars['header_attributes_array']['class'][] = 'with-picture';
    }
  }
}

/**
 * Override or insert variables into the comment template.
 *
 * @param $vars
 */
function connect_theme_preprocess_comment(&$vars) {
  // Add class if user picture exists
  if ($vars['picture']) {
    $vars['header_attributes_array']['class'][] = 'with-user-picture';
  }
}

/**
 * Process variables for region.tpl.php
 *
 * @param $vars
 */
function connect_theme_process_region(&$vars) {
  // Add the click handle inside region menu bar
  if ($vars['region'] === 'menu_bar') {
    $vars['inner_prefix'] = '<h2 class="menu-toggle"><a href="#">' . t('Menu') . '</a></h2>';
  }
}

/**
 * @param $items
 */
function connect_theme_menu_alter(&$items) {
  $items['user']['title callback'] = 'connect_theme_user_menu_title';
}

/**
 * @return null|string
 */
function connect_theme_user_menu_title() {
  global $user;
  return user_is_logged_in() ? $user->name : t('User account');
}

/**
 * @param $variables
 * @return string
 */
function connect_theme_menu_tree__user_menu(&$variables) {
  global $user;
  drupal_add_js('jQuery(document).ready(function(){
      jQuery(".dropitmenu").dropit();
    });', 'inline');
  $output = '<ul class="dropitmenu"><li title="' . $user->name . '"><a href="#"><div class="elipsis-names">' . $user->name . '</div> <span class="dropit-icon ui-icon-triangle-1-s" style="display: inline-block;"></span></a><ul id="dropdown-menu" class="dropdown-menu">' . $variables['tree'] . '</ul></li></ul>';

  return $output;
}

/**
 * Display the list of available node types for node creation.
 *
 * @ingroup themeable
 */
function connect_theme_node_add_list($content) {
  $output = '';

  if (isset($content) && isset($content['content'])) {
    $output = '<dl class="node-type-list">';
    foreach ($content['content'] as $item) {
      $parts = explode('/', $item['href']);
      // dont include our content types in the create content page
      if (!(isset($parts) && isset($parts[2]) && ($parts[2] == 'api' || $parts[2] == 'application' || $parts[2] == 'product' || $parts[2] == 'devorg'))) {
        if (isset($item['localized_options'])) {
          $output .= '<dt>' . l($item['title'], $item['href'], $item['localized_options']) . '</dt>';
        }
        else {
          $output .= '<dt>' . l($item['title'], $item['href']) . '</dt>';
        }
        $output .= '<dd>' . filter_xss_admin($item['description']) . '</dd>';
      }
    }
    $output .= '</dl>';
  }
  return $output;
}

/**
 * Display full usernames not truncated ones
 * @param $vars
 */
function connect_theme_preprocess_username(&$vars) {
  // putting back what drupal core messed with
  $vars['name'] = check_plain($vars['name_raw']);
}