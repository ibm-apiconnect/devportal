<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2024
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\apic_app\Commands;

use Drupal\Core\Database\Database;
use Drupal\Core\Session\UserSession;
use Drupal\node\Entity\Node;
use Drush\Commands\DrushCommands;


/**
 * Class ApicAppCommands.
 *
 * @package Drupal\apic_app\Commands
 */
class ApicAppCommands extends DrushCommands {

  /**
   * This function is a blunt tool that deletes all applications in a site and is probably not the function
   * you are looking for! See drush_apic_app_delete or other functions to delete applications in a
   * controlled manner.
   *
   * It deletes the nodes directly and will not trigger hooks or roles.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @command apic-app-cleanse-drush-command
   * @usage drush apic-app-cleanse-drush-command
   *   Clears the application entries back to a clean state.
   * @aliases cleanse_applications
   */
  public function drush_apic_app_cleanse_drush_command(): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = \Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'application']);

    foreach ($nodes as $node) {
      $node->delete();
    }
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }
    \Drupal::logger('apic_app')->info('All application entries deleted.');
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $content
   * @param $event
   * @param $func
   *
   * @throws \JsonException
   */
  public function drush_apic_app_createOrUpdate($content, $event, $func): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    if ($content !== NULL) {

      // if we receive an application  with no credentials it is invalid. This is likely because we receive an
      // app_update webhook on application delete, which is superfluous so we can just drop this.
      if (isset($content['app_credentials']) && \sizeof($content['app_credentials']) === 0) {
        \Drupal::logger('apic_app')->warning('Drush app_createOrUpdate - no credentials in application @app, skipping', [
          '@app' => $content['id'],
        ]);
      }
      else {
        $time_start = microtime(true);
        // in case moderation is on we need to run as admin
        // save the current user so we can switch back at the end
        $accountSwitcher = \Drupal::service('account_switcher');
        $originalUser = \Drupal::currentUser();
        if ((int) $originalUser->id() !== 1) {
          $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
        }
        if (is_string($content)) {
          $content = json_decode($content, TRUE, 512, JSON_THROW_ON_ERROR);
        }
        $ref = $content['id'];
        $createdOrUpdated = \Drupal::service('apic_app.application')->createOrUpdate($content, $event, NULL);

        $time_end = microtime(true);
        $execution_time = (microtime(true) - $time_start);

        if ($createdOrUpdated === 'created') {
          ibm_apim_snapshot_debug("Drush %s created Application '%s2' in %f seconds\n", [ '%s' => $func, '%s2' => $ref, '%f' => $execution_time]);
        }
        else if ($createdOrUpdated === 'updated') {
          ibm_apim_snapshot_debug("Drush %s updated existing Application '%s2' in %f seconds\n", [ '%s' => $func, '%s2' => $ref, '%f' => $execution_time]);
        }
        $moduleHandler = \Drupal::service('module_handler');
        if ($func !== 'MassUpdate' && $moduleHandler->moduleExists('views')) {
          views_invalidate_cache();
        }
        if ((int) $originalUser->id() !== 1) {
          $accountSwitcher->switchBack();
        }
      }
    }
    else {
      \Drupal::logger('apic_app')->error('Drush @func No application provided', ['@func' => $func]);
    }
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $app
   * @param string|null $event
   *
   * @throws \JsonException
   * @command apic-app-create
   * @usage drush apic-app-create [app] [event]
   *   Creates an application.
   * @aliases capp
   */
  public function drush_apic_app_create($app, ?string $event = 'app_create'): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    $this->drush_apic_app_createOrUpdate($app, $event, 'CreateApplication');
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $app
   * @param string|null $event
   *
   * @throws \JsonException
   * @command apic-app-update
   * @usage drush apic-app-update [app] [event]
   *   Updates an application.
   * @aliases uapp
   */
  public function drush_apic_app_update($app, ?string $event = 'app_update'): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    $this->drush_apic_app_createOrUpdate($app, $event, 'UpdateApplication');
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $app
   * @param string|null $event
   *
   * @throws \JsonException
   *
   * @command apic-app-delete
   * @usage drush apic-app-delete [id] [event]
   *   Deletes an application.
   * @aliases dapp
   */
  public function drush_apic_app_delete($app, ?string $event = 'app_del'): void {
    ibm_apim_entry_trace(__FUNCTION__);
    if ($app !== NULL) {
      // in case moderation is on we need to run as admin
      // save the current user so we can switch back at the end
      $accountSwitcher = \Drupal::service('account_switcher');
      $originalUser = \Drupal::currentUser();
      if ((int) $originalUser->id() !== 1) {
        $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
      }

      if (is_string($app)) {
        $app = json_decode($app, TRUE, 512, JSON_THROW_ON_ERROR);
      }

      $rc = \Drupal::service('apic_app.application')->deleteById($app['id'], $event);
      if ($rc === TRUE) {
        \Drupal::logger('apic_app')->info('Drush DeleteApplication deleted application @app', ['@app' => $app['id']]);
        $moduleHandler = \Drupal::service('module_handler');
        if ($moduleHandler->moduleExists('views')) {
          views_invalidate_cache();
        }
      }
      else {
        \Drupal::logger('apic_app')->warning('Drush DeleteApplication could not find application @app', ['@app' => $app['id']]);
      }
      if ((int) $originalUser->id() !== 1) {
        $accountSwitcher->switchBack();
      }
    }
    else {
      \Drupal::logger('apic_app')->error('Drush DeleteApplication No application ID provided');
    }
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $subInput
   * @param $event
   * @param $func
   *
   * @throws \JsonException
   */
  public function drush_apic_app_createOrUpdatesub($subInput, $event, $func): void {
    ibm_apim_entry_trace(__FUNCTION__, $subInput);
    $time_start = microtime(true);
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = \Drupal::service('account_switcher');
    $subService = \Drupal::service('apic_app.subscriptions');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }
    if ($subInput !== NULL) {
      if (is_string($subInput)) {
        $subInput = json_decode($subInput, TRUE, 512, JSON_THROW_ON_ERROR);
      }
      $ref = $subInput['id'];
      $createdOrUpdated = $subService->createOrUpdate($subInput);

      if ($createdOrUpdated !== 'created' && $createdOrUpdated !== 'updated' && $createdOrUpdated !== 'hashMatch') {
        throw new \Exception('Could not create/update/hashMatch subscription');
      }

      $time_end = microtime(true);
      $execution_time = (microtime(true) - $time_start);

      if ($createdOrUpdated === 'created') {
        ibm_apim_snapshot_debug("Drush %s created Subscription '%s2' in %f seconds\n", [ '%s' => $func, '%s2' => $ref, '%f' => $execution_time]);
      }
      else if ($createdOrUpdated === 'updated') {
        ibm_apim_snapshot_debug("Drush %s updated existing Subscription '%s2' in %f seconds\n", [ '%s' => $func, '%s2' => $ref, '%f' => $execution_time]);
      }
    }
    else {
      \Drupal::logger('apic_app')->error('Drush @func No subscription provided', ['@func' => $func]);
    }
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $subInput
   * @param string|null $event
   *
   * @throws \JsonException
   *
   * @command apic-app-createsub
   * @usage drush apic-app-createsub [sub] [event]
   *   Creates a subscription.
   * @aliases csub
   */
  public function drush_apic_app_createsub($subInput, ?string $event = 'create_sub'): void {
    ibm_apim_entry_trace(__FUNCTION__, $subInput);
    $this->drush_apic_app_createOrUpdatesub($subInput, $event, 'CreateSubscription');
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $subInput
   * @param string|null $event
   *
   * @throws \JsonException
   *
   * @command apic-app-updatesub
   * @usage drush apic-app-updatesub [sub] [event]
   *   Updates a subscription.
   * @aliases csub
   */
  public function drush_apic_app_updatesub($subInput, ?string $event = 'update_sub'): void {
    ibm_apim_entry_trace(__FUNCTION__, $subInput);
    $this->drush_apic_app_createOrUpdatesub($subInput, $event, 'UpdateSubscription');
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $subInput
   * @param string|null $event
   *
   *
   * @command apic-app-deletesub
   * @usage drush apic-app-deletesub [id] [event]
   *   Deletes a subscription.
   * @aliases dsub
   */
  public function drush_apic_app_deletesub($subInput, ?string $event = 'delete_sub'): void {
    ibm_apim_entry_trace(__FUNCTION__, $subInput);
    $appUrl = $subInput['app_url'];
    $subId = $subInput['id'];
    if ($appUrl !== NULL && $subId !== NULL) {
      // in case moderation is on we need to run as admin
      // save the current user so we can switch back at the end
      $accountSwitcher = \Drupal::service('account_switcher');
      $originalUser = \Drupal::currentUser();
      if ((int) $originalUser->id() !== 1) {
        $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
      }
      $subService = \Drupal::service('apic_app.subscriptions');
      $subService->delete($subId);

      if (isset($subInput['deleted_at'])) {
        $timestamp = $subInput['deleted_at'];
      }
      else {
        $timestamp = NULL;
      }

      // Get the application entity id
      $appEntityId = NULL;
      $result = Database::getConnection()
        ->query("SELECT entity_id from node__apic_url WHERE apic_url_value = :appUrl", [':appUrl' => $subInput['app_url']]);
      if ($result && $record = $result->fetch()) {
        $appEntityId = $record->entity_id;
      }
      $subInput['uuid'] = $subId;
      $subInput['consumerorg_url'] = $subInput['consumer_org_url'];
      $subService->addEventLog('delete', $timestamp, $appEntityId, $subInput, $subInput['deleted_by']);

      \Drupal::logger('apic_app')->info('Subscription deleted.');
      if ((int) $originalUser->id() !== 1) {
        $accountSwitcher->switchBack();
      }
    }
    else {
      \Drupal::logger('apic_app')->error('No subscription provided');
    }
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param array $apps
   * @param string|null $event
   *
   * @throws \JsonException
   * @command apic-app-massupdate
   * @usage drush apic-app-massupdate [apps] [event]
   *   Mass updates a list of applications.
   * @aliases mapp
   */
  public function drush_apic_app_massupdate(array $apps = [], ?string $event = 'content_refresh'): void {
    ibm_apim_entry_trace(__FUNCTION__, count($apps));
    if (!empty($apps)) {
      foreach ($apps as $app) {
        $this->drush_apic_app_createOrUpdate($app, $event, 'MassUpdate');
      }
    }
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param array $subs
   * @param string|null $event
   *
   * @throws \JsonException
   *
   * @command apic-app-sub-massupdate
   * @usage drush apic-app-sub-massupdate [subs] [event]
   *   Mass updates a list of subscriptions.
   * @aliases mapp
   */
  public function drush_apic_app_sub_massupdate(array $subs = [], ?string $event = 'content_refresh'): void {
    ibm_apim_entry_trace(__FUNCTION__, count($subs));
    if (!empty($subs)) {
      foreach ($subs as $sub) {
        $this->drush_apic_app_createOrUpdatesub($sub, $event, 'MassUpdate');
      }
    }
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param array $appUrls
   *
   * @throws \JsonException
   *
   * @command apic-app-tidy
   * @usage drush apic-app-tiday [appUrls]
   *   Tidies the list of applications to ensure consistency with APIM.
   *   [appUrls] - The JSON array of app URLs, with list of subs per appUrl as a string.
   * @aliases tapp
   */
  public function drush_apic_app_tidy(array $appUrls = []): void {
    ibm_apim_entry_trace(__FUNCTION__, count($appUrls));
    $appUrls = json_decode($appUrls, TRUE, 512, JSON_THROW_ON_ERROR);
    if (!empty($appUrls)) {
      $nids = [];
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'application')
        ->condition('apic_url.value', $appUrls, 'NOT IN');
      $results = $query->accessCheck()->execute();
      if ($results !== NULL) {
        foreach ($results as $item) {
          $nids[] = $item;
        }
      }

      foreach ($nids as $nid) {
        \Drupal::service('apic_app.application')->deleteNode($nid, 'content_refresh_tidy');
      }
    }
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $cred
   * @param string|null $event
   *
   * @throws \JsonException
   *
   * @command apic-app-createcred
   * @usage drush apic-app-createcred [cred] [event]
   *   Creates an application credential.
   * @aliases ccred
   */
  public function drush_apic_app_createcred($cred, ?string $event = 'cred_create'): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    $this->drush_apic_app_createOrUpdateCred($cred, $event, 'CreateApplication');
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $cred
   * @param string|null $event
   *
   * @throws \JsonException
   *
   * @command apic-app-updatecred
   * @usage drush apic-app-updatecred [cred] [event]
   *   Updates an application credential.
   * @aliases ucred
   */
  public function drush_apic_app_updatecred($cred, ?string $event = 'cred_update'): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    $this->drush_apic_app_createOrUpdateCred($cred, $event, 'UpdateApplication');
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $cred
   * @param string|null $event
   *
   * @command apic-app-deletecred
   * @usage drush apic-app-deletecred [cred] [event]
   *   Deletes an application credential.
   * @aliases dcred
   */
  public function drush_apic_app_deletecred($cred, ?string $event = 'cred_del'): void {
    ibm_apim_entry_trace(__FUNCTION__);
    if ($cred !== NULL) {
      // in case moderation is on we need to run as admin
      // save the current user so we can switch back at the end
      $accountSwitcher = \Drupal::service('account_switcher');
      $originalUser = \Drupal::currentUser();
      if ((int) $originalUser->id() !== 1) {
        $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
      }

      if (isset($cred['app_url'], $cred['id'])) {
        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'application');
        $query->condition('apic_url.value', $cred['app_url']);

        $nids = $query->accessCheck()->execute();

        if (isset($nids) && !empty($nids)) {
          $credsService = \Drupal::service('apic_app.credentials');
          $nid = array_shift($nids);
          $node = Node::load($nid);
          $credsService->deleteCredentials($node, $cred['id']);
        }
        else {
          \Drupal::logger('apic_app')->error('Drush @func Node not found for @app_url', [
            '@func' => 'drush_apic_app_deletecred',
            '@app_url' => $cred['app_url'],
          ]);
        }
      }
      else {
        \Drupal::logger('apic_app')->error('Drush @func app_url or id missing', ['@func' => 'drush_apic_app_deletecred']);
      }

      if ((int) $originalUser->id() !== 1) {
        $accountSwitcher->switchBack();
      }
    }
    else {
      \Drupal::logger('apic_app')->error('Drush @func No credential provided', ['@func' => 'drush_apic_app_deletecred']);
    }

    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $cred
   * @param $event
   * @param $func
   *
   * @throws \JsonException
   */
  public function drush_apic_app_createOrUpdateCred($cred, $event, $func): void {
    ibm_apim_entry_trace(__FUNCTION__, $cred);
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = \Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }
    if ($cred !== NULL) {
      if (is_string($cred)) {
        $cred = json_decode($cred, TRUE, 512, JSON_THROW_ON_ERROR);
      }
      if (isset($cred['app_url'], $cred['id'])) {
        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'application');
        $query->condition('apic_url.value', $cred['app_url']);

        $nids = $query->accessCheck()->execute();

        if (isset($nids) && !empty($nids)) {
          $credsService = \Drupal::service('apic_app.credentials');
          $nid = array_shift($nids);
          $node = Node::load($nid);
          $credsService->createOrUpdateSingleCredential($node, $cred);
        }
        else {
          \Drupal::logger('apic_app')->error('Drush @func Node not found for @app_url', [
            '@func' => 'drush_apic_app_createOrUpdateCred',
            '@app_url' => $cred['app_url'],
          ]);
        }
      }
      else {
        \Drupal::logger('apic_app')->error('Drush @func app_url or id missing', ['@func' => 'drush_apic_app_createOrUpdateCred']);
      }
    }
    else {
      \Drupal::logger('apic_app')->error('Drush @func No credential provided', ['@func' => $func]);
    }
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

}
