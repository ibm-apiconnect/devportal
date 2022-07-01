<?php
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

use Drupal\Core\Database\Database;
use Drupal\Core\Session\UserSession;
use Drupal\ibm_event_log\ApicType\ApicEvent;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

function apic_app_drush_command(): array {

  $items['apic-app-cleanse-drush-command'] = [
    'description' => 'Clears the application entries back to a clean state.',
    'aliases' => ['cleanse_applications'],
  ];
  $items['apic-app-update'] = [
    'description' => 'Updates an application',
    'aliases' => ['uapp'],
    'arguments' => [
      'event' => 'The event type',
      'app' => 'The JSON application payload',
    ],
  ];
  $items['apic-app-create'] = [
    'description' => 'Creates an application',
    'aliases' => ['capp'],
    'arguments' => [
      'event' => 'The event type',
      'app' => 'The JSON application payload',
    ],
  ];
  $items['apic-app-delete'] = [
    'description' => 'Deletes an application',
    'aliases' => ['dapp'],
    'arguments' => [
      'event' => 'The event type',
      'id' => 'The application id',
    ],
  ];
  $items['apic-app-createsub'] = [
    'description' => 'Creates a subscription',
    'aliases' => ['csub'],
    'arguments' => [
      'event' => 'The event type',
      'app' => 'The JSON subscription payload',
    ],
  ];
  $items['apic-app-updatesub'] = [
    'description' => 'Updates a subscription',
    'aliases' => ['usub'],
    'arguments' => [
      'event' => 'The event type',
      'app' => 'The JSON subscription payload',
    ],
  ];
  $items['apic-app-deletesub'] = [
    'description' => 'Deletes a subscription',
    'aliases' => ['dsub'],
    'arguments' => [
      'event' => 'The event type',
      'id' => 'The subscription id',
    ],
  ];
  $items['apic-app-massupdate'] = [
    'description' => 'Mass updates a list of applications',
    'aliases' => ['mapp'],
    'arguments' => [
      'apps' => 'The JSON array of applications',
      'event' => 'The event type',
    ],
  ];
  $items['apic-app-sub-massupdate'] = [
    'description' => 'Mass updates a list of subscriptions',
    'aliases' => ['msub'],
    'arguments' => [
      'subs' => 'The JSON array of subscriptions',
      'event' => 'The event type',
    ],
  ];
  $items['apic-app-tidy'] = [
    'description' => 'Tidies the list of applications to ensure consistency with APIm',
    'aliases' => ['tapp'],
    'arguments' => [
      'appUrls' => 'The JSON array of app URLs as a string',
    ],
  ];
  $items['apic-app-sub-tidy'] = [
    'description' => 'Tidies the list of subscriptions to ensure consistency with APIm',
    'aliases' => ['tsub'],
    'arguments' => [
      'appUrls' => 'The JSON array of app URLs, with list of subs per appUrl as a string',
    ],
  ];
  $items['apic-app-createcred'] = [
    'description' => 'Creates an application credential',
    'aliases' => ['ccred'],
    'arguments' => [
      'event' => 'The event type',
      'cred' => 'The JSON credential payload',
    ],
  ];
  $items['apic-app-updatecred'] = [
    'description' => 'Updates an application credential',
    'aliases' => ['ucred'],
    'arguments' => [
      'event' => 'The event type',
      'cred' => 'The JSON credential payload',
    ],
  ];
  $items['apic-app-deletecred'] = [
    'description' => 'Deletes an application credential',
    'aliases' => ['dcred'],
    'arguments' => [
      'event' => 'The event type',
      'cred' => 'The credential payload',
    ],
  ];

  return $items;
}

/**
 * This function is a blunt tool that deletes all applications in a site and is probably not the function
 * you are looking for! See drush_apic_app_delete or other functions to delete applications in a
 * controlled manner.
 *
 * It deletes the nodes directly and will not trigger hooks or roles.
 *
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function drush_apic_app_cleanse_drush_command() {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = Drupal::service('account_switcher');
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
  drush_log(dt('All application entries deleted.'), 'success');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $content
 * @param $event
 * @param $func
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 * @throws \Drupal\Core\Entity\EntityStorageException
 * @throws \JsonException
 */
