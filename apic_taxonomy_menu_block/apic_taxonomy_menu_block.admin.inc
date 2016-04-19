<?php

/**
 * @file
 * Admin functions and form settings for Taxonomy menu block module.
 */

/**
 * Menu callback: display the taxonomy menu block add form.
 */
function apic_taxonomy_menu_block_add_block_form($form, &$form_state) {
  // Load the standard block add form which we will alter later with
  // hook_form_alter().
  // We do not use hook_block_configure because we need Ajax callbacks.
  module_load_include('inc', 'block', 'block.admin');
  $form = block_admin_configure($form, $form_state, 'apic_taxonomy_menu_block', NULL);

  return $form;
}

/**
 * Edit function.
 */
function _apic_taxonomy_menu_block_edit_block($form, &$form_state) {
  apic_taxonomy_menu_block_save_block($form['delta']['#value'], $form_state['values']);
}

/**
 * Validate function.
 */
function _apic_taxonomy_menu_block_add_block_form_validate($form, &$form_state) {
  $values = $form_state['values'];

  switch ($values['parent']) {
    case '2':
      // Depth must be higher than parent level.
      if ($values['depth'] != '0' && $values['parent_dynamic'] >= $values['depth']) {
        form_set_error('depth', t('Depth must be higher than parent level.'));
      }
      break;

    case '1':
      $terms = apic_taxonomy_menu_block_get_taxonomy($values['vid']);
      $terms = $terms['tree'];

      // If we can't find the term. Mostly because there are no terms in
      // the vocabulary yet (in the current language).
      if (!isset($terms[$values['parent_fixed']])) {
        form_set_error('parent_fixed', t('No valid parent selected.'));
        return;
      }

      // Depth must be higher than the depth of Fixed parent.
      $term = $terms[$values['parent_fixed']];
      if ($values['depth'] != '0' && ($term['depth'] + 1) >= $values['depth']) {
        form_set_error('depth', t('Depth must be higher than the depth of your selected Fixed parent.'));
      }
      break;
  }
}

/**
 * Submit function.
 */
function apic_taxonomy_menu_block_add_block_form_submit($form, &$form_state) {
  // Determine the delta of the new block.
  $block_ids = variable_get('apic_taxonomy_menu_block_ids', array());
  $delta = empty($block_ids) ? 1 : max($block_ids) + 1;
  $form_state['values']['delta'] = $delta;

  // Save the new array of blocks IDs.
  $block_ids[] = $delta;
  variable_set('apic_taxonomy_menu_block_ids', $block_ids);

  // Save Taxonomy menu block options.
  apic_taxonomy_menu_block_save_block($delta, $form_state['values']);

  // Run the normal new block submission
  // (borrowed from block_add_block_form_submit).
  $query = db_insert('block')->fields(
    array(
      'visibility',
      'pages',
      'custom',
      'title',
      'module',
      'theme',
      'region',
      'status',
      'weight',
      'delta',
      'cache'
    )
  );

  foreach (list_themes() as $key => $theme) {
    if ($theme->status) {
      $region = !empty($form_state['values']['regions'][$theme->name]) ? $form_state['values']['regions'][$theme->name] : BLOCK_REGION_NONE;
      $query->values(array(
        'visibility' => (int) $form_state['values']['visibility'],
        'pages' => trim($form_state['values']['pages']),
        'custom' => (int) $form_state['values']['custom'],
        'title' => $form_state['values']['title'],
        'module' => $form_state['values']['module'],
        'theme' => $theme->name,
        'region' => ($region == BLOCK_REGION_NONE ? '' : $region),
        'status' => 0,
        'status' => (int) ($region != BLOCK_REGION_NONE),
        'weight' => 0,
        'delta' => $delta,
        'cache' => DRUPAL_NO_CACHE,
      ));
    }
  }
  $query->execute();

  $query = db_insert('block_role')->fields(array('rid', 'module', 'delta'));
  foreach (array_filter($form_state['values']['roles']) as $rid) {
    $query->values(array(
      'rid' => $rid,
      'module' => $form_state['values']['module'],
      'delta' => $delta,
    ));
  }
  $query->execute();

  drupal_set_message(t('The block has been created.'));
  cache_clear_all();
  $form_state['redirect'] = 'admin/structure/block';
}

