<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

use Drupal\apic_api\Api;
use Drupal\apic_app\Application;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\key\Entity\Key;
use Drupal\node\Entity\Node;
use Drupal\product\Product;
use Drupal\Core\Session\UserSession;
use Drupal\search_api\Entity\Index;

function ibm_apim_drush_command() {

  $items['ibm_apim-updateconfig'] = array(
    'description' => 'Updates the site config',
    'aliases' => array('ucon'),
    'arguments' => array(
      'config' => 'The JSON config payload',
    )
  );
  $items['ibm_apim-setcreds'] = array(
    'description' => 'Updates the catalog credentials from APIM',
    'aliases' => array('setcreds'),
    'arguments' => array(
      'clientid' => 'The catalog client ID',
      'clientsecret' => 'The catalog client secret',
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
  $items['ibm_apim-clearup'] = array(
    'description' => 'Tidies up the local database at the end of a snapshot to remove any superfluous content',
    'aliases' => array('clearup')
  );
  $items['ibm_apim-content_refresh'] = array(
    'description' => 'Listens to stdin and updates the db for each item piped in',
    'aliases' => array('contrefresh')
  );
  $items['ibm_apim-request_refresh'] = array(
    'description' => 'Invokes ApicRest.php to request a content refresh',
    'aliases' => array('reqrefresh')
  );

  $items['ibm_apim-send_welcome_email'] = array(
    'description' => 'Sends a new welcome email',
    'aliases' => array('welcomeemail')
  );
  $items['ibm_apim-checkapimcert'] = array(
    'description' => 'Check APIM management certificate',
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_ROOT,
    'aliases' => array('checkcert'),
    'arguments' => array(
      'url' => 'APIM management url',
      'insecure' => 'Insecure certificate',
      'provided_cert' => 'Provided certificate',
    )
  );
  $items['ibm_apim-set_admin_timestamps'] = array(
    'description' => 'On creation of a site from template, set admin timestamps to current time',
    'aliases' => array('admintime'),
    'hidden' => TRUE
  );
  $items['ibm_apim-generate_nlsexport'] = array(
    'description' => 'Generate .pot/.po files to be sent for translation.',
    'aliases' => array('nlsexport'),
    'options' => array(
      'required_pot_dir' => 'Directory containing complete set of .pot files for which translations are required.',
      'existing_drupal_po_dir' => 'Directory containing downloaded existing drupal .po files.',
      'output_dir' => 'Directory to place the output.',
      'platform_dir' => 'Platform directory.',
      'merge_dir' => 'Directory to merge existing translations in.'
    )
  );
  $items['ibm_apim-merge_nlsdrop'] = array(
    'description' => 'Merge new translation files with memories to give new complete set of translation (.po) files.',
    'aliases' => array('merge-nlsdrop'),
    'arguments' => array(
      'drop_dir' => 'Directory containing newly translated files received from the translation centre.',
      'original_export_dir' => 'Directory containing the original files that were sent to the translation centre (.pot and -memories.\<lang\>.po)'
    ),
    'options' => array(
      'output_dir' => 'Directory to place the output.'
    )
  );
  $items['ibm_apim-merge_nlsindividual'] = array(
    'description' => 'Merge a pair of individual .po translation files .',
    'aliases' => array('merge-nlsindividual'),
    'arguments' => array(
      'master_file' => 'File contain the master record of translations.',
      'secondary_file' => 'File containing the translations that need to be merged in, note the master file will take precedence.'
    ),
    'options' => array(
      'output_dir' => 'Where to place the merged .po file.'
    )
  );
  $items['ibm_apim-locale_clear_status'] = array(
    'description' => 'Clear the status of known translation files, used to ensure a full load of files is performed.',
    'aliases' => array('locale-clear-status'),
  );
  $items['ibm_apim-locale_load_config_translation'] = array(
    'description' => 'Load configuration translations for all languages.',
    'aliases' => array('locale-load-config'),
  );
  $items['ibm_apim-createkey'] = array(
    'description' => 'Create encryption key',
    'aliases' => array('createkey'),
    'arguments' => array(
      'clientid' => 'Site Client ID'
    )
  );

  $items['ibm_apim-user_create'] = array(
    'description' => 'Creates an IBM APIC user',
    'aliases' => array('cibmuser'),
    'arguments' => array(
      'content' => 'The webhook JSON content',
      'event' => 'The event type'
    )
  );
  $items['ibm_apim-user_update'] = array(
    'description' => 'Updates an IBM APIC user',
    'aliases' => array('uibmuser'),
    'arguments' => array(
      'content' => 'The webhook JSON content',
      'event' => 'The event type'
    )
  );
  $items['ibm_apim-user_delete'] = array(
    'description' => 'Deletes an IBM APIC user',
    'aliases' => array('dibmuser'),
    'arguments' => array(
      'content' => 'The webhook JSON content',
      'event' => 'The event type'
    )
  );
  $items['ibm_apim-user_block'] = array(
    'description' => 'Blocks an IBM APIC user',
    'aliases' => array('crtibmuser'),
    'arguments' => array(
      'content' => 'The webhook JSON content',
      'event' => 'The event type'
    )
  );
  $items['ibm_apim-user_unblock'] = array(
    'description' => 'Unblocks an IBM APIC user',
    'aliases' => array('ubibmuser'),
    'arguments' => array(
      'content' => 'The webhook JSON content',
      'event' => 'The event type'
    )
  );
  $items['ibm_apim-user_registry_create'] = array(
    'description' => 'Creates an IBM APIC user registry',
    'aliases' => array('cibmuserreg'),
    'arguments' => array(
      'content' => 'The webhook JSON content',
      'event' => 'The event type'
    )
  );
  $items['ibm_apim-user_registry_update'] = array(
    'description' => 'Updates an IBM APIC user registry',
    'aliases' => array('uibmuserreg'),
    'arguments' => array(
      'content' => 'The webhook JSON content',
      'event' => 'The event type'
    )
  );
  $items['ibm_apim-user_registry_delete'] = array(
    'description' => 'Deletes an IBM APIC user registry',
    'aliases' => array('dibmuserreg'),
    'arguments' => array(
      'content' => 'The webhook JSON content',
      'event' => 'The event type'
    )
  );
  $items['ibm_apim-vendor_extension_create'] = array(
    'description' => 'Creates an IBM APIC vendor extension',
    'aliases' => array('cibmvendext'),
    'arguments' => array(
      'content' => 'The webhook JSON content',
      'event' => 'The event type'
    )
  );
  $items['ibm_apim-vendor_extension_update'] = array(
    'description' => 'Updates an IBM APIC vendor extension',
    'aliases' => array('uibmvendext'),
    'arguments' => array(
      'content' => 'The webhook JSON content',
      'event' => 'The event type'
    )
  );
  $items['ibm_apim-vendor_extension_delete'] = array(
    'description' => 'Deletes an IBM APIC vendor extension',
    'aliases' => array('dibmvendext'),
    'arguments' => array(
      'content' => 'The webhook JSON content',
      'event' => 'The event type'
    )
  );
  $items['ibm_apim-group_create'] = array(
    'description' => 'Creates an IBM APIC group',
    'aliases' => array('cibmgroup'),
    'arguments' => array(
      'content' => 'The webhook JSON content',
      'event' => 'The event type'
    )
  );
  $items['ibm_apim-group_update'] = array(
    'description' => 'Updates an IBM APIC group',
    'aliases' => array('uibmgroup'),
    'arguments' => array(
      'content' => 'The webhook JSON content',
      'event' => 'The event type'
    )
  );
  $items['ibm_apim-group_delete'] = array(
    'description' => 'Deletes an IBM APIC group',
    'aliases' => array('dibmgroup'),
    'arguments' => array(
      'content' => 'The webhook JSON content',
      'event' => 'The event type'
    )
  );
  $items['ibm_apim-billing_create'] = array(
    'description' => 'Creates an IBM APIC billing object',
    'aliases' => array('cibmbilling'),
    'arguments' => array(
      'content' => 'The webhook JSON content',
      'event' => 'The event type'
    )
  );
  $items['ibm_apim-billing_update'] = array(
    'description' => 'Updates an IBM APIC billing object',
    'aliases' => array('uibmbilling'),
    'arguments' => array(
      'content' => 'The webhook JSON content',
      'event' => 'The event type'
    )
  );
  $items['ibm_apim-billing_delete'] = array(
    'description' => 'Deletes an IBM APIC billing object',
    'aliases' => array('dibmbilling'),
    'arguments' => array(
      'content' => 'The webhook JSON content',
      'event' => 'The event type'
    )
  );
  $items['ibm_apim-content_check'] = array(
    'description' => 'Check if a specific artifact is in the database',
    'aliases' => array('ibmcontentcheck'),
    'arguments' => array(
      'type' => 'The artifact type, e.g. product',
      'url' => 'The artifact URL reference, e.g. /product/xyz/1'
    )
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
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  if ($client_email) {
    $config = \Drupal::config('system.site');
    $mailManager = \Drupal::service('plugin.manager.mail');
    $ibm_apim_site_url = \Drupal::state()->get('ibm_apim.site_url');
    // Mail one time login URL and instructions.
    $from = $config->get('mail');
    $site_name = $config->get('name');

    if (!isset($from) || empty($from)) {
      $from = ini_get('sendmail_from');
      drush_log(dt('Setting FROM address using PHP config file: @email', array('@email' => $from)), 'success');
    } else {
      drush_log(dt('Setting FROM address using site config: @email', array('@email' => $from)), 'success');
    }

    $login_url = Url::fromRoute('user.login', array('absolute' => TRUE))->toString();
    $edit_url = Url::fromRoute('user.page', array('absolute' => TRUE))->toString();

    if (!isset($ibm_apim_site_url) || empty($ibm_apim_site_url)) {
      $ibm_apim_site_url = 'null';
    }
    if (!isset($site_name) || empty($site_name)) {
      $site_name = 'null';
    }
    if (!isset($from) || empty($from)) {
      $from = 'null@example.com';
    }

    $mail_params['variables'] = array(
      '!username' => $account->getAccountName(),
      '!site' => $site_name,
      '!login_url' => $onetime,
      '!uri' => $ibm_apim_site_url,
      '!uri_brief' => preg_replace('!^https?://!', '', $ibm_apim_site_url),
      '!mailto' => $account->getEmail(),
      '!date' => \Drupal::service('date.formatter')->format(time()),
      '!login_uri' => $login_url,
      '!edit_uri' => $edit_url
    );

    $mail_success = $mailManager->mail('ibm_apim_drush', 'welcome-admin', $account->getEmail(), $account->getPreferredLangcode(), $mail_params, $from, TRUE);

    if ($mail_success) {
      drush_log(dt('Sent welcome mail to @client', array('@client' => $client_email)), 'success');
    }
    else {
      drush_log(dt('Could not send welcome mail to @client', array('@client' => $client_email)), 'warning');
    }
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $key
 * @param $message
 * @param $params
 */
function ibm_apim_drush_mail($key, &$message, $params) {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  require_once '/var/aegir/.drush/provision/platform/provision_welcome_mail.inc';
  $message['subject'] = strtr($mail['subject'], $params['variables']);
  $message['body'][0] = Markup::create(strtr($mail['body'], $params['variables']));
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $client_email
 * @throws \Exception
 */
function drush_ibm_apim_send_welcome_email($client_email) {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  global $url;
  global $install_locale;
  global $base_url;

  // get the admin account
  $account = \Drupal\user\Entity\User::load(1);

  // temporarily disable drupal's default mail notification
  $user_settings_config = \Drupal::config('user.settings');
  $prev = $user_settings_config->get('notify.status_activated');
  \Drupal::service('config.factory')->getEditable('user.settings')->set('notify.status_activated', FALSE)->save();

  $account->set('mail', $client_email);
  $account->set('init', $client_email);
  $account->save();

  \Drupal::service('config.factory')->getEditable('user.settings')->set('notify.status_activated', $prev)->save();
  \Drupal::service('config.factory')->getEditable('system.site')->set('mail', $client_email)->save();
  \Drupal::service('config.factory')
    ->getEditable('update.settings')
    ->set('notification.emails', array($client_email))
    ->save();

  //HACK HACK HACK. Why is the base_url set wrong when this is run. Don't know, but I
  //know that we always set ibm_apim_site_url to the proper base_url so just use this.
  $base_url = \Drupal::state()->get('ibm_apim.site_url');

  $onetime = user_pass_reset_url($account);
  drush_log(dt('Login url: @onetime', array('@onetime' => $onetime)), 'success');

  _drush_ibm_apim_send_welcome_mail($url, $account, $install_locale, $client_email, $onetime);
  ibm_apim_exit_trace(__FUNCTION__, NULL);

  return $onetime;
}

/**
 * Used by webhooks
 */
function drush_ibm_apim_listen() {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  drush_log(dt('Drush ibm_apim_listen listening to stdin'), 'success');


  // Incoming payload is always of the format : <event> {content}
  // where <event> is an argument we were called with and {content} a json object
  // read from stdin

  $command = trim(fgets(STDIN));
  $attempt = 0;

  while ($command) {

    drush_log(dt('Got command: @cmd', array ('@cmd' => $command)), 'success');

    // only read from stdin on the first attempt. next time around we already have the content!
    if ($attempt == 0) {
      $content = trim(fgets(STDIN));
      $content = json_decode($content, TRUE);
    }

    try {
      $webhook_debug = \Drupal::config('ibm_apim.settings')->get('webhook_debug');
      if ($webhook_debug == TRUE) {
        $webhook_payloads = \Drupal::state()->get('ibm_apim.webhook_payloads');
        $webhook_payloads[] = array('content' => $content, 'type' => $command, 'timestamp' => time());
        \Drupal::state()->set('ibm_apim.webhook_payloads', $webhook_payloads);
      }
      if (isset($content)) {
        switch ($command) {
          case 'product_lifecycle':
            drush_apic_api_massupdate($content['consumer_apis']);
            drush_product_create($content);
            break;
          case 'product_update':
            drush_apic_api_massupdate($content['consumer_apis']);
            drush_product_update($content);
            break;
          case 'product_supersede':
            drush_product_supersede($content);
            break;
          case 'product_replace':
            drush_product_replace($content);
            break;
          case 'product_del':
            drush_product_delete($content);
            break;
          case 'api_del':
            drush_apic_api_delete($content);
            break;
          case 'api_update':
            drush_apic_api_update($content);
            break;
          case 'app_create':
            drush_apic_app_create($content);
            break;
          case 'app_update':
            drush_apic_app_update($content);
            break;
          case 'app_del':
            drush_apic_app_delete($content);
            break;
          case 'consumer_org_create':
            drush_consumerorg_create($content);
            break;
          case 'consumer_org_update':
            drush_consumerorg_update($content);
            break;
          case 'consumer_org_del':
            drush_consumerorg_delete($content);
            break;
          case 'member_invitation_create':
            drush_consumerorg_invitation_create($content);
            break;
          case 'member_invitation_update':
            drush_consumerorg_invitation_update($content);
            break;
          case 'member_invitation_del':
            drush_consumerorg_invitation_delete($content);
            break;
          case 'role_create':
            drush_consumerorg_role_create($content);
            break;
          case 'role_update':
            drush_consumerorg_role_update($content);
            break;
          case 'role_del':
            drush_consumerorg_role_delete($content);
            break;
          case 'subscription_create':
            drush_apic_app_createsub($content);
            break;
          case 'subscription_update':
            drush_apic_app_updatesub($content);
            break;
          case 'subscription_del':
            drush_apic_app_deletesub($content);
            break;
          case 'credential_create':
            drush_apic_app_createcred($content);
            break;
          case 'credential_update':
            drush_apic_app_updatecred($content);
            break;
          case 'credential_del':
            drush_apic_app_deletecred($content);
            break;
          case 'member_create':
            drush_consumerorg_member_create($content);
            break;
          case 'member_del':
            drush_consumerorg_member_delete($content);
            break;
          case 'member_block':
            drush_ibm_apim_user_block($content);
            break;
          case 'member_unblock':
            drush_ibm_apim_user_unblock($content);
            break;
          case 'member_update':
            drush_ibm_apim_user_update($content);
            break;
          case 'user_update':
            drush_ibm_apim_user_createOrUpdate($content, 'user_update');
            break;
          case 'user_create':
            drush_ibm_apim_user_createOrUpdate($content, 'user_create');
            break;
          case 'user_del':
            drush_ibm_apim_user_delete($content);
            break;
          case 'configured_catalog_user_registry_update':
            drush_ibm_apim_user_registry_update($content);
            break;
          case 'configured_catalog_user_registry_del':
            drush_ibm_apim_user_registry_delete($content);
            break;
          case 'configured_catalog_user_registry_create':
            drush_ibm_apim_user_registry_create($content);
            break;
          case 'vendor_extension_update':
            drush_ibm_apim_vendor_extension_update($content);
            break;
          case 'vendor_extension_del':
            drush_ibm_apim_vendor_extension_delete($content);
            break;
          case 'vendor_extension_create':
            drush_ibm_apim_vendor_extension_create($content);
            break;
          case 'group_update':
            drush_ibm_apim_group_update($content);
            break;
          case 'group_del':
            drush_ibm_apim_group_delete($content);
            break;
          case 'group_create':
            drush_ibm_apim_group_create($content);
            break;
          case 'catalog_setting_singletonUpdate':
            drush_ibm_apim_updateconfig($content);
            break;
          default:
            drush_log(dt('There is no drush code to handle the webhook command @cmd', array('@cmd' => $command)), 'error');
            break;
        }
      }
      else {
        drush_log(dt('No content provided for webhook command @cmd', array('@cmd' => $command)), 'error');
      }
      $attempt = 0;
    } catch (Exception $e) {
      drush_log(dt('Attempt @attempt. Caught exception: @message', array(
        '@attempt' => $attempt,
        '@message' => $e->getMessage()
      )), 'warning');
      $attempt++;

      if ($attempt > 2) {
        //Only try 3 times then give up.
        drush_log(dt('Giving up'), 'error');
        $attempt = 0;
      }
    }

    // If we were successful, get the next command
    if ($attempt == 0) {
      $command = trim(fgets(STDIN));
      $content = "";
    }


  }

  drush_log(dt('Drush ibm_apim_listen exiting'), 'success');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * Used to request a refresh from APIM
 */
function drush_ibm_apim_request_refresh() {

  // code stolen from drush_ibm_apim_checkapimcert() {

  if (function_exists('ibm_apim_entry_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }
  // HACK: need to include the classes explicitly otherwise this does not work in bootstrap level DRUSH_BOOTSTRAP_DRUPAL_ROOT
  require_once __DIR__ . "/src/ApicRestInterface.php";
  require_once __DIR__ . "/src/ApicRest.php";

  try {
    $namespace = \Drupal::state()->get('ibm_apim.site_namespace');
    $siteconfig = \Drupal::service('ibm_apim.site_config');
    $host_pieces = $siteconfig->parseApimHost();
    // need the apim hostname without any path component on the ingress (such as consumer-api)
    $url = $host_pieces['scheme'] . '://' . $host_pieces['host'] . ':' . $host_pieces['port'] . '/catalogs/' . $namespace . '/webhooks/snapshot';
    Drupal\ibm_apim\ApicRest::patch($url, NULL, 'clientid', TRUE, TRUE);
    drush_print('[[0]] Content Refresh Requested');

  } catch (Exception $e) {
    // When we have a system to test against, we need to work out what the relevant errors actually are here
    echo 'Caught exception: ', $e->getMessage(), "\n";

    $failures = array(
      7 => 'Could not communicate with server. Reason: SSL certificate problem: unable to get local issuer certificate',
      6 => 'Could not communicate with server. Reason: SSL certificate problem: self signed certificate',
      5 => 'SSL: certificate verification failed (result: 5)',
      4 => 'Could not communicate with server. Reason: Could not resolve host:',
      2 => 'Could not communicate with server. Reason: SSL: no alternative certificate subject name matches target host name',
      3 => 'Could not communicate with server.'
    );

    $message = "";

    foreach ($failures as $rc => $msg) {
      if (strpos($e->getMessage(), $failures[$rc]) !== FALSE) {
        $message = "[[" . $rc . "]] " . $failures[$rc] . ' error';
        break;
      }
    }

    // failed with unknown error
    if ($message === "") {
      $message = '[[1]] Error requesting Content Refresh: ' . $e->getMessage() . 'error';
    }

    // output message
    drush_print($message);
  }
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * Used for the streamed objects from content refresh snapshot payload
 */
function drush_ibm_apim_content_refresh() {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  drush_log(dt('Drush ibm_apim_content_refresh listening to stdin'), 'success');


  // Incoming payload is always of the format : { 'type': foo, ... }
  // where type is the type of incoming object

  $input = trim(fgets(STDIN));

  while ($input) {
    $content = json_decode($input, TRUE);
    // defensive error handling
    if (!isset($content)) {
      $content = serialize($input);
      $type = 'error';
    }
    else {
      $type = $content['type'];
    }
    if (!isset($type)) {
      $type = 'error';
    }
    $webhook_debug = \Drupal::config('ibm_apim.settings')->get('webhook_debug');
    if ($webhook_debug == TRUE) {
      $webhook_payloads = \Drupal::state()->get('ibm_apim.snapshot_webhook_payloads');
      $webhook_payloads[] = array('content' => $content, 'type' => $type, 'timestamp' => time());
      \Drupal::state()->set('ibm_apim.snapshot_webhook_payloads', $webhook_payloads);
    }

    drush_log(t('Got type: @type', array('@type' => $type)), 'success');

    // checking memory
    drush_ibm_apim_manage_memory_usage();

    // assert that the index_directly value of the search_api module's default_index is
    // set correctly according to the number of nodes in the site
    drush_ibm_apim_assert_index_directly_value();

    try {
      if (isset($type) && $type != 'error' && isset($content)) {
        switch ($type) {
          case 'product':
            $prodList = \Drupal::state()->get('ibm_apim.content_refresh_products');
            if (!isset($prodList)) {
              $prodList = array();
            }
            $prodList[] = $content['name'] . ':' . $content['version'];
            // have to massage the content so we can reuse the same logic for snapshot as we use for webhooks
            drush_product_createOrUpdate(array('product' => $content), 'ContentRefresh', 'content_refresh');
            \Drupal::state()->set('ibm_apim.content_refresh_products', $prodList);
            break;
          case 'api':
            $apiList = \Drupal::state()->get('ibm_apim.content_refresh_apis');
            if (!isset($apiList)) {
              $apiList = array();
            }
            $apiList[] = $content['name'] . ':' . $content['version'];
            drush_apic_api_createOrUpdate($content, 'ContentRefresh', 'content_refresh');
            \Drupal::state()->set('ibm_apim.content_refresh_apis', $apiList);
            break;
          case 'app':
            $appList = \Drupal::state()->get('ibm_apim.content_refresh_apps');
            if (!isset($appList)) {
              $appList = array();
            }
            $appList[] = $content['id'];
            drush_apic_app_createOrUpdate($content, 'ContentRefresh', 'content_refresh');
            \Drupal::state()->set('ibm_apim.content_refresh_apps', $appList);
            break;
          case 'consumer_org':
            $orgList = \Drupal::state()->get('ibm_apim.content_refresh_consumerorgs');
            if (!isset($orgList)) {
              $orgList = array();
            }
            $orgList[] = $content['url'];
            drush_consumerorg_createOrUpdate($content, 'ContentRefresh', 'content_refresh');
            \Drupal::state()->set('ibm_apim.content_refresh_consumerorgs', $orgList);
            break;
          case 'user':
            drush_ibm_apim_user_createOrUpdate($content, 'ContentRefresh');
            break;
          case 'catalog_setting':
            drush_ibm_apim_updateconfig($content);
            // set message that we have correctly received a config payload to disable intro warning message
            \Drupal::state()->set('ibm_apim.content_refresh_status', 1);
            break;
          case 'catalog':
            drush_ibm_apim_updatecatalog($content);
            break;
          case 'configured_catalog_user_registry':
            $urList = \Drupal::state()->get('ibm_apim.content_refresh_user_registries');
            if (!isset($urList)) {
              $urList = array();
            }
            $urList[] = $content['url'];
            drush_ibm_apim_user_registry_update($content, 'content_refresh');
            \Drupal::state()->set('ibm_apim.content_refresh_user_registries', $urList);
            break;
          case 'extension':
            $extList = \Drupal::state()->get('ibm_apim.content_refresh_extensions');
            if (!isset($extList)) {
              $extList = array();
            }
            $extList[] = $content['name'];
            drush_ibm_apim_vendor_extension_update($content, 'content_refresh');
            \Drupal::state()->set('ibm_apim.content_refresh_extensions', $extList);
            break;
          case 'group':
            $groupList = \Drupal::state()->get('ibm_apim.content_refresh_groups');
            if (!isset($groupList)) {
              $groupList = array();
            }
            $groupList[] = $content['url'];
            drush_ibm_apim_group_update($content, 'content_refresh');
            \Drupal::state()->set('ibm_apim.content_refresh_extensions', $groupList);
            break;
          case 'subscription':
            $subList = \Drupal::state()->get('ibm_apim.content_refresh_subs');
            if (!is_array($subList)) {
              $subList = array();
            }
            if (isset($content['app_url'])) {
              if (!isset($subList[$content['app_url']]) || !is_array($subList[$content['app_url']])) {
                $subList[$content['app_url']] = array();
              }
              if (!isset($content['state'])) {
                $sub['state'] = "enabled";
              }
              $subList[$content['app_url']][] = array(
                'id' => $content['id'],
                'product_url' => $content['product_url'],
                'plan' => $content['plan'],
                'state' => $content['state']
              );
            }
            \Drupal::state()->set('ibm_apim.content_refresh_subs', $subList);
            break;
          case 'tls_client_profile':
            $tlsList = \Drupal::state()->get('ibm_apim.content_refresh_tlsprofile_objects');
            if (!isset($tlsList)) {
              $tlsList = array();
            }
            $tlsList[] = $content['url'];
            drush_ibm_apim_tlsprofile_update($content, 'content_refresh');
            \Drupal::state()->set('ibm_apim.content_refresh_tlsprofile_objects', $tlsList);
            break;
          case 'oauth_provider':
            break;
          case 'role':
            drush_consumerorg_role_create($content);
            break;
          case 'member':
            drush_ibm_apim_user_createOrUpdate($content, 'ContentRefresh');
            break;
          case 'member_invitation':
            drush_consumerorg_invitation_createOrUpdate($content, 'ContentRefresh');
            break;
          case 'billing':
            $billList = \Drupal::state()->get('ibm_apim.content_refresh_billing_objects');
            if (!isset($billList)) {
              $billList = array();
            }
            $billList[] = $content['url'];
            drush_ibm_apim_billing_update($content, 'content_refresh');
            \Drupal::state()->set('ibm_apim.content_refresh_billing_objects', $billList);
            break;
          case 'permission':
            $permList = \Drupal::state()->get('ibm_apim.content_refresh_permission_objects');
            if (!isset($permList)) {
              $permList = array();
            }
            $permList[] = $content['url'];
            drush_ibm_apim_permission_update($content, 'content_refresh');
            \Drupal::state()->set('ibm_apim.content_refresh_permission_objects', $permList);
            break;
          case 'analytics_service':
            $analyticsList = \Drupal::state()->get('ibm_apim.content_refresh_analytics_objects');
            if (!isset($analyticsList)) {
              $analyticsList = array();
            }
            $analyticsList[] = $content['url'];
            drush_ibm_apim_analytics_update($content, 'content_refresh');
            \Drupal::state()->set('ibm_apim.content_refresh_analytics_objects', $analyticsList);
            break;
          case 'close_stream':
            // reached the end of the snapshot payload
            drush_ibm_apim_clearup();

            // assert that the index_directly value of the search_api module's default_index is
            // set correctly according to the number of nodes in the site
            drush_ibm_apim_assert_index_directly_value();

            break;
          default:
            drush_log(dt('There is no drush code to handle the payload type @type', array('@type' => $type)), 'warning');
            break;
        }
        unset($content);
      }
      else {
        drush_log(dt('No content provided for webhook', array()), 'error');
      }
    } catch (Exception $e) {
      drush_log(dt('Caught exception: @message', array(
        '@message' => $e->getMessage()
      )), 'warning');
    }

    $input = trim(fgets(STDIN));

  }

  drush_log(dt('Drush ibm_apim_content_refresh exiting'), 'success');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * Used for the streamed objects from content refresh snapshot payload
 */
function drush_ibm_apim_clearup() {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  drush_log(dt('Drush drush_ibm_apim_clearup tidying up after content_refresh'), 'success');


  // remove any products in our db that were not returned by apim
  drush_log(dt('Drush drush_ibm_apim_clearup tidying up products'), 'success');
  $current_products = \Drupal::state()->get('ibm_apim.content_refresh_products');
  if (!is_array($current_products)) {
    $current_products = array();
  }
  $query = \Drupal::entityQuery('node');
  $query->condition('type', 'product');
  $nids = $query->execute();
  if (isset($nids) && !empty($nids)) {
    $productnodes = Node::loadMultiple($nids);
    foreach ($productnodes as $productnode) {
      if (isset($productnode)) {
        $found = FALSE;
        foreach ($current_products as $product_ref) {
          if ($productnode->apic_ref->value == $product_ref) {
            $found = TRUE;
          }
        }
        if ($found != TRUE) {
          Product::deleteNode($productnode->id(), 'content_refresh');
        }
      }
    }
  }

  // remove any apis in our db that were not returned by apim
  drush_log(dt('Drush drush_ibm_apim_clearup tidying up apis'), 'success');
  $current_apis = \Drupal::state()->get('ibm_apim.content_refresh_apis');
  if (!is_array($current_apis)) {
    $current_apis = array();
  }
  $query = \Drupal::entityQuery('node');
  $query->condition('type', 'api');
  if (!empty($current_apis)) {
    $query->condition('apic_ref', $current_apis, 'NOT IN');
  }
  $results = $query->execute();
  $nids = array();
  if (isset($results)) {
    foreach ($results as $item) {
      $nids[] = $item;
    }
  }
  foreach ($nids as $nid) {
    Api::deleteNode($nid, 'content_refresh');
  }

  // remove any consumerorgs in our db that were not returned by apim
  drush_log(dt('Drush drush_ibm_apim_clearup tidying up consumerorgs'), 'success');
  $current_consumerorgs = \Drupal::state()->get('ibm_apim.content_refresh_consumerorgs');
  if (!is_array($current_consumerorgs)) {
    $current_consumerorgs = array();
  }
  // TODO need to handle tidying up consumerorgs once know we're getting the right webhooks - see TODO below
  // commenting out entire section for now since doing a pointless loadMultiple is silly
//  $query = \Drupal::entityQuery('node');
//  $query->condition('type', 'consumerorg');
//  $nids = $query->execute();
//  if (isset($nids) && !empty($nids)) {
//    $consumernodes = Node::loadMultiple($nids);
//    foreach ($consumernodes as $orgnode) {
//      if (isset($orgnode)) {
//        $found = FALSE;
//        foreach ($current_consumerorgs as $org_url) {
//          if ($orgnode->consumerorg_url->value == $org_url) {
//            $found = TRUE;
//          }
//        }
//        if ($found != TRUE) {
//          $consumerOrgService = \Drupal::service('ibm_apim.consumerorg');
//          // TODO : don't delete these due to a webhooks giving us different urls than /me response does
//          //$consumerOrgService->deleteNode($orgnode->id(), 'content_refresh');
//        }
//      }
//    }
//  }

  // remove any apps in our db that were not returned by apim
  drush_log(dt('Drush drush_ibm_apim_clearup tidying up applications'), 'success');
  $current_apps = \Drupal::state()->get('ibm_apim.content_refresh_apps');
  if (!is_array($current_apps)) {
    $current_apps = array();
  }
  $query = \Drupal::entityQuery('node');
  $query->condition('type', 'application');
  if (!empty($current_apps)) {
    $query->condition('application_id', $current_apps, 'NOT IN');
  }
  $results = $query->execute();
  if ($results !== null && !empty($results)) {
    foreach ($results as $nid) {
      Application::deleteNode($nid, 'content_refresh');
    }
  }

  // update application subscriptions
  drush_log(dt('Drush drush_ibm_apim_clearup tidying up subscriptions'), 'success');
  $current_subs = \Drupal::state()->get('ibm_apim.content_refresh_subs');
  if (!is_array($current_subs)) {
    $current_subs = array();
  }
  // Any applications that exist in the database that were not mentioned in the list of apps=>subscriptions
  // must have no subscriptions at all. Insert empty arrays to record this
  $query = \Drupal::entityQuery('node');
  $query->condition('type', 'application');
  if (!empty($current_subs)) {
    $query->condition('apic_url.value', array_keys($current_subs), 'NOT IN');
  }
  $nids = $query->execute();
  if (is_array($nids)) {
    $buckets = array_chunk($nids, 100);  // chop up array into bucket size units
    foreach ($buckets as $bucket) {
      $appnodes = Node::loadMultiple($bucket);
      foreach ($appnodes as $appnode) {
        $current_subs[$appnode->apic_url->value] = [];
      }
    }
  }

  // now update the db with the subscriptions for each app
  foreach ($current_subs as $appUrl => $appsub) {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'application');
    $query->condition('apic_url.value', $appUrl);
    $nids = $query->execute();
    if (isset($nids) && !empty($nids)) {
      $nid = reset($nids);
      $node = Node::load($nid);
      if (isset($node)) {
        $subs = array();
        foreach ($appsub as $nextSub) {
          $subs[] = serialize($nextSub);
        }

        $node->set("application_subscriptions", $subs);
        $node->save();
      }
    }
  }

  // remove any user registries in our db that were not returned by apim
  drush_log(dt('Drush drush_ibm_apim_clearup tidying up user registries'), 'success');
  $current_urs = \Drupal::state()->get('ibm_apim.content_refresh_user_registries');
  if (!is_array($current_urs)) {
    $current_urs = array();
  }
  $urService = \Drupal::service('ibm_apim.user_registry');
  $db_urs = $urService->getAll();
  if (!empty($db_urs)) {
    foreach ($db_urs as $url => $ur) {
      if (!in_array($url, $current_urs)) {
        $urService->delete($url);
      }
    }
  }

  // remove any vendor extensions in our db that were not returned by apim
  drush_log(dt('Drush drush_ibm_apim_clearup tidying up extensions'), 'success');
  $current_exts = \Drupal::state()->get('ibm_apim.content_refresh_extensions');
  if (!is_array($current_exts)) {
    $current_exts = array();
  }
  $extService = \Drupal::service('ibm_apim.vendor_extension');
  $db_exts = $extService->getAll();
  if (!empty($db_exts)) {
    foreach ($db_exts as $name => $ext) {
      if (!in_array($name, $current_exts)) {
        $extService->delete($name);
      }
    }
  }

  // remove any groups in our db that were not returned by apim
  drush_log(dt('Drush drush_ibm_apim_clearup tidying up groups'), 'success');
  $current_groups = \Drupal::state()->get('ibm_apim.content_refresh_groups');
  if (!is_array($current_groups)) {
    $current_groups = array();
  }
  $groupService = \Drupal::service('ibm_apim.group');
  $db_groups = $groupService->getAll();
  if (!empty($db_groups)) {
    foreach ($db_groups as $url => $ext) {
      if (!in_array($url, $current_groups)) {
        $groupService->delete($url);
      }
    }
  }

  // remove any billing objects in our db that were not returned by apim
  drush_log(dt('Drush drush_ibm_apim_clearup tidying up billing objects'), 'success');
  $current_bills = \Drupal::state()->get('ibm_apim.content_refresh_billing_objects');
  if (!is_array($current_bills)) {
    $current_bills = array();
  }
  $billService = \Drupal::service('ibm_apim.billing');
  $db_bills = $billService->getAll();
  if (!empty($db_bills)) {
    foreach ($db_bills as $url => $ur) {
      if (!in_array($url, $current_bills)) {
        $billService->delete($url);
      }
    }
  }

  // remove any tls client profile objects in our db that were not returned by apim
  drush_log(dt('Drush drush_ibm_apim_clearup tidying up TLS profiles'), 'success');
  $current_tls = \Drupal::state()->get('ibm_apim.content_refresh_tlsprofile_objects');
  if (!is_array($current_tls)) {
    $current_tls = array();
  }
  $tlsService = \Drupal::service('ibm_apim.tls_client_profiles');
  $db_tls = $tlsService->getAll();
  if (!empty($db_tls)) {
    foreach ($db_tls as $url => $ur) {
      if (!in_array($url, $current_tls)) {
        $tlsService->delete($url);
      }
    }
  }

  // remove any analytics objects in our db that were not returned by apim
  drush_log(dt('Drush drush_ibm_apim_clearup tidying up analytics objects'), 'success');
  $current_analytics = \Drupal::state()->get('ibm_apim.content_refresh_analytics_objects');
  if (!is_array($current_analytics)) {
    $current_analytics = array();
  }
  $analyticsService = \Drupal::service('ibm_apim.analytics');
  $db_analytics = $analyticsService->getAll();
  if (!empty($db_analytics)) {
    foreach ($db_analytics as $url => $ur) {
      if (!in_array($url, $current_analytics)) {
        $analyticsService->delete($url);
      }
    }
  }

  // remove any permission objects in our db that were not returned by apim
  drush_log(dt('Drush drush_ibm_apim_clearup tidying up permission objects'), 'success');
  $current_perms = \Drupal::state()->get('ibm_apim.content_refresh_permission_objects');
  if (!is_array($current_perms)) {
    $current_perms = array();
  }
  $permService = \Drupal::service('ibm_apim.permissions');
  $db_perms = $permService->getAll();
  if (!empty($db_perms)) {
    foreach ($db_perms as $url => $ur) {
      if (!in_array($url, $current_perms)) {
        $permService->delete($url);
      }
    }
  }

  // reset the state counters back to null ready for next time
  \Drupal::state()->set('ibm_apim.content_refresh_products', NULL);
  \Drupal::state()->set('ibm_apim.content_refresh_apis', NULL);
  \Drupal::state()->set('ibm_apim.content_refresh_apps', NULL);
  \Drupal::state()->set('ibm_apim.content_refresh_consumerorgs', NULL);
  \Drupal::state()->set('ibm_apim.content_refresh_subs', NULL);
  \Drupal::state()->set('ibm_apim.content_refresh_user_registries', NULL);
  \Drupal::state()->set('ibm_apim.content_refresh_extensions', NULL);
  \Drupal::state()->set('ibm_apim.content_refresh_groups', NULL);
  \Drupal::state()->set('ibm_apim.content_refresh_billing_objects', NULL);
  \Drupal::state()->set('ibm_apim.content_refresh_permission_objects', NULL);
  \Drupal::state()->set('ibm_apim.content_refresh_tlsprofile_objects', NULL);
  \Drupal::state()->set('ibm_apim.content_refresh_analytics_objects', NULL);

  drush_log(dt('Drush drush_ibm_apim_clearup exiting'), 'success');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $catalog
 */
function drush_ibm_apim_updatecatalog($catalog) {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  $siteconfig = \Drupal::service('ibm_apim.site_config');
  $siteconfig->updateCatalog($catalog);
  drush_log(dt('Catalog updated.'), 'success');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $config
 */
function drush_ibm_apim_updateconfig($config) {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  drush_log(dt('Updating site config.'), 'success');
  $siteconfig = \Drupal::service('ibm_apim.site_config');
  $siteconfig->update($config);
  drush_log(dt('Config updated.'), 'success');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $clientid
 * @param $clientsecret
 */
function drush_ibm_apim_setcreds($clientid, $clientsecret) {
  ibm_apim_entry_trace(__FUNCTION__, NULL);

  if (isset($clientid) && !empty($clientid)) {
    \Drupal::state()->set('ibm_apim.site_client_id', $clientid);
  }
  if (isset($clientsecret) && !empty($clientsecret)) {
    \Drupal::state()->set('ibm_apim.site_client_secret', $clientsecret);
  }
  drush_log(dt('Credentials updated.'), 'success');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $url
 * @param $insecure
 * @param $provided_cert
 */
function drush_ibm_apim_checkapimcert($url, $insecure, $provided_cert) {
  if (function_exists('ibm_apim_entry_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }
  // HACK: need to include the classes explicitly otherwise this does not work in bootstrap level DRUSH_BOOTSTRAP_DRUPAL_ROOT
  require_once __DIR__ . "/src/ApicRestInterface.php";
  require_once __DIR__ . "/src/ApicRest.php";

  try {
    // $insecure is a string 'true' or 'false' and needs converting to a boolean
    $insecureBool = ($insecure === 'true');
    Drupal\ibm_apim\ApicRest::json_http_request($url, 'GET', NULL, NULL, TRUE, $insecureBool, $provided_cert, FALSE);
    drush_print('[[0]] APIM certificate check complete');

  } catch (Exception $e) {

    echo 'Caught exception: ', $e->getMessage(), "\n";

    $failures = array(
      7 => 'Could not communicate with server. Reason: SSL certificate problem: unable to get local issuer certificate',
      6 => 'Could not communicate with server. Reason: SSL certificate problem: self signed certificate',
      5 => 'SSL: certificate verification failed (result: 5)',
      4 => 'Could not communicate with server. Reason: Could not resolve host:',
      2 => 'Could not communicate with server. Reason: SSL: no alternative certificate subject name matches target host name',
      3 => 'Could not communicate with server.'
    );

    $message = "";

    foreach ($failures as $rc => $msg) {
      if (strpos($e->getMessage(), $failures[$rc]) !== FALSE) {
        $message = "[[" . $rc . "]] " . $failures[$rc] . ' error';
        break;
      }
    }

    // failed with unknown error
    if ($message === "") {
      $message = '[[1]] Error checking certificate: ' . $e->getMessage() . 'error';
    }

    // output message
    drush_print($message);
  }
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

function drush_ibm_apim_set_admin_timestamps() {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  $time = \Drupal::time()->getCurrentTime();
  $result = db_update('users_field_data')->fields(array('created' => $time))->condition('uid', 1)->execute();
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

function drush_ibm_apim_generate_nlsexport() {
  ibm_apim_entry_trace(__FUNCTION__, NULL);

  $required_pot_dir = drush_get_option('required_pot_dir', '/tmp/translation_files/required_pots');
  $existing_drupal_po_dir = drush_get_option('existing_drupal_po_dir', '/tmp/translation_files/existing_drupal_pos');
  $output_dir = drush_get_option('output_dir', '/tmp/translation_files/output');
  $platform_dir = drush_get_option('platform_dir', NULL);
  $merge_dir = drush_get_option('merge_dir', '/tmp/translation_files/merge');

  if (!is_dir($required_pot_dir)) {
    drush_log("Required .pot directory does not exist: $required_pot_dir", 'error');
    return;
  }

  if (!is_dir($existing_drupal_po_dir)) {
    drush_log("Existing .po directory does not exist: $existing_drupal_po_dir", 'error');
    return;
  }

  if (!isset($platform_dir)) {
    drush_log('No platform_dir provided, using DRUPAL_ROOT: ' . DRUPAL_ROOT);
    $platform_dir = DRUPAL_ROOT;
  }
  if (!is_dir($platform_dir)) {
    drush_log("Invalid platform dir: '$platform_dir'", 'error');
    return;
  }

  $prep = new Drupal\ibm_apim\Translation\TranslationPreparation($required_pot_dir, $existing_drupal_po_dir, $output_dir, $platform_dir, $merge_dir);
  new Drupal\ibm_apim\Translation\ProjectParser($prep->getProjectInfos());
  ibm_apim_exit_trace(__FUNCTION__, NULL);

}

/**
 * @param $drop_dir
 * @param $original_export_dir
 */
function drush_ibm_apim_merge_nlsdrop($drop_dir, $original_export_dir) {
  ibm_apim_entry_trace(__FUNCTION__, NULL);

  $output_dir = drush_get_option('output_dir', '/tmp/new_translation_files');

  if (!is_dir($drop_dir)) {
    drush_log("Required new drop directory does not exist: $drop_dir", 'error');
    return;
  }

  if (!is_dir($original_export_dir)) {
    drush_log("Exported files directory does not exist: $original_export_dir", 'error');
    return;
  }

  $mergedrop = new \Drupal\ibm_apim\Translation\TranslationMerger($drop_dir, $original_export_dir, $output_dir);
  $mergedrop->merge();

  ibm_apim_exit_trace(__FUNCTION__, NULL);

}

/**
 * @param $master_file
 * @param $secondary_file
 */
function drush_ibm_apim_merge_nlsindividual($master_file, $secondary_file) {
  ibm_apim_entry_trace(__FUNCTION__, NULL);

  $output_dir = drush_get_option('output_dir', '/tmp/merged_po_files');

  if (!is_file($master_file)) {
    drush_log("Master file not found: $master_file", 'error');
    return;
  }

  if (!is_file($secondary_file)) {
    drush_log("Secondary file not found: $secondary_file", 'error');
    return;
  }

  new \Drupal\ibm_apim\Translation\MergeIndividual\Merger($master_file, $secondary_file, $output_dir);

  ibm_apim_exit_trace(__FUNCTION__, NULL);

}


function drush_ibm_apim_locale_clear_status() {
  ibm_apim_entry_trace(__FUNCTION__, NULL);

  locale_translation_clear_status();

  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 *
 */
function drush_ibm_apim_locale_load_config_translation() {
  ibm_apim_entry_trace(__FUNCTION__, NULL);

  $localeconfig = \Drupal::service('locale.config_manager');
  $components = $localeconfig->getComponentNames();
  $localeconfig->updateConfigTranslations($components);

  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param null $clientid
 */
function drush_ibm_apim_createkey($clientid = NULL) {
  if (function_exists('ibm_apim_entry_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, $clientid);
  }
  // if dont pass in a client id then try and get the stored site one
  if ($clientid == NULL) {
    drush_print('Using site client ID');
    $clientid = \Drupal::state()->get('ibm_apim.client_id');
  }

  if ($clientid != NULL) {
    $key_name = 'client_id';
    $value = str_replace(array('_', '-'), '', $clientid);

    // key must be 32bytes/256bits in length for an AES profile
    $value = str_pad($value, 32, 'x');

    $moduleHandler = \Drupal::service('module_handler');
    if (!$moduleHandler->moduleExists('key') || !$moduleHandler->moduleExists('encrypt') || !$moduleHandler->moduleExists('real_aes')) {
      drush_print('Enabling required encryption modules');
      $module_installer = \Drupal::service('module_installer');
      $module_installer->install(['key', 'encrypt', 'real_aes']);
    }

    $key = Drupal::service('key.repository')->getKey($key_name);
    if (isset($key) && !empty($key)) {
      drush_print('Key already exists, refusing to update value as it would break existing encrypted content');
    }
    else {
      drush_print('Creating new key');
      $key = \Drupal\key\Entity\Key::create([
        'id' => $key_name,
        'label' => $key_name,
        'key_type' => 'encryption',
        'key_type_settings' => array('key_size' => 256),
        'key_provider' => 'config',
        'key_provider_settings' => array('base64_encoded' => FALSE, 'key_value' => $value),
        'key_input' => 'text_field',
        'key_input_settings' => array('base64_encoded' => FALSE)
      ]);
      $key->save();
    }

    // check there is an encryption profile
    $profile_name = 'socialblock';
    $profile = Drupal::service('encrypt.encryption_profile.manager')->getEncryptionProfile($profile_name);
    if (isset($profile) && !empty($profile)) {
      drush_print('Encryption profile already exists');
      // dont think there is anything to do if already exists
    }
    else {
      drush_print('Creating new encryption profile');
      $profile = \Drupal\encrypt\Entity\EncryptionProfile::create([
        'id' => $profile_name,
        'label' => $profile_name,
        'encryption_method' => 'real_aes',
        'encryption_key' => $key_name,
        'encryption_method_configuration' => array()
      ]);
      $profile->save();
    }
  }
  else {
    drush_log("Client ID not set", 'error');
  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $apicuser
 * @param string $event
 */
function drush_ibm_apim_user_create($apicuser, $event = 'user_create') {
  if (function_exists('ibm_apim_entry_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }

  if ($apicuser !== null) {
    if (is_string($apicuser)) {
      $apicuser = json_decode($apicuser, TRUE);
    }

    if (isset($apicuser['user'])) {
      $apicuser = $apicuser['user'];
    }

    // check if user already exists in Drupal DB
    $user = user_load_by_name($apicuser['username']);
    if ($user !== null) {
      drush_print('User already exists: ' . $apicuser['username']);
      return;
    }
    else {

      $create_user = new ApicUser();
      $create_user->setFirstname($apicuser['first_name']);
      $create_user->setLastname($apicuser['last_name']);
      $create_user->setMail($apicuser['email']);
      $create_user->setUsername($apicuser['username']);
      if ($apicuser['url'] !== null) {
        $create_user->setUrl($apicuser['url']);
      }
      if ($apicuser['user_registry_url'] !== null) {
        $create_user->setApicUserRegistryUrl($apicuser['user_registry_url']);
      }

      $user_manager = \Drupal::service('auth_apic.usermanager');
      drush_print('Creating apic user ' . $apicuser['username']);
      $userService = \Drupal::service('ibm_apim.apicuser');
      $user_manager->registerApicUser($create_user->getUsername(), $userService->getUserAccountFields($create_user));
    }

  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $apicuser
 * @param string $event
 */
function drush_ibm_apim_user_delete($apicuser, $event = 'user_delete') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }

  if ($apicuser !== null) {
    if (is_string($apicuser)) {
      $apicuser = json_decode($apicuser, TRUE);
    }

    // check if user already exists in Drupal DB
    $user = user_load_by_name($apicuser['username']);
    if ($user !== null) {
      drush_print('User does not exist: ' . $apicuser['username']);
      return;
    }
    else {

      $delete_user = new ApicUser();
      $delete_user->setFirstname($apicuser['first_name']);
      $delete_user->setLastname($apicuser['last_name']);
      $delete_user->setMail($apicuser['email']);
      $delete_user->setUsername($apicuser['username']);
      if ($apicuser['url'] !== null) {
        $delete_user->setUrl($apicuser['url']);
      }
      if ($apicuser['user_registry_url'] !== null) {
        $delete_user->setApicUserRegistryUrl($apicuser['user_registry_url']);
      }

      $user_manager = \Drupal::service('auth_apic.usermanager');
      drush_print('Deleting apic user ' . $apicuser['username']);
      $user_manager->deleteLocalAccount($delete_user);
    }

  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $apicuser
 * @param string $event
 */
function drush_ibm_apim_user_block($apicuser, $event = 'user_block') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }

  if ($apicuser !== null) {
    if (is_string($apicuser)) {
      $apicuser = json_decode($apicuser, TRUE);
    }

    $user = user_load_by_name($apicuser['username']);

    if ($user === null) {
      drush_print('User does not exist: ' . $apicuser['username']);
      return;
    }
    else {
      if ($user->get('uid')->value === 1) {
        drush_print('admin so won\'t block this');
      }
      else {
        drush_print('Blocking apic user ' . $apicuser['username']);
        $user->set('status', 0);
        $user->save();
      }
    }

  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $apicuser
 * @param string $event
 */
function drush_ibm_apim_user_unblock($apicuser, $event = 'user_unblock') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }

  if ($apicuser !== null) {
    if (is_string($apicuser)) {
      $apicuser = json_decode($apicuser, TRUE);
    }

    $user = user_load_by_name($apicuser['username']);

    if ($user === null) {
      drush_print('User does not exist: ' . $apicuser['username']);
      return;
    }
    else {
      drush_print('Unblocking apic user ' . $apicuser['username']);
      $user->set('status', 1);
      $user->save();
    }
  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $apicuser
 * @param string $event
 */
function drush_ibm_apim_user_update($apicuser, $event = 'user_update') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }

  if ($apicuser !== null) {
    if (is_string($apicuser)) {
      $apicuser = json_decode($apicuser, TRUE);
    }

    // check if user already exists in Drupal DB
    $user = user_load_by_name($apicuser['username']);
    if ($user === null) {
      drush_print('User does not exist: ' . $apicuser['username']);
      return;
    }
    else {

      $edit_user = new ApicUser();
      $edit_user->setFirstname($apicuser['first_name']);
      $edit_user->setLastname($apicuser['last_name']);
      $edit_user->setMail($apicuser['email']);
      $edit_user->setUsername($apicuser['username']);
      if ($apicuser['url'] !== null) {
        $edit_user->setUrl($apicuser['url']);
      }
      if ($apicuser['user_registry_url'] !== null) {
        $edit_user->setApicUserRegistryUrl($apicuser['user_registry_url']);
      }

      $user_manager = \Drupal::service('auth_apic.usermanager');
      drush_print('Updating apic user ' . $apicuser['username']);
      $user_manager->updateLocalAccount($edit_user);
    }

  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $apicuser
 * @param string $event
 */
function drush_ibm_apim_user_createOrUpdate($apicuser, $event = 'user_update') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }

  if ($apicuser !== null) {
    if (is_string($apicuser)) {
      $apicuser = json_decode($apicuser, TRUE);
    }

    // check if user already exists in Drupal DB
    $user = user_load_by_name($apicuser['username']);
    if (!isset($user)) {
      drush_ibm_apim_user_create($apicuser, $event);
    }
    else {

      $edit_user = new ApicUser();
      $edit_user->setFirstname($apicuser['first_name']);
      $edit_user->setLastname($apicuser['last_name']);
      $edit_user->setMail($apicuser['email']);
      $edit_user->setUsername($apicuser['username']);
      if ($apicuser['url'] !== null) {
        $edit_user->setUrl($apicuser['url']);
      }
      if ($apicuser['user_registry_url'] !== null) {
        $edit_user->setApicUserRegistryUrl($apicuser['user_registry_url']);
      }

      $user_manager = \Drupal::service('auth_apic.usermanager');
      drush_print('Updating apic user ' . $apicuser['username']);
      $user_manager->updateLocalAccount($edit_user);
    }

  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $ur
 * @param string $event
 */
function drush_ibm_apim_user_registry_create($ur, $event = 'user_registry_create') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }
  if (isset($ur)) {
    if (is_string($ur)) {
      $ur = json_decode($ur, TRUE);
    }
    if (isset($ur['configured_catalog_user_registry'])) {
      $ur = $ur['configured_catalog_user_registry'];
    }

    if (!isset($ur['url'])) {
      $ur['url'] = 'unknown';
    }
    $urService = \Drupal::service('ibm_apim.user_registry');
    $urService->update($ur['url'], $ur);
  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $ur
 * @param string $event
 */
function drush_ibm_apim_user_registry_update($ur, $event = 'user_registry_update') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }

  if (isset($ur)) {
    if (is_string($ur)) {
      $ur = json_decode($ur, TRUE);
    }
    if (isset($ur['configured_catalog_user_registry'])) {
      $ur = $ur['configured_catalog_user_registry'];
    }
    if (!isset($ur['url'])) {
      $ur['url'] = 'unknown';
    }
    $urService = \Drupal::service('ibm_apim.user_registry');
    $urService->update($ur['url'], $ur);
  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $ur
 * @param string $event
 */
function drush_ibm_apim_user_registry_delete($ur, $event = 'user_registry_del') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }

  if (isset($ur)) {
    if (is_string($ur)) {
      $ur = json_decode($ur, TRUE);
    }
    if (isset($ur['configured_catalog_user_registry'])) {
      $ur = $ur['configured_catalog_user_registry'];
    }
    if (!isset($ur['url'])) {
      $ur['url'] = 'unknown';
    }
    $urService = \Drupal::service('ibm_apim.user_registry');
    $urService->delete($ur['url']);
  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $vx
 * @param string $event
 */
function drush_ibm_apim_vendor_extension_create($vx, $event = 'vendor_extension_create') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }
  if (isset($vx)) {
    if (is_string($vx)) {
      $vx = json_decode($vx, TRUE);
    }

    if (!isset($vx['name'])) {
      $vx['name'] = 'unknown';
    }
    $vxService = \Drupal::service('ibm_apim.vendor_extension');
    $vxService->update($vx['name'], $vx);
  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $vx
 * @param string $event
 */
function drush_ibm_apim_vendor_extension_update($vx, $event = 'vendor_extension_update') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }

  if (isset($vx)) {
    if (is_string($vx)) {
      $vx = json_decode($vx, TRUE);
    }
    if (!isset($vx['name'])) {
      $vx['name'] = 'unknown';
    }
    $vxService = \Drupal::service('ibm_apim.vendor_extension');
    $vxService->update($vx['name'], $vx);
  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $vx
 * @param string $event
 */
function drush_ibm_apim_vendor_extension_delete($vx, $event = 'vendor_extension_del') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }

  if (isset($vx)) {
    if (is_string($vx)) {
      $vx = json_decode($vx, TRUE);
    }
    if (!isset($vx['name'])) {
      $vx['name'] = 'unknown';
    }
    $vxService = \Drupal::service('ibm_apim.vendor_extension');
    $vxService->delete($vx['name']);
  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $group
 * @param string $event
 */
function drush_ibm_apim_group_create($group, $event = 'group_create') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }
  if (isset($group)) {
    if (is_string($group)) {
      $group = json_decode($group, TRUE);
    }

    if (!isset($group['url'])) {
      $group['url'] = 'unknown';
    }
    $groupService = \Drupal::service('ibm_apim.group');
    $groupService->update($group['url'], $group);
  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $group
 * @param string $event
 */
function drush_ibm_apim_group_update($group, $event = 'group_update') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }

  if (isset($group)) {
    if (is_string($group)) {
      $group = json_decode($group, TRUE);
    }
    if (!isset($group['url'])) {
      $group['url'] = 'unknown';
    }
    $groupService = \Drupal::service('ibm_apim.group');
    $groupService->update($group['url'], $group);
  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $group
 * @param string $event
 */
function drush_ibm_apim_group_delete($group, $event = 'group_del') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }

  if (isset($group)) {
    if (is_string($group)) {
      $group = json_decode($group, TRUE);
    }
    if (!isset($vx['url'])) {
      $group['url'] = 'unknown';
    }
    $groupService = \Drupal::service('ibm_apim.group');
    $groupService->delete($group['url']);
  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $bill
 * @param string $event
 */
function drush_ibm_apim_billing_create($bill, $event = 'billing_create') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }
  if (isset($bill)) {
    if (is_string($bill)) {
      $bill = json_decode($bill, TRUE);
    }

    if (!isset($bill['url'])) {
      $bill['url'] = 'unknown';
    }
    $billService = \Drupal::service('ibm_apim.billing');
    $billService->update($bill['url'], $bill);
  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $bill
 * @param string $event
 */
function drush_ibm_apim_billing_update($bill, $event = 'billing_update') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }

  if (isset($bill)) {
    if (is_string($bill)) {
      $bill = json_decode($bill, TRUE);
    }
    if (!isset($bill['url'])) {
      $bill['url'] = 'unknown';
    }
    $billService = \Drupal::service('ibm_apim.billing');
    $billService->update($bill['url'], $bill);
  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $bill
 * @param string $event
 */
function drush_ibm_apim_billing_delete($bill, $event = 'billing_del') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }

  if (isset($bill)) {
    if (is_string($bill)) {
      $bill = json_decode($bill, TRUE);
    }
    if (!isset($vx['url'])) {
      $bill['url'] = 'unknown';
    }
    $billService = \Drupal::service('ibm_apim.billing');
    $billService->delete($bill['url']);
  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $perm
 * @param string $event
 */
function drush_ibm_apim_permission_create($perm, $event = 'permission_create') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }
  if (isset($perm)) {
    if (is_string($perm)) {
      $perm = json_decode($perm, TRUE);
    }

    if (!isset($perm['url'])) {
      $perm['url'] = 'unknown';
    }
    $permService = \Drupal::service('ibm_apim.permissions');
    $permService->update($perm['url'], $perm);
  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $perm
 * @param string $event
 */
function drush_ibm_apim_permission_update($perm, $event = 'permission_update') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }

  if (isset($perm)) {
    if (is_string($perm)) {
      $perm = json_decode($perm, TRUE);
    }
    if (!isset($perm['url'])) {
      $perm['url'] = 'unknown';
    }
    $permService = \Drupal::service('ibm_apim.permissions');
    $permService->update($perm['url'], $perm);
  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $perm
 * @param string $event
 */
function drush_ibm_apim_permission_delete($perm, $event = 'permission_del') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }

  if (isset($perm)) {
    if (is_string($perm)) {
      $perm = json_decode($perm, TRUE);
    }
    if (!isset($perm['url'])) {
      $perm['url'] = 'unknown';
    }
    $permService = \Drupal::service('ibm_apim.permissions');
    $permService->delete($perm['url']);
  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $tls_profile
 * @param string $event
 */
function drush_ibm_apim_tlsprofile_create($tls_profile, $event = 'tlsprofile_create') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }
  if (isset($tls_profile)) {
    if (is_string($tls_profile)) {
      $tls_profile = json_decode($tls_profile, TRUE);
    }

    if (!isset($tls_profile['url'])) {
      $tls_profile['url'] = 'unknown';
    }
    $tlsService = \Drupal::service('ibm_apim.tls_client_profiles');
    $tlsService->update($tls_profile['url'], $tls_profile);
  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $tls_profile
 * @param string $event
 */
function drush_ibm_apim_tlsprofile_update($tls_profile, $event = 'tlsprofile_update') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }

  if (isset($tls_profile)) {
    if (is_string($tls_profile)) {
      $tls_profile = json_decode($tls_profile, TRUE);
    }
    if (!isset($tls_profile['url'])) {
      $tls_profile['url'] = 'unknown';
    }
    $tlsService = \Drupal::service('ibm_apim.tls_client_profiles');
    $tlsService->update($tls_profile['url'], $tls_profile);
  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $tls_profile
 * @param string $event
 */
function drush_ibm_apim_tlsprofile_delete($tls_profile, $event = 'tlsprofile_del') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }

  if (isset($tls_profile)) {
    if (is_string($tls_profile)) {
      $tls_profile = json_decode($tls_profile, TRUE);
    }
    if (!isset($tls_profile['url'])) {
      $tls_profile['url'] = 'unknown';
    }
    $tlsService = \Drupal::service('ibm_apim.tls_client_profiles');
    $tlsService->delete($tls_profile['url']);
  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $analytics
 * @param string $event
 */
function drush_ibm_apim_analytics_create($analytics, $event = 'analytics_create') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }
  if (isset($analytics)) {
    if (is_string($analytics)) {
      $analytics = json_decode($analytics, TRUE);
    }

    if (!isset($analytics['url'])) {
      $analytics['url'] = 'unknown';
    }
    $analyticsService = \Drupal::service('ibm_apim.tls_client_profiles');
    $analyticsService->update($analytics['url'], $analytics);
  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $analytics
 * @param string $event
 */
function drush_ibm_apim_analytics_update($analytics, $event = 'analytics_update') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }

  if (isset($analytics)) {
    if (is_string($analytics)) {
      $analytics = json_decode($analytics, TRUE);
    }
    if (!isset($analytics['url'])) {
      $analytics['url'] = 'unknown';
    }
    $analyticsService = \Drupal::service('ibm_apim.analytics');
    $analyticsService->update($analytics['url'], $analytics);
  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $analytics
 * @param string $event
 */
function drush_ibm_apim_analytics_delete($analytics, $event = 'analytics_del') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }

  if (isset($analytics)) {
    if (is_string($analytics)) {
      $analytics = json_decode($analytics, TRUE);
    }
    if (!isset($analytics['url'])) {
      $analytics['url'] = 'unknown';
    }
    $analyticsService = \Drupal::service('ibm_apim.analytics');
    $analyticsService->delete($analytics['url']);
  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param string $type
 * @param null $url
 * @param string $type
 * @param null $url
 * @return null|string
 */
function drush_ibm_apim_content_check($type = 'product', $url = null) {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }
  $output = null;
  if (isset($url)) {
    if (mb_strpos($url, 'https://') !== 0) {
      // in case moderation is on we need to run as admin
      // save the current user so we can switch back at the end
      $accountSwitcher = Drupal::service('account_switcher');
      $original_user = \Drupal::currentUser();
      if ($original_user->id() != 1) {
        $accountSwitcher->switchTo(new UserSession(array('uid' => 1)));
      }
      switch ($type) {
        case 'product':
          $portalProduct = new Product();
          $output = $portalProduct->getProductAsJson($url);
          break;
        case 'api':
          $portalApi = new Api();
          $output = $portalApi->getApiAsJson($url);
          break;
        case 'application':
          $output = Application::getApplicationAsJson($url);
          break;
        case 'consumerorg':
          $corg_service = \Drupal::service('ibm_apim.consumerorg');
          $output = $corg_service->getConsumerOrgAsJson($url);
          break;
        default:
          drush_log(dt('There is no drush code to check content of type @type', array('@type' => $type)), 'error');
          break;
      }
      if (isset($original_user) && $original_user->id() != 1) {
        $accountSwitcher->switchBack();
      }
    } else {
      drush_log(dt('Content Check URL invalid @url', array('@url' => $url)), 'warning');
    }
  } else {
    drush_log(dt('Content Check URL not set', array()), 'warning');
  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
  return $output;
}

/**
 * Utility function to reset drupal statics if memory reaches .7 of max
 *
 * Note: this has been borrowed from the 'import' command of Drupal's import module.
 */
function drush_ibm_apim_manage_memory_usage() {
  $mem_thresh = .7;
  $mem_usage = memory_get_usage();
  $memory_limit = drush_ibm_apim_convertToBytes(ini_get('memory_limit'));
  if (isset($memory_limit) && $memory_limit > 0) {
    $pct_mem = $mem_usage / $memory_limit;
    if ($pct_mem > $mem_thresh) {
      drush_log(dt("Memory usage reached &mem % of max, resetting statics", array('&mem'=> ($mem_thresh * 100)), 'warning'));
      drupal_static_reset();
    }
  }
}

/**
 * @param $value
 *
 * @return int|string
 */
function drush_ibm_apim_convertToBytes($value) {
  if ('-1' === $value) {
    return -1;
  }

  $value = strtolower($value);
  $max = strtolower(ltrim($value, '+'));
  if (0 === strpos($max, '0x')) {
    $max = intval($max, 16);
  }
  elseif (0 === strpos($max, '0')) {
    $max = intval($max, 8);
  }
  else {
    $max = intval($max);
  }

  switch (substr($value, -1)) {
    /** @noinspection PhpMissingBreakStatementInspection */
    case 't':
      $max *= 1024;
    // no break
    /** @noinspection PhpMissingBreakStatementInspection */
    case 'g':
      $max *= 1024;
    // no break
    /** @noinspection PhpMissingBreakStatementInspection */
    case 'm':
      $max *= 1024;
    // no break
    /** @noinspection PhpMissingBreakStatementInspection */
    case 'k':
      $max *= 1024;
    // no break
  }

  return $max;
}

/**
 * Asserts that the apropriate value is set for the index_directly property
 * on the default search api index according to the number of nodes in the site.
 * Disabling index directly on sites with lots of nodes is needed to prevent
 * php from going OOM after processing a snapshot
 */
function drush_ibm_apim_assert_index_directly_value() {
  $query = \Drupal::entityQuery('node');
  $result = $query->count()->execute();
  $count = (int)$result;
  if ($count > 50) {
    drush_ibm_apim_index_directly(FALSE, $count);
  }
}

/**
 * Sets the value of the index_directly property of the search_api module's
 * default_index.
 * @param $indexDirectly
 * @param $count
 */
function drush_ibm_apim_index_directly($indexDirectly, $count) {
  $moduleHandler = \Drupal::service('module_handler');
  if ($moduleHandler->moduleExists('search_api')) {
    $index = Index::load('default_index');
    if (isset($index) && !empty($index)) {
      if ($index->getOption('index_directly') !== $indexDirectly) {
        $index->setOption('index_directly', $indexDirectly)->save();

        drush_log(t('Set index_directly value to @value', array('@value' => $indexDirectly ? 'true' : 'false')), 'success');
      } else {
        drush_log(t('index_directly value @value matched, no action needed', array('@value' => $indexDirectly ? 'true' : 'false')));
      }
    }
  }
}

