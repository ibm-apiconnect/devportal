<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2016
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * Create the welcome page
 *
 * @param string $page
 * @throws \Exception
 */
function apim_profile_create_welcome_page($name = 'welcome') {
  // create title block for featured apis mini-panel
  db_insert('block_custom')->fields(array(
    'body' => t('<h2 class="pane-title block-title">Featured APIs</h2>'),
    'info' => 'featured_apis_title',
    'format' => 'filtered_html'
  ))->execute();
  $blockid = db_query("SELECT bid FROM {block_custom} WHERE info=:info", array(
    ':info' => 'featured_apis_title'
  ))->fetchField();
  if (isset($blockid)) {
    // Update block languages
    db_delete('i18n_block_language')
      ->condition('module', 'block')
      ->condition('delta', $blockid)
      ->execute();
    $query = db_insert('i18n_block_language')->fields(array('language', 'module', 'delta'));
    $langs = array_keys(_apim_profile_locale_get_predefined_list());
    $langs[] = 'en';
    foreach ($langs as $language) {
      $query->values(array(
        'language' => $language,
        'module' => 'block',
        'delta' => $blockid,
      ));
    }
    $query->execute();
  }

  // create mini panel
  db_insert('panels_display')->fields(array(
    'layout' => 'four_4x25',
    'layout_settings' => 'a:0:{}',
    'panel_settings' => 'a:1:{s:14:"style_settings";a:5:{s:7:"default";N;s:10:"four_first";N;s:11:"four_second";N;s:10:"four_third";N;s:11:"four_fourth";N;}}',
    'cache' => 'a:0:{}',
    'title_pane' => 0,
    'hide_title' => 0,
    'title' => 'Featured APIs',
    'uuid' => '60a355e1-ae82-46aa-9643-dbd87799e692'
  ))->execute();
  $minipanelid = db_query("SELECT did FROM {panels_display} WHERE uuid=:uuid", array(
    ':uuid' => '60a355e1-ae82-46aa-9643-dbd87799e692'
  ))->fetchField();

  db_insert('panels_mini')->fields(array(
    'name' => 'popular_apis',
    'did' => $minipanelid,
    'admin_title' => 'Featured APIs',
    'contexts' => 'a:0:{}',
    'requiredcontexts' => 'a:0:{}',
    'relationships' => 'a:0:{}'
  ))->execute();

  $path = 'home';
  if ($name != 'welcome') {
    $path = 'v5home';
  }
  // create page
  db_insert('page_manager_pages')->fields(array(
    'name' => $name,
    'task' => 'page',
    'admin_title' => $name,
    'path' => $path,
    'access' => 'a:0:{}',
    'menu' => 'a:0:{}',
    'arguments' => 'a:0:{}',
    'conf' => 'a:1:{s:11:"admin_paths";b:0;}'
  ))->execute();
  $pageid = db_query("SELECT pid FROM {page_manager_pages} WHERE name=:name AND path=:path", array(
    ':name' => $name,
    ':path' => $path
  ))->fetchField();

  db_insert('page_manager_handlers')->fields(array(
    'name' => 'page_' . $name . '__panel',
    'task' => 'page',
    'subtask' => $name,
    'conf' => 'a:11:{s:5:"title";s:5:"Panel";s:9:"no_blocks";i:0;s:8:"pipeline";s:8:"standard";s:22:"body_classes_to_remove";s:0:"";s:19:"body_classes_to_add";s:9:"frontpage";s:6:"css_id";s:0:"";s:3:"css";s:0:"";s:8:"contexts";a:0:{}s:13:"relationships";a:0:{}s:4:"name";s:5:"panel";s:3:"did";s:1:"2";}',
    'handler' => 'panel_context'
  ))->execute();
  $page_handler_id = db_query("SELECT did FROM {page_manager_handlers} WHERE subtask=:subtask AND name=:name", array(
    ':subtask' => $name,
    ':name' => 'page_' . $name . '__panel'
  ))->fetchField();

  db_insert('panels_display')->fields(array(
    'layout' => 'two_brick',
    'layout_settings' => 'a:0:{}',
    'panel_settings' => 'a:1:{s:14:"style_settings";a:8:{s:7:"default";N;s:13:"two_brick_top";N;s:20:"two_brick_left_above";N;s:21:"two_brick_right_above";N;s:16:"two_brick_middle";N;s:20:"two_brick_left_below";N;s:21:"two_brick_right_below";N;s:16:"two_brick_bottom";N;}}',
    'cache' => 'a:0:{}',
    'title_pane' => 6,
    'uuid' => '8cd3c70d-730a-432a-9fe6-96a8e03cb85f'
  ))->execute();
  $panelid = db_query("SELECT did FROM {panels_display} WHERE uuid=:uuid", array(
    ':uuid' => '8cd3c70d-730a-432a-9fe6-96a8e03cb85f'
  ))->fetchField();
  db_update('page_manager_handlers')
    ->fields(array('conf' => 'a:11:{s:5:"title";s:5:"Panel";s:9:"no_blocks";i:0;s:8:"pipeline";s:8:"standard";s:22:"body_classes_to_remove";s:0:"";s:19:"body_classes_to_add";s:9:"frontpage";s:6:"css_id";s:0:"";s:3:"css";s:0:"";s:8:"contexts";a:0:{}s:13:"relationships";a:0:{}s:4:"name";s:5:"panel";s:3:"did";s:' . strlen((string) $panelid) . ':"' . $panelid . '";}'))
    ->condition('did', $page_handler_id)
    ->execute();

  $insert_welcome_block = function ($pid, $panelid, $panel, $type, $subtype, $uuid, $override_title) {
    db_insert('panels_pane')->fields(array(
      'did' => $panelid,
      'panel' => $panel,
      'type' => $type,
      'subtype' => $subtype,
      'shown' => 1,
      'access' => 'a:0:{}',
      'configuration' => 'a:2:{s:14:"override_title";i:' . $override_title . ';s:19:"override_title_text";s:0:"";}',
      'cache' => 'a:0:{}',
      'style' => 'a:1:{s:8:"settings";N;}',
      'css' => 'a:0:{}',
      'extras' => 'a:0:{}',
      'locks' => 'a:0:{}',
      'uuid' => $uuid
    ))->execute();
  };

  apim_profile_create_socialblock();

  $insert_welcome_block(6, $panelid, 'two_brick_top', 'block', 'bean-banner_block', '150ca0df-53fe-44db-9266-5a9d1b62ff28', 1);
  $insert_welcome_block($minipanelid, $panelid, 'two_brick_middle', 'panels_mini', 'popular_apis', '64b194f9-81bb-46f8-ad61-f7ce0a2e2fab', 1);
  $insert_welcome_block(8, $panelid, 'two_brick_bottom', 'block', 'bean-socialblock', 'cf9bf3b0-f01c-4631-8c27-773bd13ade3e', 1);
  // add custom block
  db_insert('panels_pane')->fields(array(
    'did' => $panelid,
    'panel' => 'two_brick_middle',
    'type' => 'block',
    'subtype' => 'block-' . $blockid,
    'shown' => 1,
    'access' => 'a:0:{}',
    'configuration' => 'a:2:{s:14:"override_title";i:' . 1 . ';s:19:"override_title_text";s:0:"";}',
    'cache' => 'a:0:{}',
    'style' => 'a:1:{s:8:"settings";N;}',
    'css' => 'a:2:{s:6:"css_id";s:0:"";s:9:"css_class";s:19:"featured_apis_title";}',
    'extras' => 'a:0:{}',
    'locks' => 'a:0:{}',
    'uuid' => '2d598e8e-e128-4c64-8191-6092051b29ae'
  ))->execute();
  db_update('panels_pane')
    ->fields(array('position' => 1))
    ->condition('subtype', 'popular_apis')
    ->execute();

  variable_set('ibm_apim_frontpage_styling_update', 1);
}

