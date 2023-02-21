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

namespace Drupal\product;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Database;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Throwable;

/**
 * Class to work with the Product content type, takes input from the JSON returned by
 * IBM API Connect
 */
class Product {

  protected $apicTaxonomy;

  public function __construct() {
    $this->apicTaxonomy = \Drupal::service('ibm_apim.taxonomy');
  }

  /**
   * Create a new Product
   *
   * @param $product
   * @param string $event
   *
   * @return int|null|string
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
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
        $oldNode = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
      }
    }

    if ($oldNode !== NULL && $oldNode->id()) {
      $existingCategories = [];
      $oldYaml = yaml_parse($oldNode->product_data->value);
      if (isset($oldYaml['info']['categories'])) {
        $existingCategories = $oldYaml['info']['categories'];
      }
      $totalTags = $oldNode->apic_tags->getValue();
      if ($totalTags === NULL) {
        $totalTags = [];
      }

      $existingProductTags = $this->apicTaxonomy->separate_categories($totalTags, $existingCategories);
      $oldTags = [];
      if (is_array($existingProductTags) && !empty($existingProductTags)) {
        foreach ($existingProductTags as $tag) {
          if (isset($tag['target_id'])) {
            $oldTags[] = $tag['target_id'];
          }
        }
      }

      // duplicate node
      $node = $oldNode->createDuplicate();

      // update any apirefs on basic pages
      $this->updateBasicPageRefs($oldNode->id(), $node->id(), FALSE);

      // wipe all our fields to ensure they get set to new values
      $node->set('apic_tags', []);

      $node->set('uid', 1);
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
      $node->set('product_api_nids', NULL);
      $node->set('product_apis', NULL);
      $node->set('product_data', NULL);
      $node->set('apic_created_at', NULL);
      $node->set('apic_updated_at', NULL);
    }
    else {
      $node = \Drupal::entityTypeManager()->getStorage('node')->create([
        'type' => 'product',
        'title' => $product['catalog_product']['info']['name'],
        'apic_hostname' => $hostVariable,
        'apic_provider_id' => $siteConfig->getOrgId(),
        'apic_catalog_id' => $siteConfig->getEnvId(),
        'uid' => 1,
      ]);
    }

    // get the update method to do the update for us
    if ($node !== NULL) {
      $node = $this->update($node, $product, 'internal');
    }
    else {
      \Drupal::logger('product')->error('Create product: initial node not set.', []);
    }

    if ($node !== NULL && $oldTags !== NULL && !empty($oldTags)) {
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
      $node->setPublished();
      $node->save();
    }


    if ($node !== NULL) {
      // Calling all modules implementing 'hook_product_create':
      $moduleHandler->invokeAll('product_create', [
        'node' => $node,
        'data' => $product,
      ]);

      \Drupal::logger('product')->notice('Product @product @version created', [
        '@product' => $node->getTitle(),
        '@version' => $node->apic_version->value,
      ]);
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
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
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
          ->error('Update product: product @productName @version is in an invalid state: @state, deleting it.', [
            '@productName' => $product['name'],
            '@version' => $product['catalog_product']['info']['version'],
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
        $truncated_title = $utils->truncate_string($product['catalog_product']['info']['title']);
        // title must be set, if not fall back on name
        if (isset($truncated_title) && !empty($truncated_title)) {
          $node->setTitle($truncated_title);
        }
        else {
          $node->setTitle($product['catalog_product']['info']['name']);
        }
        if (isset($product['catalog_product']['info']['x-ibm-languages']['title']) && !empty($product['catalog_product']['info']['x-ibm-languages']['title'])) {
          foreach ($product['catalog_product']['info']['x-ibm-languages']['title'] as $lang => $langArray) {
            $lang = $utils->convert_lang_name_to_drupal($lang);
            // if its one of our locales or the root of one of our locales
            foreach ($languageList as $langListKey => $langListValue) {
              if (\in_array($lang, $languageList, FALSE)) {
                $truncated_title = $utils->truncate_string($product['catalog_product']['info']['x-ibm-languages']['title'][$lang]);
                // title needs to actually have a value
                if (isset($truncated_title) && !empty($truncated_title)) {
                  if (!$node->hasTranslation($lang)) {
                    $translation = $node->addTranslation($lang, ['title' => $truncated_title]);
                    $translation->save();
                  }
                  else {
                    $node->getTranslation($lang)->setTitle($truncated_title)->save();
                  }
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
                  // ensure the translation has a title as its a required field
                  $translation = $node->addTranslation($lang, [
                    'title' => $truncated_title,
                    'apic_description' => $product['catalog_product']['info']['x-ibm-languages']['description'][$lang],
                  ]);
                  $translation->save();
                }
                else {
                  $translation = $node->getTranslation($lang);
                  // ensure the translation has a title as its a required field
                  if ($translation->getTitle() === NULL || $translation->getTitle() === "") {
                    $translation->setTitle($truncated_title);
                  }
                  $translation->set('apic_description', $product['catalog_product']['info']['x-ibm-languages']['description'][$lang])
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
                  // ensure the translation has a title as its a required field
                  $translation = $node->addTranslation($lang, [
                    'title' => $truncated_title,
                    'apic_summary' => $utils->truncate_string($product['catalog_product']['info']['x-ibm-languages']['summary'][$lang]),
                    1000,
                  ]);
                  $translation->save();
                }
                else {
                  $translation = $node->getTranslation($lang);
                  // ensure the translation has a title as its a required field
                  if ($translation->getTitle() === NULL || $translation->getTitle() === "") {
                    $translation->setTitle($truncated_title);
                  }
                  $translation->set('apic_summary', $utils->truncate_string($product['catalog_product']['info']['x-ibm-languages']['summary'][$lang]), 1000)
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
        if ($moduleHandler->moduleExists('ghmarkdown')) {
          $format = 'ghmarkdown';
        }
        else {
          $format = 'full_html';
        }
        $node->set('product_terms_of_service', [
          'value' => $product['catalog_product']['info']['termsOfService'],
          'format' => $format,
        ]);
        if (isset($product['catalog_product']['info']['x-ibm-languages']['termsOfService']) && !empty($product['catalog_product']['info']['x-ibm-languages']['termsOfService'])) {
          foreach ($product['catalog_product']['info']['x-ibm-languages']['termsOfService'] as $lang => $langArray) {
            $lang = $utils->convert_lang_name_to_drupal($lang);
            // if its one of our locales or the root of one of our locales
            foreach ($languageList as $langListKey => $langListValue) {
              if (\in_array($lang, $languageList, FALSE)) {
                if (!$node->hasTranslation($lang)) {
                  // ensure the translation has a title as its a required field
                  $translation = $node->addTranslation($lang, [
                    'title' => $truncated_title,
                    'product_terms_of_service' => $product['catalog_product']['info']['x-ibm-languages']['termsOfService'][$lang],
                  ]);
                  $translation->save();
                }
                else {
                  $translation = $node->getTranslation($lang);
                  // ensure the translation has a title as its a required field
                  if ($translation->getTitle() === NULL || $translation->getTitle() === "") {
                    $translation->setTitle($truncated_title);
                  }
                  $translation->set('product_terms_of_service', $product['catalog_product']['info']['x-ibm-languages']['termsOfService'][$lang])
                    ->save();
                }
              }
            }
          }
        }
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

        if (isset($product['created_at'])) {
          // store as epoch, incoming format will be like 2021-02-26T12:18:59.000Z
          $node->set('apic_created_at', strtotime($product['created_at']));
        }
        if (isset($product['updated_at'])) {
          // store as epoch, incoming format will be like 2021-02-26T12:18:59.000Z
          $node->set('apic_updated_at', strtotime($product['updated_at']));
        }

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
            if (count($apiNids) > 0) {
              $node->set('product_api_nids', []);
              $product_api_nids = [];
              foreach (array_chunk($apiNids, 50) as $chunk) {
                $apis = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($chunk);
                foreach ($apis as $api) {
                  $product_api_nids[] = $api->id();
                  // this save is needed to trigger the update of the API's node access permissions
                  $api->save();
                }
              }
              $node->set('product_api_nids', $product_api_nids);
              $node->save();
            }
          }
        }

        // Product Categories
        $categoriesEnabled = (boolean) $config->get('categories')['enabled'];
        if ($categoriesEnabled === TRUE && isset($product['catalog_product']['info']['categories'])) {
          $this->apicTaxonomy->process_product_categories($product, $node);
        }

        // if invoked from the create code then don't invoke the update event - will be invoked from create instead
        if ($node !== NULL) {
          if ($event !== 'internal') {
            \Drupal::logger('product')->notice('Product @product @version updated', [
              '@product' => $node->getTitle(),
              '@version' => $node->apic_version->value,
            ]);
            // Calling all modules implementing 'hook_product_update':
            $moduleHandler->invokeAll('product_update', [
              'node' => $node,
              'data' => $product,
            ]);
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
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
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
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
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
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function deleteNode($nid, $event): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $nid);
    $moduleHandler = \Drupal::service('module_handler');

    $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);

    if ($node !== NULL) {
      // Calling all modules implementing 'hook_product_delete':
      $moduleHandler->invokeAll('product_delete', ['node' => $node]);
      \Drupal::logger('product')->notice('Product @product:@version deleted', [
        '@product' => $node->getTitle(),
        '@version' => $node->apic_version->value,
      ]);

      if (isset($node->apic_url->value)) {
        $subService = \Drupal::service('apic_app.subscriptions');
        $subService->deleteAllSubsForProduct($node->apic_url->value);
      }

      $node->delete();
      unset($node);

      // if deleting a product (and not part of lifecycle action like replace)
      // then remove any trailing references to it from basic doc pages
      if ($event === 'product_del' || $event === 'content_refresh') {
        self::updateBasicPageRefs($nid, NULL, FALSE);
      }

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
    $returnValue = Url::fromUri('internal:/' . \Drupal::service('extension.list.module')->getPath('product') . '/images/' . self::getRandomImageName($name))
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
    $returnValue = base_path() . \Drupal::service('extension.list.module')->getPath('product') . '/images/' . $rawImage;
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
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function checkAccess($node): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $found = FALSE;
    $viewEnabled = TRUE;
    if ($node !== NULL) {

      // if view disabled then no one has access
      if ((int) $node->product_view_enabled->value !== 1) {
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
        $query = \Drupal::entityQuery('apic_app_application_subs');
        $query->condition('consumerorg_url', $myorg['url']);
        $entityIds = $query->execute();
        if (isset($entityIds) && !empty($entityIds)) {
          foreach ($entityIds as $entityId) {
            $sub = \Drupal::entityTypeManager()->getStorage('apic_app_application_subs')->load($entityId);
            if ($sub !== NULL && $sub->product_url() === $node->apic_url->value) {
              $found = TRUE;
              break;
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
        elseif ((int) $node->product_visibility_authenticated->value === 1 && \Drupal::currentUser()->isAuthenticated()) {
          $found = TRUE;
        }
        if ($nodeCustomOrgs !== NULL && isset($myorg['url'])) {
          foreach ($nodeCustomOrgs as $customOrg) {
            if (isset($customOrg['value']) && $customOrg['value'] === $myorg['url']) {
              $found = TRUE;
            }
          }
        }
        if ($found === FALSE && $nodeCustomTags !== NULL && $myorg['url'] !== NULL) {
          $groups = [];
          if ($nodeCustomTags !== NULL && \is_array($nodeCustomTags) && count($nodeCustomTags) > 0) {
            foreach ($nodeCustomTags as $tag) {
              if (isset($tag['value'])) {
                $groups[] = $tag['value'];
              }
            }
          }

          $query = \Drupal::entityQuery('node');
          $query->condition('type', 'consumerorg');
          $query->condition('consumerorg_url.value', $myorg['url']);

          $consumerOrgResults = $query->execute();
          if ($consumerOrgResults !== NULL && !empty($consumerOrgResults)) {
            $nid = array_shift($consumerOrgResults);
            $consumerOrg = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
            if ($consumerOrg !== NULL) {
              $tags = $consumerOrg->consumerorg_tags->getValue();
              if ($tags !== NULL && \is_array($tags) && count($tags) > 0) {
                foreach ($nodeCustomTags as $customTag) {
                  if (isset($customTag['value']) && \in_array($customTag['value'], $groups, FALSE)) {
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
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
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
        if (isset($myorg['url'])) {
          $query = \Drupal::entityQuery('node');
          $query->condition('type', 'product');
          $query->condition('status', 1);
          $query->condition('product_state.value', 'published');
          $query->condition('product_view_enabled.value', 1);
          $query->condition('product_visibility_custom_orgs.value', $myorg['url'], 'CONTAINS');
          $results = $query->execute();
          if ($results !== NULL && !empty($results)) {
            $nids = array_values($results);
            if (!empty($nids)) {
              $returnNids = array_merge($returnNids, $nids);
            }
          }

          $query = \Drupal::entityQuery('node');
          $query->condition('type', 'consumerorg');
          $query->condition('consumerorg_url.value', $myorg['url']);
          $consumerOrgResults = $query->execute();
          if ($consumerOrgResults !== NULL && !empty($consumerOrgResults)) {
            $consumerOrgNid = array_shift($consumerOrgResults);

            if (!empty($consumerOrgNid)) {
              $consumerOrg = \Drupal::entityTypeManager()->getStorage('node')->load($consumerOrgNid);
              if ($consumerOrg !== NULL) {
                $tags = $consumerOrg->consumerorg_tags->getValue();
                $groups = [];
                if ($tags !== NULL && \is_array($tags) && count($tags) > 0) {
                  foreach ($tags as $tag) {
                    if (isset($tag['value'])) {
                      $groups[] = $tag['value'];
                    }
                  }
                }
                if ($groups !== NULL && \is_array($groups) && count($groups) > 0) {
                  $query = \Drupal::entityQuery('node');
                  $query->condition('type', 'product');
                  $query->condition('status', 1);
                  $query->condition('product_state.value', 'published');
                  $query->condition('product_view_enabled.value', 1);
                  $query->condition('product_visibility_custom_tags.value', $groups, 'IN');
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
          $query = \Drupal::entityQuery('apic_app_application_subs');
          $query->condition('consumerorg_url', $myorg['url']);
          $entityIds = $query->execute();
          if (isset($entityIds) && !empty($entityIds)) {
            $additional = [[]];
            foreach ($entityIds as $entityId) {
              $sub = \Drupal::entityTypeManager()->getStorage('apic_app_application_subs')->load($entityId);
              if ($sub !== NULL) {
                $query = \Drupal::entityQuery('node');
                $query->condition('type', 'product');
                $query->condition('apic_url.value', $sub->product_url());
                $results = $query->execute();
                if ($results !== NULL && !empty($results)) {
                  $nids = array_values($results);
                  $additional[] = $nids;
                }
              }
            }
            $additional = array_merge(...$additional);
            $returnNids = array_merge($returnNids, $additional);
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
      'apic_created_at',
      'apic_updated_at',
      'product_api_nids',
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

    // make sure we only include actual custom fields so check there is a field config
    foreach ($diff as $key => $field) {
      $fieldConfig = FieldConfig::loadByName('node', 'product', $field);
      if ($fieldConfig === NULL) {
        unset($diff[$key]);
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $diff);
    return $diff;
  }

  /**
   * Get all the doc pages that are listed as being linked to a given Product NID
   *
   * @param $nid
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityMalformedException
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
      foreach (array_chunk($finalNids, 50) as $chunk) {
        $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($chunk);
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
  public static function getProductsContainingAPI($apiNid = NULL): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $apiNid);

    $products = [];
    if ($apiNid !== NULL) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'product');
      $query->condition('product_api_nids', $apiNid, 'IN');
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
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
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
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
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
   * Returns an array representation of a product for returning to drush
   *
   * @param $url
   *
   * @return array
   */
  public static function getProductForDrush($url): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, ['url' => $url]);
    $output = NULL;
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'product');
    $query->condition('apic_url.value', $url);

    $nids = $query->execute();

    if ($nids !== NULL && !empty($nids)) {
      $nid = array_shift($nids);
      $node = Node::load($nid);
      if ($node !== NULL) {
        $output['url'] = $url;
        $output['id'] = $node->product_id->value;
        $output['name'] = $node->product_name->value;
        $output['version'] = $node->apic_version->value;
        $output['title'] = $node->getTitle();
        $output['state'] = $node->product_state->value;
        $output['summary'] = $node->apic_summary->value;
        $output['description'] = $node->apic_description->value;
        $output['billing_url'] = $node->product_billing_url->value;
        $output['created_at'] = $node->apic_created_at->value;
        $output['updated_at'] = $node->apic_updated_at->value;
        $productApis = [];
        foreach ($node->product_apis->getValue() as $arrayValue) {
          $productApis[] = unserialize($arrayValue['value'], ['allowed_classes' => FALSE]);
        }
        $output['apis'] = $productApis;
        $output['view_enabled'] = (bool) (int) $node->product_view_enabled->value;
        $output['subscribe_enabled'] = (bool) (int) $node->product_subscribe_enabled->value;
        $output['visibility_public'] = (bool) (int) $node->product_visibility_public->value;
        $output['visibility_authenticated'] = (bool) (int) $node->product_visibility_authenticated->value;
        $customOrgs = [];
        foreach ($node->product_visibility_custom_orgs->getValue() as $customOrg) {
          if ($customOrg['value'] !== NULL) {
            $customOrgs[] = $customOrg['value'];
          }
        }
        $output['visibility_custom_orgs'] = $customOrgs;
        $customTags = [];
        foreach ($node->product_visibility_custom_tags->getValue() as $customTag) {
          if ($customTag['value'] !== NULL) {
            $customTags[] = $customTag['value'];
          }
        }
        $output['visibility_custom_tags'] = $customTags;
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $output;
  }

  /**
   * Returns the actual product document for returning to drush
   *
   * @param $url
   *
   * @return array
   */
  public static function getProductDocumentForDrush($url): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, ['url' => $url]);
    $output = [];
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'product');
    $query->condition('apic_url.value', $url);

    $nids = $query->execute();

    if ($nids !== NULL && !empty($nids)) {
      $nid = array_shift($nids);
      $node = Node::load($nid);
      if ($node !== NULL) {
        $output = yaml_parse($node->product_data->value);
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $output;
  }

  /**
   * Used by the batch API from the AdminForm
   *
   * @param $nid
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function processCategoriesForNode($nid): void {
    $config = \Drupal::config('ibm_apim.settings');
    $categoriesEnabled = (boolean) $config->get('categories')['enabled'];
    if ($categoriesEnabled === TRUE) {
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
      if ($node !== NULL) {
        $product = yaml_parse($node->product_data->value);
        if (isset($product['info']['categories'])) {
          $this->apicTaxonomy->process_product_categories($product, $node);
        }
      }
    }
  }

  /**
   * Clears the cache of all applications that have a subscription to the given product url.
   * Optionally takes an array of subscription ids to obtain the app entity ids from
   *
   * @param $productUrl
   * @param null $subIds
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function clearAppCache($productUrl, $subIds = null): void {
    if (!isset($subIds)) {
      $subIds = self::getSubIdsFromProductUrl($productUrl);
    }

    // If any subscriptions where found get the ids of the application they belong to and reset the relevant caches
    if (!empty($subIds)) {
      $tags = [];
      $appEntityIds = self::getAppIdsFromSubIds($subIds);

      foreach ($appEntityIds as $appId) {
        $tags[] = 'node:' . $appId;
      }

      Cache::invalidateTags($tags);
      \Drupal::entityTypeManager()->getStorage('apic_app_application_subs')->resetCache($subIds);
      \Drupal::entityTypeManager()->getStorage('node')->resetCache($appEntityIds);
    }
  }

  /**
   * @param $productUrl
   * @return array
   */
  public static function getSubIdsFromProductUrl($productUrl): array {
    $subIds = [];

    $result = Database::getConnection()
      ->query("SELECT id FROM {apic_app_application_subs} WHERE product_url = :product_url", [':product_url' => $productUrl]);
    foreach ($result as $record) {
      $subIds[] = $record->id;
    }

    return $subIds;
  }

  /**
   * @param $subIds
   * @return array
   */
  public static function getAppIdsFromSubIds($subIds): array {
    $appEntityIds = [];
    $appResult = Database::getConnection()
      ->select('node__application_subscription_refs', 's')
      ->fields('s', ['entity_id'])
      ->condition('application_subscription_refs_target_id', $subIds, 'IN')
      ->execute();

    foreach ($appResult as $app) {
      $appEntityIds[] = $app->entity_id;
    }

    return $appEntityIds;
  }

  /**
   * Parse the embedded docs to handle the markdown / base 64 encoded html
   *
   * @param $productData
   *
   * @return array
   */
  public static function processEmbeddedDocs($productData): array {
    $returnArray = [];
    if (isset($productData) && is_array($productData) && !empty($productData)) {
      $returnArray = self::processEmbeddedDocsArray($productData);
    }

    return $returnArray;
  }

  /**
   * If custom docs are present then find the first one to use as the default
   *
   * @param $productData
   *
   * @return string
   */
  public static function findInitialEmbeddedDoc($productData = []): string {
    $returnValue = 'apisandplans';
    $found = FALSE;
    if (isset($productData) && is_array($productData) && !empty($productData)) {
      foreach ($productData as $key => $embeddedDoc) {
        if ($found === FALSE && !isset($embeddedDoc['docs'])) {
          $returnValue = $embeddedDoc['name'];
          $found = TRUE;
        }
        elseif ($found === FALSE && isset($embeddedDoc['docs'])) {
          foreach ($embeddedDoc['docs'] as $childkey => $embeddedDocChild) {
            if ($found === FALSE && !isset($embeddedDocChild['docs'])) {
              $returnValue = $embeddedDocChild['name'];
              $found = TRUE;
            }
            elseif ($found === FALSE && isset($embeddedDocChild['docs'])) {
              foreach ($embeddedDocChild['docs'] as $grandchildkey => $embeddedDocGrandChild) {
                if ($found === FALSE && !isset($embeddedDocGrandChild['docs'])) {
                  $returnValue = $embeddedDocGrandChild['name'];
                  $found = TRUE;
                }
              }
            }
          }
        }
      }
    }
    return $returnValue;
  }

  /**
   * Split out to separate function so it can call itself to handle nested arrays of docs
   *
   * @param $docs
   *
   * @return array
   */
  private static function processEmbeddedDocsArray($docs): array {
    $moduleHandler = \Drupal::service('module_handler');
    foreach ($docs as $key => $embeddedDoc) {
      if (isset($embeddedDoc['docs'])) {
        // nested section
        $docs[$key]['docs'] = self::processEmbeddedDocsArray($embeddedDoc['docs']);
      }
      elseif (isset($embeddedDoc['content'])) {
        if ((!isset($embeddedDoc['format']) || $embeddedDoc['format'] === 'md') && $moduleHandler->moduleExists('ghmarkdown')) {
          $parser = new \Drupal\ghmarkdown\cebe\markdown\GithubMarkdown();
          $text = $parser->parse($embeddedDoc['content']);
        }
        elseif ($embeddedDoc['format'] === 'b64html') {
          $text = base64_decode($embeddedDoc['content']);
        }
        else {
          // just use raw content
          $text = $embeddedDoc['content'];
        }
        $docs[$key]['output'] = $text;
      }
    }
    return $docs;
  }

  /**
   * This function handles mass updates to subscriptions from lifecycle actions like
   * product migrate subscriptions.  It differs from processPalnMapping because it expects
   * the mapping to contain an array of subscription urls to migrate.
   *
   * @param $mapping
   *
   * @throws Throwable
   */
  public static function processPlanMappingWithSubscriptionUrls($mapping, $subscriptionUrls): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    if (isset($mapping)) {
      if (isset($mapping['source'], $mapping['target'], $mapping['plans'], $subscriptionUrls) && !empty($mapping['plans']) && !empty($subscriptionUrls)) {
        $sourceProductUrl = '/consumer-api/products/' . $mapping['source'];
        $targetProductUrl = '/consumer-api/products/' . $mapping['target'];

        $subUuids = [];
        foreach ($subscriptionUrls as $subUrl) {
          $urlParts = explode('/', $subUrl);
          $subUuids[] = end($urlParts);
        }

        $db = Database::getConnection();

        // Perform this in a transaction so we can roll back any deleted subscriptions if an error occurs
        $transaction = $db->startTransaction();

        try {

          foreach (array_chunk($subUuids, 1000) as $chunk) {
            // Find the subscription records of any app subscribed to the source product (in the list of uuids) that already has a subscription to the target product
            $result = $db->query("SELECT uuid FROM {apic_app_application_subs} s WHERE s.product_url = :source_product_url AND s.uuid IN (:sub_uuids[]) AND EXISTS (SELECT id FROM {apic_app_application_subs} s2 WHERE s.app_url = s2.app_url AND s2.product_url = :target_product_url AND :target_product_url <> :source_product_url) ;", [':source_product_url' => $sourceProductUrl, ':target_product_url' => $targetProductUrl, ':sub_uuids[]' => $chunk]);

            if ($result && $subsToDelete = $result->fetchCol()) {
              foreach ($subsToDelete as $sub) {
                \Drupal::logger('product')->info('Found an app that already has a subscription to the target product @t as well as to the source product.  Deleting the subscription record with uuid @uuid that was subscribed to the source product @s', ['@t' => $targetProductUrl, '@uuid' => $sub, '@s' => $sourceProductUrl]);
              }
              // Delete the subscription(s) from all relevant tables
              $db->query("DELETE s, n, r FROM {apic_app_application_subs} s INNER JOIN {node__application_subscription_refs} n ON n.application_subscription_refs_target_id = s.id INNER JOIN {node_revision__application_subscription_refs} r ON r.application_subscription_refs_target_id = s.id WHERE s.uuid IN (:sub_uuids[])", [':sub_uuids[]' => $subsToDelete]);
            } else {
              \Drupal::logger('product')->info('Did not find any applications with existing subscriptions to the target product as well as to the source product.  Nothing to delete');
            }
          }

          // loop over the plans to update all the relevant subscriptions
          foreach ($mapping['plans'] as $planMap) {
            if (isset($planMap['source'], $planMap['target'])) {
              $options = ['target' => 'default'];
              foreach (array_chunk($subUuids, 1000) as $chunk) {
                Database::getConnection($options['target'])
                  ->update('apic_app_application_subs', $options)
                  ->fields(['plan' => $planMap['target'], 'product_url' => $targetProductUrl])
                  ->condition('product_url', $sourceProductUrl)
                  ->condition('plan', $planMap['source'])
                  ->condition('uuid', $chunk, 'IN')
                  ->execute();
              }

              $subIds = [];
              $subscriptionsResult = Database::getConnection($options['target'])
                ->select('apic_app_application_subs', 's')
                ->fields('s', ['id'])
                ->condition('s.product_url', $targetProductUrl)
                ->condition('s.plan', $planMap['target'])
                ->execute();

              foreach ($subscriptionsResult as $sub) {
                $subIds[] = $sub->id;
              }
            } else {
              \Drupal::logger('product')->error('ERROR: Missing required data in processPlanMapping plan map array', []);
            }
          }
        } catch (Throwable $e) {
          $transaction->rollBack();
          throw $e;
        }
        unset($transaction);

        // If any subscriptions where found get the ids of the application they belong to and reset the relevant caches
        if (!empty($subIds)) {
          self::clearAppCache($targetProductUrl, $subIds);
        }
      }
      else {
        \Drupal::logger('product')->error('ERROR: Missing required data in processPlanMapping', []);
      }
    }
    else {
      \Drupal::logger('product')->error('ERROR: No processPlanMapping mapping provided', []);
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }


  /**
   * This function handles mass updates to subscriptions from lifecycle actions like
   * product replace.
   *
   * @param $mapping
   *
   * @throws Throwable
   */
  public static function processPlanMapping($mapping): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    if (isset($mapping)) {
      if (isset($mapping['source'], $mapping['target'], $mapping['plans']) && !empty($mapping['plans'])) {
        $sourceProductUrl = '/consumer-api/products/' . $mapping['source'];
        $targetProductUrl = '/consumer-api/products/' . $mapping['target'];
        $subIds = [];
        $db = Database::getConnection();

        // Perform this in a transaction so we can roll back any deleted subscriptions if an error occurs
        $transaction = $db->startTransaction();
        try {
          // Find the subscription records of any app subscribed to the source product that already has a subscription to the target product
          $result = $db->query("SELECT uuid FROM {apic_app_application_subs} s WHERE s.product_url = :source_product_url AND EXISTS (SELECT id FROM {apic_app_application_subs} s2 WHERE s.app_url = s2.app_url AND s2.product_url = :target_product_url);", [':source_product_url' => $sourceProductUrl, ':target_product_url' => $targetProductUrl]);

          if ($result && $subsToDelete = $result->fetchCol()) {
            foreach ($subsToDelete as $sub) {
              \Drupal::logger('product')->info('Found an app that already has a subscription to the target product @t as well as to the source product.  Deleting the subscription record with uuid @uuid that was subscribed to the source product @s', ['@t' => $targetProductUrl, '@uuid' => $sub, '@s' => $sourceProductUrl]);
            }
            // Delete the subscription(s) from all relevant tables
            $db->query("DELETE s, n, r FROM {apic_app_application_subs} s INNER JOIN {node__application_subscription_refs} n ON n.application_subscription_refs_target_id = s.id INNER JOIN {node_revision__application_subscription_refs} r ON r.application_subscription_refs_target_id = s.id WHERE s.uuid IN (:sub_uuids[])", [':sub_uuids[]' => $subsToDelete]);
          } else {
            \Drupal::logger('product')->info('Did not find any applications with existing subscriptions to the target product as well as to the source product.  Nothing to delete');
          }
          // loop over the plans to update all the subscriptions
          foreach ($mapping['plans'] as $planMap) {
            if (isset($planMap['source'], $planMap['target'])) {
              $options = ['target' => 'default'];
              $db->update('apic_app_application_subs', $options)
                ->fields(['plan' => $planMap['target'], 'product_url' => $targetProductUrl])
                ->condition('product_url', $sourceProductUrl)
                ->condition('plan', $planMap['source'])
                ->execute();

              $subscriptionsResult = $db->select('apic_app_application_subs', 's')
                ->fields('s', ['id'])
                ->condition('s.product_url', $targetProductUrl)
                ->condition('s.plan', $planMap['target'])
                ->execute();

              foreach ($subscriptionsResult as $sub) {
                $subIds[] = $sub->id;
              }
            } else {
              \Drupal::logger('product')->error('ERROR: Missing required data in processPlanMapping plan map array', []);
            }
          }
        } catch (Throwable $e) {
          $transaction->rollback();
          throw $e;
        }
        unset($transaction);

        // If any subscriptions where found get the ids of the application they belong to and reset the relevant caches
        if (!empty($subIds)) {
          self::clearAppCache($targetProductUrl, $subIds);
        }
      }
      else {
        \Drupal::logger('product')->error('ERROR: Missing required data in processPlanMapping', []);
      }
    }
    else {
      \Drupal::logger('product')->error('ERROR: No processPlanMapping mapping provided', []);
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Update any basic page references to point to the new node
   *
   * @param $oldNid
   * @param $newNid
   * @param boolean $add
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function updateBasicPageRefs($oldNid, $newNid, bool $add = FALSE): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    if (isset($oldNid)) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'page')->condition('prodref', $oldNid, 'CONTAINS');
      $nids = $query->execute();
      if ($nids !== NULL && !empty($nids)) {
        foreach ($nids as $nid) {
          $node = Node::load($nid);
          if ($node !== NULL) {
            $newArray = $node->prodref->getValue();
            foreach ($newArray as $key => $value) {
              if ($value['target_id'] === (string) $oldNid) {
                if (isset($newNid)) {
                  if ($add === TRUE) {
                    // add the new node to the list
                    $newArray[]['target_id'] = (string) $newNid;
                  }
                  else {
                    // replace existing node
                    $newArray[$key]['target_id'] = (string) $newNid;
                  }
                }
                else {
                  // if no $newNid set then simply remove the old reference
                  unset($newArray[$key]);
                }
              }
            }
            $node->set('prodref', $newArray);
            $node->save();
          }
        }
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Find all plans in the product that contain the api
   *
   * @param $product - the product being looked in
   * @param $apiref - the api being looked for
   *
   * @return array(plan_name => plan)
   *
   */
  public static function getPlansThatContainApi(Node $product, string $apiRef): array {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $plans = [];

    if (isset($product, $apiRef)) {
      $filterFunc = static function ($serializedApi) use ($apiRef) {
        // data exists within serialization, can skip an unserialize here
        return str_contains($serializedApi['value'], $apiRef);
      };

      if (isset($product->product_apis) && count(array_filter($product->product_apis->getValue(), $filterFunc)) > 0) {
        $productPlans = [];
        if (isset($product->product_plans)) {
          foreach ($product->product_plans->getValue() as $arrayValue) {
            $productPlans[] = unserialize($arrayValue['value'], ['allowed_classes' => FALSE]);
          }
        }

        $productData = yaml_parse($product->product_data->value);

        foreach ($productPlans as $productPlan) {
          if (isset($productPlan['apis']) && !empty($productPlan['apis'])) {
            foreach (array_keys($productPlan['apis']) as $apiKey) {
              $productApiRef = $productData['apis'][$apiKey]['name'];
              if ($productApiRef === $apiRef) {
                $plans[$productPlan['name']] = $productPlan;
              }
            }
            // if a plan has no 'apis', it contains all the api of the product
          }
          else {
            $plans[$productPlan['name']] = $productPlan;
          }
        }
      }
    }
    else {
      \Drupal::logger('product')
        ->error('Missing values for plan lookup - product:%productSet, apiRef:%apiRefSet', [
          '%productSet' => isset($product),
          '%apiRefSet' => isset($apiRef),
        ]);
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $plans);
    }

    return $plans;
  }

}
