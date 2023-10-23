<?php

namespace Drupal\ibm_apim\Commands;

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2022
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

use Drupal\apic_api\Api;
use Drupal\apic_api\Commands\ApicApiCommands;
use Drupal\apic_app\Commands\ApicAppCommands;
use Drupal\apic_app\Entity\ApplicationSubscription;
use Drupal\consumerorg\Commands\ConsumerOrgCommands;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Database\Database;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\UserSession;
use Drupal\Core\Url;
use Drupal\ibm_apim\ApicRest;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Controller\IbmApimThemeInstallController;
use Drupal\ibm_apim\JsonStreamingParser\CollectionListener;
use Drupal\product\Commands\ProductCommands;
use Drupal\product\Product;
use Drupal\search_api\Entity\Index;
use Drupal\user\Entity\User;
use Drush\Commands\DrushCommands;
use Drush\Drush;
use Drush\Runtime\Runtime;
use JsonStreamingParser\Parser;
use Throwable;

/**
 * Class IbmApimCommands.
 *
 * @package Drupal\ibm_apim\Commands
 */
class IbmApimCommands extends DrushCommands {

  /**
   * @param $url
   * @param $account
   * @param $language
   * @param $clientEmail
   * @param $onetime
   */
  public function _drush_ibm_apim_send_welcome_mail($url, $account, $language, $clientEmail, $onetime): void {
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

      $mailSuccess = $mailManager->mail('ibm_apim_welcome', 'welcome-admin', $account->getEmail(), $account->getPreferredLangcode(), $mailParams, $from, TRUE);

      if ($mailSuccess) {
        \Drupal::logger('ibm_apim')->info('Sent welcome mail to @client', ['@client' => $clientEmail]);
      }
      else {
        \Drupal::logger('ibm_apim')->warning('Could not send welcome mail to @client', ['@client' => $clientEmail]);
      }
    }
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $clientEmail
   *
   * @return string
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @command ibm_apim-send_welcome_email [clientEmail]
   * @usage drush ibm_apim-send_welcome_email
   *   Sends a new welcome email.
   * @aliases welcomeemail
   */
  public function drush_ibm_apim_send_welcome_email($clientEmail): string {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    global $url;
    global $install_locale;
    global $base_url;

    // get the admin account
    $account = User::load(1);

    // temporarily disable drupal's default mail notification
    $userSettingsConfig = \Drupal::config('user.settings');
    $prev = $userSettingsConfig->get('notify.status_activated');
    \Drupal::service('config.factory')
      ->getEditable('user.settings')
      ->set('notify.status_activated', FALSE)
      ->save();

    if ($account !== NULL) {
      $account->set('mail', $clientEmail);
      $account->set('init', $clientEmail);
      $account->save();
    }

    \Drupal::service('config.factory')
      ->getEditable('user.settings')
      ->set('notify.status_activated', $prev)
      ->save();
    \Drupal::service('config.factory')
      ->getEditable('system.site')
      ->set('mail', $clientEmail)
      ->save();
    \Drupal::service('config.factory')
      ->getEditable('update.settings')
      ->set('notification.emails', [$clientEmail])
      ->save();

    //HACK HACK HACK. Why is the base_url set wrong when this is run. Don't know, but I
    //know that we always set ibm_apim_site_url to the proper base_url so just use this.
    $base_url = \Drupal::state()->get('ibm_apim.site_url');

    $onetime = user_pass_reset_url($account);
    \Drupal::logger('ibm_apim')->info('Login url: @onetime', ['@onetime' => $onetime]);

    $this->_drush_ibm_apim_send_welcome_mail($url, $account, $install_locale, $clientEmail, $onetime);
    ibm_apim_exit_trace(__FUNCTION__, NULL);

    return $onetime;
  }

  /**
   * Used by webhooks
   *
   * @throws \JsonException
   * @command ibm_apim-listen
   * @usage drush ibm_apim-listen
   *   Listens to stdin and runs the drush commands piped in.
   * @aliases listen
   */
  public function drush_ibm_apim_listen(): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    \Drupal::logger('ibm_apim')->info('Drush ibm_apim_listen listening to stdin');
    $listenStart = microtime(TRUE);

