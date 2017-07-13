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
 * @file
 * Import knowledge center plugin as a drupal book.
 */

/**
 * Create sample nodes
 */
function apim_profile_create_book() {
  if (!module_exists('book') || !module_exists('book_access')) {
    module_enable(array('book', 'book_access'));
  }
  variable_set('language_content_type_book', 4);
  variable_set('entity_translation_settings_node__book', array(
    'default_language' => 'en',
    'hide_language_selector' => 0,
    'exclude_language_none' => 0,
    'lock_language' => 0,
    'shared_fields_original_only' => 0
  ));
  $book_ids = variable_get('ibm_apim_book_ids', array(
    'bid' => NULL,
    'pages' => array()
  ));
  $path = dirname(__FILE__);
  require_once $path . '/book.content.inc';
  $body_content = apim_profile_book_content();

  // set book navigation menu to show on header
  $nav_blockid = db_query("SELECT bid FROM {block} WHERE module=:module AND delta=:delta AND theme=:theme", array(
    ':module' => 'book',
    ':delta' => 'navigation',
    ':theme' => 'connect_theme'
  ))->fetchField();
  if (isset($nav_blockid) && is_numeric($nav_blockid)) {
    db_update('block')
      ->fields(array('region' => 'sidebar_second'))
      ->condition('module', 'book')
      ->condition('delta', 'navigation')
      ->condition('theme', 'connect_theme')
      ->execute();
  }
  else {
    db_insert('block')->fields(array(
      'status' => 1,
      'region' => 'sidebar_second',
      'title' => '',
      'pages' => "",
      'module' => 'book',
      'delta' => 'navigation',
      'theme' => 'connect_theme'
    ))->execute();
  }
  $type = db_query("SELECT type FROM {block_node_type} WHERE module=:module AND delta=:delta", array(
    ':module' => 'book',
    ':delta' => 'navigation'
  ))->fetchField();
  if (isset($type) && !empty($type)) {
    db_update('block_node_type')
      ->fields(array('type' => 'book'))
      ->condition('module', 'book')
      ->condition('delta', 'navigation')
      ->execute();
  }
  else {
    db_insert('block_node_type')->fields(array(
      'module' => 'book',
      'delta' => 'navigation',
      'type' => 'book'
    ))->execute();
  }

  $bid = apim_profile_create_book_parent($book_ids['bid']);

  $book_ids['bid'] = $bid;
  $titles = array(
    'basic.html' => t('Basic Configuration'), //basic
    'changing_the_site_name.html' => t('Changing the site name'),
    'chnage_site_slogan.html' => t('Changing the site slogan'),
    'change_site_email.html' => t('Changing the site email'),
    'change_logo.html' => t('Changing the logo'),
    'change_shortcut_icon.html' => t('Changing the shortcut icon'),
    'banner_image_change.html' => t('Changing the front page banner image'),
    'twitter_block.html' => t('Configuring credentials for the social block'),
    'edit_twitter_social_block.html' => t('Configuring the social block'),
    'popular_product.html' => t('Configuring Featured APIs'),
    'follow_social_media_links.html' => t('Setting social media links'),
    'forum_moderation.html' => t('Forum moderation'), //forum
    'create_new_forum.html' => t('Creating a new forum'),
    'creating_new_forum_containers.html' => t('Creating forum containers'),
    'configure_creation_new_forums_for_each_API.html' => t('Configure the creation of new API forums'),
    'mark_content_as_sticky_in_forum.html' => t('Mark forum content as sticky'),
    'locking_forum_topics.html' => t('Locking forum topics'),
    'removing_forum_post.html' => t('Removing forum posts'),
    'make_forum_private.html' => t('Editing forum access'),
    'content_author.html' => t('Content authoring'), // content author
    'adding_new_pages.html' => t('Creating new nodes'),
    'edit_sample_content.html' => t('Editing sample content'),
    'edit_site_comment.html' => t('Editing comments'),
    'edit_tags_specific_item.html' => t('Edit the tags for a node'),
    'post_new_blog_entries.html' => t('Configuring blogs'),
    'add_FAQs.html' => t('Add frequently asked questions'),
    'post_poll.html' => t('Post a poll'),
    'attach_files_to_api.html' => t('Attach files to an API'),
    'embed_multimedia_in_site_content.html' => t('Embedding multimedia in nodes'),
    'cloning_static_doc.html' => t('Cloning nodes'),
    'configuring_layout.html' => t('Configuring the site layout'), // layout
    'frontpage_configure.html' => t('Configure the front page'),
    'main_menu_change.html' => t('Edit the main menu'),
    'menu_add.html' => t('Add a new menu'),
    'block_change.html' => t('Configure blocks'),
    'custom_pages.html' => t('Create custom pages'),
    'role_based_homepage.html' => t('Role based front pages'),
    'adding_context.html' => t('Using Context'),
    'configuring_content.html' => t('Configuring content'), // site content
    'create_content_type.html' => t('Create new content types'),
    'add_field.html' => t('Add fields to content types'),
    'carousel.html' => t('Setup a carousel'),
    'configure_appearance_ratings.html' => t('Configure the appearance of ratings'),
    'configure_social_sharing_widget.html' => t('Configure the social sharing widget'),
    'file_size_limitations.html' => t('Edit the file upload limits'),
    'support_ticket.html' => t('Configuring Support Tickets'),
    'workbench_moderation.html' => t('Configure workflows and reviews'),
    'using_workbench_moderation.html' => t('Using workbench moderation'),
    'managing_users.html' => t('Managing users'), // managing users
    'creating_and_customising_a_new_role.html' => t('Roles'),
    'create_a_new_role.html' => t('Creating a new role'),
    'assigning_people_to_a_role.html' => t('Assign users to a role'),
    'assigning_permissions_to_a_new_role.html' => t('Assigning permissions to a role'),
    'content_author_permissions.html' => t('Content author permissions'),
    'user_reg_config.html' => t('Configure user registration'),
    'blocking_and_unblocking_specific_users.html' => t('Blocking and unblocking users'),
    'managing_security.html' => t('Managing security'), // managing security
    'tsandcs_config.html' => t('Terms and conditions'),
    'captcha_config.html' => t('Captchas'),
    'blocked_ip_addresses.html' => t('Unblocking IP addresses'),
    'flood_control.html' => t('Flood control'),
    'session_timeout_config.html' => t('Session limits and timeout'),
    'logout_refresh.html' => t('Configure logout refresh'),
    'pword_policy_config.html' => t('Password policy'),
    'password_reset_link.html' => t('Configure password reset timeout'),
    'no_autocomplete.html' => t('Form autocomplete'),
    'restricting_users.html' => t('Restricting access based on IP Address'),
    'two_factor_auth.html' => t('Two factor authentication'),
    'security_questions.html' => t('Security questions'),
    'misc_security.html' => t('Security Kit'),
    'pdur.html' => t('Portal Delegated User Registry'),
    'social_login.html' => t('External authentication Providers'),
    'application_settings_facebook.html' => t('Facebook'),
    'application_settings_google.html' => t('Google'),
    'application_settings_twitter.html' => t('Twitter'),
    'application_settings_linkedin.html' => t('LinkedIn'),
    'application_settings_slack.html' => t('Slack'),
    'application_settings_windows_live.html' => t('Windows Live'),
    'application_settings_github.html' => t('Github'),
    'configure_ldap.html' => t('LDAP'),
    'account_approval.html' => t('Configure registration approval'),
    'modify_emails.html' => t('Modify site email templates'),
    'extending.html' => t('Extending'), // extending
    'custom_modules.html' => t('Develop custom modules'),
    'additional_modules.html' => t('Install additional modules'),
    'disable_module.html' => t('Disabling modules'),
    'install_adiitional_themes.html' => t('Install additional themes'),
    'override_content_types.html' => t('Overriding content type templates'),
    'tutorial_portal_customizing_css.html' => t('Customizing theme CSS')
  );

  foreach ($titles as $filename => $title) {
    $body = isset($body_content[$filename]) ? $body_content[$filename] : NULL;
    $id = isset($book_ids['pages'][$filename]) ? $book_ids['pages'][$filename] : NULL;
    $pageid = apim_profile_create_page($title, $body, $bid, $id);
    $book_ids['pages'][$filename] = $pageid;
  }
  variable_set('ibm_apim_book_ids', $book_ids);

  $group_index = 0;

  // basic section
  $basic = array(
    'changing_the_site_name.html',
    'chnage_site_slogan.html',
    'change_site_email.html',
    'change_logo.html',
    'change_shortcut_icon.html',
    'banner_image_change.html',
    'twitter_block.html',
    'edit_twitter_social_block.html',
    'popular_product.html',
    'follow_social_media_links.html',
    'tsandcs_config.html'
  );
  $parentid = $book_ids['pages']['basic.html'];
  $parent = node_load($parentid);
  if (isset($parent) && isset($parent->nid)) {
    $parent->book['weight'] = $group_index;
    node_save($parent);
  }
  $group_index++;
  $parent_mlid = $parent->book['mlid'];
  $index = 0;
  foreach ($basic as $filename) {
    $nid = $book_ids['pages'][$filename];
    $node = node_load($nid);
    if (isset($node)) {
      $node->book['plid'] = $parent_mlid;
      $node->book['weight'] = $index;
      node_save($node);
      $index++;
    }
  }
  // forum moderation
  $forum = array(
    'create_new_forum.html',
    'creating_new_forum_containers.html',
    'configure_creation_new_forums_for_each_API.html',
    'mark_content_as_sticky_in_forum.html',
    'locking_forum_topics.html',
    'removing_forum_post.html',
    'make_forum_private.html'
  );
  $parentid = $book_ids['pages']['forum_moderation.html'];
  $parent = node_load($parentid);
  if (isset($parent) && isset($parent->nid)) {
    $parent->book['weight'] = $group_index;
    node_save($parent);
  }
  $group_index++;
  $parent_mlid = $parent->book['mlid'];
  $index = 0;
  foreach ($forum as $filename) {
    $nid = $book_ids['pages'][$filename];
    $node = node_load($nid);
    if (isset($node)) {
      $node->book['plid'] = $parent_mlid;
      $node->book['weight'] = $index;
      node_save($node);
      $index++;
    }
  }
  // content author
  $content_author = array(
    'adding_new_pages.html',
    'edit_sample_content.html',
    'edit_site_comment.html',
    'edit_tags_specific_item.html',
    'post_new_blog_entries.html',
    'add_FAQs.html',
    'post_poll.html',
    'attach_files_to_api.html',
    'embed_multimedia_in_site_content.html',
    'cloning_static_doc.html'
  );
  $parentid = $book_ids['pages']['content_author.html'];
  $parent = node_load($parentid);
  if (isset($parent) && isset($parent->nid)) {
    $parent->book['weight'] = $group_index;
    node_save($parent);
  }
  $group_index++;
  $parent_mlid = $parent->book['mlid'];
  $index = 0;
  foreach ($content_author as $filename) {
    $nid = $book_ids['pages'][$filename];
    $node = node_load($nid);
    if (isset($node)) {
      $node->book['plid'] = $parent_mlid;
      $node->book['weight'] = $index;
      node_save($node);
      $index++;
    }
  }
  // layout
  $configuring_layout = array(
    'frontpage_configure.html',
    'main_menu_change.html',
    'menu_add.html',
    'block_change.html',
    'custom_pages.html',
    'adding_context.html',
    'role_based_homepage.html'
  );
  $parentid = $book_ids['pages']['configuring_layout.html'];
  $parent = node_load($parentid);
  if (isset($parent) && isset($parent->nid)) {
    $parent->book['weight'] = $group_index;
    node_save($parent);
  }
  $group_index++;
  $parent_mlid = $parent->book['mlid'];
  $index = 0;
  foreach ($configuring_layout as $filename) {
    $nid = $book_ids['pages'][$filename];
    $node = node_load($nid);
    if (isset($node)) {
      $node->book['plid'] = $parent_mlid;
      $node->book['weight'] = $index;
      node_save($node);
      $index++;
    }
  }
  // content
  $configuring_content = array(
    'create_content_type.html',
    'add_field.html',
    'carousel.html',
    'configure_appearance_ratings.html',
    'configure_social_sharing_widget.html',
    'file_size_limitations.html',
    'support_ticket.html',
    'workbench_moderation.html',
    'using_workbench_moderation.html'
  );
  $parentid = $book_ids['pages']['configuring_content.html'];
  $parent = node_load($parentid);
  if (isset($parent) && isset($parent->nid)) {
    $parent->book['weight'] = $group_index;
    node_save($parent);
  }
  $group_index++;
  $parent_mlid = $parent->book['mlid'];
  $index = 0;
  foreach ($configuring_content as $filename) {
    $nid = $book_ids['pages'][$filename];
    $node = node_load($nid);
    if (isset($node)) {
      $node->book['plid'] = $parent_mlid;
      $node->book['weight'] = $index;
      node_save($node);
      $index++;
    }
  }
  // users
  $managing_users = array(
    'creating_and_customising_a_new_role.html',
    'create_a_new_role.html',
    'assigning_people_to_a_role.html',
    'assigning_permissions_to_a_new_role.html',
    'content_author_permissions.html',
    'user_reg_config.html',
    'blocking_and_unblocking_specific_users.html'
  );
  $parentid = $book_ids['pages']['managing_users.html'];
  $parent = node_load($parentid);
  if (isset($parent) && isset($parent->nid)) {
    $parent->book['weight'] = $group_index;
    node_save($parent);
  }
  $group_index++;
  $parent_mlid = $parent->book['mlid'];
  $index = 0;
  foreach ($managing_users as $filename) {
    $nid = $book_ids['pages'][$filename];
    $node = node_load($nid);
    if (isset($node)) {
      $node->book['plid'] = $parent_mlid;
      $node->book['weight'] = $index;
      node_save($node);
      $index++;
    }
  }
  // security
  $managing_security = array(
    'captcha_config.html',
    'blocked_ip_addresses.html',
    'flood_control.html',
    'session_timeout_config.html',
    'logout_refresh.html',
    'pword_policy_config.html',
    'password_reset_link.html',
    'no_autocomplete.html',
    'restricting_users.html',
    'two_factor_auth.html',
    'security_questions.html',
    'misc_security.html',
    'pdur.html',
    'social_login.html',
    'application_settings_facebook.html',
    'application_settings_google.html',
    'application_settings_twitter.html',
    'application_settings_linkedin.html',
    'application_settings_slack.html',
    'application_settings_windows_live.html',
    'application_settings_github.html',
    'configure_ldap.html',
    'account_approval.html',
    'modify_emails.html'
  );
  $parentid = $book_ids['pages']['managing_security.html'];
  $parent = node_load($parentid);
  if (isset($parent) && isset($parent->nid)) {
    $parent->book['weight'] = $group_index;
    node_save($parent);
  }
  $group_index++;
  $parent_mlid = $parent->book['mlid'];
  $index = 0;
  foreach ($managing_security as $filename) {
    $nid = $book_ids['pages'][$filename];
    $node = node_load($nid);
    if (isset($node)) {
      $node->book['plid'] = $parent_mlid;
      $node->book['weight'] = $index;
      node_save($node);
      $index++;
    }
  }
  // extending
  $extending = array(
    'custom_modules.html',
    'additional_modules.html',
    'disable_module.html',
    'install_adiitional_themes.html',
    'tutorial_portal_customizing_css.html',
    'override_content_types.html'
  );
  $parentid = $book_ids['pages']['extending.html'];
  $parent = node_load($parentid);
  if (isset($parent) && isset($parent->nid)) {
    $parent->book['weight'] = $group_index;
    node_save($parent);
  }
  $parent_mlid = $parent->book['mlid'];
  $index = 0;
  foreach ($extending as $filename) {
    $nid = $book_ids['pages'][$filename];
    $node = node_load($nid);
    if (isset($node)) {
      $node->book['plid'] = $parent_mlid;
      $node->book['weight'] = $index;
      node_save($node);
      $index++;
    }
  }
  // pdur
  $pdur = array(
    'social_login.html',
    'application_settings_facebook.html',
    'application_settings_google.html',
    'application_settings_twitter.html',
    'application_settings_linkedin.html',
    'application_settings_slack.html',
    'application_settings_windows_live.html',
    'application_settings_github.html',
    'configure_ldap.html',
    'account_approval.html',
    'modify_emails.html'
  );
  $parentid = $book_ids['pages']['pdur.html'];
  $parent = node_load($parentid);
  $parent_mlid = $parent->book['mlid'];
  $index = 0;
  foreach ($pdur as $filename) {
    $nid = $book_ids['pages'][$filename];
    $node = node_load($nid);
    if (isset($node)) {
      $node->book['plid'] = $parent_mlid;
      $node->book['weight'] = $index;
      node_save($node);
      $index++;
    }
  }

  apim_profile_update_page_links('en');
}

