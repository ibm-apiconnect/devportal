<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2015, 2016
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

$GLOBALS['url'] = d()->uri;
$GLOBALS['profile'] = d()->profile;
$GLOBALS['install_locale'] = d()->language;
$GLOBALS['base_url'] = provision_get_base_url();

function ibm_apim_drush_command() {

  $items['ibm_apim-updatecatalog'] = array(
    'description' => 'Updates the catalog',
    'aliases' => array('ucat'),
    'arguments' => array(
      'catalog' => 'The JSON catalog payload',
    )
  );
  $items['ibm_apim-updateconfig'] = array(
    'description' => 'Updates the config',
    'aliases' => array('ucon'),
    'arguments' => array(
      'config' => 'The JSON config payload',
    )
  );
  $items['ibm_apim-bgsync'] = array(
    'description' => 'Runs background sync',
    'aliases' => array('bgsync')
  );
  $items['ibm_apim-listen'] = array(
    'description' => 'Listens to stdin and runs the drush commands piped in',
    'aliases' => array('listen')
  );
  $items['ibm_apim-send_welcome_email'] = array(
    'description' => 'Sends a new welcome email',
    'aliases' => array('welcomeemail')
  );
  $items['ibm_apim-translate_sample_content'] = array(
    'description' => 'Translates the sample content',
    'aliases' => array('ibmapimi18n'),
    'arguments' => array(
      'langcode' => 'The language code'
    )
  );
  $items['ibm_apim-set_admin_timestamps']=array(
    'description' => 'On creation of a site from template, set admin timestamps to current time',
    'aliases' => array('admintime'),
    'hidden' => TRUE
  );

  return $items;
}

/**
 * @param $url
 * @param $account
 * @param $language
 * @param $client_email
 * @param $onetime
 */
function _drush_ibm_apim_send_welcome_mail($url, $account, $language, $client_email, $onetime) {
  global $base_url;
  if ($client_email) {
    // Mail one time login URL and instructions.
    $from = variable_get('site_mail', ini_get('sendmail_from'));
    $mail_params['variables'] = array(
      '!username' => $account->name,
      '!site' => variable_get('site_name', 'Drupal'),
      '!login_url' => $onetime,
      '!uri' => $base_url,
      '!uri_brief' => preg_replace('!^https?://!', '', $base_url),
      '!mailto' => $account->mail,
      '!date' => format_date(time()),
      '!login_uri' => url('user', array('absolute' => TRUE)),
      '!edit_uri' => url('user/' . $account->uid . '/edit', array('absolute' => TRUE))
    );

    $mail_success = drupal_mail('ibm_apim_drush', 'welcome-admin', $account->mail, user_preferred_language($account), $mail_params, $from, TRUE);

    if ($mail_success) {
      drush_log(t('Sent welcome mail to @client', array('@client' => $client_email)), 'success');
    }
    else {
      drush_log(t('Could not send welcome mail to @client', array('@client' => $client_email)), 'warning');
    }
  }
}

/**
 * @param $key
 * @param $message
 * @param $params
 */
function ibm_apim_drush_mail($key, &$message, $params) {
  require_once DRUPAL_ROOT . '/includes/install.inc';
  require_once '/var/aegir/.drush/provision/platform/provision_welcome_mail.inc';
  $message['subject'] = st($mail['subject'], $params['variables']);
  $message['body'][] = st($mail['body'], $params['variables']);
}

/**
 * @param $client_email
 * @throws \Exception
 */
function drush_ibm_apim_send_welcome_email($client_email) {
  global $url;
  global $install_locale;
  global $base_url;

  // get the admin account
  $account = user_load(1);

  $edit['mail'] = $client_email;

  // temporarily disable drupal's default mail notification
  $prev = variable_get('user_mail_status_activated_notify', TRUE);
  variable_set('user_mail_status_activated_notify', FALSE);
  $account = user_save($account, $edit);
  variable_set('user_mail_status_activated_notify', $prev);

  variable_set('site_mail', $client_email);

  //HACK HACK HACK. Why is the base_url set wrong when this is run. Don't know, but I
  //know that we always set ibm_apim_site_url to the proper base_url so just use this.
  $base_url = variable_get('ibm_apim_site_url', $base_url);

  $onetime = user_pass_reset_url($account);
  drush_log(t('Login url: !onetime', array('!onetime' => $onetime . '/login')), 'success');

  _drush_ibm_apim_send_welcome_mail($url, $account, $install_locale, $client_email, $onetime);
}

