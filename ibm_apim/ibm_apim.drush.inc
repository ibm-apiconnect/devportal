<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2019
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

use Drupal\apic_api\Api;
use Drupal\apic_app\Application;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\UserSession;
use Drupal\Core\Url;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\node\Entity\Node;
use Drupal\product\Product;
use Drupal\search_api\Entity\Index;

function ibm_apim_drush_command() {

  $items['ibm_apim-updateconfig'] = [
    'description' => 'Updates the site config',
    'aliases' => ['ucon'],
    'arguments' => [
      'config' => 'The JSON config payload',
    ],
  ];
  $items['ibm_apim-setcreds'] = [
    'description' => 'Updates the catalog credentials from APIM',
    'aliases' => ['setcreds'],
    'arguments' => [
      'clientid' => 'The catalog client ID',
      'clientsecret' => 'The catalog client secret',
    ],
  ];
  $items['ibm_apim-bgsync'] = [
    'description' => 'Runs background sync',
    'aliases' => ['bgsync'],
  ];
  $items['ibm_apim-listen'] = [
    'description' => 'Listens to stdin and runs the drush commands piped in',
    'aliases' => ['listen'],
  ];
  $items['ibm_apim-clearup'] = [
    'description' => 'Tidies up the local database at the end of a snapshot to remove any superfluous content',
    'aliases' => ['clearup'],
  ];
  $items['ibm_apim-deleteconfig'] = [
    'description' => 'Removes catalog configuration from the portal database',
    'aliases' => ['deleteconfig'],
  ];
  $items['ibm_apim-content_refresh'] = [
    'description' => 'Listens to stdin and updates the db for each item piped in',
    'aliases' => ['contrefresh'],
  ];
  $items['ibm_apim-request_refresh'] = [
    'description' => 'Invokes ApicRest.php to request a content refresh',
    'aliases' => ['reqrefresh'],
  ];

  $items['ibm_apim-send_welcome_email'] = [
    'description' => 'Sends a new welcome email',
    'aliases' => ['welcomeemail'],
  ];
  $items['ibm_apim-checkapimcert'] = [
    'description' => 'Check APIM management certificate',
    'bootstrap' => DRUSH_BOOTSTRAP_DRUPAL_ROOT,
    'aliases' => ['checkcert'],
    'arguments' => [
      'url' => 'APIM management url',
      'insecure' => 'Insecure certificate',
      'provided_cert' => 'Provided certificate',
    ],
  ];
  $items['ibm_apim-set_admin_timestamps'] = [
    'description' => 'On creation of a site from template, set admin timestamps to current time',
    'aliases' => ['admintime'],
    'hidden' => TRUE,
  ];
  $items['ibm_apim-generate_nlsexport'] = [
    'description' => 'Generate .pot/.po files to be sent for translation.',
    'aliases' => ['nlsexport'],
    'options' => [
      'required_pot_dir' => 'Directory containing complete set of .pot files for which translations are required.',
      'existing_drupal_po_dir' => 'Directory containing downloaded existing drupal .po files.',
      'output_dir' => 'Directory to place the output.',
      'platform_dir' => 'Platform directory.',
      'merge_dir' => 'Directory to merge existing translations in.',
    ],
  ];
  $items['ibm_apim-merge_nlsdrop'] = [
    'description' => 'Merge new translation files with memories to give new complete set of translation (.po) files.',
    'aliases' => ['merge-nlsdrop'],
    'arguments' => [
      'drop_dir' => 'Directory containing newly translated files received from the translation centre.',
      'original_export_dir' => 'Directory containing the original files that were sent to the translation centre (.pot and -memories.\<lang\>.po)',
    ],
    'options' => [
      'output_dir' => 'Directory to place the output.',
    ],
  ];
  $items['ibm_apim-merge_nlsindividual'] = [
    'description' => 'Merge a pair of individual .po translation files .',
    'aliases' => ['merge-nlsindividual'],
    'arguments' => [
      'master_file' => 'File contain the master record of translations.',
      'secondary_file' => 'File containing the translations that need to be merged in, note the master file will take precedence.',
    ],
    'options' => [
      'output_dir' => 'Where to place the merged .po file.',
    ],
  ];
  $items['ibm_apim-locale_clear_status'] = [
    'description' => 'Clear the status of known translation files, used to ensure a full load of files is performed.',
    'aliases' => ['locale-clear-status'],
  ];
  $items['ibm_apim-locale_load_config_translation'] = [
    'description' => 'Load configuration translations for all languages.',
    'aliases' => ['locale-load-config'],
  ];
  $items['ibm_apim-createkey'] = [
    'description' => 'Create encryption key',
    'aliases' => ['createkey'],
    'arguments' => [
      'clientid' => 'Site Client ID',
    ],
  ];

  $items['ibm_apim-apicuser_create'] = [
    'description' => 'Creates an IBM APIC user',
    'aliases' => ['cibmuser'],
    'arguments' => [
      'content' => 'The webhook JSON content',
      'event' => 'The event type',
    ],
  ];
  $items['ibm_apim-apicuser_update'] = [
    'description' => 'Updates an IBM APIC user',
    'aliases' => ['uibmuser'],
    'arguments' => [
      'content' => 'The webhook JSON content',
      'event' => 'The event type',
    ],
  ];
  $items['ibm_apim-apicuser_delete'] = [
    'description' => 'Deletes an IBM APIC user',
    'aliases' => ['dibmuser'],
    'arguments' => [
      'content' => 'The webhook JSON content',
      'event' => 'The event type',
    ],
  ];
  $items['ibm_apim-apicuser_block'] = [
    'description' => 'Blocks an IBM APIC user',
    'aliases' => ['crtibmuser'],
    'arguments' => [
      'content' => 'The webhook JSON content',
      'event' => 'The event type',
    ],
  ];
  $items['ibm_apim-apicuser_unblock'] = [
    'description' => 'Unblocks an IBM APIC user',
    'aliases' => ['ubibmuser'],
    'arguments' => [
      'content' => 'The webhook JSON content',
      'event' => 'The event type',
    ],
  ];
  $items['ibm_apim-user_registry_create'] = [
    'description' => 'Creates an IBM APIC user registry',
    'aliases' => ['cibmuserreg'],
    'arguments' => [
      'content' => 'The webhook JSON content',
      'event' => 'The event type',
    ],
  ];
  $items['ibm_apim-user_registry_update'] = [
    'description' => 'Updates an IBM APIC user registry',
    'aliases' => ['uibmuserreg'],
    'arguments' => [
      'content' => 'The webhook JSON content',
      'event' => 'The event type',
    ],
  ];
  $items['ibm_apim-user_registry_delete'] = [
    'description' => 'Deletes an IBM APIC user registry',
    'aliases' => ['dibmuserreg'],
    'arguments' => [
      'content' => 'The webhook JSON content',
      'event' => 'The event type',
    ],
  ];
  $items['ibm_apim-vendor_extension_create'] = [
    'description' => 'Creates an IBM APIC vendor extension',
    'aliases' => ['cibmvendext'],
    'arguments' => [
      'content' => 'The webhook JSON content',
      'event' => 'The event type',
    ],
  ];
  $items['ibm_apim-vendor_extension_update'] = [
    'description' => 'Updates an IBM APIC vendor extension',
    'aliases' => ['uibmvendext'],
    'arguments' => [
      'content' => 'The webhook JSON content',
      'event' => 'The event type',
    ],
  ];
  $items['ibm_apim-vendor_extension_delete'] = [
    'description' => 'Deletes an IBM APIC vendor extension',
    'aliases' => ['dibmvendext'],
    'arguments' => [
      'content' => 'The webhook JSON content',
      'event' => 'The event type',
    ],
  ];
  $items['ibm_apim-group_create'] = [
    'description' => 'Creates an IBM APIC group',
    'aliases' => ['cibmgroup'],
    'arguments' => [
      'content' => 'The webhook JSON content',
      'event' => 'The event type',
    ],
  ];
  $items['ibm_apim-group_update'] = [
    'description' => 'Updates an IBM APIC group',
    'aliases' => ['uibmgroup'],
    'arguments' => [
      'content' => 'The webhook JSON content',
      'event' => 'The event type',
    ],
  ];
  $items['ibm_apim-group_delete'] = [
    'description' => 'Deletes an IBM APIC group',
    'aliases' => ['dibmgroup'],
    'arguments' => [
      'content' => 'The webhook JSON content',
      'event' => 'The event type',
    ],
  ];
  $items['ibm_apim-billing_create'] = [
    'description' => 'Creates an IBM APIC billing object',
    'aliases' => ['cibmbilling'],
    'arguments' => [
      'content' => 'The webhook JSON content',
      'event' => 'The event type',
    ],
  ];
  $items['ibm_apim-billing_update'] = [
    'description' => 'Updates an IBM APIC billing object',
    'aliases' => ['uibmbilling'],
    'arguments' => [
      'content' => 'The webhook JSON content',
      'event' => 'The event type',
    ],
  ];
  $items['ibm_apim-billing_delete'] = [
    'description' => 'Deletes an IBM APIC billing object',
    'aliases' => ['dibmbilling'],
    'arguments' => [
      'content' => 'The webhook JSON content',
      'event' => 'The event type',
    ],
  ];
  $items['ibm_apim-content_check'] = [
    'description' => 'Check if a specific artifact is in the database',
    'aliases' => ['ibmcontentcheck'],
    'arguments' => [
      'type' => 'The artifact type, e.g. product',
      'url' => 'The artifact URL reference, e.g. /product/xyz/1',
    ],
  ];
  $items['ibm_apim-assert_config_uuids'] = [
    'description' => 'Updates the UUIDs of any config files in the provided source dir (or /private/config/sync dir for the site by default) to match those on the running site.  This is needed to allow config from one site to be imported into another',
    'aliases' => ['assert_config_uuids'],
    'options' => [
      'source' => 'Source directory containing the config to check',
      'extended_output' => 'Log additional output about the config uuids that get set'
    ]
  ];

  return $items;
}

