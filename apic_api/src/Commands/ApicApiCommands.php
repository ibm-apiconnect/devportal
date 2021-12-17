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

namespace Drupal\apic_api\Commands;

use Drupal\apic_api\Api;
use Drupal\Core\Session\UserSession;
use Drupal\node\Entity\Node;
use Drush\Commands\DrushCommands;

/**
 * Class ApicApiCommands.
 *
 * @package Drupal\apic_api\Commands
 */
class ApicApiCommands extends DrushCommands {

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @command apic-api-cleanse-drush-command
   * @usage drush apic-api-cleanse-drush-command
   *   Clears the API entries back to a clean state.
   * @aliases cleanse_apis
   */
  public function drush_apic_api_cleanse_drush_command(): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = \Drupal::service('account_switcher');
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
    \Drupal::logger('apic_api')->info(dt('All API entries deleted.'));
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
  public function drush_apic_api_createOrUpdate($api, $event, $func): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    if ($api !== NULL) {
      // in case moderation is on we need to run as admin
      // save the current user so we can switch back at the end
      $accountSwitcher = \Drupal::service('account_switcher');
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
        \Drupal::logger('apic_api')->info('Drush @func created API @api', [
          '@func' => $func,
          '@api' => $ref,
        ]);
      }
      else {
        \Drupal::logger('apic_api')->info('Drush @func updated existing API @api', [
          '@func' => $func,
          '@api' => $ref,
        ]);
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
      \Drupal::logger('apic_api')->error('Drush @func No API provided', ['@func' => $func]);
    }
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $api
   * @param string|null $event
   *
   * @throws \Drupal\Core\Entity\EntityStorageException|\JsonException
   *
   * @command apic-api-create
   * @usage drush apic-api-create [api] [event]
   *   Creates an API.
   * @aliases capi
   */
  public function drush_apic_api_create($api, ?string $event = 'api_create'): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    $this->drush_apic_api_createOrUpdate($api, $event, 'CreateAPI');
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $api
   * @param string|null $event
   *
   * @throws \Drupal\Core\Entity\EntityStorageException|\JsonException
   *
   * @command apic-api-update
   * @usage drush apic-api-update [api] [event]
   *   Updates an API.
   * @aliases uapi
   */
  public function drush_apic_api_update($api, ?string $event = 'api_update'): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    $this->drush_apic_api_createOrUpdate($api, $event, 'UpdateAPI');
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $api
   * @param string|null $event
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   *
   * @command apic-api-delete
   * @usage drush apic-api-delete [api] [event]
   *   Deletes an API.
   * @aliases dapi
   */
  public function drush_apic_api_delete($api, ?string $event = 'api_delete'): void {
    ibm_apim_entry_trace(__FUNCTION__);
    if ($api !== NULL) {
      // in case moderation is on we need to run as admin
      // save the current user so we can switch back at the end
      $accountSwitcher = \Drupal::service('account_switcher');
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
            \Drupal::logger('apic_api')
              ->warning('Drush DeleteAPI NOT deleting API @api as it is referenced by the following product(s) @prods', [
                '@api' => $apiNode->id(),
                '@prods' => $prodIds,
              ]);
          }
          else {
            Api::deleteNode($nid, $event);
            \Drupal::logger('apic_api')->info('Drush DeleteAPI deleted API @api', ['@api' => $api['url']]);
            $moduleHandler = \Drupal::service('module_handler');
            if ($moduleHandler->moduleExists('views')) {
              views_invalidate_cache();
            }
          }
        }
      }
      else {
        \Drupal::logger('apic_api')->warning('Drush DeleteAPI could not find API @api', ['@api' => $api['url']]);
      }
      if ((int) $originalUser->id() !== 1) {
        $accountSwitcher->switchBack();
      }
    }
    else {
      \Drupal::logger('apic_api')->error('Drush DeleteAPI No API provided');
    }
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param array $apis
   * @param string|null $event
   *
   * @throws \Drupal\Core\Entity\EntityStorageException|\JsonException
   *
   * @command apic-api-massupdate
   * @usage drush apic-api-massupdate [apis] [event]
   *   Mass updates a list of APIs.
   * @aliases mapi
   */
  public function drush_apic_api_massupdate(array $apis = [], ?string $event = 'api_massupdate'): void {
    ibm_apim_entry_trace(__FUNCTION__, count($apis));

    if (!empty($apis)) {
      foreach ($apis as $api) {
        $this->drush_apic_api_createOrUpdate($api, $event, 'MassUpdate');
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
   *
   * @command apic-api-tidy
   * @usage drush apic-api-tidy [apiRefs]
   *   Tidies the list of APIs to ensure consistent with APIM.
   * @aliases tapi
   */
  public function drush_apic_api_tidy(array $apiRefs = []): void {
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

}