function apim_profile_create_socialblock() {
  if (module_exists('socialblock')) {
    $bean = bean_create(array('type' => 'socialblock'));
    $bean->label = 'socialblock';
    $bean->title = '';
    $bean->socialblocktitle['en'] = array(array('value' => t('Latest forum posts and tweets'), 'format' => 'plain_text'));
    $bean->delta = 'socialblock';
    $bean->type = 'socialblock_block';
    $bean->view_mode = 'default';
    $bean->data = array('tiles' => '8', 'forums' => array(1 => '1'));
    $bean->save();
  }
}

/**
 * Switch an existing page to old and enable the new one.
 */
function apim_profile_migrate_frontpage() {
  $pageid = db_query("SELECT pid FROM {page_manager_pages} WHERE path=:path", array(
    ':path' => 'v5home'
  ))->fetchField();
  if (isset($pageid) && is_numeric($pageid)) {
    db_update('page_manager_pages')
      ->fields(array('path' => 'home_old'))
      ->condition('path', 'home')
      ->execute();

    db_update('page_manager_pages')
      ->fields(array('path' => 'home'))
      ->condition('path', 'v5home')
      ->execute();
  }
}

function apim_profile_update_frontpage_i18n() {
  // create title block for featured apis mini-panel
  $blockid = db_query("SELECT bid FROM {block_custom} WHERE info=:info", array(
    ':info' => 'featured_apis_title'
  ))->fetchField();
  if (isset($blockid)) {
    db_update('block_custom')
      ->fields(array('body' => t('<h2 class="pane-title block-title">Featured APIs</h2>'), 'format' => 'filtered_html'))
      ->condition('info', 'featured_apis_title')
      ->execute();
  }
  else {
    db_insert('block_custom')->fields(array(
      'body' => t('<h2 class="pane-title block-title">Featured APIs</h2>'),
      'info' => 'featured_apis_title',
      'format' => 'filtered_html'
    ))->execute();
  }

  $blockid = db_query("SELECT bid FROM {block_custom} WHERE info=:info", array(
    ':info' => 'featured_apis_title'
  ))->fetchField();
  if (isset($blockid)) {
    // Update block languages
    db_delete('i18n_block_language')
      ->condition('module', 'block')
      ->condition('delta', $blockid)
      ->execute();
    $query = db_insert('i18n_block_language')->fields(array('language', 'module', 'delta'));
    $langs = array_keys(_apim_profile_locale_get_predefined_list());
    $langs[] = 'en';
    foreach ($langs as $language) {
      $query->values(array(
        'language' => $language,
        'module' => 'block',
        'delta' => $blockid,
      ));
    }
    $query->execute();

    // add custom block
    $panelid = db_query("SELECT did FROM {panels_display} WHERE uuid=:uuid", array(
      ':uuid' => '8cd3c70d-730a-432a-9fe6-96a8e03cb85f'
    ))->fetchField();
    $paneid = db_query("SELECT did FROM {panels_pane} WHERE did=:did AND subtype=:subtype", array(
      ':did' => $panelid,
      ':subtype' => 'block-' . $blockid
    ))->fetchField();
    if (isset($paneid)) {
      db_update('panels_pane')
        ->fields(array(
          'css' => 'a:2:{s:6:"css_id";s:0:"";s:9:"css_class";s:19:"featured_apis_title";}',
          'configuration' => 'a:2:{s:14:"override_title";i:' . 1 . ';s:19:"override_title_text";s:0:"";}'
        ))
        ->condition('did', $panelid)
        ->condition('subtype', 'block-' . $blockid)
        ->execute();
    }
    else {
      db_insert('panels_pane')->fields(array(
        'did' => $panelid,
        'panel' => 'two_brick_middle',
        'type' => 'block',
        'subtype' => 'block-' . $blockid,
        'shown' => 1,
        'access' => 'a:0:{}',
        'configuration' => 'a:2:{s:14:"override_title";i:' . 1 . ';s:19:"override_title_text";s:0:"";}',
        'cache' => 'a:0:{}',
        'style' => 'a:1:{s:8:"settings";N;}',
        'css' => 'a:2:{s:6:"css_id";s:0:"";s:9:"css_class";s:19:"featured_apis_title";}',
        'extras' => 'a:0:{}',
        'locks' => 'a:0:{}',
        'uuid' => '2d598e8e-e128-4c64-8191-6092051b29ae'
      ))->execute();
    }
  }

  db_update('panels_pane')
    ->fields(array('position' => 1, 'configuration' => 'a:2:{s:14:"override_title";i:' . 1 . ';s:19:"override_title_text";s:0:"";}'))
    ->condition('subtype', 'popular_apis')
    ->execute();
  db_update('panels_pane')
    ->fields(array('configuration' => 'a:2:{s:14:"override_title";i:' . 1 . ';s:19:"override_title_text";s:0:"";}'))
    ->condition('subtype', 'bean-socialblock')
    ->execute();
}

function apim_profile_update_frontpage_styling() {
  db_update('page_manager_handlers')
    ->fields(array('conf' => 'a:11:{s:5:"title";s:5:"Panel";s:9:"no_blocks";i:0;s:8:"pipeline";s:8:"standard";s:22:"body_classes_to_remove";s:0:"";s:19:"body_classes_to_add";s:9:"frontpage";s:6:"css_id";s:0:"";s:3:"css";s:0:"";s:8:"contexts";a:0:{}s:13:"relationships";a:0:{}s:4:"name";s:5:"panel";s:3:"did";s:1:"2";}'))
    ->condition('name', 'page_welcome__panel')
    ->condition('task', 'page')
    ->condition('subtask', 'welcome')
    ->execute();
}