/**
 * @param $url
 * @param $account
 * @param $language
 * @param $clientEmail
 * @param $onetime
 */
function _drush_ibm_apim_send_welcome_mail($url, $account, $language, $clientEmail, $onetime) {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  if ($clientEmail) {
    $config = \Drupal::config('system.site');
    $mailManager = \Drupal::service('plugin.manager.mail');
    $ibmApimSiteUrl = \Drupal::state()->get('ibm_apim.site_url');
    // Mail one time login URL and instructions.
    $siteName = $config->get('name');

    $loginUrl = Url::fromRoute('user.login', ['absolute' => TRUE])->toString();
    $editUrl = Url::fromRoute('user.page', ['absolute' => TRUE])->toString();

    if (!isset($ibmApimSiteUrl) || empty($ibmApimSiteUrl)) {
      $ibmApimSiteUrl = 'null';
    }
    if (!isset($siteName) || empty($siteName)) {
      $siteName = 'null';
    }
    $from = 'null@example.com';

    $mailParams['variables'] = [
      '!username' => $account->getAccountName(),
      '!site' => $siteName,
      '!login_url' => $onetime,
      '!uri' => $ibmApimSiteUrl,
      '!uri_brief' => preg_replace('!^https?://!', '', $ibmApimSiteUrl),
      '!mailto' => $account->getEmail(),
      '!date' => \Drupal::service('date.formatter')->format(time()),
      '!login_uri' => $loginUrl,
      '!edit_uri' => $editUrl,
    ];

    $mailSuccess = $mailManager->mail('ibm_apim_drush', 'welcome-admin', $account->getEmail(), $account->getPreferredLangcode(), $mailParams, $from, TRUE);

    if ($mailSuccess) {
      drush_log(dt('Sent welcome mail to @client', ['@client' => $clientEmail]), 'success');
    }
    else {
      drush_log(dt('Could not send welcome mail to @client', ['@client' => $clientEmail]), 'warning');
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
  // $mail is magically set here by drupal
  $message['subject'] = strtr($mail['subject'], $params['variables']);
  $message['body'][0] = Markup::create(strtr($mail['body'], $params['variables']));
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $clientEmail
 *
 * @return string
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function drush_ibm_apim_send_welcome_email($clientEmail) {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  global $url;
  global $install_locale;
  global $base_url;

  // get the admin account
  $account = \Drupal\user\Entity\User::load(1);

  // temporarily disable drupal's default mail notification
  $userSettingsConfig = \Drupal::config('user.settings');
  $prev = $userSettingsConfig->get('notify.status_activated');
  \Drupal::service('config.factory')->getEditable('user.settings')->set('notify.status_activated', FALSE)->save();

  if ($account !== NULL) {
    $account->set('mail', $clientEmail);
    $account->set('init', $clientEmail);
    $account->save();
  }

  \Drupal::service('config.factory')->getEditable('user.settings')->set('notify.status_activated', $prev)->save();
  \Drupal::service('config.factory')->getEditable('system.site')->set('mail', $clientEmail)->save();
  \Drupal::service('config.factory')
    ->getEditable('update.settings')
    ->set('notification.emails', [$clientEmail])
    ->save();

  //HACK HACK HACK. Why is the base_url set wrong when this is run. Don't know, but I
  //know that we always set ibm_apim_site_url to the proper base_url so just use this.
  $base_url = \Drupal::state()->get('ibm_apim.site_url');

  $onetime = user_pass_reset_url($account);
  drush_log(dt('Login url: @onetime', ['@onetime' => $onetime]), 'success');

  _drush_ibm_apim_send_welcome_mail($url, $account, $install_locale, $clientEmail, $onetime);
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
  $content = NULL;
  while ($command) {

    drush_log(dt('Got command: @cmd', ['@cmd' => $command]), 'success');

    // only read from stdin on the first attempt. next time around we already have the content!
    if ($attempt === 0) {
      $content = trim(fgets(STDIN));
      $content = json_decode($content, TRUE);
    }

    try {
      $webhookDebug = (boolean) \Drupal::config('ibm_apim.settings')->get('webhook_debug');
      if ($webhookDebug === TRUE) {
        $webhookPayloads = \Drupal::state()->get('ibm_apim.webhook_payloads');
        $webhookPayloads[] = ['content' => $content, 'type' => $command, 'timestamp' => time()];
        \Drupal::state()->set('ibm_apim.webhook_payloads', $webhookPayloads);
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
            drush_ibm_apim_apicuser_block($content);
            break;
          case 'member_unblock':
            drush_ibm_apim_apicuser_unblock($content);
            break;
          case 'member_update':
            drush_consumerorg_member_update($content);
            break;
          case 'user_update':
            drush_ibm_apim_user_createOrUpdate($content, 'user_update');
            break;
          case 'user_create':
            drush_ibm_apim_user_createOrUpdate($content, 'user_create');
            break;
          case 'user_del':
            drush_ibm_apim_apicuser_delete($content);
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
            drush_log(dt('There is no drush code to handle the webhook command @cmd', ['@cmd' => $command]), 'warning');
            break;
        }
      }
      else {
        drush_log(dt('No content provided for webhook command @cmd', ['@cmd' => $command]), 'warning');
      }
      $attempt = 0;
    } catch (Exception $e) {
      $errors = \Drupal::state()->get('ibm_apim.sync_errors');
      if ($errors === null) {
        $errors = [];
      }
      $errors[] = ['type'=>'exception', 'message' => $e->getMessage(), 'code' =>$e->getCode(), 'trace' => $e->getTrace(), 'timestamp' => time()];
      \Drupal::state()->set('ibm_apim.sync_errors', $errors);

      drush_log(dt('Attempt @attempt. Caught exception: @message', [
        '@attempt' => $attempt,
        '@message' => $e->getMessage(),
      ]), 'warning');
      $attempt++;

      if ($attempt > 2) {
        //Only try 3 times then give up.
        drush_log(dt('Giving up'), 'warning');
        $attempt = 0;
      }
    }

    // If we were successful, get the next command
    if ($attempt === 0) {
      $command = trim(fgets(STDIN));
      $content = '';
    }


  }

  drush_log(dt('Drush ibm_apim_listen exiting'), 'success');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
  exitClean();
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
  require_once __DIR__ . '/src/ApicRestInterface.php';
  require_once __DIR__ . '/src/ApicRest.php';

  try {
    $namespace = \Drupal::state()->get('ibm_apim.site_namespace');
    $siteConfig = \Drupal::service('ibm_apim.site_config');
    $host_pieces = $siteConfig->parseApimHost();
    // need the apim hostname without any path component on the ingress (such as consumer-api)
    $url = $host_pieces['scheme'] . '://' . $host_pieces['host'] . ':' . $host_pieces['port'] . '/catalogs/' . $namespace . '/webhooks/snapshot';
    Drupal\ibm_apim\ApicRest::patch($url, NULL, 'clientid', TRUE, TRUE);
    drush_print('[[0]] Content Refresh Requested');

  } catch (Exception $e) {
    // When we have a system to test against, we need to work out what the relevant errors actually are here
    echo 'Caught exception: ', $e->getMessage(), "\n";

    $failures = [
      7 => 'Could not communicate with server. Reason: SSL certificate problem: unable to get local issuer certificate',
      6 => 'Could not communicate with server. Reason: SSL certificate problem: self signed certificate',
      5 => 'SSL: certificate verification failed (result: 5)',
      4 => 'Could not communicate with server. Reason: Could not resolve host:',
      2 => 'Could not communicate with server. Reason: SSL: no alternative certificate subject name matches target host name',
      3 => 'Could not communicate with server.',
    ];

    $message = '';

    foreach ($failures as $rc => $msg) {
      if (strpos($e->getMessage(), $failures[$rc]) !== FALSE) {
        $message = '[[' . $rc . ']] ' . $failures[$rc] . ' error';
        break;
      }
    }

    // failed with unknown error
    if ($message === '') {
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
    $webhookDebug = (boolean) \Drupal::config('ibm_apim.settings')->get('webhook_debug');
    if ($webhookDebug === TRUE) {
      $webhookPayloads = \Drupal::state()->get('ibm_apim.snapshot_webhook_payloads');
      $webhookPayloads[] = ['content' => $content, 'type' => $type, 'timestamp' => time()];
      \Drupal::state()->set('ibm_apim.snapshot_webhook_payloads', $webhookPayloads);
    }

    drush_log(t('Got type: @type', ['@type' => $type]), 'success');

    // checking memory
    drush_ibm_apim_manage_memory_usage();

    // assert that the index_directly value of the search_api module's default_index is
    // set correctly according to the number of nodes in the site
    drush_ibm_apim_assert_index_directly_value();

    try {
      if ($type !== NULL && $type !== 'error' && $content !== NULL) {
        switch ($type) {
          case 'product':
            $prodList = \Drupal::state()->get('ibm_apim.content_refresh_products');
            if (!isset($prodList)) {
              $prodList = [];
            }
            $prodList[] = $content['name'] . ':' . $content['version'];
            // have to massage the content so we can reuse the same logic for snapshot as we use for webhooks
            \Drupal::state()->set('ibm_apim.content_refresh_products', $prodList);
            drush_product_createOrUpdate(['product' => $content], 'ContentRefresh', 'content_refresh');
            break;
          case 'api':
            $apiList = \Drupal::state()->get('ibm_apim.content_refresh_apis');
            if (!isset($apiList)) {
              $apiList = [];
            }
            $apiList[] = $content['name'] . ':' . $content['version'];
            \Drupal::state()->set('ibm_apim.content_refresh_apis', $apiList);
            drush_apic_api_createOrUpdate($content, 'ContentRefresh', 'content_refresh');
            break;
          case 'app':
            $appList = \Drupal::state()->get('ibm_apim.content_refresh_apps');
            if (!isset($appList)) {
              $appList = [];
            }
            $appList[] = $content['id'];
            \Drupal::state()->set('ibm_apim.content_refresh_apps', $appList);
            drush_apic_app_createOrUpdate($content, 'ContentRefresh', 'content_refresh');
            break;
          case 'consumer_org':
            $orgList = \Drupal::state()->get('ibm_apim.content_refresh_consumerorgs');
            if (!isset($orgList)) {
              $orgList = [];
            }
            $orgList[] = $content['url'];
            \Drupal::state()->set('ibm_apim.content_refresh_consumerorgs', $orgList);
            drush_consumerorg_createOrUpdate($content, 'ContentRefresh', 'content_refresh');
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
              $urList = [];
            }
            $urList[] = $content['url'];
            \Drupal::state()->set('ibm_apim.content_refresh_user_registries', $urList);
            drush_ibm_apim_user_registry_update($content, 'content_refresh');
            break;
          case 'extension':
            $extList = \Drupal::state()->get('ibm_apim.content_refresh_extensions');
            if (!isset($extList)) {
              $extList = [];
            }
            $extList[] = $content['name'];
            \Drupal::state()->set('ibm_apim.content_refresh_extensions', $extList);
            drush_ibm_apim_vendor_extension_update($content, 'content_refresh');
            break;
          case 'group':
            $groupList = \Drupal::state()->get('ibm_apim.content_refresh_groups');
            if (!isset($groupList)) {
              $groupList = [];
            }
            $groupList[] = $content['url'];
            \Drupal::state()->set('ibm_apim.content_refresh_extensions', $groupList);
            drush_ibm_apim_group_update($content, 'content_refresh');
            break;
          case 'subscription':
            $subList = \Drupal::state()->get('ibm_apim.content_refresh_subs');
            if (!is_array($subList)) {
              $subList = [];
            }
            if (isset($content['app_url'])) {
              if (!isset($subList[$content['app_url']]) || !is_array($subList[$content['app_url']])) {
                $subList[$content['app_url']] = [];
              }
              if (!isset($content['state'])) {
                $sub['state'] = 'enabled';
              }
              $subList[$content['app_url']][] = [
                'id' => $content['id'],
                'product_url' => $content['product_url'],
                'plan' => $content['plan'],
                'state' => $content['state'],
              ];
            }
            \Drupal::state()->set('ibm_apim.content_refresh_subs', $subList);
            break;
          case 'tls_client_profile':
            $tlsList = \Drupal::state()->get('ibm_apim.content_refresh_tlsprofile_objects');
            if (!isset($tlsList)) {
              $tlsList = [];
            }
            $tlsList[] = $content['url'];
            \Drupal::state()->set('ibm_apim.content_refresh_tlsprofile_objects', $tlsList);
            drush_ibm_apim_tlsprofile_update($content, 'content_refresh');
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
              $billList = [];
            }
            $billList[] = $content['url'];
            \Drupal::state()->set('ibm_apim.content_refresh_billing_objects', $billList);
            drush_ibm_apim_billing_update($content, 'content_refresh');
            break;
          case 'permission':
            $permList = \Drupal::state()->get('ibm_apim.content_refresh_permission_objects');
            if (!isset($permList)) {
              $permList = [];
            }
            $permList[] = $content['url'];
            \Drupal::state()->set('ibm_apim.content_refresh_permission_objects', $permList);
            drush_ibm_apim_permission_update($content, 'content_refresh');
            break;
          case 'analytics_service':
            $analyticsList = \Drupal::state()->get('ibm_apim.content_refresh_analytics_objects');
            if (!isset($analyticsList)) {
              $analyticsList = [];
            }
            $analyticsList[] = $content['url'];
            \Drupal::state()->set('ibm_apim.content_refresh_analytics_objects', $analyticsList);
            drush_ibm_apim_analytics_update($content, 'content_refresh');
            break;
          case 'close_stream':
            // reached the end of the snapshot payload
            drush_ibm_apim_clearup();

            // assert that the index_directly value of the search_api module's default_index is
            // set correctly according to the number of nodes in the site
            drush_ibm_apim_assert_index_directly_value();

            break;
          default:
            drush_log(dt('There is no drush code to handle the payload type @type', ['@type' => $type]), 'warning');
            break;
        }
        unset($content);
      }
      else {
        drush_log(dt('No content provided for webhook', []), 'warning');
      }
    } catch (Exception $e) {
      $errors = \Drupal::state()->get('ibm_apim.sync_errors');
      if ($errors === null) {
        $errors = [];
      }
      $errors[] = ['type'=>'exception', 'message' => $e->getMessage(), 'code' =>$e->getCode(), 'trace' => $e->getTrace(), 'timestamp' => time()];
      \Drupal::state()->set('ibm_apim.sync_errors', $errors);
      drush_log(dt('Caught exception: @message', [
        '@message' => $e->getMessage(),
      ]), 'warning');
    }

    $input = trim(fgets(STDIN));

  }

  drush_log(dt('Drush ibm_apim_content_refresh exiting'), 'success');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
  exitClean();
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
    $current_products = [];
  }
  $query = \Drupal::entityQuery('node');
  $query->condition('type', 'product');
  if (!empty($current_products)) {
    $group = $query->orConditionGroup()
      ->condition('apic_ref', NULL, 'IS NULL')
      ->condition('apic_ref', $current_products, 'NOT IN');
    $query->condition($group);
  }
  $results = $query->execute();
  $nids = [];
  if (isset($results)) {
    foreach ($results as $item) {
      $nids[] = $item;
    }
  }
  foreach ($nids as $nid) {
    Product::deleteNode($nid, 'content_refresh');
  }

  // remove any apis in our db that were not returned by apim
  drush_log(dt('Drush drush_ibm_apim_clearup tidying up apis'), 'success');
  $current_apis = \Drupal::state()->get('ibm_apim.content_refresh_apis');
  if (!is_array($current_apis)) {
    $current_apis = [];
  }
  $query = \Drupal::entityQuery('node');
  $query->condition('type', 'api');
  if (!empty($current_apis)) {
    $group = $query->orConditionGroup()
      ->condition('apic_ref', NULL, 'IS NULL')
      ->condition('apic_ref', $current_apis, 'NOT IN');
    $query->condition($group);
  }
  $results = $query->execute();
  $nids = [];
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
    $current_consumerorgs = [];
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
    $current_apps = [];
  }
  $query = \Drupal::entityQuery('node');
  $query->condition('type', 'application');
  if (!empty($current_apps)) {
    $group = $query->orConditionGroup()
      ->condition('application_id', NULL, 'IS NULL')
      ->condition('application_id', $current_apps, 'NOT IN');
    $query->condition($group);
  }
  $results = $query->execute();
  if ($results !== NULL && !empty($results)) {
    foreach ($results as $nid) {
      Application::deleteNode($nid, 'content_refresh');
    }
  }

  // update application subscriptions
  drush_log(dt('Drush drush_ibm_apim_clearup tidying up subscriptions'), 'success');
  $currentSubs = \Drupal::state()->get('ibm_apim.content_refresh_subs');
  if (!is_array($currentSubs)) {
    $currentSubs = [];
  }
  // Any applications that exist in the database that were not mentioned in the list of apps=>subscriptions
  // must have no subscriptions at all. Insert empty arrays to record this
  $query = \Drupal::entityQuery('node');
  $query->condition('type', 'application');
  if (!empty($currentSubs)) {
    $group = $query->orConditionGroup()
      ->condition('apic_url.value', NULL, 'IS NULL')
      ->condition('apic_url.value', array_keys($currentSubs), 'NOT IN');
    $query->condition($group);
  }
  $nids = $query->execute();
  if (is_array($nids)) {
    $buckets = array_chunk($nids, 100);  // chop up array into bucket size units
    foreach ($buckets as $bucket) {
      $appNodes = Node::loadMultiple($bucket);
      foreach ($appNodes as $appNode) {
        $currentSubs[$appNode->apic_url->value] = [];
      }
    }
  }

  // now update the db with the subscriptions for each app
  foreach ($currentSubs as $appUrl => $appSub) {
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'application');
    $query->condition('apic_url.value', $appUrl);
    $nids = $query->execute();
    if (isset($nids) && !empty($nids)) {
      $nid = reset($nids);
      $node = Node::load($nid);
      if (isset($node)) {
        $subs = [];
        foreach ($appSub as $nextSub) {
          $subs[] = serialize($nextSub);
        }

        $node->set('application_subscriptions', $subs);
        $node->save();
      }
    }
  }

  // remove any user registries in our db that were not returned by apim
  drush_log(dt('Drush drush_ibm_apim_clearup tidying up user registries'), 'success');
  $currentUrs = \Drupal::state()->get('ibm_apim.content_refresh_user_registries');
  if (!is_array($currentUrs)) {
    $currentUrs = [];
  }
  $urService = \Drupal::service('ibm_apim.user_registry');
  $dbUrs = $urService->getAll();
  if (!empty($dbUrs)) {
    foreach ($dbUrs as $url => $ur) {
      if (!in_array($url, $currentUrs, FALSE)) {
        $urService->delete($url);
      }
    }
  }

  // remove any vendor extensions in our db that were not returned by apim
  drush_log(dt('Drush drush_ibm_apim_clearup tidying up extensions'), 'success');
  $currentExts = \Drupal::state()->get('ibm_apim.content_refresh_extensions');
  if (!is_array($currentExts)) {
    $currentExts = [];
  }
  $extService = \Drupal::service('ibm_apim.vendor_extension');
  $dbExts = $extService->getAll();
  if (!empty($dbExts)) {
    foreach ($dbExts as $name => $ext) {
      if (!in_array($name, $currentExts, FALSE)) {
        $extService->delete($name);
      }
    }
  }

  // remove any groups in our db that were not returned by apim
  drush_log(dt('Drush drush_ibm_apim_clearup tidying up groups'), 'success');
  $currentGroups = \Drupal::state()->get('ibm_apim.content_refresh_groups');
  if (!is_array($currentGroups)) {
    $currentGroups = [];
  }
  $groupService = \Drupal::service('ibm_apim.group');
  $dbGroups = $groupService->getAll();
  if (!empty($dbGroups)) {
    foreach ($dbGroups as $url => $group) {
      if (!in_array($url, $currentGroups, FALSE)) {
        $groupService->delete($url);
      }
    }
  }

  // remove any billing objects in our db that were not returned by apim
  drush_log(dt('Drush drush_ibm_apim_clearup tidying up billing objects'), 'success');
  $currentBills = \Drupal::state()->get('ibm_apim.content_refresh_billing_objects');
  if (!is_array($currentBills)) {
    $currentBills = [];
  }
  $billService = \Drupal::service('ibm_apim.billing');
  $dbBills = $billService->getAll();
  if (!empty($dbBills)) {
    foreach ($dbBills as $url => $ur) {
      if (!in_array($url, $currentBills, FALSE)) {
        $billService->delete($url);
      }
    }
  }

  // remove any tls client profile objects in our db that were not returned by apim
  drush_log(dt('Drush drush_ibm_apim_clearup tidying up TLS profiles'), 'success');
  $currentTls = \Drupal::state()->get('ibm_apim.content_refresh_tlsprofile_objects');
  if (!is_array($currentTls)) {
    $currentTls = [];
  }
  $tlsService = \Drupal::service('ibm_apim.tls_client_profiles');
  $dbTls = $tlsService->getAll();
  if (!empty($dbTls)) {
    foreach ($dbTls as $url => $tls) {
      if (!in_array($url, $currentTls, FALSE)) {
        $tlsService->delete($url);
      }
    }
  }

  // remove any analytics objects in our db that were not returned by apim
  drush_log(dt('Drush drush_ibm_apim_clearup tidying up analytics objects'), 'success');
  $currentAnalytics = \Drupal::state()->get('ibm_apim.content_refresh_analytics_objects');
  if (!is_array($currentAnalytics)) {
    $currentAnalytics = [];
  }
  $analyticsService = \Drupal::service('ibm_apim.analytics');
  $dbAnalytics = $analyticsService->getAll();
  if (!empty($dbAnalytics)) {
    foreach ($dbAnalytics as $url => $analytics) {
      if (!in_array($url, $currentAnalytics, FALSE)) {
        $analyticsService->delete($url);
      }
    }
  }

  // remove any permission objects in our db that were not returned by apim
  drush_log(dt('Drush drush_ibm_apim_clearup tidying up permission objects'), 'success');
  $currentPerms = \Drupal::state()->get('ibm_apim.content_refresh_permission_objects');
  if (!is_array($currentPerms)) {
    $currentPerms = [];
  }
  $permService = \Drupal::service('ibm_apim.permissions');
  $dbPerms = $permService->getAll();
  if (!empty($dbPerms)) {
    foreach ($dbPerms as $url => $perm) {
      if (!in_array($url, $currentPerms, FALSE)) {
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
  $siteConfig = \Drupal::service('ibm_apim.site_config');
  $siteConfig->updateCatalog($catalog);
  drush_log(dt('Catalog updated.'), 'success');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $config
 */
function drush_ibm_apim_updateconfig($config) {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  drush_log(dt('Updating site config.'), 'success');
  $siteConfig = \Drupal::service('ibm_apim.site_config');
  $siteConfig->update($config);
  drush_log(dt('Config updated.'), 'success');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

function drush_ibm_apim_deleteconfig() {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  $siteConfig = \Drupal::service('ibm_apim.site_config');
  $siteConfig->deleteAll();
  drush_log(dt('Configuration deleted.'), 'success');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $clientId
 * @param $clientSecret
 */
function drush_ibm_apim_setcreds($clientId, $clientSecret) {
  ibm_apim_entry_trace(__FUNCTION__, NULL);

  if (isset($clientId) && !empty($clientId)) {
    \Drupal::state()->set('ibm_apim.site_client_id', $clientId);
  }
  if (isset($clientSecret) && !empty($clientSecret)) {
    \Drupal::state()->set('ibm_apim.site_client_secret', $clientSecret);
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
  require_once __DIR__ . '/src/ApicRestInterface.php';
  require_once __DIR__ . '/src/ApicRest.php';

  try {
    // $insecure is a string 'true' or 'false' and needs converting to a boolean
    $insecureBool = ($insecure === 'true');
    Drupal\ibm_apim\ApicRest::json_http_request($url, 'GET', NULL, NULL, TRUE, $insecureBool, $provided_cert, FALSE);
    drush_print('[[0]] APIM certificate check complete');

  } catch (Exception $e) {

    echo 'Caught exception: ', $e->getMessage(), "\n";

    $failures = [
      7 => 'Could not communicate with server. Reason: SSL certificate problem: unable to get local issuer certificate',
      6 => 'Could not communicate with server. Reason: SSL certificate problem: self signed certificate',
      5 => 'SSL: certificate verification failed (result: 5)',
      4 => 'Could not communicate with server. Reason: Could not resolve host:',
      2 => 'Could not communicate with server. Reason: SSL: no alternative certificate subject name matches target host name',
      3 => 'Could not communicate with server.',
    ];

    $message = '';

    foreach ($failures as $rc => $msg) {
      if (strpos($e->getMessage(), $failures[$rc]) !== FALSE) {
        $message = '[[' . $rc . ']] ' . $failures[$rc] . ' error';
        break;
      }
    }

    // failed with unknown error
    if ($message === '') {
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
  $result = db_update('users_field_data')->fields(['created' => $time])->condition('uid', 1)->execute();
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

function drush_ibm_apim_generate_nlsexport() {
  ibm_apim_entry_trace(__FUNCTION__, NULL);

  $required_pot_dir = drush_get_option('required_pot_dir', '/tmp/translation_files/required_pots');
  $existing_drupal_po_dir = drush_get_option('existing_drupal_po_dir', '/tmp/translation_files/existing_drupal_pos');
  $outputDir = drush_get_option('output_dir', '/tmp/translation_files/output');
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

  $prep = new Drupal\ibm_apim\Translation\TranslationPreparation($required_pot_dir, $existing_drupal_po_dir, $outputDir, $platform_dir, $merge_dir);
  new Drupal\ibm_apim\Translation\ProjectParser($prep->getProjectInfos());
  ibm_apim_exit_trace(__FUNCTION__, NULL);

}

/**
 * @param $dropDir
 * @param $originalExportDir
 */
function drush_ibm_apim_merge_nlsdrop($dropDir, $originalExportDir) {
  ibm_apim_entry_trace(__FUNCTION__, NULL);

  $outputDir = drush_get_option('output_dir', '/tmp/new_translation_files');

  if (!is_dir($dropDir)) {
    drush_log("Required new drop directory does not exist: $dropDir", 'error');
    return;
  }

  if (!is_dir($originalExportDir)) {
    drush_log("Exported files directory does not exist: $originalExportDir", 'error');
    return;
  }

  $mergeDrop = new \Drupal\ibm_apim\Translation\TranslationMerger($dropDir, $originalExportDir, $outputDir);
  $mergeDrop->merge();

  ibm_apim_exit_trace(__FUNCTION__, NULL);

}

/**
 * @param $masterFile
 * @param $secondaryFile
 */
function drush_ibm_apim_merge_nlsindividual($masterFile, $secondaryFile) {
  ibm_apim_entry_trace(__FUNCTION__, NULL);

  $outputDir = drush_get_option('output_dir', '/tmp/merged_po_files');

  if (!is_file($masterFile)) {
    drush_log("Master file not found: $masterFile", 'error');
    return;
  }

  if (!is_file($secondaryFile)) {
    drush_log("Secondary file not found: $secondaryFile", 'error');
    return;
  }

  new \Drupal\ibm_apim\Translation\MergeIndividual\Merger($masterFile, $secondaryFile, $outputDir);

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

  $localeConfig = \Drupal::service('locale.config_manager');
  $components = $localeConfig->getComponentNames();
  $localeConfig->updateConfigTranslations($components);

  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param string|null $clientId
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function drush_ibm_apim_createkey($clientId = NULL) {
  if (function_exists('ibm_apim_entry_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, $clientId);
  }
  // if dont pass in a client id then try and get the stored site one
  if ($clientId === NULL) {
    drush_print('Using site client ID');
    $clientId = \Drupal::state()->get('ibm_apim.client_id');
  }

  if ($clientId !== NULL) {
    $keyName = 'client_id';
    $value = str_replace(['_', '-'], '', $clientId);

    // key must be 32bytes/256bits in length for an AES profile
    $value = str_pad($value, 32, 'x');

    $moduleHandler = \Drupal::service('module_handler');
    if (!$moduleHandler->moduleExists('key') || !$moduleHandler->moduleExists('encrypt') || !$moduleHandler->moduleExists('real_aes')) {
      drush_print('Enabling required encryption modules');
      $moduleInstaller = \Drupal::service('module_installer');
      $moduleInstaller->install(['key', 'encrypt', 'real_aes']);
    }

    $key = Drupal::service('key.repository')->getKey($keyName);
    if (isset($key) && !empty($key)) {
      drush_print('Key already exists, refusing to update value as it would break existing encrypted content');
    }
    else {
      drush_print('Creating new key');
      $key = \Drupal\key\Entity\Key::create([
        'id' => $keyName,
        'label' => $keyName,
        'key_type' => 'encryption',
        'key_type_settings' => ['key_size' => 256],
        'key_provider' => 'config',
        'key_provider_settings' => ['base64_encoded' => FALSE, 'key_value' => $value],
        'key_input' => 'text_field',
        'key_input_settings' => ['base64_encoded' => FALSE],
      ]);
      $key->save();
    }

    // check there is an encryption profile
    $profileName = 'socialblock';
    $profile = Drupal::service('encrypt.encryption_profile.manager')->getEncryptionProfile($profileName);
    if (isset($profile) && !empty($profile)) {
      drush_print('Encryption profile already exists');
      // dont think there is anything to do if already exists
    }
    else {
      drush_print('Creating new encryption profile');
      $profile = \Drupal\encrypt\Entity\EncryptionProfile::create([
        'id' => $profileName,
        'label' => $profileName,
        'encryption_method' => 'real_aes',
        'encryption_key' => $keyName,
        'encryption_method_configuration' => [],
      ]);
      $profile->save();
    }
  }
  else {
    drush_log('Client ID not set', 'error');
  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $apicUser
 * @param string $event
 */
function drush_ibm_apim_apicuser_create($apicUser, $event = 'user_create') {
  if (function_exists('ibm_apim_entry_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }

  if ($apicUser !== NULL) {
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }
    if (is_string($apicUser)) {
      $apicUser = json_decode($apicUser, TRUE);
    }

    if (isset($apicUser['user'])) {
      $apicUser = $apicUser['user'];
    }

    // check if user already exists in Drupal DB
    $user = user_load_by_name($apicUser['username']);
    if ($user !== NULL && $user !== FALSE) {
      drush_print('User already exists: ' . $apicUser['username']);
    }
    else {
      $createUser = new ApicUser();
      $createUser->setFirstname($apicUser['first_name']);
      $createUser->setLastname($apicUser['last_name']);
      $createUser->setMail($apicUser['email']);
      $createUser->setUsername($apicUser['username']);
      if ($apicUser['url'] !== NULL) {
        $createUser->setUrl($apicUser['url']);
      }
      if ($apicUser['user_registry_url'] !== NULL) {
        $createUser->setApicUserRegistryUrl($apicUser['user_registry_url']);
      }
      if ($apicUser['state'] !== NULL) {
        $createUser->setState($apicUser['state']);
      }

      $userManager = \Drupal::service('auth_apic.usermanager');
      drush_print('Creating apic user ' . $apicUser['username']);
      $userService = \Drupal::service('ibm_apim.apicuser');
      $userManager->registerApicUser($createUser->getUsername(), $userService->getUserAccountFields($createUser));
    }
    if (isset($originalUser) && (int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }
  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $apicUser
 * @param string $event
 */
function drush_ibm_apim_apicuser_delete($apicUser, $event = 'user_delete') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }

  if ($apicUser !== NULL) {
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }
    if (is_string($apicUser)) {
      $apicUser = json_decode($apicUser, TRUE);
    }

    // check if user exists in Drupal DB
    $user = user_load_by_name($apicUser['username']);
    if ($user === NULL || $user === FALSE) {
      drush_print('Nothing to delete as user does not exist: ' . $apicUser['username']);
    }
    else {

      $deleteUser = new ApicUser();
      $deleteUser->setFirstname($apicUser['first_name']);
      $deleteUser->setLastname($apicUser['last_name']);
      $deleteUser->setMail($apicUser['email']);
      $deleteUser->setUsername($apicUser['username']);
      if ($apicUser['url'] !== NULL) {
        $deleteUser->setUrl($apicUser['url']);
      }
      if ($apicUser['user_registry_url'] !== NULL) {
        $deleteUser->setApicUserRegistryUrl($apicUser['user_registry_url']);
      }

      $userManager = \Drupal::service('auth_apic.usermanager');
      drush_print('Deleting apic user ' . $apicUser['username']);
      $userManager->deleteLocalAccount($deleteUser);
    }
    if (isset($originalUser) && (int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }
  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $apicUser
 * @param string $event
 */
function drush_ibm_apim_apicuser_block($apicUser, $event = 'user_block') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }

  if ($apicUser !== NULL) {
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }
    if (is_string($apicUser)) {
      $apicUser = json_decode($apicUser, TRUE);
    }

    $user = user_load_by_name($apicUser['username']);

    if ($user === NULL || $user === FALSE) {
      drush_print('User does not exist: ' . $apicUser['username']);
    }
    elseif ((int) $user->get('uid')->value === 1) {
      drush_print('admin so won\'t block this');
    }
    else {
      drush_print('Blocking apic user ' . $apicUser['username']);
      $user->set('status', 0);
      $user->save();
    }
    if (isset($originalUser) && (int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }
  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $apicUser
 * @param string $event
 */
function drush_ibm_apim_apicuser_unblock($apicUser, $event = 'user_unblock') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }

  if ($apicUser !== NULL) {
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }
    if (is_string($apicUser)) {
      $apicUser = json_decode($apicUser, TRUE);
    }

    $user = user_load_by_name($apicUser['username']);

    if ($user === NULL || $user === FALSE) {
      drush_print('User does not exist: ' . $apicUser['username']);
    }
    else {
      drush_print('Unblocking apic user ' . $apicUser['username']);
      $user->set('status', 1);
      $user->save();
    }
    if (isset($originalUser) && (int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }
  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $apicUser
 * @param string $event
 */
function drush_ibm_apim_apicuser_update($apicUser, $event = 'user_update') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }

  if ($apicUser !== NULL) {
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }
    if (is_string($apicUser)) {
      $apicUser = json_decode($apicUser, TRUE);
    }

    // check if user already exists in Drupal DB
    $user = user_load_by_name($apicUser['username']);
    if ($user === NULL || $user === FALSE) {
      drush_print('User does not exist: ' . $apicUser['username']);
    }
    else {
      $editUser = new ApicUser();
      $editUser->setFirstname($apicUser['first_name']);
      $editUser->setLastname($apicUser['last_name']);
      $editUser->setMail($apicUser['email']);
      $editUser->setUsername($apicUser['username']);
      if ($apicUser['url'] !== NULL) {
        $editUser->setUrl($apicUser['url']);
      }
      if ($apicUser['user_registry_url'] !== NULL) {
        $editUser->setApicUserRegistryUrl($apicUser['user_registry_url']);
      }
      if ($apicUser['state'] !== NULL) {
        $editUser->setState($apicUser['state']);
      }

      $userManager = \Drupal::service('auth_apic.usermanager');
      drush_print('Updating apic user ' . $apicUser['username']);
      $userManager->updateLocalAccount($editUser);
    }
    if (isset($originalUser) && (int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }
  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $apicUser
 * @param string $event
 */
function drush_ibm_apim_user_createOrUpdate($apicUser, $event = 'user_update') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }

  if ($apicUser !== NULL) {
    if (is_string($apicUser)) {
      $apicUser = json_decode($apicUser, TRUE);
    }

    // check if user already exists in Drupal DB
    $user = user_load_by_name($apicUser['username']);
    if ($user !== NULL && $user !== FALSE) {
      drush_ibm_apim_apicuser_update($apicUser, $event);
    }
    else {
      drush_ibm_apim_apicuser_create($apicUser, $event);
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

  $accountSwitcher = Drupal::service('account_switcher');
  $original_user = \Drupal::currentUser();
  if ($original_user->id() !== 1) {
    $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
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

  if ($original_user !== NULL && $original_user->id() !== 1) {
    $accountSwitcher->switchBack();
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

  $accountSwitcher = Drupal::service('account_switcher');
  $original_user = \Drupal::currentUser();
  if ($original_user->id() !== 1) {
    $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
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

  if ($original_user !== NULL && $original_user->id() !== 1) {
    $accountSwitcher->switchBack();
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

  $accountSwitcher = Drupal::service('account_switcher');
  $original_user = \Drupal::currentUser();
  if ($original_user->id() !== 1) {
    $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
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

  if ($original_user !== NULL && $original_user->id() !== 1) {
    $accountSwitcher->switchBack();
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
 * @param $tlsProfile
 * @param string $event
 */
function drush_ibm_apim_tlsprofile_create($tlsProfile, $event = 'tlsprofile_create') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }
  if (isset($tlsProfile)) {
    if (is_string($tlsProfile)) {
      $tlsProfile = json_decode($tlsProfile, TRUE);
    }

    if (!isset($tlsProfile['url'])) {
      $tlsProfile['url'] = 'unknown';
    }
    $tlsService = \Drupal::service('ibm_apim.tls_client_profiles');
    $tlsService->update($tlsProfile['url'], $tlsProfile);
  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $tlsProfile
 * @param string $event
 */
function drush_ibm_apim_tlsprofile_update($tlsProfile, $event = 'tlsprofile_update') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }

  if (isset($tlsProfile)) {
    if (is_string($tlsProfile)) {
      $tlsProfile = json_decode($tlsProfile, TRUE);
    }
    if (!isset($tlsProfile['url'])) {
      $tlsProfile['url'] = 'unknown';
    }
    $tlsService = \Drupal::service('ibm_apim.tls_client_profiles');
    $tlsService->update($tlsProfile['url'], $tlsProfile);
  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $tlsProfile
 * @param string $event
 */
function drush_ibm_apim_tlsprofile_delete($tlsProfile, $event = 'tlsprofile_del') {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }

  if (isset($tlsProfile)) {
    if (is_string($tlsProfile)) {
      $tlsProfile = json_decode($tlsProfile, TRUE);
    }
    if (!isset($tlsProfile['url'])) {
      $tlsProfile['url'] = 'unknown';
    }
    $tlsService = \Drupal::service('ibm_apim.tls_client_profiles');
    $tlsService->delete($tlsProfile['url']);
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
 * Asserts that the config in the provided source directory
 * (or the default site sync dir if not provided) are the same
 * as matching config items in the running site.
 *
 * @return mixed
 */
function drush_ibm_apim_assert_config_uuids() {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }

  $sourceDir = drush_get_option('source', NULL);
  $extendedOutput = drush_get_option('extended_output', NULL);

  if (isset($sourceDir)) {
    if (file_exists($sourceDir) && is_dir($sourceDir)) {
      $sync = new FileStorage($sourceDir);
      drush_log('Using provided source dir ' . $sourceDir, 'success');
    } else {
      return drush_set_error('INVALID_SOURCE_DIR', 'The provided source dir \'' . $sourceDir . '\' does not exist');
    }
  } else {
    $sync = \Drupal::service('config.storage.sync');
  }

  foreach ($sync->listAll() as $name) {
    $existing_config = \Drupal::configFactory()->get($name);
    if (!$existing_config->isNew() && ($uuid = $existing_config->get('uuid'))) {
      $data = $sync->read($name);
      $data['uuid'] = $uuid;
      $sync->write($name, $data);

      if (isset($extendedOutput)) {
        drush_log('Setting UUID of \'' . $name . '\' to \'' . $uuid . '\'', 'success');
      }
    } else {
      if (isset($extendedOutput)) {
        drush_log('The running site does not have any config with name \'' . $name . '\', not updating the UUID', 'success');
      }
    }
  }

  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param string $type
 * @param null $url
 *
 * @return string|null
 */
function drush_ibm_apim_content_check($type = 'product', $url = NULL) {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }
  $output = NULL;
  if (isset($url)) {
    if (mb_strpos($url, 'https://') !== 0) {
      // in case moderation is on we need to run as admin
      // save the current user so we can switch back at the end
      $accountSwitcher = Drupal::service('account_switcher');
      $originalUser = \Drupal::currentUser();
      if ((int) $originalUser->id() !== 1) {
        $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
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
          $cOrgService = \Drupal::service('ibm_apim.consumerorg');
          $output = $cOrgService->getConsumerOrgAsJson($url);
          break;
        default:
          drush_log(dt('There is no drush code to check content of type @type', ['@type' => $type]), 'error');
          break;
      }
      if (isset($originalUser) && (int) $originalUser->id() !== 1) {
        $accountSwitcher->switchBack();
      }
    }
    else {
      drush_log(dt('Content Check URL invalid @url', ['@url' => $url]), 'warning');
    }
  }
  else {
    drush_log(dt('Content Check URL not set', []), 'warning');
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
  $memThreshold = .7;
  $memUsage = memory_get_usage();
  $memoryLimit = drush_ibm_apim_convertToBytes(ini_get('memory_limit'));
  if (isset($memoryLimit) && $memoryLimit > 0) {
    $pctMem = $memUsage / $memoryLimit;
    if ($pctMem > $memThreshold) {
      drush_log(dt('Memory usage reached &mem % of max, resetting statics', ['&mem' => $memThreshold * 100], 'warning'));
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
    $max = (int) $max;
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
  $count = (int) $result;
  if ($count > 50) {
    drush_ibm_apim_index_directly(FALSE, $count);
  }
}

/**
 * Sets the value of the index_directly property of the search_api module's
 * default_index.
 *
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

        drush_log(t('Set index_directly value to @value', ['@value' => $indexDirectly ? 'true' : 'false']), 'success');
      }
      else {
        drush_log(t('index_directly value @value matched, no action needed', ['@value' => $indexDirectly ? 'true' : 'false']));
      }
    }
  }
}

/**
 * Clean Drush exit
 */
function exitClean() {
  drush_set_context('DRUSH_EXECUTION_COMPLETED', TRUE);
  drush_set_context('DRUSH_EXIT_CODE', DRUSH_SUCCESS);
  exit(0);
}

