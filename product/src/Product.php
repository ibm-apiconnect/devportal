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

namespace Drupal\product;

use Drupal\consumerorg\ApicType\Member;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\product\Event\ProductPublishEvent;
use Drupal\product\Event\ProductDeleteEvent;
use Drupal\product\Event\ProductUpdateEvent;
use Drupal\product\Event\ProductDeprecateEvent;
use Drupal\product\Event\ProductSupersedeEvent;
use Drupal\product\Event\ProductReplaceEvent;
use Drupal\product\Event\ProductRestageEvent;

/**
 * Class to work with the Product content type, takes input from the JSON returned by
 * IBM API Connect
 */
class Product {

  protected $productTaxonomy;

  public function __construct() {
    $productTaxonomy = \Drupal::service('product.taxonomy');
    $this->productTaxonomy = $productTaxonomy;
  }

  /**
   * Create a new Product
   *
   * @param $product
   * @param string $event
   *
   * @return int|null|string
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function create($product, $event = 'publish') {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $moduleHandler = \Drupal::service('module_handler');
    $siteConfig = \Drupal::service('ibm_apim.site_config');
    $hostVariable = $siteConfig->getApimHost();

    $oldTags = NULL;
    $xName = NULL;
    $oldNode = NULL;

    if ($product !== NULL && isset($product['catalog_product']['info']['name'], $product['catalog_product']['info']['version'])) {
      $xName = $product['catalog_product']['info']['name'];
      if (\strlen($product['catalog_product']['info']['name'] . ':' . $product['catalog_product']['info']['version']) > 254) {
        // if product reference is too long then bomb out
        \Drupal::logger('product')
          ->error('ERROR: Cannot create product. The "name:version" for this product is greater than 254 characters: %name %version', [
            '%name' => $product['catalog_product']['info']['name'],
            '%version' => $product['catalog_product']['info']['version'],
          ]);
        return NULL;
      }
    }

    if ($xName !== NULL) {
      // find if there is an existing node for this API (maybe at old version)
      // using x-ibm-name from swagger doc
      // if so then clone it and base new node on that.
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'product')->condition('product_name.value', $xName)->sort('nid', 'ASC');
      $nids = $query->execute();
      if ($nids !== NULL && !empty($nids)) {
        $nid = array_shift($nids);
        $oldNode = Node::load($nid);
      }
    }

    if ($oldNode !== NULL && $oldNode->id()) {
      $existingProductTags = $oldNode->apic_tags->getValue();
      if ($existingProductTags !== NULL && \is_array($existingProductTags)) {
        foreach ($existingProductTags as $tag) {
          if (isset($tag['target_id'])) {
            $oldTags[] = $tag['target_id'];
          }
        }
      }

      // duplicate node
      $node = $oldNode->createDuplicate();

      // wipe all our fields to ensure they get set to new values
      $node->set('apic_tags', []);

      $node->set('apic_hostname', $hostVariable);
      $node->set('apic_provider_id', $siteConfig->getOrgId());
      $node->set('apic_catalog_id', $siteConfig->getEnvId());
      $node->set('apic_description', NULL);
      $node->set('apic_url', NULL);
      $node->set('apic_version', NULL);
      $node->set('apic_ref', NULL);
      $node->set('apic_pathalias', NULL);
      $node->set('product_id', NULL);
      $node->set('product_contact_name', NULL);
      $node->set('product_contact_email', NULL);
      $node->set('product_contact_url', NULL);
      $node->set('product_license_name', NULL);
      $node->set('product_license_url', NULL);
      $node->set('product_terms_of_service', NULL);
      $node->set('product_visibility', NULL);
      $node->set('product_view_enabled', NULL);
      $node->set('product_subscribe_enabled', NULL);
      $node->set('product_visibility_public', NULL);
      $node->set('product_visibility_authenticated', NULL);
      $node->set('product_visibility_custom_orgs', NULL);
      $node->set('product_visibility_custom_tags', NULL);
      $node->set('product_billing_url', NULL);
      $node->set('product_state', NULL);
      $node->set('product_plans', NULL);
      $node->set('product_apis', NULL);
      $node->set('product_data', NULL);
    }
    else {
      $node = Node::create([
        'type' => 'product',
        'title' => $product['catalog_product']['info']['name'],
        'apic_hostname' => $hostVariable,
        'apic_provider_id' => $siteConfig->getOrgId(),
        'apic_catalog_id' => $siteConfig->getEnvId(),
      ]);
    }

    // get the update method to do the update for us
    if ($node !== NULL) {
      $node = $this->update($node, $product, 'internal');
    }
    else {
      \Drupal::logger('product')->error('Create product: initial node not set.', []);
    }

    if ($node !== NULL && $oldTags !== NULL) {
      $currentTags = $node->get('apic_tags')->getValue();
      if (!\is_array($currentTags)) {
        $currentTags = [];
      }
      foreach ($oldTags as $tid) {
        if ($tid !== NULL) {
          $found = FALSE;
          foreach ($currentTags as $currentValue) {
            if (isset($currentValue['target_id']) && $currentValue['target_id'] === $tid) {
              $found = TRUE;
            }
          }
          if ($found === FALSE) {
            $currentTags[] = ['target_id' => $tid];
          }
        }
      }
      $node->set('apic_tags', $currentTags);
      $node->save();
    }

    if ($node !== NULL && !$moduleHandler->moduleExists('workbench_moderation')) {
      $node->setPublished(TRUE);
      $node->save();
    }


    if ($node !== NULL) {
      // Calling all modules implementing 'hook_product_create':
      $moduleHandler->invokeAll('product_create', [
        'node' => $node,
        'data' => $product,
      ]);
      // invoke rules
      if ($moduleHandler->moduleExists('rules')) {
        // Set the args twice on the event: as the main subject but also in the
        // list of arguments.
        $event = new ProductPublishEvent($node, ['product' => $node]);
        $eventDispatcher = \Drupal::service('event_dispatcher');
        $eventDispatcher->dispatch(ProductPublishEvent::EVENT_NAME, $event);
      }

      \Drupal::logger('product')->notice('Product @product created', ['@product' => $node->getTitle()]);
    }
    if ($node !== NULL) {
      $returnId = $node->id();
    }
    else {
      $returnId = 'ERROR';
      \Drupal::logger('product')->notice('ERROR: Create product node ID not set', []);
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnId);
    return $returnId;
  }

  /**
   * Update an existing Product
   *
   * @param \Drupal\node\NodeInterface $node
   * @param $product
   * @param string $event
   *
   * @return \Drupal\node\NodeInterface|null
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function update(NodeInterface $node, $product, $event = 'content_refresh'): ?NodeInterface {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $returnValue = NULL;
    if ($node !== NULL) {
      $utils = \Drupal::service('ibm_apim.utils');
      $siteConfig = \Drupal::service('ibm_apim.site_config');
      $moduleHandler = \Drupal::service('module_handler');
      $hostVariable = $siteConfig->getApimHost();
      $config = \Drupal::config('ibm_apim.settings');
      $languageList = array_keys(\Drupal::languageManager()->getLanguages(LanguageInterface::STATE_ALL));
      // filter out any retired products and remove them (and other invalid states)
      if (array_key_exists('state', $product) && ($product['state'] === 'retired' || $product['state'] === 'archived' || $product['state'] === 'staged' || $product['state'] === 'pending')) {
        \Drupal::logger('product')
          ->error('Update product: product @productName is in an invalid state: @state, deleting it.', [
            '@productName' => $product['name'],
            '@state' => $product['state'],
          ]);
        self::deleteNode($node->id(), 'invalid_product_state');
        $node = NULL;
        $returnValue = NULL;
      }
      elseif ($product !== NULL && isset($product['catalog_product']['info']['name'], $product['catalog_product']['info']['version']) && \strlen($product['catalog_product']['info']['name'] . ':' . $product['catalog_product']['info']['version']) > 254) {
        // if product reference is too long then bomb out
        \Drupal::logger('product')
          ->error('ERROR: Cannot update product. The "name:version" for this product is greater than 254 characters: %name %version', [
            '%name' => $product['catalog_product']['info']['name'],
            '%version' => $product['catalog_product']['info']['version'],
          ]);
        self::deleteNode($node->id(), 'invalid_product_name_version');
        $node = NULL;
        $returnValue = NULL;
      }
      else {
        $node->setTitle($utils->truncate_string($product['catalog_product']['info']['title']));
        if (isset($product['catalog_product']['info']['x-ibm-languages']['title']) && !empty($product['catalog_product']['info']['x-ibm-languages']['title'])) {
          foreach ($product['catalog_product']['info']['x-ibm-languages']['title'] as $lang => $langArray) {
            $lang = $utils->convert_lang_name_to_drupal($lang);
            // if its one of our locales or the root of one of our locales
            foreach ($languageList as $langListKey => $langListValue) {
              if (\in_array($lang, $languageList, FALSE)) {
                if (!$node->hasTranslation($lang)) {
                  $translation = $node->addTranslation($lang, ['title' => $utils->truncate_string($product['catalog_product']['info']['x-ibm-languages']['title'][$lang])]);
                  $translation->save();
                }
                else {
                  $node->getTranslation($lang)
                    ->setTitle($utils->truncate_string($product['catalog_product']['info']['x-ibm-languages']['title'][$lang]))
                    ->save();
                }
              }
            }
          }
        }
        $node->set('apic_hostname', $hostVariable);
        $node->set('apic_provider_id', $siteConfig->getOrgId());
        $node->set('apic_catalog_id', $siteConfig->getEnvId());
        $node->set('product_id', $product['id']);
        if (!isset($product['state']) || empty($product['state'])) {
          $product['state'] = 'published';
        }
        switch (mb_strtolower($product['state'])) {
          case 'deprecated' :
            $productState = 'deprecated';
            break;
          case 'staged' :
            $productState = 'staged';
            break;
          case 'pending' :
            $productState = 'pending';
            break;
          case 'retired' :
            $productState = 'retired';
            break;
          case 'archived' :
            $productState = 'archived';
            break;
          default :
            $productState = 'published';
            break;
        }
        $node->set('product_state', $productState);
        $node->set('apic_ref', $product['catalog_product']['info']['name'] . ':' . $product['catalog_product']['info']['version']);
        $node->set('product_name', $product['catalog_product']['info']['name']);
        $node->set('apic_version', $product['catalog_product']['info']['version']);

        // ensure description is at least set to empty string
        if (!isset($product['catalog_product']['info']['description']) || empty($product['catalog_product']['info']['description'])) {
          $product['catalog_product']['info']['description'] = '';
        }
        if ($moduleHandler->moduleExists('ghmarkdown')) {
          $format = 'ghmarkdown';
        }
        else {
          $format = 'full_html';
        }
        $node->set('apic_description', [
          'value' => $product['catalog_product']['info']['description'],
          'format' => $format,
        ]);
        if (isset($product['catalog_product']['info']['x-ibm-languages']['description']) && !empty($product['catalog_product']['info']['x-ibm-languages']['description'])) {
          foreach ($product['catalog_product']['info']['x-ibm-languages']['description'] as $lang => $langArray) {
            $lang = $utils->convert_lang_name_to_drupal($lang);
            // if its one of our locales or the root of one of our locales
            foreach ($languageList as $langListKey => $langListValue) {
              if (\in_array($lang, $languageList, FALSE)) {
                if (!$node->hasTranslation($lang)) {
                  $translation = $node->addTranslation($lang, ['apic_description' => $product['catalog_product']['info']['x-ibm-languages']['description'][$lang]]);
                  $translation->save();
                }
                else {
                  $node->getTranslation($lang)
                    ->set('apic_description', $product['catalog_product']['info']['x-ibm-languages']['description'][$lang])
                    ->save();
                }
              }
            }
          }
        }
        // ensure summary is at least set to empty string
        if (!isset($product['catalog_product']['info']['summary']) || empty($product['catalog_product']['info']['summary'])) {
          $product['catalog_product']['info']['summary'] = '';
        }
        if (isset($product['catalog_product']['info']['x-ibm-languages']['summary']) && !empty($product['catalog_product']['info']['x-ibm-languages']['summary'])) {
          foreach ($product['catalog_product']['info']['x-ibm-languages']['summary'] as $lang => $langArray) {
            $lang = $utils->convert_lang_name_to_drupal($lang);
            // if its one of our locales or the root of one of our locales
            foreach ($languageList as $langListKey => $langListValue) {
              if (\in_array($lang, $languageList, FALSE)) {
                if (!$node->hasTranslation($lang)) {
                  $translation = $node->addTranslation($lang, [
                    'apic_summary' => $utils->truncate_string($product['catalog_product']['info']['x-ibm-languages']['summary'][$lang]),
                    1000,
                  ]);
                  $translation->save();
                }
                else {
                  $node->getTranslation($lang)
                    ->set('apic_summary', $utils->truncate_string($product['catalog_product']['info']['x-ibm-languages']['summary'][$lang]), 1000)
                    ->save();
                }
              }
            }
          }
        }
        $node->set('apic_summary', [
          'value' => $utils->truncate_string($product['catalog_product']['info']['summary'], 1000),
          'format' => 'plaintext',
        ]);

        if (!isset($product['catalog_product']['info']['contact'])) {
          $product['catalog_product']['info']['contact'] = [
            'name' => '',
            'email' => '',
            'url' => '',
          ];
        }
        if (!isset($product['catalog_product']['info']['contact']['name'])) {
          $product['catalog_product']['info']['contact']['name'] = '';
        }
        if (!isset($product['catalog_product']['info']['contact']['email'])) {
          $product['catalog_product']['info']['contact']['email'] = '';
        }
        if (!isset($product['catalog_product']['info']['contact']['url'])) {
          $product['catalog_product']['info']['contact']['url'] = '';
        }
        $node->set('product_contact_name', $product['catalog_product']['info']['contact']['name']);
        $node->set('product_contact_email', $product['catalog_product']['info']['contact']['email']);
        $node->set('product_contact_url', $product['catalog_product']['info']['contact']['url']);
        if (!isset($product['catalog_product']['info']['license'])) {
          $product['catalog_product']['info']['license'] = [
            'name' => '',
            'url' => '',
          ];
        }
        $node->set('product_license_name', $product['catalog_product']['info']['license']['name']);
        $node->set('product_license_url', $product['catalog_product']['info']['license']['url']);
        if (!isset($product['catalog_product']['info']['termsOfService']) || empty($product['catalog_product']['info']['termsOfService'])) {
          $product['catalog_product']['info']['termsOfService'] = '';
        }
        $node->set('product_terms_of_service', $product['catalog_product']['info']['termsOfService']);
        $node->set('product_visibility', []);
        // If there is a 'visibility' block in the product use that to determine visibility,
        // otherwise use the one inside catalog_product
        if (array_key_exists('visibility', $product)) {
          $visBlock = $product['visibility'];
        }
        else {
          $visBlock = $product['catalog_product']['visibility'];
        }
        if ($visBlock !== NULL) {
          foreach ($visBlock as $type => $visibility) {
            $node->product_visibility[] = serialize([$type => $visibility]);
          }
        }
        else {
          $node->set('product_visibility', []);
        }
        // default to product visibility being enabled to avoid bugs in apim where the value is not getting set correctly
        if (isset($visBlock['view']) && array_key_exists('enabled', $visBlock['view']) && (boolean) $visBlock['view']['enabled'] === FALSE) {
          $node->set('product_view_enabled', 0);
        }
        else {
          $node->set('product_view_enabled', 1);
        }
        if (isset($visBlock['subscribe']['enabled']) && (boolean) $visBlock['subscribe']['enabled'] === TRUE) {
          $node->set('product_subscribe_enabled', 1);
        }
        else {
          $node->set('product_subscribe_enabled', 0);
        }
        if (isset($visBlock['view']['type']) && mb_strtolower($visBlock['view']['type']) === 'public') {
          $node->set('product_visibility_public', 1);
        }
        else {
          $node->set('product_visibility_public', 0);
        }
        if (isset($visBlock['view']['type']) && mb_strtolower($visBlock['view']['type']) === 'authenticated') {
          $node->set('product_visibility_authenticated', 1);
        }
        else {
          $node->set('product_visibility_authenticated', 0);
        }
        $productVisibilityCustomOrgs = [];
        if (isset($visBlock['view']['type'], $visBlock['view']['org_urls']) && $visBlock['view']['type'] === 'custom') {
          foreach ($visBlock['view']['org_urls'] as $orgUrl) {
            $productVisibilityCustomOrgs[] = $orgUrl;
          }
        }
        $node->set('product_visibility_custom_orgs', $productVisibilityCustomOrgs);
        $productVisibilityCustomTags = [];
        if (isset($visBlock['view']['type'], $visBlock['view']['group_urls']) && $visBlock['view']['type'] === 'custom') {
          foreach ($visBlock['view']['group_urls'] as $tag) {
            $productVisibilityCustomTags[] = $tag;
          }
        }
        $node->set('product_visibility_custom_tags', $productVisibilityCustomTags);
        $node->set('apic_url', $product['url']);

        if (isset($product['catalog_product']['info']['x-pathalias'])) {
          $node->set('apic_pathalias', $product['catalog_product']['info']['x-pathalias']);
        }

        if (isset($product['billing_url'])) {
          $node->set('product_billing_url', $product['billing_url']);
        }
        if (isset($product['catalog_product']['plans'])) {
          // merge in the supersedes / superseded-by info which is outside the product doc
          if (isset($product['supersedes'])) {
            foreach ($product['supersedes'] as $supersedesArray) {
              $prodUrl = $supersedesArray['product_url'];

              foreach ($supersedesArray['plans'] as $supersedePlan) {
                $sourcePlan = $supersedePlan['source'];
                $targetPlan = $supersedePlan['target'];
                if (isset($product['catalog_product']['plans'][$targetPlan])) {
                  if (!\is_array($product['catalog_product']['plans'][$targetPlan]['supersedes'])) {
                    $product['catalog_product']['plans'][$targetPlan]['supersedes'] = [];
                  }
                  $product['catalog_product']['plans'][$targetPlan]['supersedes'][] = [
                    'product_url' => $prodUrl,
                    'plan' => $sourcePlan,
                  ];
                }
              }
            }
          }
          if (isset($product['superseded_by'])) {
            $prodUrl = $product['superseded_by']['product_url'];
            foreach ($product['superseded_by']['plans'] as $supersedePlan) {
              $sourcePlan = $supersedePlan['source'];
              $targetPlan = $supersedePlan['target'];
              if (isset($product['catalog_product']['plans'][$sourcePlan])) {
                $product['catalog_product']['plans'][$sourcePlan]['superseded-by'] = [
                  'product_url' => $prodUrl,
                  'plan' => $targetPlan,
                ];
              }
            }
          }
          $node->set('product_plans', []);
          foreach ($product['catalog_product']['plans'] as $planName => $plan) {
            $plan['name'] = $planName;
            $node->product_plans[] = serialize($plan);
          }
        }
        else {
          $node->set('product_plans', []);
        }
        $node->set('product_apis', []);
        if (isset($product['catalog_product']['apis'])) {
          foreach ($product['catalog_product']['apis'] as $api) {
            $node->product_apis[] = serialize($api);
          }
        }
        else {
          $node->set('product_apis', []);
        }
        $node->set('product_data', \yaml_emit($product['catalog_product'], YAML_UTF8_ENCODING));
        $node->save();

        // need to trigger save of all apis in order to build up ACL
        if (isset($product['catalog_product']['apis']) && !empty($product['catalog_product']['apis'])) {
          $apiRefs = [];
          $apiNids = [];
          foreach ($product['catalog_product']['apis'] as $key => $prodref) {
            $apiRefs[$key] = $prodref['name'];
          }
          $query = \Drupal::entityQuery('node');
          $query->condition('type', 'api');
          $query->condition('status', 1);
          $query->condition('apic_ref.value', $apiRefs, 'IN');
          $results = $query->execute();
          if ($results !== NULL && !empty($results)) {
            $apiNids = array_values($results);
          }
          if (count($apiNids) > 0) {
            $apis = Node::loadMultiple($apiNids);
            foreach ($apis as $api) {
              $api->save();
            }
          }
        }

        // Product Categories
        $categoriesEnabled = (boolean) $config->get('categories')['enabled'];
        if ($categoriesEnabled === TRUE && isset($product['catalog_product']['info']['categories'])) {
          $this->productTaxonomy->process_categories($product, $node);
        }

        // if invoked from the create code then don't invoke the update event - will be invoked from create instead
        if ($node !== NULL) {
          if ($event !== 'internal') {
            \Drupal::logger('product')->notice('Product @product updated', ['@product' => $node->getTitle()]);

            // Calling all modules implementing 'hook_product_update':
            $moduleHandler->invokeAll('product_update', [
              'node' => $node,
              'data' => $product,
            ]);
            // invoke rules
            if ($moduleHandler->moduleExists('rules')) {

              $eventDispatcher = \Drupal::service('event_dispatcher');
              // Set the args twice on the event: as the main subject but also in the
              // list of arguments.
              if ($event !== NULL && $utils->endsWith($event, 'replace')) {
                $event = new ProductReplaceEvent($node, ['product' => $node]);
                $eventDispatcher->dispatch(ProductReplaceEvent::EVENT_NAME, $event);
              }
              elseif ($event !== NULL && $utils->endsWith($event, 'supersede')) {
                $event = new ProductSupersedeEvent($node, ['product' => $node]);
                $eventDispatcher->dispatch(ProductSupersedeEvent::EVENT_NAME, $event);
              }
              elseif ($event !== NULL && $utils->endsWith($event, 'restageFromDraft')) {
                $event = new ProductRestageEvent($node, ['product' => $node]);
                $eventDispatcher->dispatch(ProductRestageEvent::EVENT_NAME, $event);
              }
              elseif ($event !== NULL && $utils->endsWith($event, 'deprecate')) {
                $event = new ProductDeprecateEvent($node, ['product' => $node]);
                $eventDispatcher->dispatch(ProductDeprecateEvent::EVENT_NAME, $event);
              }
              elseif ($event !== NULL || $utils->endsWith($event, 'update')) {
                $event = new ProductUpdateEvent($node, ['product' => $node]);
                $eventDispatcher->dispatch(ProductUpdateEvent::EVENT_NAME, $event);
              }
            }
          }
          ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $node->id());
          $returnValue = $node;
        }
        else {
          \Drupal::logger('product')->error('Update product: no node provided.', []);
          ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
          $returnValue = NULL;
        }
      }
    }
    return $returnValue;
  }

  /**
   * Create a new product if one doesn't already exist for that Product reference
   * Update one if it does
   *
   * @param $product
   * @param $event
   *
   * @return bool
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createOrUpdate($product, $event): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'product');
    $query->condition('apic_ref.value', $product['catalog_product']['info']['name'] . ':' . $product['catalog_product']['info']['version']);

    $nids = $query->execute();

    if ($nids !== NULL && !empty($nids)) {
      $nid = array_shift($nids);
      $node = Node::load($nid);
      if ($node !== NULL) {
        $this->update($node, $product, $event);
        $createdOrUpdated = FALSE;
      }
      else {
        // no existing node for this Product so create one
        $this->create($product, $event);
        $createdOrUpdated = TRUE;
      }
    }
    else {
      // no existing node for this Product so create one
      $this->create($product, $event);
      $createdOrUpdated = TRUE;
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $createdOrUpdated);
    return $createdOrUpdated;
  }

  /**
   * Delete a product by NID
   *
   * @param $nid
   * @param $event
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function deleteNode($nid, $event): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $nid);
    $moduleHandler = \Drupal::service('module_handler');

    $node = Node::load($nid);

    if ($node !== NULL) {
      // Calling all modules implementing 'hook_product_delete':
      $moduleHandler->invokeAll('product_delete', ['node' => $node]);
      \Drupal::logger('product')->notice('Product @product deleted', ['@product' => $node->getTitle()]);

      // invoke rules
      if ($moduleHandler->moduleExists('rules')) {
        // Set the args twice on the event: as the main subject but also in the
        // list of arguments.
        $event = new ProductDeleteEvent($node, ['product' => $node]);
        $eventDispatcher = \Drupal::service('event_dispatcher');
        $eventDispatcher->dispatch(ProductDeleteEvent::EVENT_NAME, $event);
      }
      $node->delete();
      unset($node);

      \Drupal::logger('product')->notice('delete product nid=@prod', ['@prod' => $nid]);
    }
    else {
      \Drupal::logger('product')->notice('No node found to delete for product nid=@prod', ['@prod' => $nid]);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * @param $name
   *
   * @return string - product icon for a given name
   *
   * @return string
   */
  public static function getRandomImageName($name): string {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $name);
    $asInt = 0;
    $strLength = mb_strlen($name);
    for ($i = 0; $i < $strLength; $i++) {
      $asInt += \ord($name[$i]);
    }
    $digit = $asInt % 19;
    if ($digit === 0) {
      $digit = 1;
    }
    $num = str_pad($digit, 2, 0, STR_PAD_LEFT);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $num);
    return 'product_' . $num . '.png';
  }

  /**
   * @param $name
   *
   * @return string - path to placeholder image for a given name
   *
   * @return string
   */
  public static function getPlaceholderImage($name): string {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $name);
    $returnValue = Url::fromUri('internal:/' . drupal_get_path('module', 'product') . '/images/' . self::getRandomImageName($name))
      ->toString();
    \Drupal::moduleHandler()->alter('product_getplaceholderimage', $returnValue);
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $returnValue;
  }

  /**
   * @param $name
   *
   * @return string - path to placeholder image for a given name
   *
   * @return string
   */
  public static function getPlaceholderImageURL($name): string {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $name);
    $rawImage = self::getRandomImageName($name);
    $returnValue = base_path() . drupal_get_path('module', 'product') . '/images/' . $rawImage;
    \Drupal::moduleHandler()->alter('product_getplaceholderimageurl', $returnValue);
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    return $returnValue;
  }

  /**
   * Check if we have access to specified product Node
   *
   * @param $node
   *
   * @return bool
   */
  public static function checkAccess($node): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $found = FALSE;
    $viewEnabled = TRUE;
    if ($node !== NULL) {

      // if view disabled then no one has access
      if ($node->product_view_enabled->value !== 1) {
        $viewEnabled = FALSE;
      }
      $userUtils = \Drupal::service('ibm_apim.user_utils');
      $myorg = $userUtils->getCurrentConsumerOrg();
      // allow those with permission to bypass check
      if ($userUtils->explicitUserAccess('edit any product content')) {
        $found = TRUE;
      }
      elseif (isset($myorg['url'])) {
        // if we're subscribed then allowed access
        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'application');
        $query->condition('application_consumer_org_url.value', $myorg['url']);

        $appResults = $query->execute();

        if ($appResults !== NULL && !empty($appResults)) {
          $nids = [];
          foreach ($appResults as $item) {
            $nids[] = $item;
          }
          $nodes = Node::loadMultiple($nids);
          if ($nodes) {
            foreach ($nodes as $app) {
              $subs = NULL;
              foreach ($app->application_subscriptions->getValue() as $nextSub) {
                $subs[] = unserialize($nextSub['value'], ['allowed_classes' => FALSE]);
              }
              if ($subs !== NULL && \is_array($subs)) {
                foreach ($subs as $sub) {
                  if (isset($sub['product']) && $sub['product'] === $node->apic_ref->value) {
                    $found = TRUE;
                  }
                }
              }
            }
          }
        }
      }
      if ($viewEnabled === TRUE) {
        $nodeCustomOrgs = $node->product_visibility_custom_orgs->getValue();
        $nodeCustomTags = $node->product_visibility_custom_tags->getValue();
        // check public nodes
        if ((int) $node->product_visibility_public->value === 1) {
          $found = TRUE;
        }
        elseif ((int) $node->product_visibility_authenticated->value === 1 && !\Drupal::currentUser()->isAnonymous()) {
          $found = TRUE;
        }
        elseif ($nodeCustomOrgs !== NULL && isset($myorg['url'])) {
          foreach ($nodeCustomOrgs as $customOrg) {
            if (isset($customOrg['value']) && $customOrg['value'] === $myorg['url']) {
              $found = TRUE;
            }
          }
        }
        elseif ($nodeCustomTags !== NULL && $myorg['url'] !== NULL) {
          $query = \Drupal::entityQuery('node');
          $query->condition('type', 'consumerorg');
          $query->condition('consumerorg_url.value', $myorg['url']);

          $consumerOrgResults = $query->execute();
          if ($consumerOrgResults !== NULL && !empty($consumerOrgResults)) {
            $nid = array_shift($consumerOrgResults);
            $consumerOrg = Node::load($nid);
            if ($consumerOrg !== NULL) {
              $tags = $consumerOrg->consumerorg_tags->getValue();
              if ($tags !== NULL && \is_array($tags) && count($tags) > 0) {
                foreach ($nodeCustomTags as $customTag) {
                  if (isset($customTag['value']) && \in_array($customTag['value'], $tags, FALSE)) {
                    $found = TRUE;
                  }
                }
              }
            }
          }
        }
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $found);
    return $found;
  }

  /**
   * Returns a list of product node ids the current user can access
   *
   * @return array
   */
  public static function listProducts(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $returnNids = [];
    $userUtils = \Drupal::service('ibm_apim.user_utils');

    // users with right perms get to see all
    if ($userUtils->explicitUserAccess('edit any product content')) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'product');
      $query->condition('status', 1);
      $query->condition('product_state.value', 'published');

      $results = $query->execute();
      if ($results !== NULL && !empty($results)) {
        $nids = array_values($results);
        if (!empty($nids)) {
          $returnNids = array_merge($returnNids, $nids);
        }
      }
    }
    else {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'product');
      $query->condition('status', 1);
      $query->condition('product_state.value', 'published');
      $query->condition('product_view_enabled.value', 1);
      $query->condition('product_visibility_public.value', 1);
      $results = $query->execute();
      if ($results !== NULL && !empty($results)) {
        $nids = array_values($results);
        if (!empty($nids)) {
          $returnNids = array_merge($returnNids, $nids);
        }
      }
      if (!\Drupal::currentUser()->isAnonymous()) {
        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'product');
        $query->condition('status', 1);
        $query->condition('product_state.value', 'published');
        $query->condition('product_view_enabled.value', 1);
        $query->condition('product_visibility_authenticated.value', 1);
        $results = $query->execute();
        if ($results !== NULL && !empty($results)) {
          $nids = array_values($results);
          if (!empty($nids)) {
            $returnNids = array_merge($returnNids, $nids);
          }
        }
        $myorg = $userUtils->getCurrentConsumerOrg();
        if (isset($myorg['id'])) {
          $query = \Drupal::entityQuery('node');
          $query->condition('type', 'product');
          $query->condition('status', 1);
          $query->condition('product_state.value', 'published');
          $query->condition('product_view_enabled.value', 1);
          $query->condition('product_visibility_custom_orgs.value', $myorg['id'], 'CONTAINS');
          $results = $query->execute();
          if ($results !== NULL && !empty($results)) {
            $nids = array_values($results);
            if (!empty($nids)) {
              $returnNids = array_merge($returnNids, $nids);
            }
          }

          $query = \Drupal::entityQuery('node');
          $query->condition('type', 'consumerorg');
          $query->condition('consumerorg_id.value', $myorg['id']);
          $consumerOrgResults = $query->execute();
          if ($consumerOrgResults !== NULL && !empty($consumerOrgResults)) {
            $consumerOrgNid = array_shift($consumerOrgResults);

            if (!empty($consumerOrgNid)) {
              $consumerOrg = Node::load($consumerOrgNid);
              if ($consumerOrg !== NULL) {
                $tags = $consumerOrg->consumerorg_tags->getValue();
                if ($tags !== NULL && \is_array($tags) && count($tags) > 0) {
                  $query = \Drupal::entityQuery('node');
                  $query->condition('type', 'product');
                  $query->condition('status', 1);
                  $query->condition('product_state.value', 'published');
                  $query->condition('product_view_enabled.value', 1);
                  $query->condition('product_visibility_custom_tags.value', $tags, 'IN');
                  $results = $query->execute();
                  if ($results !== NULL && !empty($results)) {
                    $nids = array_values($results);
                    if (!empty($nids)) {
                      $returnNids = array_merge($returnNids, $nids);
                    }
                  }
                }
              }
            }
          }

          // also grab any products we're subscribed to in order to include deprecated products
          $query = \Drupal::entityQuery('node');
          $query->condition('type', 'application');
          $query->condition('application_orgid.value', $myorg['id']);
          $results = $query->execute();
          if ($results !== NULL && !empty($results)) {
            $nids = array_values($results);
            $nodes = Node::loadMultiple($nids);
            if ($nodes) {
              $additional = [[]];
              foreach ($nodes as $app) {
                $subs = [];
                foreach ($app->application_subscriptions->getValue() as $nextSub) {
                  $subs[] = unserialize($nextSub['value'], ['allowed_classes' => FALSE]);
                }
                if ($subs !== NULL) {

                  foreach ($subs as $sub) {
                    if (isset($sub['product'])) {
                      $query = \Drupal::entityQuery('node');
                      $query->condition('type', 'product');
                      $query->condition('status', 1);
                      $query->condition('apic_ref.value', $sub['product']);
                      $results = $query->execute();
                      if ($results !== NULL && !empty($results)) {
                        $nids = array_values($results);
                        $additional[] = $nids;
                      }
                    }
                  }
                }
              }
              // this flattens the array and avoids doing array_merge in a for loop which is very CPU intensive
              $additional = array_merge(...$additional);
              $returnNids = array_merge($returnNids, $additional);
            }
          }
        }
      }
    }
    $returnValue = array_reverse(array_unique($returnNids, SORT_NUMERIC));
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    return $returnValue;
  }

  /**
   * A list of all the IBM created fields for this content type
   *
   * @return array
   */
  public static function getIBMFields(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $ibmFields = [
      'apic_hostname',
      'apic_provider_id',
      'apic_catalog_id',
      'apic_description',
      'apic_rating',
      'apic_tags',
      'apic_pathalias',
      'apic_summary',
      'apic_url',
      'apic_version',
      'apic_ref',
      'apic_image',
      'apic_attachments',
      'product_apis',
      'product_contact_email',
      'product_contact_name',
      'product_contact_url',
      'product_data',
      'product_id',
      'product_license_name',
      'product_license_url',
      'product_name',
      'product_plans',
      'product_state',
      'product_subscribe_enabled',
      'product_terms_of_service',
      'product_view_enabled',
      'product_visibility',
      'product_visibility_authenticated',
      'product_visibility_custom_orgs',
      'product_visibility_custom_tags',
      'product_visibility_public',
    ];
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $ibmFields;
  }

  /**
   * Get a list of all the custom fields on this content type
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function getCustomFields(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $coreFields = ['title', 'vid', 'status', 'nid', 'revision_log', 'created'];
    $components = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load('node.product.default')
      ->getComponents();
    $keys = array_keys($components);
    $ibmFields = self::getIBMFields();
    $merged = array_merge($coreFields, $ibmFields);
    $diff = array_diff($keys, $merged);
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $diff);
    return $diff;
  }

  /**
   * Get all the doc pages that are listed as being linked to a given Product NID
   *
   * @param $nid
   *
   * @return array
   */
  public static function getLinkedPages($nid): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $nid);
    $finalNids = [];
    $docs = [];
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'page');
    $query->condition('status', 1);
    $query->condition('allproducts.value', 1);
    $results = $query->execute();
    if ($results !== NULL && !empty($results)) {
      $finalNids = array_values($results);
    }
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'page');
    $query->condition('status', 1);
    $query->condition('prodref.target_id', $nid);
    $results = $query->execute();
    if ($results !== NULL && !empty($results)) {
      $nids = array_values($results);
      $finalNids = array_merge($finalNids, $nids);
    }
    // process the nodes and build an array of the info we need
    $finalNids = array_unique($finalNids, SORT_NUMERIC);
    if ($finalNids !== NULL && !empty($finalNids)) {
      $nodes = Node::loadMultiple($finalNids);
      if ($nodes !== NULL) {
        foreach ($nodes as $node) {
          $docs[] = [
            'title' => $node->getTitle(),
            'nid' => $node->id(),
            'url' => $node->toUrl()->toString(),
          ];
        }
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $docs);
    return $docs;
  }

  /**
   * Get a list of product nodes that include a given API reference
   *
   * @param null $apiRef
   *
   * @return array
   */
  public static function getProductsContainingAPI($apiRef = NULL): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $apiRef);

    $products = [];
    if ($apiRef !== NULL) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'product');
      $query->condition('product_apis', $apiRef, 'CONTAINS');
      $results = $query->execute();
      if ($results !== NULL && !empty($results)) {
        $products = array_values($results);
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $products);
    return $products;
  }

  /**
   * Returns a JSON representation of a product
   *
   * @param $url
   *
   * @return string (JSON)
   */
  public function getProductAsJson($url): string {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, ['url' => $url]);
    $output = '';
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'product');
    $query->condition('apic_url.value', $url);

    $nids = $query->execute();

    if ($nids !== NULL && !empty($nids)) {
      $nid = array_shift($nids);
      $node = Node::load($nid);
      $moduleHandler = \Drupal::service('module_handler');
      if ($moduleHandler->moduleExists('serialization')) {
        $serializer = \Drupal::service('serializer');
        $output = $serializer->serialize($node, 'json', ['plugin_id' => 'entity']);
      }
      else {
        \Drupal::logger('product')->notice('getProductAsJson: serialization module not enabled', []);
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $output;
  }

  /**
   * Used by the batch API from the AdminForm
   *
   * @param $nid
   */
  public function processCategoriesForNode($nid): void {
    $config = \Drupal::config('ibm_apim.settings');
    $categoriesEnabled = (boolean) $config->get('categories')['enabled'];
    if ($categoriesEnabled === TRUE) {
      $node = Node::load($nid);
      if ($node !== NULL) {
        $product = yaml_parse($node->product_data->value);
        if (isset($product['info']['categories'])) {
          $this->productTaxonomy->process_categories($product, $node);
        }
      }
    }
  }
}