/**
 * @param null $bid
 * @return null
 * @throws \Exception
 */
function apim_profile_create_book_parent($bid = NULL) {
  $body = t('<h2>Welcome to your new IBM API Connect Developer Portal.</h2>
<p>This guide should help familiarise you with the extensive configuration and customization options available.</p>');
  $body = str_replace(array("\r", "\n"), ' ', $body);

  if ($bid != NULL) {
    $node = node_load($bid);
    if (!isset($node->type) || $node->type!= 'book' ||!isset($node->book) || !isset($node->book['bid'])|| $node->book['bid'] != $node->nid) {
      unset($node);
    }
  }
  if (!isset($node) || !isset($node->type) || $node->type != 'book') {
    $node = new stdClass();
    $node->nid = NULL;
    $node->type = 'book';
    $node->uid = 1;
    $node->status = 1;
    $node->comment = 1;
    $node->promote = 0;
    $node->sticky = 0;
    $node->language = 'en';
    node_object_prepare($node);
    $node->book = array(
      "bid" => 'new',
      "plid" => 0
    );
  }

  $node->title = t('Admin Guide');
  $node->body['en'] = array(
    array(
      'value' => $body,
      'format' => 'filtered_html',
      'safe_value' => $body
    )
  );
  node_save($node);
  if (module_exists('book_access')) {
    // set role grants for the book
    $path = drupal_get_path('module', 'book_access');
    require_once $path . '/book_access.module';
    BookAccess::setRoleGrants($node->nid, array(1, 2, 3, 4, 5), array(
      'grant_admin_access' => array(3 => 1),
      'grant_update' => array(3 => 1),
      'grant_delete' => array(3 => 1),
      'grant_add_child' => array(3 => 1),
      'grant_edit_outline' => array(3 => 1),
      'grant_view' => array(3 => 1, 4 => 1, 5 => 1)
    ));
  }

  return $node->nid;
}

/**
 * @param $title
 * @param $body
 * @param $bid
 * @param null $nid
 * @return null
 * @throws \Exception
 */
function apim_profile_create_page($title, $body, $bid, $nid = NULL) {
  $body = str_replace(array("\r", "\n"), ' ', $body);
  $book = node_load($bid);
  if (isset($book->book['mlid'])) {
    $plid = $book->book['mlid'];
  }
  else {
    $plid = 0;
  }

  $lang_default = 'en';
  if ($nid != NULL) {
    $node = node_load($nid);
  }
  else {
    $node = new stdClass();
    $node->nid = NULL;
    $node->type = 'book';
    $node->uid = 1;
    $node->status = 1;
    $node->comment = 1;
    $node->promote = 0;
    $node->sticky = 0;
    node_object_prepare($node);
    $node->language = $lang_default;
    $node->book = array(
      "bid" => $bid,
      "link_title" => $title,
      "plid" => $plid
    );
  }

  // Set title and body fields.
  $node->title = $title;
  $node->body[$lang_default] = array(
    array(
      'value' => $body,
      'format' => 'filtered_html',
      'safe_value' => $body
    )
  );

  node_save($node);
  return $node->nid;
}

/**
 * Function to update the links within the book pages to now point to our content instead of the infocenter
 *
 * @param string $langcode
 */
function apim_profile_update_page_links($langcode = 'en') {
  $book_ids = variable_get('ibm_apim_book_ids', array(
    'bid' => NULL,
    'pages' => array()
  ));
  $nid = $book_ids['pages']['edit_twitter_social_block.html'];
  $linkid = $book_ids['pages']['twitter_block.html'];
  _apim_profile_replace_link($nid, 'tapic_portal_twitter_block.html', $linkid, $langcode);
  $nid = $book_ids['pages']['carousel.html'];
  $linkid = $book_ids['pages']['tutorial_portal_customizing_css.html'];
  _apim_profile_replace_link($nid, 'tutorial_portal_customizing_css.html', $linkid, $langcode);
  $nid = $book_ids['pages']['file_size_limitations.html'];
  $linkid = $book_ids['pages']['disable_module.html'];
  _apim_profile_replace_link($nid, 'tapim_portal_disable_module.html', $linkid, $langcode);
  $nid = $book_ids['pages']['using_workbench_moderation.html'];
  $linkid = $book_ids['pages']['workbench_moderation.html'];
  _apim_profile_replace_link($nid, 'tapic_portal_workbench_moderation.html', $linkid, $langcode);
  $nid = $book_ids['pages']['frontpage_configure.html'];
  $linkid = $book_ids['pages']['custom_pages.html'];
  _apim_profile_replace_link($nid, 'tapim_portal_custom_pages.html', $linkid, $langcode);
  $nid = $book_ids['pages']['frontpage_configure.html'];
  $linkid = $book_ids['pages']['role_based_homepage.html'];
  _apim_profile_replace_link($nid, 'tapim_portal_role_based_homepage.html', $linkid, $langcode);
  $nid = $book_ids['pages']['menu_add.html'];
  $linkid = $book_ids['pages']['block_change.html'];
  _apim_profile_replace_link($nid, 'tapim_portal_block_change.html', $linkid, $langcode);
  $nid = $book_ids['pages']['custom_pages.html'];
  $linkid = $book_ids['pages']['role_based_homepage.html'];
  _apim_profile_replace_link($nid, 'tapim_portal_role_based_homepage.html', $linkid, $langcode);
  $nid = $book_ids['pages']['post_poll.html'];
  $linkid = $book_ids['pages']['edit_sample_content.html'];
  _apim_profile_replace_link($nid, 'tapim_portal_edit_sample_content.html', $linkid, $langcode);
  $nid = $book_ids['pages']['override_content_types.html'];
  $linkid = $book_ids['pages']['install_adiitional_themes.html'];
  _apim_profile_replace_link($nid, 'tapim_portal_install_adiitional_themes.html', $linkid, $langcode);
  $nid = $book_ids['pages']['social_login.html'];
  $linkid = $book_ids['pages']['pdur.html'];
  _apim_profile_replace_link($nid, 'capic_portal_pdur.html', $linkid, $langcode);
  $nid = $book_ids['pages']['social_login.html'];
  $linkid = $book_ids['pages']['disable_module.html'];
  _apim_profile_replace_link($nid, 'tapim_portal_disable_module.html', $linkid, $langcode);
  $nid = $book_ids['pages']['configure_ldap.html'];
  $linkid = $book_ids['pages']['pdur.html'];
  _apim_profile_replace_link($nid, 'capic_portal_pdur.html', $linkid, $langcode);
  $nid = $book_ids['pages']['account_approval.html'];
  $linkid = $book_ids['pages']['pdur.html'];
  _apim_profile_replace_link($nid, 'capic_portal_pdur.html', $linkid, $langcode);
  $nid = $book_ids['pages']['modify_emails.html'];
  $linkid = $book_ids['pages']['pdur.html'];
  _apim_profile_replace_link($nid, 'capic_portal_pdur.html', $linkid, $langcode);
  $nid = $book_ids['pages']['content_author_permissions.html'];
  $linkid = $book_ids['pages']['assigning_people_to_a_role.html'];
  _apim_profile_replace_link($nid, 'tapim_portal_assigning_people_to_a_role.html', $linkid, $langcode);
  $linkid = $book_ids['pages']['create_a_new_role.html'];
  _apim_profile_replace_link($nid, 'tapim_portal_create_a_new_role.html', $linkid, $langcode);
  $linkid = $book_ids['pages']['assigning_permissions_to_a_new_role.html'];
  _apim_profile_replace_link($nid, 'tapim_portal_assigning_permissions_to_a_new_role.html', $linkid, $langcode);
  $nid = $book_ids['pages']['user_reg_config.html'];
  $linkid = $book_ids['pages']['tsandcs_config.html'];
  _apim_profile_replace_link($nid, 'tapim_tsandcs_config.html', $linkid, $langcode);
  $linkid = $book_ids['pages']['captcha_config.html'];
  _apim_profile_replace_link($nid, 'tapim_captcha_config.html', $linkid, $langcode);
}

/**
 * @param $nid
 * @param $replace
 * @param $linkid
 * @param string $langcode
 * @throws \Exception
 */
function _apim_profile_replace_link($nid, $replace, $linkid, $langcode = 'en') {
  if (isset($nid)) {
    $node = node_load($nid);
    if (isset($node)) {
      $body = str_replace($replace, $linkid, $node->body[$langcode][0]['value']);
      $node->body[$langcode] = array(
        array(
          'value' => $body,
          'format' => 'filtered_html',
          'safe_value' => $body
        )
      );
      node_save($node);
    }
  }
}