/**
 * Save function.
 */
function apic_taxonomy_menu_block_save_block($delta = '', $values = array()) {
  if (empty($delta)) {
    return;
  }

  $ctypes = array();
  foreach ($values['ctype'] as $key => $type) {
    if ($type) {
      $ctypes[] = $type;
    }
  }

  $apic_taxonomy_menu_block = array(
    'delta' => $delta,
    'admin_title' => $values['admin_title'],
    'vid' => $values['vid'],
    'parent' => $values['parent'],
    'parent_fixed' => $values['parent_fixed'],
    'parent_dynamic' => $values['parent_dynamic'],
    'depth' => $values['depth'],
    'home_link' => $values['home_link'],
    'nodes' => $values['nodes'],
    'nodes_aggregation' => $values['nodes_aggregation'],
    'hide_empty' => $values['hide_empty'],
    'ctype' => $ctypes,
  );

  variable_set("apic_taxonomy_menu_block_{$delta}", $apic_taxonomy_menu_block);
}

/**
 * Menu callback: confirm deletion of taxonomy menu blocks.
 */
function apic_taxonomy_menu_block_delete($form, &$form_state, $delta = 0) {
  $form['delta'] = array('#type' => 'hidden', '#value' => $delta);

  $name = apic_taxonomy_menu_block_get_name($delta);

  return confirm_form($form, t('Are you sure you want to delete the "%name" block?', array('%name' => $name)), 'admin/structure/block', NULL, t('Delete'), t('Cancel'));
}

/**
 * Deletion of taxonomy menu blocks.
 */
function apic_taxonomy_menu_block_delete_submit($form, &$form_state) {
  // Get delta.
  $delta = $form_state['values']['delta'];

  $name = apic_taxonomy_menu_block_get_name($delta);

  // Remove the menu block configuration variables.
  $block_ids = variable_get('apic_taxonomy_menu_block_ids', array());
  unset($block_ids[array_search($delta, $block_ids)]);
  sort($block_ids);
  variable_set('apic_taxonomy_menu_block_ids', $block_ids);

  variable_del("apic_taxonomy_menu_block_{$delta}");

  db_delete('block')
    ->condition('module', 'apic_taxonomy_menu_block')
    ->condition('delta', $delta)
    ->execute();
  db_delete('block_role')
    ->condition('module', 'apic_taxonomy_menu_block')
    ->condition('delta', $delta)
    ->execute();

  drupal_set_message(t('The block "%name" has been removed.', array('%name' => $name)));
  cache_clear_all();
  $form_state['redirect'] = 'admin/structure/block';

  return;
}

/**
 * Expand normal Block add/edit form.
 *
 * Function that will add Taxonomy Menu Block options to the standard
 * Block add/edit form.
 */