/**
 * Used by webhooks
 */
function drush_ibm_apim_listen() {
  global $user;
  // in case moderation is on we need cron to run as admin
  // save the current user so we can switch back at the end
  $original_user = $user;
  drupal_save_session(FALSE);
  $user = user_load(1);
  drupal_set_message(t('Drush ibm_apim_listen listening to stdin'), 'success');
  _drush_log_drupal_messages();

  $cmdline = trim(fgets(STDIN));
  $attempt = 0;
  $json = "";
  while ($cmdline) {
    $pieces = explode(" ", $cmdline);
    $cmd = $pieces[0];
    $event = $pieces[1];
    $id = $pieces[2];
    $subid = $pieces[3];
    drupal_set_message(t('Got command: %cmd', array('%cmd' => $cmd)), 'success');
    _drush_log_drupal_messages();

    try {
      if ($cmd == "dapi") {
        drush_api_deleteapi($event, $id);
      }
      elseif ($cmd == "dprod") {
        drush_product_deleteproduct($event, $id);
      }
      elseif ($cmd == "dapp") {
        drush_application_deleteapp($event, $id);
      }
      elseif ($cmd == "dsub") {
        drush_application_deletesub($event, $id, $subid);
      }
      elseif ($cmd == "dorg") {
        drush_devorg_deleteorg($event, $id);
      }
      elseif ($cmd == "bgsync") {
        drush_ibm_apim_bgsync();
      }
      else {
        //create or update
        if (!$json) {
          $json = trim(fgets(STDIN));
        }
        if ($cmd == "capi") {
          drush_api_createapi($event, $json);
        }
        elseif ($cmd == "uapi") {
          drush_api_updateapi($event, $json);
        }
        elseif ($cmd == "cprod") {
          drush_product_createproduct($event, $json);
        }
        elseif ($cmd == "uprod") {
          drush_product_updateproduct($event, $json);
        }
        elseif ($cmd == "capp") {
          drush_application_createapp($event, $json);
        }
        elseif ($cmd == "uapp") {
          drush_application_updateapp($event, $json);
        }
        elseif ($cmd == "csub") {
          drush_application_createsub($event, $json);
        }
        elseif ($cmd == "usub") {
          drush_application_updatesub($event, $json);
        }
        elseif ($cmd == "corg") {
          drush_devorg_createorg($event, $json);
        }
        elseif ($cmd == "uorg") {
          drush_devorg_updateorg($event, $json);
        }
        elseif ($cmd == "mapi") {
          drush_api_massupdate($json, $event);
        }
        elseif ($cmd == "mprod") {
          drush_product_massupdate($json, $event);
        }
        elseif ($cmd == "morg") {
          drush_devorg_massupdate($json, $event);
        }
        elseif ($cmd == "mapp") {
          drush_application_massupdate($json, $event);
        }
        elseif ($cmd == "msub") {
          drush_application_sub_massupdate($json, $event);
        }
        elseif ($cmd == "tprod") {
          drush_product_tidy($json);
        }
        elseif ($cmd == "tapi") {
          drush_api_tidy($json);
        }
        elseif ($cmd == "tapp") {
          drush_application_tidy($json);
        }
        elseif ($cmd == "torg") {
          drush_devorg_tidy($json);
        }
        elseif ($cmd == "tsub") {
          drush_application_sub_tidy($json);
        }
      }

      $attempt = 0;
    } catch (Exception $e) {
      drupal_set_message(t('Attempt %attempt. Caught exception: %message', array(
        '%attempt' => $attempt,
        '%message' => $e->getMessage()
      )), 'warning');
      $attempt++;

      if ($attempt > 2) {
        //Only try 3 times then give up.
        drupal_set_message(t('Giving up'), 'warning');
        $attempt = 0;
      }
    }

    if ($attempt == 0) {
      $cmdline = trim(fgets(STDIN));
      $json = "";
    }

    _drush_log_drupal_messages();
  }

  // switch back to original user
  $user = $original_user;
  drupal_save_session(TRUE);
  drupal_set_message(t('Drush ibm_apim_listen exiting'), 'success');
}

