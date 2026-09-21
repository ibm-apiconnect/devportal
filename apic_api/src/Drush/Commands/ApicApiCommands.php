<?php

namespace Drupal\apic_api\Drush\Commands;

use Drupal\apic_api\Api;
use Drupal\Core\Session\UserSession;
use Drupal\node\Entity\Node;
use Drush\Commands\DrushCommands;
use Drush\Attributes as CLI;
use Drupal\ibm_apim\Controller\IbmApimContentController;

/**
 * Class ApicApiCommands.
 *
 * @package Drupal\apic_api\Drush\Commands
 */
class ApicApiCommands extends DrushCommands {

  /**
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  #[CLI\Command(name: 'apic-api-cleanse-drush-command', aliases: ['cleanse_apis'])]
  #[CLI\Usage(name: 'drush apic-api-cleanse-drush-command', description: 'Clears the API entries back to a clean state.')]
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
    \Drupal::logger('apic_api')->info('All API entries deleted.');
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
      $time_start = microtime(true);
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

      $time_end = microtime(true);
      $execution_time = (microtime(true) - $time_start);
      $utils = \Drupal::service('ibm_apim.utils');
      if ($createdOrUpdated === 'created') {
        $utils->snapshotDebug("Drush %s created API '%s2' in %f seconds\n", [ '%s' => $func, '%s2' => $ref, '%f' => $execution_time]);
      }
      else if ($createdOrUpdated === 'updated') {
        $utils->snapshotDebug("Drush %s updated API '%s2' in %f seconds\n", [ '%s' => $func, '%s2' => $ref, '%f' => $execution_time]);
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
   */
  #[CLI\Command(name: 'apic-api-create', aliases: ['capi'])]
  #[CLI\Argument(name: 'api', description: 'API to create')]
  #[CLI\Argument(name: 'event', description: 'The type of event')]
  #[CLI\Usage(name: 'drush apic-api-create [api] [event]', description: 'Creates an API.')]
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
   */
  #[CLI\Command(name: 'apic-api-update', aliases: ['uapi'])]
  #[CLI\Argument(name: 'api', description: 'The updated API object')]
  #[CLI\Argument(name: 'event', description: 'The type of event')]
  #[CLI\Usage(name: 'drush apic-api-update [api] [event]', description: 'Update an existing API.')]
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
   */
  #[CLI\Command(name: 'apic-api-delete', aliases: ['dapi'])]
  #[CLI\Argument(name: 'api', description: 'The API object to delete')]
  #[CLI\Argument(name: 'event', description: 'The type of event')]
  #[CLI\Usage(name: 'drush apic-api-delete [api] [event]', description: 'Delete an existing API.')]
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

      $nids = $query->accessCheck()->execute();
      if ($nids !== NULL && !empty($nids)) {
        $nid = array_shift($nids);
        $apiNode = Node::load($nid);
        if ($apiNode !== NULL) {
          //Check this api is not still referenced by a product
          $query = \Drupal::entityQuery('node');
          $query->condition('type', 'product');
          $query->condition('product_apis', $apiNode->apic_ref->value, 'CONTAINS');

          $results = $query->accessCheck()->execute();
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
   */
   #[CLI\Command(name: 'apic-api-massupdate', aliases: ['mapi'])]
   #[CLI\Argument(name: 'apis', description: 'The updated API objects')]
   #[CLI\Argument(name: 'event', description: 'The type of event')]
   #[CLI\Usage(name: 'drush apic-api-massupdate [apis] [event]', description: 'Mass updates a list of APIs.')]
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
   */
  #[CLI\Command(name: 'apic-api-tidy', aliases: ['tapi'])]
  #[CLI\Argument(name: 'apiRefs', description: 'An array of API References')]
  #[CLI\Usage(name: 'drush apic-api-tidy [apiRefs]', description: 'Tidies the list of APIs to ensure consistent with APIM.')]
  public function drush_apic_api_tidy(array $apiRefs = []): void {
    ibm_apim_entry_trace(__FUNCTION__, count($apiRefs));

    if (!empty($apiRefs)) {
      $nids = [];
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'api')
        ->condition('apic_ref', $apiRefs, 'NOT IN');
      $results = $query->accessCheck()->execute();
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

   /**
    * Sets the icon image and alt text for the api from the provided image path
    *
    * @param string $apiRef The name:version or id of the api
    * @param string $iconPath The path to the icon image file
    * @param string $iconAltText The alternative text for the icon
    */
    #[CLI\Command(name: 'api-set-icon', aliases: ['siapi'])]
    #[CLI\Argument(name: 'apiRef', description: 'The name:version or id of the api')]
    #[CLI\Argument(name: 'iconPath', description: 'The path to the icon image file')]
    #[CLI\Argument(name: 'iconAltText', description: 'The alternative text for the icon')]
    #[CLI\Usage(name: 'drush api-set-icon my-api:1.0.0 /tmp/myicon.png "icon of a cat"', description: 'Sets the icon for the my-api:1.0.0 api to be the one located at /tmp/myicon.png with some alt text.')]
    public function drush_api_set_icon($apiRef, $iconPath, $iconAltText): ?string {
      return IbmApimContentController::setApiIcon($apiRef, $iconPath, $iconAltText);
    }

     /**
      * Add a category for the given api
      *
      * @param string $apiRef The name:version or id of the api
      * @param string $category The category name e.g. top_level_element/next_level_element
      */
      #[CLI\Command(name: 'api-add-tag', aliases: ['stapi'])]
      #[CLI\Argument(name: 'apiRef', description: 'The name:version or id of the api')]
      #[CLI\Argument(name: 'category', description: 'The category name e.g. top_level_element/next_level_element')]
      #[CLI\Usage(name: 'drush api-add-tag my-api:1.0.0 top_level_element/next_level_element', description: 'Adds the tag for the my-api:1.0.0 api to be top_level_element/next_level_element')]
      public function drush_api_set_tag($apiRef, $category): ?string {
        return IbmApimContentController::addApiCategory($apiRef, $category);
      }


    /**
    * Add an attachment for the given api
    *
    * @param string $apiRef The name:version or id of the api
    * @param string $attachmentPath The path to the attachment file e.g. /tmp/content-doc.pdf
    */
    #[CLI\Command(name: 'api-add-attachment', aliases: ['saaapi'])]
    #[CLI\Argument(name: 'apiRef', description: 'The name:version or id of the api')]
    #[CLI\Argument(name: 'attachmentPath', description: 'The path to the attachment file e.g. /tmp/content-doc.pdf')]
    #[CLI\Option(name: 'description', description: 'A description of the attachment')]
    #[CLI\Usage(name: 'drush api-add-attachment my-api:1.0.0 /tmp/content-doc.pdf', description: 'Add the attachment for the my-api:1.0.0 api to be the file located at /tmp/content-doc.pdf')]
    public function drush_api_add_attachment($apiRef, $attachmentPath, array $options = [ 'description' => self::REQ ]): ?string {
      $description = $options['description'] ?? '';
      return IbmApimContentController::addApiAttachment($apiRef, $attachmentPath, $description);
    }
}
