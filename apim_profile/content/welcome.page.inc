<?php

/**
 * Create the welcome page
 *
 * @param string $page
 * @throws \Exception
 */
function apim_profile_create_welcome_page($name = 'welcome') {

  // create mini panel
  db_insert('panels_display')->fields(array(
    'layout' => 'four_4x25',
    'layout_settings' => 'a:0:{}',
    'panel_settings' => 'a:1:{s:14:"style_settings";a:5:{s:7:"default";N;s:10:"four_first";N;s:11:"four_second";N;s:10:"four_third";N;s:11:"four_fourth";N;}}',
    'cache' => 'a:0:{}',
    'title_pane' => 0,
    'hide_title' => 0,
    'title' => 'Popular APIs',
    'uuid' => '60a355e1-ae82-46aa-9643-dbd87799e692'
  ))->execute();
  $minipanelid = db_query("SELECT did FROM {panels_display} WHERE uuid=:uuid", array(
    ':uuid' => '60a355e1-ae82-46aa-9643-dbd87799e692'
  ))->fetchField();

  db_insert('panels_mini')->fields(array(
    'name' => 'popular_apis',
    'did' => $minipanelid,
    'admin_title' => 'Popular APIs',
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
    'conf' => 'a:11:{s:5:"title";s:5:"Panel";s:9:"no_blocks";i:0;s:8:"pipeline";s:8:"standard";s:22:"body_classes_to_remove";s:0:"";s:19:"body_classes_to_add";s:0:"";s:6:"css_id";s:0:"";s:3:"css";s:0:"";s:8:"contexts";a:0:{}s:13:"relationships";a:0:{}s:4:"name";s:5:"panel";s:3:"did";s:1:"7";}',
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
    ->fields(array('conf' => 'a:11:{s:5:"title";s:5:"Panel";s:9:"no_blocks";i:0;s:8:"pipeline";s:8:"standard";s:22:"body_classes_to_remove";s:0:"";s:19:"body_classes_to_add";s:0:"";s:6:"css_id";s:0:"";s:3:"css";s:0:"";s:8:"contexts";a:0:{}s:13:"relationships";a:0:{}s:4:"name";s:5:"panel";s:3:"did";s:' . strlen((string) $panelid) . ':"' . $panelid . '";}'))
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
  $insert_welcome_block($minipanelid, $panelid, 'two_brick_middle', 'panels_mini', 'popular_apis', '64b194f9-81bb-46f8-ad61-f7ce0a2e2fab', 0);
  $insert_welcome_block(8, $panelid, 'two_brick_bottom', 'block', 'bean-socialblock', 'cf9bf3b0-f01c-4631-8c27-773bd13ade3e', 0);

}

function apim_profile_create_socialblock() {
  if (module_exists('socialblock')) {
    $bean = bean_create(array('type' => 'socialblock'));
    $bean->label = 'socialblock';
    $bean->title = 'Latest forum posts and tweets';
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