function drush_ibm_apim_bgsync() {
  global $user;
  // in case moderation is on we need cron to run as admin
  // save the current user so we can switch back at the end
  $original_user = $user;
  drupal_save_session(FALSE);
  $user = user_load(1);
  ibm_apim_cron();
  // switch back to original user
  $user = $original_user;
  drupal_save_session(TRUE);
  drupal_set_message(t('Background sync run.'), 'success');
}

function drush_ibm_apim_updatecatalog($catalog) {
  ibm_apim_update_catalog($catalog);
  drupal_set_message(t('Catalog updated.'), 'success');
}

function drush_ibm_apim_updateconfig($config) {
  ibm_apim_update_config($config);
  drupal_set_message(t('Config updated.'), 'success');
}

/**
 * Translate our sample content
 * @param $langcode
 */
function drush_ibm_apim_translate_sample_content($langcode) {
  $content = array(
    'title' => 'Getting started',
    'menu' => 'main-menu',
    'weight' => 1
  );
  _drush_ibm_apim_translate_node($content, $langcode);
  $content = array(
    'title' => 'Looking for help?',
    'menu' => 'main-menu',
    'weight' => 30
  );
  _drush_ibm_apim_translate_node($content, $langcode);
  $content = array(
    'title' => 'Terms of use',
    'menu' => NULL
  );
  _drush_ibm_apim_translate_node($content, $langcode);
  $content = array(
    'title' => 'Privacy policy',
    'menu' => NULL
  );
  _drush_ibm_apim_translate_node($content, $langcode);

  // faqs
  $content = array(
    'title' => 'How do I reset my application client secret?',
    'menu' => NULL
  );
  _drush_ibm_apim_translate_node($content, $langcode);
  $content = array(
    'title' => 'What are plans?',
    'menu' => NULL
  );
  _drush_ibm_apim_translate_node($content, $langcode);
  $content = array(
    'title' => 'How do I register an application?',
    'menu' => NULL
  );
  _drush_ibm_apim_translate_node($content, $langcode);
  $content = array(
    'title' => 'How do I see my API usage?',
    'menu' => NULL
  );
  _drush_ibm_apim_translate_node($content, $langcode);
  $content = array(
    'title' => 'How cat I test an API?',
    'menu' => NULL
  );
  _drush_ibm_apim_translate_node($content, $langcode);

  // load translated footer block
  _drush_ibm_apim_translate_block_strings('Developer portal footer', $langcode);
  // load translated featured apis title block
  _drush_ibm_apim_translate_block_strings('featured_apis_title', $langcode);

  _drush_ibm_apim_translate_menu_entries($langcode, 'main-menu', 'Blogs');
  _drush_ibm_apim_translate_menu_entries($langcode, 'main-menu', 'Forums');
  _drush_ibm_apim_translate_menu_entries($langcode, 'support-menu', 'Forum');
  _drush_ibm_apim_translate_menu_entries($langcode, 'support-menu', 'Frequently Asked Questions');
  _drush_ibm_apim_translate_menu_entries($langcode, 'support-menu', 'Raise a support ticket');
  _drush_ibm_apim_translate_menu_entries($langcode, 'support-menu', 'Contact Us');
  _drush_ibm_apim_translate_menu_entries($langcode, 'login-menu', 'Login');
  _drush_ibm_apim_translate_menu_entries($langcode, 'login-menu', 'Create an account');

  _drush_ibm_apim_translate_beans($langcode);

  _drush_ibm_apim_translate_book($langcode);

  drupal_set_message(t('Sample content updated for lang=@lang', array('@lang' => $langcode)), 'success');
}

/**
 * Create translation for the Getting started page
 * @param $langcode
 * @throws \Exception
 */