function _apic_taxonomy_menu_block_form_options(&$form, &$form_state) {
  $form['#attached']['css'][] = drupal_get_path('module', 'apic_taxonomy_menu_block') . '/apic_taxonomy_menu_block_admin.css';
  $form['#attached']['js'][] = drupal_get_path('module', 'apic_taxonomy_menu_block') . '/apic_taxonomy_menu_block_admin.js';

  // Get all taxonomy vocabularies.
  $vocabs = array();
  foreach (taxonomy_vocabulary_get_names() as $vocab) {
    $vocabs[$vocab->vid] = $vocab->name;
  }

  // Get default values for this block.
  $delta = $form['delta']['#value'];
  $config = apic_taxonomy_menu_block_get_config($delta);
  $form_state['#config'] = $config;

  // Override if we have already submitted our form
  // (with Ajax callback for example).
  if (!empty($form_state['values'])) {
    $config = $form_state['values'];
  }

  // Build the fixed parent dropdown list and get the content types for which 
  // we can possibly apply the node options.
  if ($config['vid']) {
    $parents_fixed = apic_taxonomy_menu_block_get_parents_fixed($config['vid']);
    $ctypes = apic_taxonomy_menu_block_get_node_types($config['vid']);
  }
  else {
    $parents_fixed = apic_taxonomy_menu_block_get_parents_fixed(key($vocabs));
    $ctypes = apic_taxonomy_menu_block_get_node_types(key($vocabs));
  }

  $form['settings']['wrapper-start'] = array(
    '#markup' => '<div id="apim-taxonomy-menu-block-wrapper">',
    '#weight' => -20,
  );

  $form['settings']['admin_title'] = array(
    '#type' => 'textfield',
    '#default_value' => $config['admin_title'],
    '#title' => t('Administrative title'),
    '#description' => t('This title will be used administratively to identify this block. If blank, "Taxonomy Menu Block" will be used.'),
  );

  $form['settings']['vid'] = array(
    '#type' => 'select',
    '#title' => t('Select a Vocabulary'),
    '#default_value' => $config['vid'],
    '#options' => $vocabs,
    '#ajax' => array(
      'callback' => 'apic_taxonomy_menu_block_ajax_callback',
    ),
  );

  $form['settings']['parent'] = array(
    '#type' => 'select',
    '#title' => t('Select a parent option'),
    '#default_value' => $config['parent'],
    '#options' => array(
      '0' => t('Display whole tree'),
      '1' => t('Fixed parent'),
      '2' => t('Dynamic parent'),
    ),
  );

  $form['settings']['parent_fixed'] = array(
    '#type' => 'select',
    '#title' => t('Select a parent'),
    '#description' => t('Select a taxonomy term from your chosen vocabulary. An empty list means you have no terms in this vocabulary (for the current language).'),
    '#prefix' => '<div id="dropdown-fixed-parent">',
    '#suffix' => '</div>',
    '#options' => $parents_fixed,
    '#default_value' => $config['parent_fixed'],
  );

  $form['settings']['parent_dynamic'] = array(
    '#type' => 'select',
    '#title' => t('Select a parent level'),
    '#description' => t('Choose a parent option for this tree. Default is 1, meaning the tree will show everything under the first level of your
      taxonomy tree. Make sure this is lower than your depth!'),
    '#default_value' => $config['parent_dynamic'],
    '#options' => array(
      '1' => '1',
      '2' => '2',
      '3' => '3',
      '4' => '4',
      '5' => '5',
      '6' => '6',
      '7' => '7',
      '8' => '8',
      '9' => '9',
      '0' => t('Follow active item'),
    ),
  );

  $form['settings']['depth'] = array(
    '#type' => 'select',
    '#title' => t('Depth'),
    '#description' => t('Specify the maximum depth of the taxonomy tree. Depth is always relative to the root, except when you choose Dynamic parent > Follow active item, then depth is relative to the active item.'),
    '#default_value' => $config['depth'],
    '#options' => array(
      '1' => '1',
      '2' => '2',
      '3' => '3',
      '4' => '4',
      '5' => '5',
      '6' => '6',
      '7' => '7',
      '8' => '8',
      '9' => '9',
      '0' => t('Unlimited'),
    ),
  );

  $form['settings']['home_link'] = array(
    '#type' => 'checkbox',
    '#title' => t('Add Home link'),
    '#description' => t('Adds a Home-link (link to frontpage) at the beginning of the menu.'),
    '#default_value' => $config['home_link'],
  );

  $form['settings']['node_options'] = array(
    '#type' => 'fieldset',
    '#title' => t('Node options'),
    '#collapsible' => TRUE,
    '#collapsed' => TRUE,
  );

  $form['settings']['node_options']['nodes'] = array(
    '#type' => 'checkbox',
    '#title' => t('Display node count.'),
    '#description' => t('Display number of nodes attached to each term.'),
    '#default_value' => $config['nodes'],
  );

  $form['settings']['node_options']['nodes_aggregation'] = array(
    '#type' => 'checkbox',
    '#title' => t('Aggregate node count.'),
    '#description' => t('Add the node count of children to the node count of their <em>direct</em> parents. For this to work you need to check "Display node count".'),
    '#default_value' => $config['nodes_aggregation'],
  );

  $form['settings']['node_options']['hide_empty'] = array(
    '#type' => 'checkbox',
    '#title' => t('Hide empty.'),
    '#description' => t('Don\'t display terms with no content attached.'),
    '#default_value' => $config['hide_empty'],
  );

  $form['settings']['node_options']['ctype'] = array(
    '#type' => 'checkboxes',
    '#title' => t('Content type'),
    '#title_display' => 'before',
    '#description' => t('Only apply node options for certain content type(s). Content types must have a term reference field to the chosen vocabulary. Leave all unchecked to select all types.'),
    '#options' => $ctypes,
    '#default_value' => $config['ctype'],
    '#prefix' => '<div id="node-types">',
    '#suffix' => '</div>',
  );

  $form['settings']['wrapper-close'] = array(
    '#markup' => '</div>',
  );
}

/**
 * Ajax callback function.
 */
function apic_taxonomy_menu_block_ajax_callback(&$form, &$form_state) {
  $vid = $form_state['values']['vid'];
  $commands = array();

  // Get the correct form elements and add in new option values.
  $parent_fixed = $form['settings']['parent_fixed'];
  $parent_fixed['#options'] = apic_taxonomy_menu_block_get_parents_fixed($vid);
  $parent_fixed = form_builder($form['#id'], $parent_fixed, $form_state);
  $commands[] = ajax_command_replace('#dropdown-fixed-parent', drupal_render($parent_fixed));

  $ctype = $form['settings']['node_options']['ctype'];
  $ctype['#options'] = apic_taxonomy_menu_block_get_node_types($vid);
  $ctype = form_builder($form['#id'], $ctype, $form_state);
  $commands[] = ajax_command_replace('#node-types', drupal_render($ctype));

  return array(
    '#type' => 'ajax',
    '#commands' => $commands,
  );
}

/**
 * Get parent tids.
 *
 * @param int $vid
 *   Vocabulary id.
 *
 * @return array $parents_fixed
 *   Array of terms of a certain vocabulary.
 */
function apic_taxonomy_menu_block_get_parents_fixed($vid = 0) {
  $parents_fixed = array();

  if (!$vid) {
    return $parents_fixed;
  }

  $terms = apic_taxonomy_menu_block_get_taxonomy($vid);
  $terms = $terms['tree'];

  if ($terms) {
    foreach ($terms as $tid => $term) {
      $indent = str_repeat('-', $term['depth']);
      $parents_fixed[$tid] = $indent . ' ' . $term['name'];
    }
  }

  return $parents_fixed;
}

/**
 * Get content types that have term references to a certain vocabulary.
 *
 * @param int $vid
 *   Vocabulary id.
 *
 * @return array $options
 *   Array of node types
 */
function apic_taxonomy_menu_block_get_node_types($vid = 0) {
  $options = array();

  if (!$vid) {
    return $options;
  }

  $vocab = taxonomy_vocabulary_load($vid);
  $name = $vocab->machine_name;

  // Load all defined fields.
  $fields = field_info_fields();

  // See if any are term reference fields that reference to $vid.
  $types = array();
  foreach ($fields as $key => $field) {
    if ($field['type'] != 'taxonomy_term_reference' || $field['deleted'] != '0' || !isset($field['bundles']['node']) || $field['settings']['allowed_values'][0]['vocabulary'] != $name) {
      continue;
    }

    $types = array_merge($types, $field['bundles']['node']);
  }

  $ctypes = node_type_get_types();

  foreach ($types as $type) {
    $options[$type] = $ctypes[$type]->name;
  }

  return $options;
}
