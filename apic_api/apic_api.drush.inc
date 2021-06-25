<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2021
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

use Drupal\apic_api\Api;
use Drupal\Core\Session\UserSession;
use Drupal\node\Entity\Node;

/**
 * @return array
 */
function apic_api_drush_command(): array {

  $items['apic-api-cleanse-drush-command'] = [
    'description' => 'Clears the API entries back to a clean state.',
    'aliases' => ['cleanse_apis'],
  ];
  $items['apic-api-update'] = [
    'description' => 'Updates an API',
    'aliases' => ['uapi'],
    'arguments' => [
      'event' => 'The event type',
      'api' => 'The JSON API payload',
    ],
  ];
  $items['apic-api-create'] = [
    'description' => 'Creates an API',
    'aliases' => ['capi'],
    'arguments' => [
      'event' => 'The event type',
      'api' => 'The JSON API payload',
    ],
  ];
  $items['apic-api-delete'] = [
    'description' => 'Deletes an API',
    'aliases' => ['dapi'],
    'arguments' => [
      'event' => 'The event type',
      'id' => 'The API ID',
    ],
  ];
  $items['apic-api-massupdate'] = [
    'description' => 'Mass updates a list of APIs',
    'aliases' => ['mapi'],
    'arguments' => [
      'apis' => 'The JSON array of APIs',
      'event' => 'The event type',
    ],
  ];
  $items['apic-api-tidy'] = [
    'description' => 'Tidies the list of APIs to ensure consistent with APIm',
    'aliases' => ['tapi'],
    'arguments' => [
      'apirefs' => 'The JSON array of API references',
    ],
  ];


  return $items;
}

/**
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function drush_apic_api_cleanse_drush_command() {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  // in case moderation is on we need to run as admin
  // save the current user so we can switch back at the end
  $accountSwitcher = Drupal::service('account_switcher');
  $originalUser = \Drupal::currentUser();
  if ((int) $originalUser->id() !== 1) {
    $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
  }
  $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'api']);

  foreach ($nodes as $node) {
    $node->delete();
  }
  if ((int) $originalUser->id() !== 1) {
    $accountSwitcher->switchBack();
  }
  drush_log(dt('All API entries deleted.'), 'success');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $api
 * @param $event
 * @param $func
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 * @throws \JsonException
 */
function drush_apic_api_createOrUpdate($api, $event, $func) {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  if ($api !== NULL) {
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }
    if (is_string($api)) {
      $api = json_decode($api, TRUE, 512, JSON_THROW_ON_ERROR);
    }
    if (isset($api['consumer_api']['definitions']) && empty($api['consumer_api']['definitions'])) {
      unset($api['consumer_api']['definitions']);
    }
    $ref = $api['consumer_api']['info']['x-ibm-name'] . ':' . $api['consumer_api']['info']['version'];

    $portalApi = new Api();
    $createdOrUpdated = $portalApi->createOrUpdate($api, $event);
    if ($createdOrUpdated) {
      drush_log(dt('Drush @func created API @api', [
        '@func' => $func,
        '@api' => $ref,
      ]), 'success');
    }
    else {
      drush_log(dt('Drush @func updated existing API @api', [
        '@func' => $func,
        '@api' => $ref,
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
  else {
    drush_log(dt('Drush @func No API provided', ['@func' => $func]), 'error');
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $api
 * @param string $event
 *
 * @throws \Drupal\Core\Entity\EntityStorageException|\JsonException
 */
function drush_apic_api_create($api, $event = 'api_create') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  drush_apic_api_createOrUpdate($api, $event, 'CreateAPI');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $api
 * @param string $event
 *
 * @throws \Drupal\Core\Entity\EntityStorageException|\JsonException
 */
function drush_apic_api_update($api, $event = 'api_update') {
  ibm_apim_entry_trace(__FUNCTION__, NULL);
  drush_apic_api_createOrUpdate($api, $event, 'UpdateAPI');
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param $api
 * @param string $event
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 * @throws \JsonException
 */
function drush_apic_api_delete($api, $event = 'api_delete') {
  ibm_apim_entry_trace(__FUNCTION__);
  if ($api !== NULL) {
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }

    if (is_string($api)) {
      $api = json_decode($api, TRUE, 512, JSON_THROW_ON_ERROR);
    }
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'api');
    $query->condition('status', 1);
    $query->condition('apic_url', $api['url']);

    $nids = $query->execute();
    if ($nids !== NULL && !empty($nids)) {
      $nid = array_shift($nids);
      $apiNode = Node::load($nid);
      if ($apiNode !== NULL) {
        //Check this api is not still referenced by a product
        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'product');
        $query->condition('product_apis', $apiNode->apic_ref->value, 'CONTAINS');

        $results = $query->execute();
        if ($results !== NULL && !empty($results)) {
          $productIds = [];
          if (is_array($results) && count($results) > 0) {
            foreach ($results as $prod_nid) {
              $product = Node::load($prod_nid);
              if ($product !== NULL) {
                $productId = $product->apic_ref->value;
                $productIds[] = $productId;
              }
            }
          }
          if (is_array($productIds) && count($productIds) > 0) {
            $prodIds = implode(' ', $productIds);
          }
          else {
            $prodIds = implode(' ', $results);
          }
          drush_log(dt('Drush DeleteAPI NOT deleting API @api as it is referenced by the following product(s) @prods', [
            '@api' => $apiNode->id,
            '@prods' => $prodIds,
          ]), 'warning');
        }
        else {
          Api::deleteNode($nid, $event);
          drush_log(dt('Drush DeleteAPI deleted API @api', ['@api' => $api['url']]), 'success');
          $moduleHandler = \Drupal::service('module_handler');
          if ($moduleHandler->moduleExists('views')) {
            views_invalidate_cache();
          }
        }
      }
    }
    else {
      drush_log(dt('Drush DeleteAPI could not find API @api', ['@api' => $api['url']]), 'warning');
    }
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }
  }
  else {
    drush_log(dt('Drush DeleteAPI No API provided'), 'error');
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param array $apis
 * @param string $event
 *
 * @throws \Drupal\Core\Entity\EntityStorageException|\JsonException
 */
function drush_apic_api_massupdate($apis = [], $event = 'api_massupdate') {
  ibm_apim_entry_trace(__FUNCTION__, count($apis));

  if (!empty($apis)) {
    foreach ($apis as $api) {
      drush_apic_api_createOrUpdate($api, $event, 'MassUpdate');
    }
  }
  $moduleHandler = \Drupal::service('module_handler');
  if ($moduleHandler->moduleExists('views')) {
    views_invalidate_cache();
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}

/**
 * @param array $apiRefs
 *
 * @throws \Drupal\Core\Entity\EntityStorageException
 */
function drush_apic_api_tidy($apiRefs = []) {
  ibm_apim_entry_trace(__FUNCTION__, count($apiRefs));

  if (!empty($apiRefs)) {
    $nids = [];
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'api')
      ->condition('apic_ref', $apiRefs, 'NOT IN');
    $results = $query->execute();
    if ($results !== NULL) {
      foreach ($results as $item) {
        $nids[] = $item;
      }
    }

    foreach ($nids as $nid) {
      Api::deleteNode($nid, 'MassUpdate');
    }
  }
  ibm_apim_exit_trace(__FUNCTION__, NULL);
}