function drush_apic_app_createOrUpdate($content, $event, $func) {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  if ($content !== NULL) {

    // if we receive an application  with no credentials it is invalid. This is likely because we receive an
    // app_update webhook on application delete, which is superfluous so we can just drop this.
    if (isset($content['app_credentials']) && \sizeof($content['app_credentials']) === 0) {
      drush_log(dt('Drush app_createOrUpdate - no credentials in application @app, skipping', [
        '@app' => $content['id'],
      ]), 'warning');
    }
    else {
      // in case moderation is on we need to run as admin
      // save the current user so we can switch back at the end
      $accountSwitcher = Drupal::service('account_switcher');
      $originalUser = \Drupal::currentUser();
      if ((int) $originalUser->id() !== 1) {
        $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
      }
      if (is_string($content)) {
        $content = json_decode($content, TRUE, 512, JSON_THROW_ON_ERROR);
      }
      $ref = $content['id'];
      $createdOrUpdated = \Drupal::service('apic_app.application')->createOrUpdate($content, $event, NULL);
      if ($createdOrUpdated) {
        drush_log(dt('Drush @func created application @app', [
          '@func' => $func,
          '@app' => $ref,
        ]), 'success');
      }
      else {
        drush_log(dt('Drush @func updated existing application @app', [
          '@func' => $func,
          '@app' => $ref,
        ]), 'success');
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
    drush_log(dt('Drush @func No application provided', ['@func' => $func]), 'error');
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $app
 * @param string $event
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 * @throws \Drupal\Core\Entity\EntityStorageException
 * @throws \JsonException
 */
function drush_apic_app_create($app, $event = 'app_create') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  drush_apic_app_createOrUpdate($app, $event, 'CreateApplication');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $app
 * @param string $event
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 * @throws \Drupal\Core\Entity\EntityStorageException
 * @throws \JsonException
 */
function drush_apic_app_update($app, $event = 'app_update') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  drush_apic_app_createOrUpdate($app, $event, 'UpdateApplication');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $app
 * @param string $event
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 * @throws \JsonException
 */
function drush_apic_app_delete($app, $event = 'app_del') {
  ibm_apim_entry_trace(__FUNCTION__);
  if ($app !== NULL) {
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }

    if (is_string($app)) {
      $app = json_decode($app, TRUE, 512, JSON_THROW_ON_ERROR);
    }

    $rc = \Drupal::service('apic_app.application')->deleteById($app['id'], $event);
    if ($rc === TRUE) {
      drush_log(dt('Drush DeleteApplication deleted application @app', ['@app' => $app['id']]), 'success');
      $moduleHandler = \Drupal::service('module_handler');
      if ($moduleHandler->moduleExists('views')) {
        views_invalidate_cache();
      }
    }
    else {
      drush_log(dt('Drush DeleteApplication could not find application @app', ['@app' => $app['id']]), 'warning');
    }
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }
  }
  else {
    drush_log(dt('Drush DeleteApplication No application ID provided'), 'error');
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $subInput
 * @param $event
 * @param $func
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 * @throws \JsonException
 */
function drush_apic_app_createOrUpdatesub($subInput, $event, $func) {
  ibm_apim_entry_trace(__FUNCTION__, $subInput);
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
    if ($createdOrUpdated) {
      drush_log(dt('Drush @func created subscription @sub', [
        '@func' => $func,
        '@sub' => $ref,
      ]), 'success');
    }
    else {
      drush_log(dt('Drush @func updated existing subscription @sub', [
        '@func' => $func,
        '@sub' => $ref,
      ]), 'success');
    }
  }
  else {
    drush_log(dt('Drush @func No subscription provided', ['@func' => $func]), 'error');
  }
  if ((int) $originalUser->id() !== 1) {
    $accountSwitcher->switchBack();
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $subInput
 * @param string $event
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 * @throws \JsonException
 */
function drush_apic_app_createsub($subInput, $event = 'create_sub') {
  ibm_apim_entry_trace(__FUNCTION__, $subInput);
  drush_apic_app_createOrUpdatesub($subInput, $event, 'CreateSubscription');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $subInput
 * @param string $event
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 * @throws \JsonException
 */
function drush_apic_app_updatesub($subInput, $event = 'update_sub') {
  ibm_apim_entry_trace(__FUNCTION__, $subInput);
  drush_apic_app_createOrUpdatesub($subInput, $event, 'UpdateSubscription');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $subInput
 * @param string $event
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function drush_apic_app_deletesub($subInput, $event = 'delete_sub') {
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
    $appEntityId = null;
    $result = Database::getConnection()
      ->query("SELECT entity_id from node__apic_url WHERE apic_url_value = :appUrl", [':appUrl' => $subInput['app_url'] ]);
    if ($result && $record = $result->fetch()) {
      $appEntityId = $record->entity_id;
    }
    $subInput['uuid'] = $subId;
    $subInput['consumerorg_url'] = $subInput['consumer_org_url'];
    $subService->addEventLog('delete', $timestamp, $appEntityId, $subInput, $subInput['deleted_by']);

    drush_log(dt('Subscription deleted.'), 'success');
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }
  }
  else {
    drush_log(dt('No subscription provided'), 'error');
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param array $apps
 * @param string $event
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 * @throws \Drupal\Core\Entity\EntityStorageException
 * @throws \JsonException
 */
function drush_apic_app_massupdate($apps = [], $event = 'content_refresh') {
  ibm_apim_entry_trace(__FUNCTION__, count($apps));
  if (!empty($apps)) {
    foreach ($apps as $app) {
      drush_apic_app_createOrUpdate($app, $event, 'MassUpdate');
    }
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param array $subs
 * @param string $event
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 * @throws \JsonException
 */
function drush_apic_app_sub_massupdate($subs = [], $event = 'content_refresh') {
  ibm_apim_entry_trace(__FUNCTION__, count($subs));
  if (!empty($subs)) {
    foreach ($subs as $sub) {
      drush_apic_app_createOrUpdatesub($sub, $event, 'MassUpdate');
    }
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param array $appUrls
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 * @throws \JsonException
 */
function drush_apic_app_tidy($appUrls = []) {
  ibm_apim_entry_trace(__FUNCTION__, count($appUrls));
  $appUrls = json_decode($appUrls, TRUE, 512, JSON_THROW_ON_ERROR);
  if (!empty($appUrls)) {
    $nids = [];
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'application')
      ->condition('apic_url.value', $appUrls, 'NOT IN');
    $results = $query->execute();
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
 * @param string $event
 *
 * @throws \JsonException
 */
function drush_apic_app_createcred($cred, $event = 'cred_create') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  drush_apic_app_createOrUpdateCred($cred, $event, 'CreateApplication');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $cred
 * @param string $event
 *
 * @throws \JsonException
 */
function drush_apic_app_updatecred($cred, $event = 'cred_update') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  drush_apic_app_createOrUpdateCred($cred, $event, 'UpdateApplication');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $cred
 * @param string $event
 */
function drush_apic_app_deletecred($cred, $event = 'cred_del') {
  ibm_apim_entry_trace(__FUNCTION__);
  if ($cred !== NULL) {
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }

    if (isset($cred['app_url'], $cred['id'])) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'application');
      $query->condition('apic_url.value', $cred['app_url']);

      $nids = $query->execute();

      if (isset($nids) && !empty($nids)) {
        $credsService = Drupal::service('apic_app.credentials');
        $nid = array_shift($nids);
        $node = Node::load($nid);
        $credsService->deleteCredentials($node, $cred['id']);
      }
      else {
        drush_log(dt('Drush @func Node not found for @app_url', [
          '@func' => 'drush_apic_app_deletecred',
          '@app_url' => $cred['app_url'],
        ]), 'error');
      }
    }
    else {
      drush_log(dt('Drush @func app_url or id missing', ['@func' => 'drush_apic_app_deletecred']), 'error');
    }

    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }
  }
  else {
    drush_log(dt('Drush @func No credential provided', ['@func' => 'drush_apic_app_deletecred']), 'error');
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
function drush_apic_app_createOrUpdateCred($cred, $event, $func) {
  ibm_apim_entry_trace(__FUNCTION__, $cred);
  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = Drupal::service('account_switcher');
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

      $nids = $query->execute();

      if (isset($nids) && !empty($nids)) {
        $credsService = Drupal::service('apic_app.credentials');
        $nid = array_shift($nids);
        $node = Node::load($nid);
        $credsService->createOrUpdateSingleCredential($node, $cred);
      }
      else {
        drush_log(dt('Drush @func Node not found for @app_url', [
          '@func' => 'drush_apic_app_createOrUpdateCred',
          '@app_url' => $cred['app_url'],
        ]), 'error');
      }
    }
    else {
      drush_log(dt('Drush @func app_url or id missing', ['@func' => 'drush_apic_app_createOrUpdateCred']), 'error');
    }
  }
  else {
    drush_log(dt('Drush @func No credential provided', ['@func' => $func]), 'error');
  }
  if ((int) $originalUser->id() !== 1) {
    $accountSwitcher->switchBack();
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}
