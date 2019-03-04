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

use Drupal\apic_app\Application;
use Drupal\apic_app\Subscription;
use Drupal\Core\Session\UserSession;
use Drupal\node\Entity\Node;

function apic_app_drush_command() {

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
  if ($originalUser !== NULL && (int) $originalUser->id() !== 1) {
    $accountSwitcher->switchBack();
  }
  drush_log(dt('All application entries deleted.'), 'success');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

function drush_apic_app_createOrUpdate($content, $event, $func) {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  if ($content !== NULL) {
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }
    if (is_string($content)) {
      $content = json_decode($content, TRUE);
    }
    $ref = $content['id'];
    $createdOrUpdated = Application::createOrUpdate($content, $event);
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
    if ($originalUser !== NULL && (int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }
  }
  else {
    drush_log(dt('Drush @func No application provided', ['@func' => $func]), 'error');
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

function drush_apic_app_create($app, $event = 'app_create') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  drush_apic_app_createOrUpdate($app, $event, 'CreateApplication');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

function drush_apic_app_update($app, $event = 'app_update') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  drush_apic_app_createOrUpdate($app, $event, 'UpdateApplication');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

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
      $app = json_decode($app, TRUE);
    }

    $rc = Application::deleteById($app['id'], $event);
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
    if ($originalUser !== NULL && (int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }
  }
  else {
    drush_log(dt('Drush DeleteApplication No application ID provided'), 'error');
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

function drush_apic_app_createOrUpdatesub($subInput, $event, $func) {
  ibm_apim_entry_trace(__FUNCTION__, $subInput);
  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = Drupal::service('account_switcher');
  $originalUser = \Drupal::currentUser();
  if ((int) $originalUser->id() !== 1) {
    $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
  }
  if ($subInput !== NULL) {
    if (is_string($subInput)) {
      $subInput = json_decode($subInput, TRUE);
    }
    $ref = $subInput['id'];
    $createdOrUpdated = Subscription::createOrUpdate($subInput);
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
  if ($originalUser !== NULL && (int) $originalUser->id() !== 1) {
    $accountSwitcher->switchBack();
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

function drush_apic_app_createsub($subInput, $event = 'create_sub') {
  ibm_apim_entry_trace(__FUNCTION__, $subInput);
  drush_apic_app_createOrUpdatesub($subInput, $event, 'CreateSubscription');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

function drush_apic_app_updatesub($subInput, $event = 'update_sub') {
  ibm_apim_entry_trace(__FUNCTION__, $subInput);
  drush_apic_app_createOrUpdatesub($subInput, $event, 'UpdateSubscription');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

function drush_apic_app_deletesub($subInput, $event = 'delete_sub') {
  ibm_apim_entry_trace(__FUNCTION__, $subInput);
  $appUrl = $subInput['app_url'];
  $subId = $subInput['id'];
  if ($appUrl !== NULL && $subId !== NULL) {
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }
    Subscription::delete($appUrl, $subId);
    drush_log(dt('Subscription deleted.'), 'success');
    if ($originalUser !== NULL && (int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }
  }
  else {
    drush_log(dt('No subscription provided'), 'error');
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

function drush_apic_app_massupdate($apps = [], $event = 'content_refresh') {
  ibm_apim_entry_trace(__FUNCTION__, count($apps));
  if (!empty($apps)) {
    foreach ($apps as $app) {
      drush_apic_app_createOrUpdate($app, $event, 'MassUpdate');
    }
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

function drush_apic_app_sub_massupdate($subs = [], $event = 'content_refresh') {
  ibm_apim_entry_trace(__FUNCTION__, count($subs));
  if (!empty($subs)) {
    foreach ($subs as $sub) {
      drush_apic_app_createOrUpdatesub($sub, $event, 'MassUpdate');
    }
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

function drush_apic_app_tidy($appUrls = []) {
  ibm_apim_entry_trace(__FUNCTION__, count($appUrls));
  $appUrls = json_decode($appUrls, TRUE);
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
      Application::deleteNode($nid, 'content_refresh');
    }
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

function drush_apic_app_sub_tidy($appUrls) {
  ibm_apim_entry_trace(__FUNCTION__, count($appUrls));
  $appUrls = json_decode($appUrls, TRUE);
  if (!empty($appUrls)) {

    // Any applications that exist in the database that were not mentioned in the list of apps=>subscriptions
    // must have no subscriptions at all. Insert empty arrays to record this
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'application')->condition('apic_url.value', array_keys($appUrls), 'NOT IN');
    $results = $query->execute();

    if ($results !== NULL) {
      foreach ($results as $nid) {
        $node = Node::load($nid);
        if ($node !== NULL) {
          $appUrls[$node->apic_url->value] = '';
        }
      }
    }

    foreach ($appUrls as $appUrl => $appSub) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'application')->condition('apic_url.value', $appUrl);
      $results = $query->execute();

      if ($results !== NULL) {
        foreach ($results as $nid) {
          $node = Node::load($nid);
          if ($node !== NULL) {
            $newSubs = [];
            foreach ($appSub as $sub) {
              $newSubs[] = serialize($sub);
            }
            $node->set('application_subscriptions', $newSubs);
            $node->save();
          }
        }
      }
    }
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

function drush_apic_app_createcred($cred, $event = 'cred_create') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  drush_apic_app_createOrUpdateCred($cred, $event, 'CreateApplication');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

function drush_apic_app_updatecred($cred, $event = 'cred_update') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  drush_apic_app_createOrUpdateCred($cred, $event, 'UpdateApplication');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

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
      Application::deleteCredential($cred['app_url'], $cred['id']);
    }
    else {
      drush_log(dt('Drush @func app_url or id missing', ['@func' => 'drush_apic_app_deletecred']), 'error');
    }

    if ($originalUser !== NULL && (int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }
  }
  else {
    drush_log(dt('Drush @func No credential provided', ['@func' => 'drush_apic_app_deletecred']), 'error');
  }

  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

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
      $cred = json_decode($cred, TRUE);
    }

    if (isset($cred['app_url'], $cred['id'])) {
      Application::createOrUpdateCredential($cred['app_url'], $cred);
    }
    else {
      drush_log(dt('Drush @func app_url or id missing', ['@func' => 'drush_apic_app_deletecred']), 'error');
    }
  }
  else {
    drush_log(dt('Drush @func No credential provided', ['@func' => $func]), 'error');
  }
  if ($originalUser !== NULL && (int) $originalUser->id() !== 1) {
    $accountSwitcher->switchBack();
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}