function _drush_ibm_apim_translate_node($content = array(), $langcode) {
  global $user;
  // in case moderation is on we need cron to run as admin
  // save the current user so we can switch back at the end
  $original_user = $user;
  drupal_save_session(FALSE);
  $user = user_load(1);
  $title = $content['title'];
  $menu = $content['menu'];

  $query = new EntityFieldQuery();
  $query->entityCondition('entity_type', 'node')
    ->propertyCondition('title', $title)
    ->execute();
  $results = $query->execute();
  if (isset($results['node'])) {
    $first = array_shift($results['node']);
    $nid = $first->nid;
    $node = node_load($nid);
  }

  if (isset($node)) {
    // Skip the default language.
    if ($langcode == 'en') {
      return;
    }
    // skip if there is already a translation in this lang
    if (!isset($node->translations->data[$langcode]) || empty($node->translations->data[$langcode])) {
      $tsid = variable_get('ibm_apim_translation_set_id', 0);
      $menu_translation_set = i18n_translation_set_load($tsid, 'menu_link');

      // Add translated title field (assumes title module is present).
      $translated_title = locale($node->title, $langcode);
      $node->title_field[$langcode][]['value'] = (isset($translated_title) && !empty($translated_title)) ? $translated_title : $node->title . " [$langcode]";
      // Add translated body field.
      $translated_body = locale($node->body['en'][0]['value'], $langcode);
      $body = (isset($translated_body) && !empty($translated_body)) ? $translated_body : $node->body['en'][0]['value'] . " [$langcode]";
      $translated_summary = locale($node->body['en'][0]['summary'], $langcode);
      $summary = (isset($translated_summary) && !empty($translated_summary)) ? $translated_summary : $node->body['en'][0]['summary'] . " [$langcode]";
      $node->body[$langcode] = array(
        array(
          'value' => $body,
          'format' => 'filtered_html',
          'summary' => $summary,
          'safe_summary' => $summary,
          'safe_value' => $body
        )
      );
      if (isset($node->field_detailed_question['en'][0]['value'])) {
        // Add translated question field.
        $translated_question = locale($node->field_detailed_question['en'][0]['value'], $langcode);
        $question = (isset($translated_question) && !empty($translated_question)) ? $translated_question : $node->field_detailed_question['en'][0]['value'] . " [$langcode]";
        $node->field_detailed_question[$langcode] = array(
          array(
            'value' => $question,
            'format' => 'filtered_html',
            'safe_value' => $question
          )
        );
      }
      // Add node translation.
      $node->translations->data[$langcode]['entity_type'] = 'node';
      $node->translations->data[$langcode]['entity_id'] = $node->nid;
      $node->translations->data[$langcode]['language'] = $langcode;
      $node->translations->data[$langcode]['source'] = $node->language;
      $node->translations->data[$langcode]['uid'] = $node->uid;
      $node->translations->data[$langcode]['status'] = $node->status;
      $node->translations->data[$langcode]['translate'] = 0;
      $node->translations->data[$langcode]['created'] = $node->created;
      $node->translations->data[$langcode]['changed'] = $node->changed;
      // Save node translation.
      node_save($node);
      if (isset($menu)) {
        // Add translation for menu item
        $mlid = db_query("SELECT mlid FROM {menu_links} WHERE link_title=:link_title AND menu_name=:menu_name", array(
          ':link_title' => $node->title,
          ':menu_name' => $menu
        ))->fetchField();
        $link = menu_link_load($mlid);
        $description = $link['description'];

        // Add translated description field.
        if (isset($description) && !empty($description)) {
          $translated_description = locale($description, $langcode);
        }
        $translated_title = locale($title, $langcode);
        i18n_string_translation_update('menu:item:' . $mlid . ':title', $translated_title, $langcode, $title);
        if (isset($translated_description) && !empty($translated_description)) {
          i18n_string_translation_update('menu:item:' . $mlid . ':description', $translated_description, $langcode, $description);
        }
      }
    }
  }
  // switch back to original user
  $user = $original_user;
  drupal_save_session(TRUE);
}

/**
 * @param $info
 * @param $langcode
 */
