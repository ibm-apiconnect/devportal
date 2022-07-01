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

namespace Drupal\product\Commands;

use Drupal\apic_api\Api;
use Drupal\apic_api\Commands\ApicApiCommands;
use Drupal\Core\Session\UserSession;
use Drupal\node\Entity\Node;
use Drupal\product\Product;
use Drush\Commands\DrushCommands;

/**
 * Class ProductCommands.
 *
 * @package Drupal\product\Commands
 */
class ProductCommands extends DrushCommands {

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   * @command product-cleanse-drush-command
   * @usage drush product-cleanse-drush-command
   *   Clears the product entries back to a clean state.
   * @aliases cleanse_products
   */
  public function drush_product_cleanse_drush_command(): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    // in case moderation is on we need to run as admin
    // save the current user so we can switch back at the end
    $accountSwitcher = \Drupal::service('account_switcher');
    $originalUser = \Drupal::currentUser();
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
    }
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'product']);

    foreach ($nodes as $node) {
      $node->delete();
    }
    if ((int) $originalUser->id() !== 1) {
      $accountSwitcher->switchBack();
    }
    \Drupal::logger('product')->info('All Product entries deleted.', []);
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
  public function drush_product_createOrUpdate($content, $event, $func): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    if ($content !== NULL) {

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

      if (isset($content['product'])) {
        $product = $content['product'];
        $ref = $product['catalog_product']['info']['name'] . ':' . $product['catalog_product']['info']['version'];

        // we don't care about staged, retired, or whatever other state products might be in. we only want published products in the portal.
        $stateToLower = strtolower($product['state']);
        if ($stateToLower !== 'published' && $stateToLower !== 'deprecated') {
          $this->drush_product_delete($product, $product['state']);
        }
        else {
          $portalProduct = new Product();
          $createdOrUpdated = $portalProduct->createOrUpdate($product, $event);

          if ($createdOrUpdated) {
            \Drupal::logger('product')->info('Drush @func created Product @prod', [
              '@func' => $func,
              '@prod' => $ref,
            ]);
          }
          else {
            \Drupal::logger('product')->info('Drush @func updated existing Product @prod', [
              '@func' => $func,
              '@prod' => $ref,
            ]);
          }
        }
        $moduleHandler = \Drupal::service('module_handler');
        if ($func !== 'MassUpdate' && $moduleHandler->moduleExists('views')) {
          views_invalidate_cache();
        }

        Product::clearAppCache($content['product']['url']);

        if ((int) $originalUser->id() !== 1) {
          $accountSwitcher->switchBack();
        }

        if (isset($content['consumer_apis']) && !empty($content['consumer_apis'])) {
          $apiCommand = new ApicApiCommands();
          $apiCommand->drush_apic_api_massupdate($content['consumer_apis'], $event);
        }
      }
      else {
        \Drupal::logger('product')->error('Drush @func No Product provided', ['@func' => $func]);
      }
    }
    else {
      \Drupal::logger('product')->error('Drush @func No Product provided', ['@func' => $func]);
    }
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $content - The webhook JSON content
   * @param string|null $event - The event type
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   * @command product-create
   * @usage drush product-create [content] [event]
   *   Creates a product
   * @aliases cprod
   */
  public function drush_product_create($content, ?string $event = 'product_lifecycle'): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    $this->drush_product_createOrUpdate($content, $event, 'CreateProduct');
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $content - The webhook JSON content
   * @param string|null $event - The event type
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   * @command product-update
   * @usage drush product-update [content] [event]
   *   Updates a product
   * @aliases uprod
   */
  public function drush_product_update($content, ?string $event = 'product_update'): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    $this->drush_product_createOrUpdate($content, $event, 'UpdateProduct');
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $product - The webhook JSON content
   * @param string|null $event - The event type
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @command product-update
   * @usage drush product-update [product] [event]
   *   Deletes a product
   * @aliases dprod
   */
  public function drush_product_delete($product, ?string $event = 'product_del'): void {
    ibm_apim_entry_trace(__FUNCTION__, NULL);
    if ($product !== NULL) {
      // handle being sent the content payload or the sub-element 'product'
      if (!isset($product['product'])) {
        $newProduct['product'] = $product;
        $product = $newProduct;
      }
      // in case moderation is on we need to run as admin
      // save the current user so we can switch back at the end
      $accountSwitcher = \Drupal::service('account_switcher');
      $originalUser = \Drupal::currentUser();
      if ((int) $originalUser->id() !== 1) {
        $accountSwitcher->switchTo(new UserSession(['uid' => 1]));
      }
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'product');
      $query->condition('apic_ref.value', $product['product']['name'] . ':' . $product['product']['version']);

      $nids = $query->execute();
      if ($nids !== NULL && !empty($nids)) {
        $nid = array_shift($nids);
        $productNode = Node::load($nid);
        if ($productNode !== NULL) {
          $productUrl = $productNode->apic_url->value;
        }
        Product::deleteNode($nid, $event);
        \Drupal::logger('product')->info('Drush DeleteProduct deleted Product @prod', ['@prod' => $product['product']['id']]);
      }
      else {
        \Drupal::logger('product')->warning('Drush DeleteProduct could not find Product @prod', ['@prod' => $product['product']['id']]);
      }
      // check if any APIs are in retired state, if so delete them too
      if (isset($product['consumer_apis']) && !empty($product['consumer_apis'])) {
        foreach ($product['consumer_apis'] as $consumer_api) {
          if (isset($consumer_api['state']) && strtolower($consumer_api['state']) === 'retired') {
            $query = \Drupal::entityQuery('node');
            $query->condition('type', 'api');
            $query->condition('status', 1);
            $query->condition('apic_url', $consumer_api['url']);
            $apiNids = $query->execute();
            if ($apiNids !== NULL && !empty($apiNids)) {
              $apiNid = array_shift($apiNids);
              Api::deleteNode($apiNid, $event);
              \Drupal::logger('product')->info('Drush DeleteProduct deleted retired API @api', ['@api' => $consumer_api['url']]);
            }
          }
        }
      }

      $moduleHandler = \Drupal::service('module_handler');
      if ($moduleHandler->moduleExists('views')) {
        views_invalidate_cache();
      }
      if ((int) $originalUser->id() !== 1) {
        $accountSwitcher->switchBack();
      }
    }
    else {
      \Drupal::logger('product')->error('Drush DeleteProduct No Product provided.', []);
    }
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $content - The webhook JSON content
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   *
   * @command product-supersede
   * @usage drush product-supersede [content]
   *   Supersedes a product (marking it deprecated) and publishes another product.
   * @aliases sprod
   */
  public function drush_product_supersede($content): void {
    ibm_apim_entry_trace(__FUNCTION__);
    if ($content !== NULL) {
      if (is_string($content)) {
        $content = json_decode($content, TRUE, 512, JSON_THROW_ON_ERROR);
      }
      // We should have two products in the content['products'] array
      if (sizeof($content['products']) === 2) {
        foreach ($content['products'] as $product) {
          if ($product['state'] === 'deprecated') {
            $this->drush_product_update(['product' => $product], 'product_supersede');

            $deprecatedProdUrl = $product['url'];
            $query = \Drupal::entityQuery('node');
            $query->condition('type', 'product');
            $query->condition('apic_ref.value', $product['name'] . ':' . $product['version']);
            $nids = $query->execute();
            if ($nids !== NULL && !empty($nids)) {
              $oldNid = array_shift($nids);
            }
          }
          elseif ($product['state'] === 'published') {
            $this->drush_product_create(['product' => $product], 'product_supersede');

            $query = \Drupal::entityQuery('node');
            $query->condition('type', 'product');
            $query->condition('apic_ref.value', $product['name'] . ':' . $product['version']);
            $nids = $query->execute();
            if ($nids !== NULL && !empty($nids)) {
              $newNid = array_shift($nids);
            }
          }
          else {
            // this shouldn't happen
            \Drupal::logger('product')
              ->error('Drush product supersede : found a product in a strange lifecycle state \'@state\'. Expected \'published\' or \'deprecated\'.', ['@state' => $product['state']]);
          }
        }
        if (isset($oldNid, $newNid)) {
          Product::updateBasicPageRefs($oldNid, $newNid, TRUE);
        }
        // process apis
        // check if any APIs are in retired state, if so delete them too
        if (isset($content['consumer_apis']) && !empty($content['consumer_apis'])) {
          foreach ($content['consumer_apis'] as $consumer_api) {
            if (isset($consumer_api['state']) && strtolower($consumer_api['state']) === 'retired') {
              $query = \Drupal::entityQuery('node');
              $query->condition('type', 'api');
              $query->condition('status', 1);
              $query->condition('apic_url', $consumer_api['url']);
              $apiNids = $query->execute();
              if ($apiNids !== NULL && !empty($apiNids)) {
                $apiNid = array_shift($apiNids);
                Api::deleteNode($apiNid, 'product_supersede');
                \Drupal::logger('product')->info('Drush SupersedeProduct deleted retired API @api', ['@api' => $consumer_api['url']]);
              }
            }
          }
        }
        $apiCommand = new ApicApiCommands();
        $apiCommand->drush_apic_api_massupdate($content['consumer_apis'], 'product_supersede');

        // update all subscribed apps
        if (isset($deprecatedProdUrl)) {
          $query = \Drupal::entityQuery('apic_app_application_subs');
          $query->condition('product_url', $deprecatedProdUrl);
          $subIds = $query->execute();
          if (isset($subIds) && !empty($subIds)) {
            foreach (array_chunk($subIds, 50) as $chunk) {
              $subEntities = \Drupal::entityTypeManager()->getStorage('apic_app_application_subs')->loadMultiple($chunk);
              foreach ($subEntities as $sub) {
                $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties(['apic_url' => $sub->get('app_url')->value]);
                if (count($nodes) === 1) {
                  array_pop($nodes)->save();
                }
                else {
                  \Drupal::logger('product')
                    ->error('Drush product supersede : incorrect number of applications with url \'@url\' found. Should be 1 but was @count.', [
                      '@url' => $deprecatedProdUrl,
                      '@count' => count($nodes),
                    ]);
                }
              }
            }
          }
        }
        $moduleHandler = \Drupal::service('module_handler');
        if ($moduleHandler->moduleExists('views')) {
          views_invalidate_cache();
        }
      }
      else {
        \Drupal::logger('product')
          ->error('Drush product supersede : incorrect number of products found. Should be 2 but was @count.', ['@count' => sizeof($content['products'])]);
      }
    }
    else {
      \Drupal::logger('product')->error('Drush product supersede : no content provided.', []);
    }
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param $content - The webhook JSON content
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   * @command product-replace
   * @usage drush product-replace [content]
   *   Replaces a product (removing it) and replaces it with another product.
   * @aliases rprod
   */
  public function drush_product_replace($content): void {
    ibm_apim_entry_trace(__FUNCTION__);
    if ($content !== NULL) {
      if (is_string($content)) {
        $content = json_decode($content, TRUE, 512, JSON_THROW_ON_ERROR);
      }
      // We should have two products in the content['products'] array
      if (sizeof($content['products']) === 2) {
        // if there happens to be a plan mapping then handle it
        if (isset($content['plan_map']) && !empty($content['plan_map'])) {
          Product::processPlanMapping($content['plan_map']);
        }

        // process apis - do this before products so that we can create any new published apis first
        // check if any APIs are in retired state, if so delete them too
        if (isset($content['consumer_apis']) && !empty($content['consumer_apis'])) {
          foreach ($content['consumer_apis'] as $consumer_api) {
            if (isset($consumer_api['state']) && strtolower($consumer_api['state']) === 'retired') {
              $query = \Drupal::entityQuery('node');
              $query->condition('type', 'api');
              $query->condition('status', 1);
              $query->condition('apic_url', $consumer_api['url']);
              $apiNids = $query->execute();
              if ($apiNids !== NULL && !empty($apiNids)) {
                $apiNid = array_shift($apiNids);
                Api::deleteNode($apiNid, 'product_supersede');
                \Drupal::logger('product')->info('Drush ReplaceProduct deleted retired API @api', ['@api' => $consumer_api['url']]);
              }
            }
          }
        }
        $apiCommand = new ApicApiCommands();
        $apiCommand->drush_apic_api_massupdate($content['consumer_apis'], 'product_supersede');

        foreach ($content['products'] as $product) {
          if ($product['state'] === 'retired') {
            $query = \Drupal::entityQuery('node');
            $query->condition('type', 'product');
            $query->condition('apic_ref.value', $product['name'] . ':' . $product['version']);
            $nids = $query->execute();
            if ($nids !== NULL && !empty($nids)) {
              $oldNid = array_shift($nids);
            }

            $this->drush_product_delete($product, 'product_replace');
          }
          elseif ($product['state'] === 'deprecated') {
            $this->drush_product_update(['product' => $product], 'product_replace');

            $query = \Drupal::entityQuery('node');
            $query->condition('type', 'product');
            $query->condition('apic_ref.value', $product['name'] . ':' . $product['version']);
            $nids = $query->execute();
            if ($nids !== NULL && !empty($nids)) {
              $oldNid = array_shift($nids);
            }
          }
          elseif ($product['state'] === 'published') {
            $this->drush_product_create(['product' => $product], 'product_replace');

            $query = \Drupal::entityQuery('node');
            $query->condition('type', 'product');
            $query->condition('apic_ref.value', $product['name'] . ':' . $product['version']);
            $nids = $query->execute();
            if ($nids !== NULL && !empty($nids)) {
              $newNid = array_shift($nids);
            }
          }
          else {
            // this shouldn't happen
            \Drupal::logger('product')
              ->error('Drush product replace : found a product in a strange lifecycle state \'@state\'. Expected \'published\', \'deprecated\' or \'retired\'.', ['@state' => $product['state']]);
          }
        }
        if (isset($oldNid, $newNid)) {
          Product::updateBasicPageRefs($oldNid, $newNid, FALSE);
        }

        $moduleHandler = \Drupal::service('module_handler');
        if ($moduleHandler->moduleExists('views')) {
          views_invalidate_cache();
        }
      }
      else {
        \Drupal::logger('product')
          ->error('Drush product replace : incorrect number of products found. Should be 2 but was @count.', ['@count' => sizeof($content['products'])]);
      }
    }
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param string $products
   * @param string|null $event
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   * @command product-massupdate
   * @usage drush product-massupdate [products] [event]
   *   Mass updates a list of Products.
   * @aliases mprod
   */
  public function drush_product_massupdate(string $products, string $event = 'content_refresh'): void {
    ibm_apim_entry_trace(__FUNCTION__, strlen($products));
    $products = json_decode($products, TRUE, 512, JSON_THROW_ON_ERROR);
    if (!empty($products)) {
      foreach ($products as $product) {
        $this->drush_product_createOrUpdate($product, $event, 'MassUpdate');
      }
      $moduleHandler = \Drupal::service('module_handler');
      if ($moduleHandler->moduleExists('views')) {
        views_invalidate_cache();
      }
    }
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

  /**
   * @param string $prodRefs - The JSON array of Product references
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   *
   * @command product-tidy
   * @usage drush product-tidy [prodRefs]
   *   Tidies the list of Products to ensure consistent with APIM.
   * @aliases tprod
   */
  public function drush_product_tidy(string $prodRefs): void {
    ibm_apim_entry_trace(__FUNCTION__, count($prodRefs));
    $prodRefs = json_decode($prodRefs, TRUE, 512, JSON_THROW_ON_ERROR);
    if (!empty($prodRefs)) {
      $nids = [];
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'product')
        ->condition('apic_ref', $prodRefs, 'NOT IN');
      $results = $query->execute();
      if ($results !== NULL) {
        foreach ($results as $item) {
          $nids[] = $item;
        }
      }

      foreach ($nids as $nid) {
        Product::deleteNode($nid, 'content_refresh');
      }
    }
    ibm_apim_exit_trace(__FUNCTION__, NULL);
  }

}