    // save the current user so we can switch back at the end
    $accountSwitcher = \Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }

    // Incoming payload is always of the format : <event> {content}
    // where <event> is an argument we were called with and {content} a json object
    // read from stdin

    $command = trim(fgets(STDIN));
    $attempt = 0;
    $content = NULL;
    while ($command) {
      $start = microtime(TRUE);
      \Drupal::logger('ibm_apim')->info('Got command: @cmd', ['@cmd' => $command]);

      // only read from stdin on the first attempt. next time around we already have the content!
      if ($attempt === 0) {
        $content = trim(fgets(STDIN));
        $content = json_decode($content, TRUE, 512, JSON_THROW_ON_ERROR);
      }

      try {
        $webhookDebug = (bool) \Drupal::config('ibm_apim.devel_settings')
          ->get('webhook_debug');
        if ($webhookDebug === TRUE) {
          $webhookPayloads = \Drupal::state()->get('ibm_apim.webhook_payloads');
          $webhookPayloads[] = [
            'content' => $content,
            'type' => $command,
            'timestamp' => time(),
          ];
          \Drupal::state()->set('ibm_apim.webhook_payloads', $webhookPayloads);
        }
        if (isset($content)) {
          switch ($command) {
            case 'product_lifecycle':
              $apiCommand = new ApicApiCommands();
              $productCommand = new ProductCommands();
              $apiCommand->drush_apic_api_massupdate($content['consumer_apis']);
              $productCommand->drush_product_create($content);
              break;
            case 'product_update':
              $apiCommand = new ApicApiCommands();
              $productCommand = new ProductCommands();
              $apiCommand->drush_apic_api_massupdate($content['consumer_apis']);
              $productCommand->drush_product_update($content);
              break;
            case 'product_supersede':
              $productCommand = new ProductCommands();
              $productCommand->drush_product_supersede($content);
              break;
            case 'product_replace_v2':
            case 'product_replace':
              // map both product replace webhooks to the same code
              $productCommand = new ProductCommands();
              $productCommand->drush_product_replace($content);
              break;
            case 'product_migrate_subscriptions':
              $productCommand = new ProductCommands();
              $productCommand->drush_product_migrate_subscriptions($content);
              break;
            case 'execute_migration_target':
              $productCommand = new ProductCommands();
              $productCommand->drush_product_execute_migration_target($content);
              break;
            case 'product_del':
              $productCommand = new ProductCommands();
              $productCommand->drush_product_delete($content);
              break;
            case 'activation_update':
                $this->drush_ibm_apim_activation_update($content);
                break;
            case 'activation_del':
              $this->drush_ibm_apim_activation_del($content);
              break;
            case 'api_del':
              $apiCommand = new ApicApiCommands();
              $apiCommand->drush_apic_api_delete($content);
              break;
            case 'api_update':
              $apiCommand = new ApicApiCommands();
              $apiCommand->drush_apic_api_update($content);
              break;
            case 'app_create':
              $appCommand = new ApicAppCommands();
              $appCommand->drush_apic_app_create($content);
              break;
            case 'app_update':
              $appCommand = new ApicAppCommands();
              $appCommand->drush_apic_app_update($content);
              break;
            case 'app_del':
              $appCommand = new ApicAppCommands();
              $appCommand->drush_apic_app_delete($content);
              break;
            case 'consumer_org_create':
              $consumerOrgCommand = new ConsumerOrgCommands();
              $consumerOrgCommand->drush_consumerorg_create($content);
              break;
            case 'consumer_org_update':
              $consumerOrgCommand = new ConsumerOrgCommands();
              $consumerOrgCommand->drush_consumerorg_update($content);
              break;
            case 'consumer_org_del':
              $consumerOrgCommand = new ConsumerOrgCommands();
              $consumerOrgCommand->drush_consumerorg_delete($content);
              break;
            case 'member_invitation_create':
              $consumerOrgCommand = new ConsumerOrgCommands();
              $consumerOrgCommand->drush_consumerorg_invitation_create($content);
              break;
            case 'member_invitation_update':
              $consumerOrgCommand = new ConsumerOrgCommands();
              $consumerOrgCommand->drush_consumerorg_invitation_update($content);
              break;
            case 'member_invitation_del':
              $consumerOrgCommand = new ConsumerOrgCommands();
              $consumerOrgCommand->drush_consumerorg_invitation_delete($content);
              break;
            case 'role_create':
              $consumerOrgCommand = new ConsumerOrgCommands();
              $consumerOrgCommand->drush_consumerorg_role_create($content);
              break;
            case 'role_update':
              $consumerOrgCommand = new ConsumerOrgCommands();
              $consumerOrgCommand->drush_consumerorg_role_update($content);
              break;
            case 'role_del':
              $consumerOrgCommand = new ConsumerOrgCommands();
              $consumerOrgCommand->drush_consumerorg_role_delete($content);
              break;
            case 'subscription_create':
              $appCommand = new ApicAppCommands();
              $appCommand->drush_apic_app_createsub($content);
              break;
            case 'subscription_update':
              $appCommand = new ApicAppCommands();
              $appCommand->drush_apic_app_updatesub($content);
              break;
            case 'subscription_del':
              $appCommand = new ApicAppCommands();
              $appCommand->drush_apic_app_deletesub($content);
              break;
            case 'member_create':
              $consumerOrgCommand = new ConsumerOrgCommands();
              $consumerOrgCommand->drush_consumerorg_member_create($content);
              break;
            case 'member_del':
              $consumerOrgCommand = new ConsumerOrgCommands();
              $consumerOrgCommand->drush_consumerorg_member_delete($content);
              break;
            case 'member_block':
              $this->drush_ibm_apim_apicuser_block($content);
              break;
            case 'member_unblock':
              $this->drush_ibm_apim_apicuser_unblock($content);
              break;
            case 'member_update':
              $consumerOrgCommand = new ConsumerOrgCommands();
              $consumerOrgCommand->drush_consumerorg_member_update($content);
              break;
            case 'user_del':
              $this->drush_ibm_apim_apicuser_delete($content);
              break;
            case 'configured_catalog_user_registry_update':
              $this->drush_ibm_apim_user_registry_update($content);
              break;
            case 'configured_catalog_user_registry_del':
              $this->drush_ibm_apim_user_registry_delete($content);
              break;
            case 'configured_catalog_user_registry_create':
              $this->drush_ibm_apim_user_registry_create($content);
              break;
            case 'vendor_extension_update':
              $this->drush_ibm_apim_vendor_extension_update($content);
              break;
            case 'vendor_extension_del':
              $this->drush_ibm_apim_vendor_extension_delete($content);
              break;
            case 'vendor_extension_create':
              $this->drush_ibm_apim_vendor_extension_create($content);
              break;
            case 'group_update':
              $this->drush_ibm_apim_group_update($content);
              break;
            case 'group_del':
              $this->drush_ibm_apim_group_delete($content);
              break;
            case 'group_create':
              $this->drush_ibm_apim_group_create($content);
              break;
            case 'configured_billing_update':
              $this->drush_ibm_apim_billing_update($content);
              break;
            case 'configured_billing_del':
              $this->drush_ibm_apim_billing_delete($content);
              break;
            case 'configured_billing_create':
              $this->drush_ibm_apim_billing_create($content);
              break;
            case 'payment_method_create':
              $consumerOrgCommand = new ConsumerOrgCommands();
              $consumerOrgCommand->drush_consumerorg_payment_method_create($content);
              break;
            case 'payment_method_update':
              $consumerOrgCommand = new ConsumerOrgCommands();
              $consumerOrgCommand->drush_consumerorg_payment_method_update($content);
              break;
            case 'payment_method_del':
              $consumerOrgCommand = new ConsumerOrgCommands();
              $consumerOrgCommand->drush_consumerorg_payment_method_delete($content);
              break;
            case 'integration_create':
              $this->drush_ibm_apim_integration_create($content);
              break;
            case 'integration_update':
              $this->drush_ibm_apim_integration_update($content);
              break;
            case 'integration_updateall':
              $this->drush_ibm_apim_integration_updateall($content);
              break;
            case 'integration_del':
              $this->drush_ibm_apim_integration_delete($content);
              break;
            case 'integration_delall':
              $this->drush_ibm_apim_integration_deleteall($content);
              break;
            case 'catalog_setting_singletonUpdate':
              $this->drush_ibm_apim_updateconfig($content);
              break;
            default:
              \Drupal::logger('ibm_apim')->warning('There is no drush code to handle the webhook command @cmd', ['@cmd' => $command]);
              break;
          }
          \Drupal::logger('ibm_apim')->info('listen_done: @cmd ( @time )', [
            '@cmd' => $command,
            '@time' => round(microtime(TRUE) - $start, 3) . 's',
          ]);
        }
        else {
          \Drupal::logger('ibm_apim')->warning('No content provided for webhook command @cmd', ['@cmd' => $command]);
        }
        $attempt = 0;
      } catch (Throwable $e) {
        \Drupal::logger('ibm_apim')->warning('Attempt @attempt. Caught exception in drush_ibm_apim_listen: @message', [
          '@attempt' => $attempt,
          '@message' => $e->getMessage(),
        ]);

        $errors = \Drupal::state()->get('ibm_apim.sync_errors');
        if ($errors === NULL) {
          $errors = [];
        }
        // only keep the last 20 errors
        $errors = array_slice($errors, -10, 10, TRUE);
        $errors[] = [
          'type' => 'exception',
          'message' => $e->getMessage(),
          'code' => $e->getCode(),
          'trace' => $e->getTrace(),
          'timestamp' => time(),
        ];
        \Drupal::state()->set('ibm_apim.sync_errors', $errors);

        $attempt++;

        if ($attempt > 2) {
          //Only try 3 times then give up.
          \Drupal::logger('ibm_apim')->warning('listen_done: Giving up. @cmd ( @time )', ['@cmd' => $command, '@time' => round(microtime(TRUE) - $start, 3) . 's']);
          $attempt = 0;
        }
      }

      // If we were successful, get the next command
      if ($attempt === 0) {
        $command = trim(fgets(STDIN));
        $content = '';
      }
    }

    if (isset($originalUser) && (int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }

    \Drupal::logger('ibm_apim')
      ->info('Drush ibm_apim_listen exiting ( @time )', ['@time' => round(microtime(TRUE) - $listenStart, 3) . 's']);
    ibm_apim_exit_trace(__FUNCTION__, NULL);
    $this->exitClean();
  }

  /**
   * Used to request a refresh from APIM
   *
   * @command ibm_apim-request_refresh
   * @usage drush ibm_apim-request_refresh
   *   Invokes ApicRest.php to request a content refresh.
   * @aliases reqrefresh
   */
  public function drush_ibm_apim_request_refresh(): void {
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
      Drush::output()->writeln('[[0]] Content Refresh Requested');
    } catch (Throwable $e) {
      // When we have a system to test against, we need to work out what the relevant errors actually are here
      echo 'Caught exception: ', $e->getMessage(), "\n";

      $failures = [
        2 => 'Could not communicate with server. Reason: SSL: no alternative certificate subject name matches target host name',
        3 => 'Could not communicate with server.',
        4 => 'Could not communicate with server. Reason: Could not resolve host:',
        5 => 'SSL: certificate verification failed (result: 5)',
        6 => 'Could not communicate with server. Reason: SSL certificate problem: self signed certificate',
        7 => 'Could not communicate with server. Reason: SSL certificate problem: unable to get local issuer certificate',
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
      Drush::output()->writeln($message);
    }
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__FUNCTION__, NULL);
    }
  }

  /**
   * Used for the streamed objects from content refresh snapshot payload
   *
   * @throws \Exception
   *
   * @command ibm_apim-content_refresh
   * @usage drush ibm_apim-content_refresh
   *   Listens to stdin and updates the db for each item piped in.
   * @option string $uuid
   *   Unique ID for the files to process.
   * @aliases contrefresh
   */
  public function drush_ibm_apim_content_refresh(array $options = [ 'uuid' => self::REQ ]): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    $startTime = time();
    $uStartTime = microtime(true);
    $UUID = $options['uuid'];
    \Drupal::logger('ibm_apim')->info('Drush ibm_apim_content_refresh start processing');

    // save the current user so we can switch back at the end
    $accountSwitcher = \Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }

    // Incoming payload is always of the format : { 'type': foo, ... }
    // where type is the type of incoming object

    $handle = @fopen("/tmp/snapshot.$UUID/snapshot.stream.$UUID.file", "r");

    $fileHandles = $this->create_content_refresh_file_handles($UUID);

    // Turn off direct search index creation for the duration of the snapshot
    $this->drush_ibm_apim_index_directly(FALSE);

    $data = [
      'fileHandles' => $fileHandles,
      'startTime' => $startTime,
      'uStartTime' => $uStartTime,
      'memory_limit' => $this->drush_ibm_apim_convertToBytes(ini_get('memory_limit'))
    ];

    $listener = new CollectionListener([$this, 'processContent'], $UUID, $data);
    try {
      $parser = new Parser($handle, $listener);
      $parser->parse();
      fclose($handle);
      $handle = NULL;

      // They should have already been closed by the close_stream code, by just in case
      $this->close_content_refresh_file_handles($fileHandles);
    } catch (Throwable $e) {
      if ($handle) {
        fclose($handle);
      }
      $this->close_content_refresh_file_handles($fileHandles);
      throw $e;
    }

    if (isset($originalUser) && (int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }

    \Drupal::logger('ibm_apim')->info('Drush ibm_apim_content_refresh exiting');
    ibm_apim_exit_trace(__FUNCTION__, NULL);
    $this->exitClean();
  }
  /**
   * returns a hashmap of object names -> file handles
   */

  function create_content_refresh_file_handles($UUID) {
    return (object) [
      'api' => fopen("/tmp/snapshot.$UUID/content_refresh_apis.$UUID.list", 'w'),
      'product' => fopen("/tmp/snapshot.$UUID/content_refresh_products.$UUID.list", 'w'),
      'app' => fopen("/tmp/snapshot.$UUID/content_refresh_apps.$UUID.list", 'w'),
      'consumer_org' => fopen("/tmp/snapshot.$UUID/content_refresh_consumerorgs.$UUID.list", 'w'),
      'configured_catalog_user_registry' => fopen("/tmp/snapshot.$UUID/content_refresh_user_registries.$UUID.list", 'w'),
      'extension' => fopen("/tmp/snapshot.$UUID/content_refresh_extensions.$UUID.list", 'w'),
      'group' => fopen("/tmp/snapshot.$UUID/content_refresh_groups.$UUID.list", 'w'),
      'subscription' => fopen("/tmp/snapshot.$UUID/content_refresh_subs.$UUID.list", 'w'),
      'tls_client_profile' => fopen("/tmp/snapshot.$UUID/content_refresh_tlsprofile_objects.$UUID.list", 'w'),
      'member' => fopen("/tmp/snapshot.$UUID/content_refresh_users.$UUID.list", 'w'),
      'configured_billing' => fopen("/tmp/snapshot.$UUID/content_refresh_billing_objects.$UUID.list", 'w'),
      'permission' => fopen("/tmp/snapshot.$UUID/content_refresh_permission_objects.$UUID.list", 'w'),
      'analytics_service' => fopen("/tmp/snapshot.$UUID/content_refresh_analytics_objects.$UUID.list", 'w')
    ];
  }

  function close_content_refresh_file_handles($fileHandles) {
    foreach ($fileHandles as $key => $value) {
        fclose($value);
        unset($fileHandles->$key);
    }
  }


  /**
   * @param $content
   * @param $UUID
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   */
  public function processContent($content, $UUID, $count, $data): void {
    $type = $content['type'];
    if (!isset($type)) {
      $type = 'error';
    }

    fprintf(STDERR, "Got type: %s\n", $type);

    $fileHandles = $data['fileHandles'];
    $startTime = $data['startTime'];

    $webhookDebug = (bool) \Drupal::config('ibm_apim.devel_settings')
      ->get('webhook_debug');
    if ($webhookDebug === TRUE) {
      $webhookPayloads = \Drupal::state()
        ->get('ibm_apim.snapshot_webhook_payloads');
      if ($type == 'configured_catalog_user_registry') {
        $webhookPayloads[][$type][] = [
          'content' => $content,
          'type' => $type,
          'timestamp' => time(),
        ];
      } else {
        $webhookPayloads[count($webhookPayloads)-1][$type][] =  [
          'content' => $content,
          'type' => $type,
          'timestamp' => time(),
        ];
      }
      \Drupal::state()
        ->set('ibm_apim.snapshot_webhook_payloads', $webhookPayloads);
    }

    // checking memory
    if ($count % 10 == 0)  {
      $this->drush_ibm_apim_manage_memory_usage($data['memory_limit'], $data['uStartTime'], $count);
    }

    try {
      if ($type !== NULL && $type !== 'error' && $content !== NULL) {
        switch ($type) {
          case 'api':
            fwrite($fileHandles->api, $content['name'] . ':' . $content['version'] . "\n");
            $apiCommand = new ApicApiCommands();
            $apiCommand->drush_apic_api_createOrUpdate($content, 'ContentRefresh', 'content_refresh');
            break;
          case 'product':
            // we don't care about staged, retired, or whatever other state products might be in. we only want published products in the portal.
            $stateToLower = strtolower($content['state']);
            if ($stateToLower === 'published' || $stateToLower === 'deprecated') {
              fwrite($fileHandles->product, $content['name'] . ':' . $content['version'] . "\n");
              $productCommand = new ProductCommands();
              $productCommand->drush_product_createOrUpdate(['product' => $content], 'ContentRefresh', 'content_refresh');
            }
            else {
              \Drupal::logger('ibm_apim')->warning('Ignoring product in invalid state %state: %product', [
                '%state' => $stateToLower,
                '%product' => $content['name'],
              ]);
            }
            break;
          case 'app':
            fwrite($fileHandles->app, $content['id']. "\n");
            $appCommand = new ApicAppCommands();
            $appCommand->drush_apic_app_createOrUpdate($content, 'ContentRefresh', 'content_refresh');
            break;
          case 'consumer_org':
            if (isset($content['consumer_org']['url'])) {
              fwrite($fileHandles->consumer_org, $content['consumer_org']['url'] . "\n");
            }
            else {
              fwrite($fileHandles->consumer_org, '/consumer-api/orgs/' . $content['id'] . "\n");
            }

            $userList = [];
            foreach ($content['members'] as $member) {
              $userList[] = $member['user_url'] . "\n";
            }
            fwrite($fileHandles->member, implode( "" , $userList ));
            $consumerOrgCommand = new ConsumerOrgCommands();
            $consumerOrgCommand->drush_consumerorg_createOrUpdate($content, 'ContentRefresh', 'content_refresh');
            break;
          case 'catalog_setting':
            $this->drush_ibm_apim_updateconfig($content);
            // set message that we have correctly received a config payload to disable intro warning message
            \Drupal::state()->set('ibm_apim.content_refresh_status', 1);
            break;
          case 'catalog':
            $this->drush_ibm_apim_updatecatalog($content);
            break;
          case 'configured_catalog_user_registry':
            fwrite($fileHandles->configured_catalog_user_registry, $content['url'] . "\n");
            $this->drush_ibm_apim_user_registry_update($content, 'content_refresh');
            break;
          case 'extension':
            fwrite($fileHandles->extension, $content['name'] . "\n");
            $this->drush_ibm_apim_vendor_extension_update($content, 'content_refresh');
            break;
          case 'group':
            fwrite($fileHandles->group, $content['url'] . "\n");
            $this->drush_ibm_apim_group_update($content, 'content_refresh');
            break;
          case 'subscription':
            fwrite($fileHandles->subscription, $content['id'] . "\n");
            $appCommand = new ApicAppCommands();
            $appCommand->drush_apic_app_createOrUpdatesub($content, 'create_sub', 'snapshot');
            break;
          case 'tls_client_profile':
            fwrite($fileHandles->tls_client_profile, $content['url'] . "\n");
            $this->drush_ibm_apim_tlsprofile_update($content, 'content_refresh');
            break;
          case 'oauth_provider':
            break;
          case 'role':
            $consumerOrgCommand = new ConsumerOrgCommands();
            $consumerOrgCommand->drush_consumerorg_role_create($content);
            break;
          case 'member_invitation':
            $consumerOrgCommand = new ConsumerOrgCommands();
            $consumerOrgCommand->drush_consumerorg_invitation_createOrUpdate($content, 'ContentRefresh');
            break;
          case 'configured_billing':
            fwrite($fileHandles->configured_billing, $content['url'] . "\n");
            $this->drush_ibm_apim_billing_update($content, 'content_refresh');
            break;
          case 'permission':
            fwrite($fileHandles->permission, $content['url'] . "\n");
            $this->drush_ibm_apim_permission_update($content, 'content_refresh');
            break;
          case 'analytics_service':
            fwrite($fileHandles->analytics_service, $content['url'] . "\n");
            $this->drush_ibm_apim_analytics_update($content, 'content_refresh');
            break;
          case 'close_stream':
            // reached the end of the snapshot payload

            $this->close_content_refresh_file_handles($fileHandles);

            $this->drush_ibm_apim_clearup($UUID, $startTime);

            // assert that the index_directly value of the search_api module's default_index is
            // set correctly according to the number of nodes in the site
            $this->drush_ibm_apim_assert_index_directly_value();

            break;
          default:
            \Drupal::logger('ibm_apim')->warning('There is no drush code to handle the payload type @type', ['@type' => $type]);
            break;
        }
        unset($content);
      }
      else {
        \Drupal::logger('ibm_apim')->warning('No content provided for webhook', []);
      }
    } catch (Throwable $e) {
      \Drupal::logger('ibm_apim')->warning('Caught exception in drush_ibm_apim_content_refresh: @message', [
        '@message' => $e->getMessage(),
      ]);

      $errors = \Drupal::state()->get('ibm_apim.sync_errors');
      if ($errors === NULL) {
        $errors = [];
      }
      // only keep the last 20 errors
      $errors = array_slice($errors, -10, 10, TRUE);
      $errors[] = [
        'type' => 'exception',
        'message' => $e->getMessage(),
        'code' => $e->getCode(),
        'trace' => $e->getTrace(),
        'timestamp' => time(),
      ];
      \Drupal::state()->set('ibm_apim.sync_errors', $errors);
    }
  }


  /**
   * This function is used to wipe the platform API token
   * This is used when doing backup / restore operations since the content won't be valid for the new site
   *
   * @command ibm_apim-delete_tokens
   * @usage drush ibm_apim-delete_tokens
   *   Delete any saved OIDC tokens.
   * @aliases deletetokens
   */
  public function drush_ibm_apim_delete_tokens(): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    \Drupal::logger('ibm_apim')->info('Drush drush_ibm_apim_delete_tokens delete saved OIDC tokens');

    // delete the Mail Platform API token
    \Drupal::state()->delete('ibm_apic_mail.token');
    // delete the integration hash too in order to ensure they're update to date on next cron run
    \Drupal::state()->delete('ibm_apic_integration.hash');

    \Drupal::logger('ibm_apim')->info('Drush drush_ibm_apim_delete_tokens exiting');
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * Used for the streamed objects from content refresh snapshot payload
   *
   * @param $UUID
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   *
   * @command ibm_apim-clearup
   * @usage drush ibm_apim-clearup
   *   Tidies up the local database at the end of a snapshot to remove any superfluous content
   * @aliases clearup
   */
  public function drush_ibm_apim_clearup($UUID, $startTime): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    \Drupal::logger('ibm_apim')->info('Drush drush_ibm_apim_clearup tidying up after content_refresh');


    // remove any products in our db that were not returned by apim
    \Drupal::logger('ibm_apim')->info('Drush drush_ibm_apim_clearup tidying up products');
    if (file_exists("/tmp/snapshot.$UUID/content_refresh_products.$UUID.list")) {
      $current_products = explode("\n", file_get_contents("/tmp/snapshot.$UUID/content_refresh_products.$UUID.list"));
    }
    else {
      $current_products = [];
    }
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
    $results = $query->accessCheck()->execute();
    $nids = [];
    if (isset($results)) {
      foreach ($results as $item) {
        $nids[] = $item;
      }
    }
    foreach ($nids as $nid) {
      Product::deleteNode($nid, 'content_refresh');
    }
    unset($current_products);

    // remove any apis in our db that were not returned by apim
    \Drupal::logger('ibm_apim')->info('Drush drush_ibm_apim_clearup tidying up apis');

    if (file_exists("/tmp/snapshot.$UUID/content_refresh_apis.$UUID.list")) {
      $current_apis = explode("\n", file_get_contents("/tmp/snapshot.$UUID/content_refresh_apis.$UUID.list"));
    }
    else {
      $current_apis = [];
    }
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
    $results = $query->accessCheck()->execute();
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
    \Drupal::logger('ibm_apim')->info('Drush drush_ibm_apim_clearup tidying up consumerorgs');
    if (file_exists("/tmp/snapshot.$UUID/content_refresh_consumerorgs.$UUID.list")) {
      $current_consumerorgs = explode("\n", file_get_contents("/tmp/snapshot.$UUID/content_refresh_consumerorgs.$UUID.list"));
    }
    else {
      $current_consumerorgs = [];
    }
    if (!is_array($current_consumerorgs)) {
      $current_consumerorgs = [];
    }
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'consumerorg');
    if (!empty($current_consumerorgs)) {
      $group = $query->orConditionGroup()
        ->condition('consumerorg_url', NULL, 'IS NULL')
        ->condition('consumerorg_url', $current_consumerorgs, 'NOT IN');
      $query->condition($group);
    }
    $results = $query->accessCheck()->execute();
    if ($results !== NULL && !empty($results)) {
      $consumerOrgService = \Drupal::service('ibm_apim.consumerorg');
      foreach ($results as $nid) {
        $consumerOrgService->deleteNode($nid);
        // \Drupal::entityTypeManager()->getStorage('node')->resetCache(array($nid));
      }
    }
    unset($current_consumerorgs);

    // remove any apps in our db that were not returned by apim
    \Drupal::logger('ibm_apim')->info('Drush drush_ibm_apim_clearup tidying up applications');
    if (file_exists("/tmp/snapshot.$UUID/content_refresh_apps.$UUID.list")) {
      $current_apps = explode("\n", file_get_contents("/tmp/snapshot.$UUID/content_refresh_apps.$UUID.list"));
    }
    else {
      $current_apps = [];
    }

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
    $results = $query->accessCheck()->execute();
    if ($results !== NULL && !empty($results)) {
      foreach ($results as $nid) {
        \Drupal::service('apic_app.application')->deleteNode($nid, 'content_refresh');
        // \Drupal::entityTypeManager()->getStorage('node')->resetCache(array($nid));
      }
    }

    // remove any subs in our db that were not returned by apim
    \Drupal::logger('ibm_apim')->info('Drush drush_ibm_apim_clearup tidying up subscriptions');
    if (file_exists("/tmp/snapshot.$UUID/content_refresh_subs.$UUID.list")) {
      $current_subs = explode("\n", file_get_contents("/tmp/snapshot.$UUID/content_refresh_subs.$UUID.list"));
      $current_subs = array_unique($current_subs);
    }
    else {
      $current_subs = [];
    }
    if (!is_array($current_subs)) {
      $current_subs = [];
    }
    $query = \Drupal::entityQuery('apic_app_application_subs');
    if (!empty($current_subs)) {
      $group = $query->orConditionGroup()
        ->condition('uuid', NULL, 'IS NULL')
        ->condition('uuid', $current_subs, 'NOT IN');
      $query->condition($group);
    }
    $results = $query->accessCheck()->execute();
    $entityIds = [];
    if (isset($results)) {
      foreach ($results as $item) {
        $entityIds[] = $item;
      }
    }
    $moduleHandler = \Drupal::service('module_handler');
    if (isset($entityIds) && !empty($entityIds)) {
      foreach (array_chunk($entityIds, 50) as $chunk) {
        $subEntities = ApplicationSubscription::loadMultiple($chunk);
        foreach ($subEntities as $subEntity) {
          $subId = $subEntity->id();
          $moduleHandler->invokeAll('apic_app_subscription_pre_delete', ['subId' => $subId]);
          $subEntity->delete();
          $moduleHandler->invokeAll('apic_app_subscription_post_delete', ['subId' => $subId]);
        }
      }
    }
    unset($current_subs);

    // remove any user registries in our db that were not returned by apim
    \Drupal::logger('ibm_apim')->info('Drush drush_ibm_apim_clearup tidying up user registries');
    if (file_exists("/tmp/snapshot.$UUID/content_refresh_user_registries.$UUID.list")) {
      $currentUrs = explode("\n", file_get_contents("/tmp/snapshot.$UUID/content_refresh_user_registries.$UUID.list"));
    }
    else {
      $currentUrs = [];
    }

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

    // remove any users from the db that do not have a consumerorg
    \Drupal::logger('ibm_apim')->info('Drush drush_ibm_apim_clearup tidying up users');
    \Drupal::service('ibm_apim.db_usersfielddata')->cleanUserConsumerorgUrlTable();

    $query = \Drupal::entityQuery('user');
    $group = $query->orConditionGroup()
    ->condition('consumerorg_url', NULL, 'IS NULL')
    ->condition('consumerorg_url', '');
    $query->condition($group);
    $query->condition('uid', 0, '<>');
    $query->condition('uid', 1, '<>');
    $uids = $query->accessCheck()->execute();

    $performBatch = FALSE;

    foreach ($uids as $uid) {
      user_cancel([], $uid, 'user_cancel_reassign');
      $performBatch = TRUE;
    }

    if ($performBatch) {
      $batch = &batch_get();
      $batch['progressive'] = FALSE;
      batch_process();
      $performBatch = FALSE;
    }

    // Delete all users from db with duplicate emails
    \Drupal::service('ibm_apim.db_usersfielddata')->deleteUsersWithDuplicateEmails();
    \Drupal::service('ibm_apim.db_usersfielddata')->deleteExpiredPendingApprovalUsers();

    // remove any users that were not in the snapshot from apim
    if (file_exists("/tmp/snapshot.$UUID/content_refresh_users.$UUID.list")) {
      $current_users = explode("\n", file_get_contents("/tmp/snapshot.$UUID/content_refresh_users.$UUID.list"));
    }
    else {
      $current_users = [];
    }

    if (!is_array($current_users)) {
      $current_users = [];
    }

    $query = \Drupal::entityTypeManager()->getStorage('user')->getQuery();
    if (!empty($current_users)) {
      $group = $query->orConditionGroup()
        ->condition('apic_url', NULL, 'IS NULL')
        ->condition('apic_url', $current_users, 'NOT IN');
      $query->condition($group);
    }
    $results = $query->accessCheck()->execute();
    if ($results !== NULL && !empty($results)) {
      $performBatch = FALSE;
      foreach ($results as $id) {
        // DO NOT DELETE THE ADMIN USER!
        if ((int) $id > 1) {
          \Drupal::logger('ibm_apim')
            ->notice('Found user %id in the database that did not have a matching user_url in the snapshot.', ['%id' => $id]);

          user_cancel([], $id, 'user_cancel_reassign');
          $performBatch = TRUE;
        }
      }

      if ($performBatch) {
        \Drupal::logger('ibm_apim')->notice('Processing batch delete of users...');
        $batch =& batch_get();
        if (function_exists('drush_backend_batch_process')) {
          drush_backend_batch_process();
        } else {
          $batch['progressive'] = FALSE;
          batch_process();
        }
      }
    }

    // remove any vendor extensions in our db that were not returned by apim
    \Drupal::logger('ibm_apim')->info('Drush drush_ibm_apim_clearup tidying up extensions');
    if (file_exists("/tmp/snapshot.$UUID/content_refresh_extensions.$UUID.list")) {
      $currentExts = explode("\n", file_get_contents("/tmp/snapshot.$UUID/content_refresh_extensions.$UUID.list"));
    }
    else {
      $currentExts = [];
    }

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
    \Drupal::logger('ibm_apim')->info('Drush drush_ibm_apim_clearup tidying up groups');
    if (file_exists("/tmp/snapshot.$UUID/content_refresh_groups.$UUID.list")) {
      $currentGroups = explode("\n", file_get_contents("/tmp/snapshot.$UUID/content_refresh_groups.$UUID.list"));
    }
    else {
      $currentGroups = [];
    }

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
    \Drupal::logger('ibm_apim')->info('Drush drush_ibm_apim_clearup tidying up billing objects');
    if (file_exists("/tmp/snapshot.$UUID/content_refresh_billing_objects.$UUID.list")) {
      $currentBills = explode("\n", file_get_contents("/tmp/snapshot.$UUID/content_refresh_billing_objects.$UUID.list"));
    }
    else {
      $currentBills = [];
    }

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
    \Drupal::logger('ibm_apim')->info('Drush drush_ibm_apim_clearup tidying up TLS profiles');
    if (file_exists("/tmp/snapshot.$UUID/content_refresh_tlsprofile_objects.$UUID.list")) {
      $currentTls = explode("\n", file_get_contents("/tmp/snapshot.$UUID/content_refresh_tlsprofile_objects.$UUID.list"));
    }
    else {
      $currentTls = [];
    }

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
    \Drupal::logger('ibm_apim')->info('Drush drush_ibm_apim_clearup tidying up analytics objects');
    if (file_exists("/tmp/snapshot.$UUID/content_refresh_analytics_objects.$UUID.list")) {
      $currentAnalytics = explode("\n", file_get_contents("/tmp/snapshot.$UUID/content_refresh_analytics_objects.$UUID.list"));
    }
    else {
      $currentAnalytics = [];
    }

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
    \Drupal::logger('ibm_apim')->info('Drush drush_ibm_apim_clearup tidying up permission objects');
    if (file_exists("/tmp/snapshot.$UUID/content_refresh_permission_objects.$UUID.list")) {
      $currentPerms = explode("\n", file_get_contents("/tmp/snapshot.$UUID/content_refresh_permission_objects.$UUID.list"));
    }
    else {
      $currentPerms = [];
    }

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

    $errors = \Drupal::state()->get('ibm_apim.sync_errors');
    if ($errors !== NULL && !empty($errors)) {
      $errorInCurrentSnapshot = FALSE;

      foreach ($errors as $error) {
        if ($error['timestamp'] >= $startTime) {
          \Drupal::logger('ibm_apim')->warning('Drush drush_ibm_apim_clearup found an error in ibm_apim.sync_errors from this snapshot processing run, not clearing sync_errors');
          $errorInCurrentSnapshot = TRUE;
          break;
        }
      }

      if ($errorInCurrentSnapshot === FALSE) {
        \Drupal::logger('ibm_apim')->info('Drush drush_ibm_apim_clearup did not find any errors in ibm_apim.sync_errors from this snapshot processing run, clearing previous sync_errors');
        // clear the sync_error array if all parsed successfully
        \Drupal::state()->delete('ibm_apim.sync_errors');
      }
    } else {
      \Drupal::logger('ibm_apim')->info('Drush drush_ibm_apim_clearup ibm_apim.sync_errors state var was empty, nothing to do');
    }

    // clear the sync_error array if all parsed successfully
    \Drupal::state()->set('ibm_apim.sync_errors', []);

    \Drupal::logger('ibm_apim')->info('Drush drush_ibm_apim_clearup exiting');
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $catalog
   */
  public function drush_ibm_apim_updatecatalog($catalog): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    $siteConfig = \Drupal::service('ibm_apim.site_config');
    $siteConfig->updateCatalog($catalog);
    \Drupal::logger('ibm_apim')->info('Catalog updated.');
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $config - The JSON config payload
   *
   * @command ibm_apim-updateconfig
   * @usage drush ibm_apim-updateconfig [config]
   *   Updates the site config.
   * @aliases ucon
   */
  public function drush_ibm_apim_updateconfig($config): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    \Drupal::logger('ibm_apim')->info('Updating site config.');
    $siteConfig = \Drupal::service('ibm_apim.site_config');
    $siteConfig->update($config);
    \Drupal::logger('ibm_apim')->info('Config updated.');
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $filename - The JSON config filename
   *
   * @command ibm_apim-updateconfigfile
   * @usage drush ibm_apim-updateconfigfile [filename]
   *   Updates the site config from a file
   * @aliases uconfile
   */
  public function drush_ibm_apim_updateconfigfile($filename): void {
    ibm_apim_entry_trace(__FUNCTION__, $filename);
    \Drupal::logger('ibm_apim')->info('Updating site config using file: @filename', ['@filename' => $filename]);
    if ($filename !== NULL && file_exists(\Drupal::service('extension.list.module')->getPath('apictest') . '/' . $filename)) {
      $string = file_get_contents(\Drupal::service('extension.list.module')->getPath('apictest') . '/' . $filename);
      $this->drush_ibm_apim_updateconfig($string);
    }
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @command ibm_apim-deleteconfig
   * @usage drush ibm_apim-deleteconfig
   *   Removes catalog configuration from the portal database
   * @aliases deleteconfig
   */
  public function drush_ibm_apim_deleteconfig(): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    $siteConfig = \Drupal::service('ibm_apim.site_config');
    $siteConfig->deleteAll();
    \Drupal::logger('ibm_apim')->info('Configuration deleted.');
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $clientId - The catalog client ID
   * @param $clientSecret - The catalog client secret
   *
   * @command ibm_apim-setcreds
   * @usage drush ibm_apim-setcreds [clientid] [clientsecret]
   *   Updates the catalog credentials from APIM.
   * @aliases setcreds
   */
  public function drush_ibm_apim_setcreds($clientId, $clientSecret): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);

    if (isset($clientId) && !empty($clientId)) {
      \Drupal::state()->set('ibm_apim.site_client_id', $clientId);
    }
    if (isset($clientSecret) && !empty($clientSecret)) {
      \Drupal::state()->set('ibm_apim.site_client_secret', $clientSecret);
    }
    \Drupal::logger('ibm_apim')->info('Credentials updated.');
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $url - APIM management url
   * @param $apiType - 'consumer' or 'platform'
   *
   * @command ibm_apim-checkapimcert
   * @bootstrap root
   * @usage drush ibm_apim-checkapimcert [url] [apiType]
   *   Check APIM management certificate.
   * @aliases checkcert
   */
  public function drush_ibm_apim_checkapimcert($url, $apiType): void {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }
    // HACK: need to include the classes explicitly otherwise this does not work in bootstrap level DRUSH_BOOTSTRAP_DRUPAL_ROOT
    require_once __DIR__ . '/src/ApicRestInterface.php';
    require_once __DIR__ . '/src/ApicRest.php';

    try {
      ApicRest::json_http_request($url, 'GET', NULL, NULL, TRUE, FALSE, $apiType);
      Drush::output()->writeln('[[0]] APIM certificate check complete');
    } catch (Throwable $e) {
      echo 'Caught exception: ', $e->getMessage(), "\n";

      $failures = [
        2 => 'Could not communicate with server. Reason: SSL: no alternative certificate subject name matches target host name',
        3 => 'Could not communicate with server.',
        4 => 'Could not communicate with server. Reason: Could not resolve host:',
        5 => 'SSL: certificate verification failed (result: 5)',
        6 => 'Could not communicate with server. Reason: SSL certificate problem: self signed certificate',
        7 => 'Could not communicate with server. Reason: SSL certificate problem: unable to get local issuer certificate',
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
      Drush::output()->writeln($message);
    }
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__FUNCTION__, NULL);
    }
  }

  /**
   * @command ibm_apim-set_admin_timestamps
   * @hidden
   * @usage drush ibm_apim-set_admin_timestamps
   *   On creation of a site from template, set admin timestamps to current time.
   * @aliases admintime
   */
  public function drush_ibm_apim_set_admin_timestamps(): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    $time = \Drupal::time()->getCurrentTime();

    $options = ['target' => 'default'];
    $result = Database::getConnection($options['target'])
      ->update('users_field_data', $options)
      ->fields(['created' => $time])
      ->condition('uid', 1)
      ->execute();
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * Used to generate nls files for translation process
   *
   * @command ibm_apim-generate_nlsexport
   * @usage drush ibm_apim-generate_nlsexport
   *   Generate .pot/.po files to be sent for translation.
   * @option string $required_pot_dir
   *   Directory containing complete set of .pot files for which translations are required.
   *   Default: /tmp/translation_files/required_pots
   * @option string $existing_drupal_po_dir
   *   Directory containing downloaded existing drupal .po files.
   *   Default: /tmp/translation_files/existing_drupal_pos
   * @option string $output_dir
   *   Directory to place the output.
   *   Default: /tmp/translation_files/output
   * @option string $platform_dir
   *   Platform directory.
   * @option string $merge_dir
   *   Directory to merge existing translations in.
   *   Default: /tmp/translation_files/merge
   * @aliases nlsexport
   */
  public function drush_ibm_apim_generate_nlsexport(array $options = [ 'required_pot_dir' => self::REQ, 'existing_drupal_po_dir' => self::REQ, 'output_dir' => self::REQ, 'platform_dir' => self::REQ, 'merge_dir' => self::REQ]): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);

    $required_pot_dir = $options['required_pot_dir'];
    $existing_drupal_po_dir = $options['existing_drupal_po_dir'];
    $outputDir = $options['output_dir'];
    $platform_dir = $options['platform_dir'];
    $merge_dir = $options['merge_dir'];

    if (!is_dir($required_pot_dir)) {
      \Drupal::logger('ibm_apim')->error("Required .pot directory does not exist: @required_pot_dir", ['@required_pot_dir' => $required_pot_dir]);
      return;
    }

    if (!is_dir($existing_drupal_po_dir)) {
      \Drupal::logger('ibm_apim')->error("Existing .po directory does not exist: @existing_drupal_po_dir", ['@existing_drupal_po_dir' => $existing_drupal_po_dir]);
      return;
    }

    if (!isset($platform_dir) || $platform_dir === '') {
      \Drupal::logger('ibm_apim')->notice('No platform_dir provided, using DRUPAL_ROOT: @DRUPAL_ROOT', ['@DRUPAL_ROOT' => DRUPAL_ROOT]);
      $platform_dir = DRUPAL_ROOT;
    }
    if (!is_dir($platform_dir)) {
      \Drupal::logger('ibm_apim')->error("Invalid platform dir: '@platform_dir'", ['@platform_dir' => $platform_dir]);
      return;
    }

    $prep = new \Drupal\ibm_apim\Translation\TranslationPreparation($required_pot_dir, $existing_drupal_po_dir, $outputDir, $platform_dir, $merge_dir);
    new \Drupal\ibm_apim\Translation\ProjectParser($prep->getProjectInfos());
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * Merges the new translation drops with the olds files.
   *
   * @param $dropDir - Directory containing newly translated files received from the translation centre.
   * @param $originalExportDir - Directory containing the original files that were sent to the translation centre (.pot and
   *   -memories.\<lang\>.po)
   *
   * @command ibm_apim-merge_nlsdrop
   * @usage drush ibm_apim-merge_nlsdrop [dropDir] [originalExportDir]
   *   Merge new translation files with memories to give new complete set of translation (.po) files.
   * @option string $output_dir
   *   Directory to place the output.
   *   Default: /tmp/new_translation_files
   * @aliases merge-nlsdrop
   */
  public function drush_ibm_apim_merge_nlsdrop($dropDir, $originalExportDir, array $options = [ 'output_dir' => self::REQ ]): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);

    $outputDir = $options['output_dir'];

    if (!is_dir($dropDir)) {
      \Drupal::logger('ibm_apim')->error("Required new drop directory does not exist: @dropDir", ['@dropDir' => $dropDir]);
      return;
    }

    if (!is_dir($originalExportDir)) {
      \Drupal::logger('ibm_apim')->error("Exported files directory does not exist: @originalExportDir", ['@originalExportDir' => $originalExportDir]);
      return;
    }

    $mergeDrop = new \Drupal\ibm_apim\Translation\TranslationMerger($dropDir, $originalExportDir, $outputDir);
    $mergeDrop->merge();

    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   *  Used to merge existing and new translations together.
   *
   * @param $masterFile - File contain the master record of translations.
   * @param $secondaryFile - File containing the translations that need to be merged in, note the master file will take precedence.
   *
   * @command ibm_apim-merge_nlsindividual
   * @usage drush ibm_apim-merge_nlsindividual [masterFile] [secondaryFile]
   *   Merge a pair of individual .po translation files.
   * @option string $output_dir
   *   Where to place the merged .po file.
   *   Default: /tmp/merged_po_files
   * @aliases merge-nlsindividual
   */
  public function drush_ibm_apim_merge_nlsindividual($masterFile, $secondaryFile, array $options = [ 'output_dir' => self::REQ ]): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);

    $outputDir = $options['output_dir'];

    if (!is_file($masterFile)) {
      \Drupal::logger('ibm_apim')->error("Master file not found: @masterFile", ['@masterFile' => $masterFile]);
      return;
    }

    if (!is_file($secondaryFile)) {
      \Drupal::logger('ibm_apim')->error("Secondary file not found: @secondaryFile", ['@secondaryFile' => $secondaryFile]);
      return;
    }

    new \Drupal\ibm_apim\Translation\MergeIndividual\Merger($masterFile, $secondaryFile, $outputDir);

    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   *
   * @command ibm_apim-locale_clear_status
   * @usage drush ibm_apim-locale_clear_status
   *   Clear the status of known translation files, used to ensure a full load of files is performed.
   * @aliases locale-clear-status
   */
  public function drush_ibm_apim_locale_clear_status(): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    locale_translation_clear_status();
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   *
   * @command ibm_apim-locale_load_config_translation
   * @usage drush ibm_apim-locale_load_config_translation
   *   Load configuration translations for all languages.
   * @aliases locale-load-config
   */
  public function drush_ibm_apim_locale_load_config_translation(): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);

    $localeConfig = \Drupal::service('locale.config_manager');
    $components = $localeConfig->getComponentNames();
    $localeConfig->updateConfigTranslations($components);

    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param string|null $clientId - Site Client ID
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @command ibm_apim-createkey
   * @usage drush ibm_apim-createkey [clientId]
   *   Create encryption key.
   * @aliases createkey
   */
  public function drush_ibm_apim_createkey(?string $clientId = NULL): void {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, $clientId);
    }
    // if dont pass in a client id then try and get the stored site one
    if ($clientId === NULL) {
      Drush::output()->writeln('Using site client ID');
      $clientId = \Drupal::state()->get('ibm_apim.site_client_id');
    }

    if ($clientId !== NULL) {
      $keyName = 'client_id';
      $value = str_replace(['_', '-'], '', $clientId);

      // key must be 32bytes/256bits in length for an AES profile
      $value = str_pad($value, 32, 'x');

      $moduleHandler = \Drupal::service('module_handler');
      if (!$moduleHandler->moduleExists('key') || !$moduleHandler->moduleExists('encrypt') || !$moduleHandler->moduleExists('real_aes')) {
        Drush::output()->writeln('Enabling required encryption modules');
        $moduleInstaller = \Drupal::service('module_installer');
        $moduleInstaller->install(['key', 'encrypt', 'real_aes']);
      }

      $key = \Drupal::service('key.repository')->getKey($keyName);
      if (isset($key) && !empty($key)) {
        Drush::output()->writeln('Key already exists, so decrypting existing content and re-encrypting using the new client id');
        \Drupal::service('ibm_apim.site_config')->updateEncryptionKey($clientId);
      }
      else {
        Drush::output()->writeln('Creating new key');
        $key = \Drupal\key\Entity\Key::create([
          'id' => $keyName,
          'label' => $keyName,
          'key_type' => 'encryption',
          'key_type_settings' => ['key_size' => 256],
          'key_provider' => 'config',
          'key_provider_settings' => [
            'base64_encoded' => FALSE,
            'key_value' => $value,
          ],
          'key_input' => 'text_field',
          'key_input_settings' => ['base64_encoded' => FALSE],
        ]);
        $key->save();

        // check there is an encryption profile
        $profileName = 'socialblock';
        $profile = \Drupal::service('encrypt.encryption_profile.manager')
          ->getEncryptionProfile($profileName);
        if (isset($profile) && !empty($profile)) {
          Drush::output()->writeln('Encryption profile already exists');
          // dont think there is anything to do if already exists
        }
        else {
          Drush::output()->writeln('Creating new encryption profile');
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
    }
    else {
      \Drupal::logger('ibm_apim')->error('Client ID not set');
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__FUNCTION__, NULL);
    }
  }

  /**
   * @param $apicUser - The webhook JSON content
   * @param string|null $event - The event type
   *
   * @throws \JsonException
   *
   * @command ibm_apim-apicuser_create
   * @usage drush ibm_apim-apicuser_create [apicUser] [event]
   *   Creates an IBM APIC user
   * @aliases cibmuser
   */
  public function drush_ibm_apim_apicuser_create($apicUser, ?string $event = 'user_create'): void {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }

    if ($apicUser !== NULL) {
      // in case moderation is on we need to run as admin
      // save the current user so we can switch back at the end
      $accountSwitcher = \Drupal::service('account_switcher');
      $originalUser = \Drupal::currentUser();
      if ((int) $originalUser->id() !== 1) {
        $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
      }
      if (is_string($apicUser)) {
        $apicUser = json_decode($apicUser, TRUE, 512, JSON_THROW_ON_ERROR);
      }

      if (isset($apicUser['user'])) {
        $apicUser = $apicUser['user'];
      }

      // if there is an email address and it is already in database we shouldn't create a new user, so bail out now.
      // note - no email address is possible so if it isn't present we continue as normal.
      if (isset($apicUser['email']) && $apicUser['email'] !== '') {
        $matchingEmailAddress = \Drupal::entityQuery('user')
          ->condition('mail', $apicUser['email'])
          ->accessCheck()
          ->execute();

        if (sizeof($matchingEmailAddress) > 0) {
          \Drupal::logger('ibm_apim')
            ->warning('user_create: Skipping user @name - a user with the same email address (@mail) already exists.', [
              '@name' => $apicUser['username'],
              '@mail' => $apicUser['email'],
            ]);

          if (function_exists('ibm_apim_exit_trace')) {
            ibm_apim_exit_trace(__FUNCTION__, 'skip - email address in use');
          }
          return;
        }
      }

      // check if user already exists in Drupal DB
      $createUser = new ApicUser();
      $createUser->setUsername($apicUser['username']);
      $createUser->setApicUserRegistryUrl($apicUser['user_registry_url']);

      if ($createUser->getUsername() === NULL || $createUser->getApicUserRegistryUrl() === NULL) {
        Drush::output()->writeln('Unable to check if user already exists because of missing data. Skipping user create.');
      }
      else {
        $userStorage = \Drupal::service('ibm_apim.user_storage');
        $user = $userStorage->load($createUser);
        if ($user !== NULL && $user !== FALSE) {
          Drush::output()->writeln('User already exists: ' . $apicUser['username']);
        }
        else {
          $createUser->setFirstname($apicUser['first_name']);
          $createUser->setLastname($apicUser['last_name']);

          if (isset($apicUser['email']) && $apicUser['email'] !== '') {
            $createUser->setMail($apicUser['email']);
          }

          if ($apicUser['url'] !== NULL) {
            $createUser->setUrl($apicUser['url']);
          }
          if ($apicUser['user_registry_url'] !== NULL) {
            $createUser->setApicUserRegistryUrl($apicUser['user_registry_url']);
          }
          if ($apicUser['state'] !== NULL) {
            $createUser->setState($apicUser['state']);
          }

          $userManager = \Drupal::service('ibm_apim.account');
          Drush::output()->writeln('Creating apic user ' . $apicUser['username']);

          try {
            $userManager->registerApicUser($createUser);
          } catch (Throwable $e) {
            // Quietly ignore errors from duplicate users to prevent webhooks from blowing up.
            Drush::output()->writeln('Failed creating apic user ' . $apicUser['username'] . ', ignoring exception');
          }
        }
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
 *
 * @throws \JsonException
 */
function drush_ibm_apim_activation_del($activation) {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }
  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = \Drupal::service('account_switcher');
  $originalUser = \Drupal::currentUser();
  if ((int) $originalUser->id() !== 1) {
    $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
  }
  if (is_string($activation)) {
    $activation = json_decode($activation, TRUE, 512, JSON_THROW_ON_ERROR);
  }


  // check if user already exists in Drupal DB
  $deleteUser = new ApicUser();
  $deleteUser->setUsername($activation['username']);
  $deleteUser->setApicUserRegistryUrl($activation['user_registry_url']);

  if ($deleteUser->getUsername() === NULL || $deleteUser->getApicUserRegistryUrl() === NULL) {
    Drush::output()->writeln('Unable to check if user already exists because of missing data. Skipping .');
  } else {
    $userStorage = \Drupal::service('ibm_apim.user_storage');
    $user = $userStorage->load($deleteUser);
    if ($user === NULL || $user === FALSE) {
      Drush::output()->writeln('Nothing to delete as user does not exist: ' . $activation['username']);
    }
    else if ($user->apic_state->value !== 'pending_approval') {
      Drush::output()->writeln($activation['username'] . ' is not in pending_approval state. Skipping rejection.');
    }
    else if ($activation['root_operation'] === 'task_rejection') {
      // ensure they have been removed from all corg membership
      $delete_service = \Drupal::service('auth_apic.delete_user');
      Drush::output()->writeln('Deleting apic user ' . $activation['username']);
      $delete_service->deleteLocalAccount($deleteUser);
    }
  }

  if (isset($originalUser) && (int) $originalUser->id() !== 1) {
    $accountSwitcher->switchBack();
  }
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

/**
 * @param $apicUser
 * @param string $event
 *
 * @throws \JsonException
 */
function drush_ibm_apim_activation_update($activation) {
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
  }
  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = \Drupal::service('account_switcher');
  $originalUser = \Drupal::currentUser();
  if ((int) $originalUser->id() !== 1) {
    $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
  }
  if (is_string($activation)) {
    $activation = json_decode($activation, TRUE, 512, JSON_THROW_ON_ERROR);
  }


  // check if user already exists in Drupal DB
  $editUser = new ApicUser();
  $editUser->setUsername($activation['username']);
  $editUser->setApicUserRegistryUrl($activation['user_registry_url']);

  if ($editUser->getUsername() === NULL || $editUser->getApicUserRegistryUrl() === NULL) {
    Drush::output()->writeln('Unable to check if user already exists because of missing data. Skipping .');
  } else {
    $userStorage = \Drupal::service('ibm_apim.user_storage');
    $user = $userStorage->load($editUser);
    if ($user === NULL || $user === FALSE) {
      Drush::output()->writeln('Nothing to update as user does not exist: ' . $activation['username']);
    }
    else if ($user->apic_state->value !== 'pending_approval') {
      Drush::output()->writeln($activation['username'] . ' is not in pending_approval state. Skipping approval.');
    }
    else if ($activation['root_operation'] === 'task_approval') {
      $editUser->setFirstname($activation['first_name']);
      $editUser->setLastname($activation['last_name']);
      $editUser->setMail($activation['email']);
      $editUser->setState("pending");
      Drush::output()->writeln('Approving apic user ' . $activation['username']);
      $accountService = \Drupal::service('ibm_apim.account');
      $accountService->updateLocalAccount($editUser);
    }
  }

  if (isset($originalUser) && (int) $originalUser->id() !== 1) {
    $accountSwitcher->switchBack();
  }
  if (function_exists('ibm_apim_exit_trace')) {
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }
}

  /**
   * @param $apicUser - The webhook JSON content
   * @param string|null $event - The event type
   *
   * @throws \JsonException
   *
   * @command ibm_apim-apicuser_delete
   * @usage drush ibm_apim-apicuser_delete [apicUser] [event]
   *   Deletes an IBM APIC user
   * @aliases dibmuser
   */
  public function drush_ibm_apim_apicuser_delete($apicUser, ?string $event = 'user_delete'): void {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }

    if ($apicUser !== NULL) {
      // in case moderation is on we need to run as admin
      // save the current user so we can switch back at the end
      $accountSwitcher = \Drupal::service('account_switcher');
      $originalUser = \Drupal::currentUser();
      if ((int) $originalUser->id() !== 1) {
        $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
      }
      if (is_string($apicUser)) {
        $apicUser = json_decode($apicUser, TRUE, 512, JSON_THROW_ON_ERROR);
      }

      // check if user already exists in Drupal DB
      $deleteUser = new ApicUser();
      $deleteUser->setUsername($apicUser['username']);
      $deleteUser->setApicUserRegistryUrl($apicUser['user_registry_url']);

      if ($deleteUser->getUsername() === NULL || $deleteUser->getApicUserRegistryUrl() === NULL) {
        Drush::output()->writeln('Unable to check if user already exists because of missing data. Skipping user delete.');
      }
      else {
        $userStorage = \Drupal::service('ibm_apim.user_storage');
        $user = $userStorage->load($deleteUser);
        if ($user === NULL || $user === FALSE) {
          Drush::output()->writeln('Nothing to delete as user does not exist: ' . $apicUser['username']);
        }
        else {
          $deleteUser->setFirstname($apicUser['first_name']);
          $deleteUser->setLastname($apicUser['last_name']);
          $deleteUser->setMail($apicUser['email']);
          if ($apicUser['url'] !== NULL) {
            $deleteUser->setUrl($apicUser['url']);
          }

          // ensure they have been removed from all corg membership
          $corg_service = \Drupal::service('ibm_apim.consumerorg');
          $corg_service->deleteUserFromAllCOrgs($apicUser['url']);

          $delete_service = \Drupal::service('auth_apic.delete_user');
          Drush::output()->writeln('Deleting apic user ' . $apicUser['username']);
          $delete_service->deleteLocalAccount($deleteUser);
        }
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
   * @param $apicUser - The webhook JSON content
   * @param string|null $event - The event type
   *
   * @throws \JsonException
   *
   * @command ibm_apim-apicuser_block
   * @usage drush ibm_apim-apicuser_block [apicUser] [event]
   *   Blocks an IBM APIC user
   * @aliases crtibmuser
   */
  public function drush_ibm_apim_apicuser_block($apicUser, ?string $event = 'user_block'): void {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }

    if ($apicUser !== NULL) {
      // in case moderation is on we need to run as admin
      // save the current user so we can switch back at the end
      $accountSwitcher = \Drupal::service('account_switcher');
      $originalUser = \Drupal::currentUser();
      if ((int) $originalUser->id() !== 1) {
        $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
      }
      if (is_string($apicUser)) {
        $apicUser = json_decode($apicUser, TRUE, 512, JSON_THROW_ON_ERROR);
      }

      // check if user already exists in Drupal DB
      $blockUser = new ApicUser();
      $blockUser->setUsername($apicUser['username']);
      $blockUser->setApicUserRegistryUrl($apicUser['user_registry_url']);

      if ($blockUser->getUsername() === NULL || $blockUser->getApicUserRegistryUrl() === NULL) {
        Drush::output()->writeln('Unable to check if user already exists because of missing data. Skipping user block.');
      }
      else {
        $userStorage = \Drupal::service('ibm_apim.user_storage');
        $user = $userStorage->load($blockUser);

        if ($user === NULL || $user === FALSE) {
          Drush::output()->writeln('User does not exist: ' . $apicUser['username']);
        }
        elseif ((int) $user->get('uid')->value === 1) {
          Drush::output()->writeln('admin so won\'t block this');
        }
        else {
          Drush::output()->writeln('Blocking apic user ' . $apicUser['username']);
          $user->set('status', 0);
          $user->save();
        }
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
   * @param $apicUser - The webhook JSON content
   * @param string|null $event - The event type
   *
   * @throws \JsonException
   *
   * @command ibm_apim-apicuser_unblock
   * @usage drush ibm_apim-apicuser_unblock [apicUser] [event]
   *   Unblocks an IBM APIC user
   * @aliases ubibmuser
   */
  public function drush_ibm_apim_apicuser_unblock($apicUser, ?string $event = 'user_unblock'): void {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }

    if ($apicUser !== NULL) {
      // in case moderation is on we need to run as admin
      // save the current user so we can switch back at the end
      $accountSwitcher = \Drupal::service('account_switcher');
      $originalUser = \Drupal::currentUser();
      if ((int) $originalUser->id() !== 1) {
        $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
      }
      if (is_string($apicUser)) {
        $apicUser = json_decode($apicUser, TRUE, 512, JSON_THROW_ON_ERROR);
      }

      // check if user already exists in Drupal DB
      $unblockUser = new ApicUser();
      $unblockUser->setUsername($apicUser['username']);
      $unblockUser->setApicUserRegistryUrl($apicUser['user_registry_url']);

      if ($unblockUser->getUsername() === NULL || $unblockUser->getApicUserRegistryUrl() === NULL) {
        Drush::output()->writeln('Unable to check if user already exists because of missing data. Skipping user unblock.');
      }
      else {
        $userStorage = \Drupal::service('ibm_apim.user_storage');
        $user = $userStorage->load($unblockUser);
        if ($user === NULL || $user === FALSE) {
          Drush::output()->writeln('User does not exist: ' . $apicUser['username']);
        }
        else {
          Drush::output()->writeln('Unblocking apic user ' . $apicUser['username']);
          $user->set('status', 1);
          $user->save();
        }
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
   * @param $apicUser - The webhook JSON content
   * @param string|null $event - The event type
   *
   * @throws \JsonException
   *
   * @command ibm_apim-apicuser_update
   * @usage drush ibm_apim-apicuser_update [apicUser] [event]
   *   Updates an IBM APIC user
   * @aliases uibmuser
   */
  public function drush_ibm_apim_apicuser_update($apicUser, ?string $event = 'user_update'): void {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }

    if ($apicUser !== NULL) {
      // in case moderation is on we need to run as admin
      // save the current user so we can switch back at the end
      $accountSwitcher = \Drupal::service('account_switcher');
      $originalUser = \Drupal::currentUser();
      if ((int) $originalUser->id() !== 1) {
        $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
      }
      if (is_string($apicUser)) {
        $apicUser = json_decode($apicUser, TRUE, 512, JSON_THROW_ON_ERROR);
      }


      // check if user already exists in Drupal DB
      $editUser = new ApicUser();
      $editUser->setUsername($apicUser['username']);
      $editUser->setApicUserRegistryUrl($apicUser['user_registry_url']);

      if ($editUser->getUsername() === NULL || $editUser->getApicUserRegistryUrl() === NULL) {
        Drush::output()->writeln('Unable to check if user already exists because of missing data. Skipping user update.');
      }
      else {
        $userStorage = \Drupal::service('ibm_apim.user_storage');
        $user = $userStorage->load($editUser);
        if ($user === NULL || $user === FALSE) {
          Drush::output()->writeln('User does not exist: ' . $apicUser['username']);
        }
        else {
          $editUser->setFirstname($apicUser['first_name']);
          $editUser->setLastname($apicUser['last_name']);
          if (isset($apicUser['email']) && $apicUser['email'] !== '') {
            $editUser->setMail($apicUser['email']);
          }
          if ($apicUser['url'] !== NULL) {
            $editUser->setUrl($apicUser['url']);
          }
          if ($apicUser['state'] !== NULL) {
            $editUser->setState($apicUser['state']);
          }

          $accountService = \Drupal::service('ibm_apim.account');
          Drush::output()->writeln('Updating apic user ' . $apicUser['username']);
          $accountService->updateLocalAccount($editUser);
        }
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
   * @param $ur - The webhook JSON content
   * @param string|null $event - The event type
   *
   * @throws \JsonException
   *
   * @command ibm_apim-user_registry_create
   * @usage drush ibm_apim-user_registry_create [ur] [event]
   *   Creates an IBM APIC user registry
   * @aliases cibmuserreg
   */
  public function drush_ibm_apim_user_registry_create($ur, ?string $event = 'user_registry_create'): void {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }
    if (isset($ur)) {
      if (is_string($ur)) {
        $ur = json_decode($ur, TRUE, 512, JSON_THROW_ON_ERROR);
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
   * @param $ur - The webhook JSON content
   * @param string|null $event - The event type
   *
   * @throws \JsonException
   *
   * @command ibm_apim-user_registry_update
   * @usage drush ibm_apim-user_registry_update [ur] [event]
   *   Updates an IBM APIC user registry
   * @aliases uibmuserreg
   */
  public function drush_ibm_apim_user_registry_update($ur, ?string $event = 'user_registry_update'): void {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }

    if (isset($ur)) {
      if (is_string($ur)) {
        $ur = json_decode($ur, TRUE, 512, JSON_THROW_ON_ERROR);
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
   * @param $ur - The webhook JSON content
   * @param string|null $event - The event type
   *
   * @throws \JsonException
   *
   * @command ibm_apim-user_registry_delete
   * @usage drush ibm_apim-user_registry_delete [ur] [event]
   *   Deletes an IBM APIC user registry
   * @aliases dibmuserreg
   */
  public function drush_ibm_apim_user_registry_delete($ur, ?string $event = 'user_registry_del'): void {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }

    if (isset($ur)) {
      if (is_string($ur)) {
        $ur = json_decode($ur, TRUE, 512, JSON_THROW_ON_ERROR);
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
   * @param $vx - The webhook JSON content
   * @param string|null $event - The event type
   *
   * @throws \JsonException
   *
   * @command ibm_apim-vendor_extension_create
   * @usage drush ibm_apim-vendor_extension_create [vx] [event]
   *   Creates an IBM APIC vendor extension
   * @aliases cibmvendext
   */
  public function drush_ibm_apim_vendor_extension_create($vx, ?string $event = 'vendor_extension_create'): void {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }
    if (isset($vx)) {
      if (is_string($vx)) {
        $vx = json_decode($vx, TRUE, 512, JSON_THROW_ON_ERROR);
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
   * @param $vx - The webhook JSON content
   * @param string|null $event - The event type
   *
   * @throws \JsonException
   *
   * @command ibm_apim-vendor_extension_update
   * @usage drush ibm_apim-vendor_extension_update [vx] [event]
   *   Updates an IBM APIC vendor extension
   * @aliases uibmvendext
   */
  public function drush_ibm_apim_vendor_extension_update($vx, ?string $event = 'vendor_extension_update'): void {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }

    if (isset($vx)) {
      if (is_string($vx)) {
        $vx = json_decode($vx, TRUE, 512, JSON_THROW_ON_ERROR);
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
   * @param $vx - The webhook JSON content
   * @param string|null $event - The event type
   *
   * @throws \JsonException
   *
   * @command ibm_apim-vendor_extension_delete
   * @usage drush ibm_apim-vendor_extension_delete [vx] [event]
   *   Deletes an IBM APIC vendor extension
   * @aliases dibmvendext
   */
  public function drush_ibm_apim_vendor_extension_delete($vx, ?string $event = 'vendor_extension_del'): void {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }

    if (isset($vx)) {
      if (is_string($vx)) {
        $vx = json_decode($vx, TRUE, 512, JSON_THROW_ON_ERROR);
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
   * @param $group - The webhook JSON content
   * @param string|null $event - The event type
   *
   * @throws \JsonException
   *
   * @command ibm_apim-group_create
   * @usage drush ibm_apim-group_create [group] [event]
   *   Creates an IBM APIC group
   * @aliases cibmgroup
   */
  public function drush_ibm_apim_group_create($group, ?string $event = 'group_create'): void {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }

    $accountSwitcher = \Drupal::service('account_switcher');
    $original_user = \Drupal::currentUser();
    if ($original_user->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }

    if (isset($group)) {
      if (is_string($group)) {
        $group = json_decode($group, TRUE, 512, JSON_THROW_ON_ERROR);
      }

      if (!isset($group['url']) || empty($group['url'])) {
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
   * @param $group - The webhook JSON content
   * @param string|null $event - The event type
   *
   * @throws \JsonException
   *
   * @command ibm_apim-group_update
   * @usage drush ibm_apim-group_update [group] [event]
   *   Updates an IBM APIC group
   * @aliases uibmgroup
   */
  public function drush_ibm_apim_group_update($group, ?string $event = 'group_update'): void {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }

    $accountSwitcher = \Drupal::service('account_switcher');
    $original_user = \Drupal::currentUser();
    if ($original_user->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }

    if (isset($group)) {
      if (is_string($group)) {
        $group = json_decode($group, TRUE, 512, JSON_THROW_ON_ERROR);
      }
      if (!isset($group['url']) || empty($group['url'])) {
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
   * @param $group - The webhook JSON content
   * @param string|null $event - The event type
   *
   * @throws \JsonException
   *
   * @command ibm_apim-group_delete
   * @usage drush ibm_apim-group_delete [group] [event]
   *   Deletes an IBM APIC group
   * @aliases dibmgroup
   */
  public function drush_ibm_apim_group_delete($group, ?string $event = 'group_del'): void {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }

    $accountSwitcher = \Drupal::service('account_switcher');
    $original_user = \Drupal::currentUser();
    if ($original_user->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }

    if (isset($group)) {
      if (is_string($group)) {
        $group = json_decode($group, TRUE, 512, JSON_THROW_ON_ERROR);
      }
      if (!isset($group['url']) || empty($group['url'])) {
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
   * @param $bill - The webhook JSON content
   * @param string|null $event - The event type
   *
   * @throws \JsonException
   *
   * @command ibm_apim-billing_create
   * @usage drush ibm_apim-billing_create [bill] [event]
   *   Creates an IBM APIC billing object
   * @aliases cibmbilling
   */
  public function drush_ibm_apim_billing_create($bill, ?string $event = 'billing_create'): void {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }
    if (isset($bill)) {
      if (is_string($bill)) {
        $bill = json_decode($bill, TRUE, 512, JSON_THROW_ON_ERROR);
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
   * @param $bill - The webhook JSON content
   * @param string|null $event - The event type
   *
   * @throws \JsonException
   *
   * @command ibm_apim-billing_update
   * @usage drush ibm_apim-billing_update [bill] [event]
   *   Updates an IBM APIC billing object
   * @aliases uibmbilling
   */
  public function drush_ibm_apim_billing_update($bill, ?string $event = 'billing_update'): void {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }

    if (isset($bill)) {
      if (is_string($bill)) {
        $bill = json_decode($bill, TRUE, 512, JSON_THROW_ON_ERROR);
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
   * @param $bill - The webhook JSON content
   * @param string|null $event - The event type
   *
   * @throws \JsonException
   *
   * @command ibm_apim-billing_delete
   * @usage drush ibm_apim-billing_delete [bill] [event]
   *   Deletes an IBM APIC billing object
   * @aliases dibmbilling
   */
  public function drush_ibm_apim_billing_delete($bill, ?string $event = 'billing_del'): void {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }

    if (isset($bill)) {
      if (is_string($bill)) {
        $bill = json_decode($bill, TRUE, 512, JSON_THROW_ON_ERROR);
      }
      if (!isset($bill['url'])) {
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
   * @param $integration - The webhook JSON content
   * @param string|null $event - The event type
   *
   * @throws \JsonException
   *
   * @command ibm_apim-integration_create
   * @usage drush ibm_apim-integration_create [integration] [event]
   *   Creates an IBM APIC payment method schema object.
   * @aliases cibmintegration
   */
  public function drush_ibm_apim_integration_create($integration, ?string $event = 'integration_create'): void {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }
    if (isset($integration)) {
      if (is_string($integration)) {
        $integration = json_decode($integration, TRUE, 512, JSON_THROW_ON_ERROR);
      }

      $existingHash = \Drupal::state()->get('ibm_apic_integration.hash');
      if (!isset($integration['hash'], $existingHash) || $integration['hash'] !== $existingHash) {
        if (!isset($integration['content']['url'])) {
          $integration['content']['url'] = 'unknown';
        }
        $paymentMethodSchemaService = \Drupal::service('ibm_apim.payment_method_schema');
        $paymentMethodSchemaService->update($integration['content']['url'], $integration['content']);
        // store the new hash
        if (isset($integration['hash'])) {
          \Drupal::state()->set('ibm_apic_integration.hash', $integration['hash']);
        }
      }
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__FUNCTION__, NULL);
    }
  }

  /**
   * @param $integration - The webhook JSON content
   * @param string|null $event - The event type
   *
   * @throws \JsonException
   *
   * @command ibm_apim-integration_update
   * @usage drush ibm_apim-integration_update [integration] [event]
   *   Updates an IBM APIC payment method schema object.
   * @aliases uibmintegration
   */
  public function drush_ibm_apim_integration_update($integration, ?string $event = 'integration_update'): void {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }

    if (isset($integration)) {
      if (is_string($integration)) {
        $integration = json_decode($integration, TRUE, 512, JSON_THROW_ON_ERROR);
      }
      $existingHash = \Drupal::state()->get('ibm_apic_integration.hash');
      if (!isset($integration['hash'], $existingHash) || $integration['hash'] !== $existingHash) {
        if (!isset($integration['content']['url'])) {
          $integration['content']['url'] = 'unknown';
        }
        $paymentMethodSchemaService = \Drupal::service('ibm_apim.payment_method_schema');
        $paymentMethodSchemaService->update($integration['content']['url'], $integration['content']);
        // store the new hash
        if (isset($integration['hash'])) {
          \Drupal::state()->set('ibm_apic_integration.hash', $integration['hash']);
        }
      }
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__FUNCTION__, NULL);
    }
  }

  /**
   * @param $integrations - The webhook JSON content, an array of objects
   * @param string|null $event - The event type
   *
   * @throws \JsonException
   *
   * @command ibm_apim-integration_updateall
   * @usage drush ibm_apim-integration_updateall [integrations] [event]
   *   Updates all IBM APIC payment method schema objects.
   * @aliases uallibmintegration
   */
  public function drush_ibm_apim_integration_updateall($integrations, ?string $event = 'integration_update_all'): void {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }
    if (is_string($integrations)) {
      $integrations = json_decode($integrations, TRUE, 512, JSON_THROW_ON_ERROR);
    }

    // we dont bother checking the hash for updateall since it will only be run if things have changed
    $paymentMethodSchemaService = \Drupal::service('ibm_apim.payment_method_schema');
    $paymentMethodSchemaService->updateAll($integrations['content']);
    // store the new hash
    if (isset($integrations['hash'])) {
      \Drupal::state()->set('ibm_apic_integration.hash', $integrations['hash']);
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__FUNCTION__, NULL);
    }
  }

  /**
   * @param $integration - The webhook JSON content
   * @param string|null $event - The event type
   *
   * @throws \JsonException
   *
   * @command ibm_apim-integration_delete
   * @usage drush ibm_apim-integration_delete [integration] [event]
   *   Deletes an IBM APIC payment method schema object.
   * @aliases dibmintegration
   */
  public function drush_ibm_apim_integration_delete($integration, ?string $event = 'integration_del'): void {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }

    if (isset($integration)) {
      if (is_string($integration)) {
        $integration = json_decode($integration, TRUE, 512, JSON_THROW_ON_ERROR);
      }
      $existingHash = \Drupal::state()->get('ibm_apic_integration.hash');
      if (!isset($integration['hash'], $existingHash) || $integration['hash'] !== $existingHash) {
        if (!isset($integration['content']['url'])) {
          $integration['content']['url'] = 'unknown';
        }
        $paymentMethodSchemaService = \Drupal::service('ibm_apim.payment_method_schema');
        $paymentMethodSchemaService->delete($integration['content']['url']);
        // store the new hash
        if (isset($integration['hash'])) {
          \Drupal::state()->set('ibm_apic_integration.hash', $integration['hash']);
        }
      }
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__FUNCTION__, NULL);
    }
  }

  /**
   * @param $integration - The webhook JSON content
   * @param string|null $event - The event type
   *
   * @throws \JsonException
   *
   * @command ibm_apim-integration_deleteall
   * @usage drush ibm_apim-integration_deleteall [integrations] [event]
   *   Deletes all IBM APIC payment method schema objects.
   * @aliases dallibmintegration
   */
  public function drush_ibm_apim_integration_deleteall($integration, ?string $event = 'integration_del_all'): void {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }

    $existingHash = \Drupal::state()->get('ibm_apic_integration.hash');
    if (!isset($integration['hash'], $existingHash) || $integration['hash'] !== $existingHash) {
      $paymentMethodSchemaService = \Drupal::service('ibm_apim.payment_method_schema');
      $paymentMethodSchemaService->deleteAll();
      // store the new hash
      if (isset($integration['hash'])) {
        \Drupal::state()->set('ibm_apic_integration.hash', $integration['hash']);
      }
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__FUNCTION__, NULL);
    }
  }

  /**
   * @param $perm
   * @param string|null $event
   *
   * @throws \JsonException
   */
  public function drush_ibm_apim_permission_create($perm, ?string $event = 'permission_create'): void {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }
    if (isset($perm)) {
      if (is_string($perm)) {
        $perm = json_decode($perm, TRUE, 512, JSON_THROW_ON_ERROR);
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
   * @param string|null $event
   *
   * @throws \JsonException
   */
  public function drush_ibm_apim_permission_update($perm, ?string $event = 'permission_update'): void {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }

    if (isset($perm)) {
      if (is_string($perm)) {
        $perm = json_decode($perm, TRUE, 512, JSON_THROW_ON_ERROR);
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
   * @param string|null $event
   *
   * @throws \JsonException
   */
  public function drush_ibm_apim_permission_delete($perm, ?string $event = 'permission_del'): void {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }

    if (isset($perm)) {
      if (is_string($perm)) {
        $perm = json_decode($perm, TRUE, 512, JSON_THROW_ON_ERROR);
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
   * @param string|null $event
   *
   * @throws \JsonException
   */
  public function drush_ibm_apim_tlsprofile_create($tlsProfile, ?string $event = 'tlsprofile_create'): void {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }
    if (isset($tlsProfile)) {
      if (is_string($tlsProfile)) {
        $tlsProfile = json_decode($tlsProfile, TRUE, 512, JSON_THROW_ON_ERROR);
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
   * @param string|null $event
   *
   * @throws \JsonException
   */
  public function drush_ibm_apim_tlsprofile_update($tlsProfile, ?string $event = 'tlsprofile_update'): void {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }

    if (isset($tlsProfile)) {
      if (is_string($tlsProfile)) {
        $tlsProfile = json_decode($tlsProfile, TRUE, 512, JSON_THROW_ON_ERROR);
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
   * @param string|null $event
   *
   * @throws \JsonException
   */
  public function drush_ibm_apim_tlsprofile_delete($tlsProfile, ?string $event = 'tlsprofile_del'): void {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }

    if (isset($tlsProfile)) {
      if (is_string($tlsProfile)) {
        $tlsProfile = json_decode($tlsProfile, TRUE, 512, JSON_THROW_ON_ERROR);
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
   * @param string|null $event
   *
   * @throws \JsonException
   */
  public function drush_ibm_apim_analytics_create($analytics, ?string $event = 'analytics_create'): void {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }
    if (isset($analytics)) {
      if (is_string($analytics)) {
        $analytics = json_decode($analytics, TRUE, 512, JSON_THROW_ON_ERROR);
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
   * @param string|null $event
   *
   * @throws \JsonException
   */
  public function drush_ibm_apim_analytics_update($analytics, ?string $event = 'analytics_update'): void {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }

    if (isset($analytics)) {
      if (is_string($analytics)) {
        $analytics = json_decode($analytics, TRUE, 512, JSON_THROW_ON_ERROR);
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
   * @param string|null $event
   *
   * @throws \JsonException
   */
  public function drush_ibm_apim_analytics_delete($analytics, ?string $event = 'analytics_del'): void {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }

    if (isset($analytics)) {
      if (is_string($analytics)) {
        $analytics = json_decode($analytics, TRUE, 512, JSON_THROW_ON_ERROR);
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
   *
   * @command ibm_apim-assert_config_uuids
   * @usage drush ibm_apim-assert_config_uuids
   *   Updates the UUIDs of any config files in the provided source dir (or /private/config/sync dir for the site by default) to match
   *   those on the running site.  This is needed to allow config from one site to be imported into another
   * @option string $source
   *   Source directory containing the config to check
   * @option string $extended_output
   *   Log additional output about the config uuids that get set
   * @aliases assert_config_uuids
   * @throws \Exception
   */
  public function drush_ibm_apim_assert_config_uuids(array $options = ['source' => self::REQ, 'extended_output' => self::REQ]) {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }

    $sourceDir = $options['source'];
    $extendedOutput = $options['extended_output'];

    if ($sourceDir !== '') {
      if (file_exists($sourceDir) && is_dir($sourceDir)) {
        $sync = new FileStorage($sourceDir);
        \Drupal::logger('ibm_apim')->info('Using provided source dir @sourceDir', ['@sourceDir' => $sourceDir]);
      }
      else {
        throw new \Exception('The provided source dir \'' . $sourceDir . '\' does not exist');
      }
    }
    else {
      $sync = \Drupal::service('config.storage.sync');
    }

    foreach ($sync->listAll() as $name) {
      $existing_config = \Drupal::configFactory()->get($name);
      if (!$existing_config->isNew() && ($uuid = $existing_config->get('uuid'))) {
        $data = $sync->read($name);
        $data['uuid'] = $uuid;
        $sync->write($name, $data);

        if ($extendedOutput !== '') {
          \Drupal::logger('ibm_apim')->info('Setting UUID of \'@name\' to \'@uuid\'', ['@name' => $name, '@uuid' => $uuid]);
        }
      }
      elseif ($extendedOutput !== '') {
        \Drupal::logger('ibm_apim')->info('The running site does not have any config with name \'@name\', not updating the UUID', ['@name' => $name]);
      }
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__FUNCTION__, NULL);
    }
  }

  /**
   * @param string $type - The artifact type, e.g. product
   * @param null $url - The artifact URL reference, e.g. /product/xyz/1
   *
   * @return string|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @command ibm_apim-content_check
   * @usage drush ibm_apim-content_check
   *   Check if a specific artifact is in the database
   * @aliases ibmcontentcheck
   */
  public function drush_ibm_apim_content_check(string $type = 'product', $url = NULL): ?string {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }
    $output = NULL;
    if (isset($url)) {
      if (mb_strpos($url, 'https://') !== 0) {
        // in case moderation is on we need to run as admin
        // save the current user so we can switch back at the end
        $accountSwitcher = \Drupal::service('account_switcher');
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
            $output = \Drupal::service('apic_app.application')->getApplicationAsJson($url);
            break;
          case 'consumerorg':
            $cOrgService = \Drupal::service('ibm_apim.consumerorg');
            $output = $cOrgService->getConsumerOrgAsJson($url);
            break;
          default:
            \Drupal::logger('ibm_apim')->error('There is no drush code to check content of type @type', ['@type' => $type]);
            break;
        }
        if (isset($originalUser) && (int) $originalUser->id() !== 1) {
          $accountSwitcher->switchBack();
        }
      }
      else {
        \Drupal::logger('ibm_apim')->warning('Content Check URL invalid @url', ['@url' => $url]);
      }
    }
    else {
      \Drupal::logger('ibm_apim')->warning('Content Check URL not set', []);
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__FUNCTION__, NULL);
    }
    return $output;
  }


  /**
   * Utility function to reset drupal static cache if memory reaches 70% of max
   *
   */
  function drush_ibm_apim_manage_memory_usage($memoryLimit, $uStartTime, $count) {
    static $last_time = 0;
    if ($last_time == 0) {
      $last_time = $uStartTime;
    }
    $memThreshold = 70;
    $memUsage = memory_get_usage();
    if (isset($memoryLimit) && $memoryLimit > 0) {
      $pctMem = round($memUsage / $memoryLimit * 100, 1);
      $memUsageMB = round($memUsage / 1024 / 1024, 1);
      $memoryLimitMB = round($memoryLimit / 1024 / 1024, 1);

      $time_now = microtime(true);
      $elapsed_time = $time_now - $uStartTime;
      $elapsed_time_last_10 = $time_now - $last_time;
      $last_time = $time_now;

      if ($pctMem > $memThreshold) {
        fprintf(STDERR, "Processed %d objects in %f seconds (last 10 in %f seconds). Memory usage %.2f%%/%d%% (%d/%d bytes). Resetting Drupal cache.\n", $count, $elapsed_time, $elapsed_time_last_10, $pctMem, $memThreshold, $memUsageMB, $memoryLimitMB);

        // Only need to do one type of storage here as they all share the same memory cache instance
        \Drupal::entityTypeManager()->getStorage('node')->memoryCache->deleteAll();

        // As well as the entity cache we also need to clear the type and field caches
        \Drupal::entityTypeManager()->clearCachedDefinitions();
        \Drupal::getContainer()->get('entity_field.manager')->clearCachedFieldDefinitions();
        gc_collect_cycles();

        $memUsage = memory_get_usage();
        $pctMem = round($memUsage / $memoryLimit * 100, 1);
        $memUsageMB = round($memUsage / 1024 / 1024, 1);
        $memoryLimitMB = round($memoryLimit / 1024 / 1024, 1);

        $elapsed_time = microtime(true) - $uStartTime;
        fprintf(STDERR, "After Drupal Cache reset: Processed %d objects in %f seconds (last 10 in %f seconds). Memory usage %.2f%%/%d%% (%d/%d bytes)\n", $count, $elapsed_time, $elapsed_time_last_10, $pctMem, $memThreshold, $memUsageMB, $memoryLimitMB);
      } else {
        if ($last_time)
        fprintf(STDERR, "Processed %d objects in %f seconds (last 10 in %f seconds). Memory usage %.2f%%/%d%% (%d/%d bytes).\n", $count, $elapsed_time, $elapsed_time_last_10, $pctMem, $memThreshold, $memUsageMB, $memoryLimitMB);
      }
    }
  }


  /**
   * @param $value
   *
   * @return int
   */
  public function drush_ibm_apim_convertToBytes($value): int {
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
      /** @noinspection PhpMissingBreakStatementInspection */
      // no break
      case 'g':
        $max *= 1024;
      /** @noinspection PhpMissingBreakStatementInspection */
      // no break
      case 'm':
        $max *= 1024;
      // no break
      case 'k':
        $max *= 1024;
    }

    return $max;
  }

  /**
   * Asserts that the apropriate value is set for the index_directly property
   * on the default search api index according to the number of nodes in the site.
   * Disabling index directly on sites with lots of nodes is needed to prevent
   * php from going OOM after processing a snapshot
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function drush_ibm_apim_assert_index_directly_value(): void {
    $query = \Drupal::entityQuery('node');
    $result = $query->count()->accessCheck()->execute();
    $count = (int) $result;
    if ($count > 50) {
      $this->drush_ibm_apim_index_directly(FALSE);
    } else {
      $this->drush_ibm_apim_index_directly(TRUE);
    }
  }

  /**
   * Sets the value of the index_directly property of the search_api module's
   * default_index.
   *
   * @param $indexDirectly
   * @param $count
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function drush_ibm_apim_index_directly($indexDirectly): void {
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('search_api')) {
      $index = Index::load('default_index');
      if (isset($index) && !empty($index)) {
        if ($index->getOption('index_directly') !== $indexDirectly) {
          $index->setOption('index_directly', $indexDirectly)->save();

          \Drupal::logger('ibm_apim')->info('Set index_directly value to @value', ['@value' => $indexDirectly ? 'true' : 'false']);
        }
        else {
          \Drupal::logger('ibm_apim')
            ->notice('index_directly value @value matched, no action needed', ['@value' => $indexDirectly ? 'true' : 'false']);
        }
      }
    }
  }

  /**
   * Purge all blocklisted modules installed in a site.
   * The modules will be uninstalled then deleted from the file system.
   *
   * @command ibm_apim-purge_blocklist_modules
   * @usage drush ibm_apim-purge_blocklist_modules
   *   Purge any blocklisted modules (uninstall + delete).
   * @aliases purge_blocklist
   */
  public function drush_ibm_apim_purge_blocklist_modules(): void {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }
    $moduleService = \Drupal::service('ibm_apim.module');
    $result = $moduleService->purgeBlockListedModules();
    if ($result) {
      \Drupal::logger('ibm_apim')->info('All blocklisted modules purged.');
    }
    else {
      \Drupal::logger('ibm_apim')->error('Error purging blocklisted modules.');
    }
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__FUNCTION__, NULL);
    }
  }

  /**
   * If the site URL changes then this will update any content
   * that needs it
   *
   * @command ibm_apim-update_site_url
   * @usage drush ibm_apim-update_site_url
   *   Update content when the site URL changes
   * @aliases updatesiteurl
   */
  public function drush_ibm_apim_update_site_url(): void {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }

    // this is an evil hack to update the site URLs in content that needs it
    // this has to be done as part of a user browsing session since drush doesnt know what the site URL is
    // so this sets a state variable and then the AdminMessagesBlock does the actual updating.
    \Drupal::state()->set('ibm_apim.update_site_url', TRUE);

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__FUNCTION__, NULL);
    }
  }

  /**
   * We need the translations to have been loaded before this function is called
   * So created a custom drush command for it
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @command ibm_apim-update_block_translations
   * @usage drush ibm_apim-update_block_translations
   *   Update block translations after new strings loaded
   * @aliases updateblocktranslations
   */
  public function drush_ibm_apim_update_block_translations(): void {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }

    $path = __DIR__;
    require_once $path . '/../../ibm_apim.emptycontent.inc';
    require_once $path . '/../../../../profiles/apim_profile/apim_profile.homepage.inc';
    require_once $path . '/../../../../profiles/apim_profile/apim_profile.import_nodes.inc';
    ibm_apim_update_no_content_blocks();
    if (function_exists('apim_profile_update_homepage_blocks')) {
      apim_profile_update_homepage_blocks();
    }
    if (function_exists('apim_profile_update_forumsidebar_block')) {
      apim_profile_update_forumsidebar_block();
    }
    if (function_exists('apim_profile_update_nodes')) {
      apim_profile_update_nodes();
    }
    if (function_exists('apim_profile_update_menu_links')) {
      apim_profile_update_menu_links();
    }
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__FUNCTION__, NULL);
    }
  }

  /**
   * Disable IP based security features
   *
   * @command ibm_apim-disable-ipsecurity
   * @usage drush ibm_apim-disable-ipsecurity
   *   Disable IP based security features in IBM Cloud
   * @aliases disableipsec
   */
  public function drush_ibm_apim_disable_ipsecurity(): void {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }
    \Drupal::service('ibm_apim.site_config')->disableIPSecurityFeatures();
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__FUNCTION__, NULL);
    }
  }

  /**
   * Compile the SCSS for a custom theme
   *
   * @command ibm_apim-compile-theme-scss
   * @usage drush ibm_apim-compile-theme-scss
   *   Compile the SCSS for a custom theme
   * @param $themeName
   * @aliases compilescss
   */
  public function drush_ibm_apim_compile_theme_scss($themeName): void {
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_entry_trace(__FUNCTION__, NULL);
    }
    if (isset($themeName)) {
      $themeHandler = \Drupal::service('theme_handler');
      if ($themeHandler->themeExists($themeName)) {
      $themeController = new IbmApimThemeInstallController(
        \Drupal::service('ibm_apim.utils'),
        \Drupal::service('theme_handler'),
        \Drupal::service('extension.list.theme'),
        \Drupal::service('theme_installer'),
        \Drupal::service('config.factory'),
        \Drupal::service('messenger'));
      $themeController->compile_scss($themeName);
      } else {
        \Drupal::logger('ibm_apim')->info('This function can only be used for enabled themes.');
      }
    }
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__FUNCTION__, NULL);
    }
  }

  /**
   * Clean Drush exit
   */
  public function exitClean(): void {
    Runtime::setCompleted();
    exit(0);
  }

}