function _drush_ibm_apim_translate_block_strings($info, $langcode) {
  $blockid = db_query("SELECT bid FROM {block_custom} WHERE info=:info", array(
    ':info' => $info
  ))->fetchField();
  if (isset($blockid)) {
    $block = block_block_view($blockid);
    if (isset($block['content'])) {
      i18n_string_translation_update(
        array('blocks', 'block', $blockid, 'body'),
        locale($block['content'], $langcode),
        $langcode,
        $block['content']
      );
      // need to set the format to html
      list($textgroup, $context) = i18n_string_context('blocks:block:' . $blockid . ':body');
      i18n_string_textgroup($textgroup)->context_update($context, $block['content'], array('format' => 'FILTER_XSS_ADMIN'));
    }
  }
}

/**
 * Add translations for a specific menu item
 * @param $langcode
 * @param $menu
 * @param $title
 */
function _drush_ibm_apim_translate_menu_entries($langcode, $menu, $title) {
  $mlid = db_query("SELECT mlid FROM {menu_links} WHERE link_title=:link_title AND menu_name=:menu_name", array(
    ':link_title' => $title,
    ':menu_name' => $menu
  ))->fetchField();
  $link = menu_link_load($mlid);
  $description = $link['description'];
  if (isset($description) && !empty($description)) {
    $translated_description = locale($description, $langcode);
  }
  $translated_title = locale($title, $langcode);
  i18n_string_translation_update('menu:item:' . $mlid . ':title', $translated_title, $langcode, $title);
  if (isset($translated_description) && !empty($translated_description)) {
    i18n_string_translation_update('menu:item:' . $mlid . ':description', $translated_description, $langcode, $description);
  }
}

/**
 * Translate bean based blocks
 * @param $langcode
 */
function _drush_ibm_apim_translate_beans($langcode) {
  $beans = bean_get_all_beans();
  if (isset($beans)) {
    foreach ($beans as $bean) {
      if ((isset($bean->delta) && ($bean->delta == 'socialblock')) || (isset($bean->type) && $bean->type == 'socialblock_block')) {
        if (isset($bean->socialblocktitle['en'][0]['value'])) {
          $translation = array(
            'translate' => 0,
            'status' => 1,
            'language' => $langcode,
            'source' => 'en',
          );
          $translated = locale($bean->socialblocktitle['en'][0]['value'], $langcode);
          $handler = entity_translation_get_handler('bean', $bean);
          $values = array(
            'socialblocktitle' => array(
              $langcode => array(
                array(
                  'value' => $translated,
                  'format' => 'plain_text'
                )
              )
            )
          );
          $bean->socialblocktitle = array(
            $langcode => array(
              array(
                'value' => $translated,
                'format' => 'plain_text'
              )
            )
          );
          $handler->setTranslation($translation, $values);
          bean_save($bean);
        }
      }
      elseif ((isset($bean->delta) && ($bean->delta == 'banner_block')) || (isset($bean->type) && $bean->type == 'banner_block')) {
        if (isset($bean->bannercontent['en'][0]['value'])) {
          $content = $bean->bannercontent['en'][0]['value'];
        }
        else if (isset($bean->bannercontent['und'][0]['value'])) {
          $content = $bean->bannercontent['und'][0]['value'];
        }
        if (isset($content)) {
          $translation = array(
            'translate' => 0,
            'status' => 1,
            'language' => $langcode,
            'source' => 'en',
          );
          $translated = locale($content, $langcode);
          $handler = entity_translation_get_handler('bean', $bean);
          $values = array(
            'bannercontent' => array(
              $langcode => array(
                array(
                  'value' => $translated,
                  'format' => 'filtered_html'
                )
              )
            )
          );
          $bean->bannercontent = array(
            $langcode => array(
              array(
                'value' => $translated,
                'format' => 'filtered_html'
              )
            )
          );
          // ensure en is set if it isn't already
          if (!isset($bean->bannercontent['en'][0]['value'])) {
            $bean->bannercontent = array(
              'en' => array(
                array(
                  'value' => $bean->bannercontent['und'][0]['value'],
                  'format' => 'filtered_html'
                )
              )
            );
          }
          $handler->setTranslation($translation, $values);
          bean_save($bean);
        }
      }
    }
  }
}

/**
 * Translate admin guide book pages
 * @param $langcode
 * @throws \Exception
 */
function _drush_ibm_apim_translate_book($langcode) {
  $book_ids = variable_get('ibm_apim_book_ids', array());
  require_once DRUPAL_ROOT . '/profiles/apim_profile/content/book.content.inc';
  $parent = node_load($book_ids['bid']);
  if (isset($parent)) {
    // Skip the default language.
    if ($langcode == 'en') {
      return;
    }
    // skip if there is already a translation in this lang
    if (!isset($parent->translations->data[$langcode]) || empty($parent->translations->data[$langcode])) {
      // Add translated title field (assumes title module is present).
      $translated_title = locale($parent->title, $langcode);
      $parent->title_field[$langcode][]['value'] = (isset($translated_title) && !empty($translated_title)) ? $translated_title : $parent->title . " [$langcode]";
      // Add translated body field.
      $translated_body = locale($parent->body['en'][0]['value'], $langcode);
      $body = (isset($translated_body) && !empty($translated_body)) ? $translated_body : $parent->body['en'][0]['value'] . " [$langcode]";
      $body = str_replace(array("\r", "\n"), ' ', $body);
      $parent->body[$langcode] = array(
        array(
          'value' => $body,
          'format' => 'filtered_html',
          'safe_value' => $body
        )
      );
      // Add node translation.
      $parent->translations->data[$langcode]['entity_type'] = 'node';
      $parent->translations->data[$langcode]['entity_id'] = $parent->nid;
      $parent->translations->data[$langcode]['language'] = $langcode;
      $parent->translations->data[$langcode]['source'] = $parent->language;
      $parent->translations->data[$langcode]['uid'] = $parent->uid;
      $parent->translations->data[$langcode]['status'] = $parent->status;
      $parent->translations->data[$langcode]['translate'] = 0;
      $parent->translations->data[$langcode]['created'] = $parent->created;
      $parent->translations->data[$langcode]['changed'] = $parent->changed;
      // Save node translation.
      node_save($parent);
    }
  }
  $body_content = apim_profile_book_content();
  foreach ($body_content as $key => $content) {
    if (isset($book_ids['pages'][$key])) {
      $node = node_load($book_ids['pages'][$key]);
      if (isset($node)) {
        // Skip the default language.
        if ($langcode == 'en') {
          return;
        }
        // skip if there is already a translation in this lang
        if (!isset($node->translations->data[$langcode]) || empty($node->translations->data[$langcode])) {

          // Add translated title field (assumes title module is present).
          $translated_title = locale($node->title, $langcode);
          $node->title_field[$langcode][]['value'] = (isset($translated_title) && !empty($translated_title)) ? $translated_title : $node->title . " [$langcode]";
          // Add translated body field.
          $translated_body = locale($content, $langcode);
          $body = (isset($translated_body) && !empty($translated_body)) ? $translated_body : $node->body['en'][0]['value'] . " [$langcode]";
          $body = str_replace(array("\r", "\n"), ' ', $body);
          $node->body[$langcode] = array(
            array(
              'value' => $body,
              'format' => 'filtered_html',
              'safe_value' => $body
            )
          );
          // Add node translation.
          $node->translations->data[$langcode]['entity_type'] = 'node';
          $node->translations->data[$langcode]['entity_id'] = $node->nid;
          $node->translations->data[$langcode]['language'] = $langcode;
          $node->translations->data[$langcode]['source'] = $node->language;
          $node->translations->data[$langcode]['uid'] = $node->uid;
          $node->translations->data[$langcode]['status'] = $node->status;
          $node->translations->data[$langcode]['translate'] = 0;
          $node->translations->data[$langcode]['created'] = $node->created;
          $node->translations->data[$langcode]['changed'] = $node->changed;
          // Save node translation.
          node_save($node);
        }
      }
    }
  }
  require_once DRUPAL_ROOT . '/profiles/apim_profile/content/book.import.inc';
  apim_profile_update_page_links($langcode);
}

function drush_ibm_apim_set_admin_timestamps(){
  $time = \Drupal::time()->getCurrentTime();
  db_update('users_field_data')->fields(array('created'=>$time,
    'login'=>$time))
    ->condition('uid',1)
    ->execute();
}
