<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\product;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
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
   * @return int|null|string
   */
  public function create($product, $event = 'publish') {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $moduleHandler = \Drupal::service('module_handler');
    $siteconfig = \Drupal::service('ibm_apim.site_config');
    $hostvariable = $siteconfig->getApimHost();

    $oldtags = NULL;

    if (isset($product) && isset($product['catalog_product']['info']) && isset($product['catalog_product']['info']['name']) && isset($product['catalog_product']['info']['version'])) {
      $xname = $product['catalog_product']['info']['name'];
      if (strlen($product['catalog_product']['info']['name'] . ':' . $product['catalog_product']['info']['version']) > 254) {
        // if product reference is too long then bomb out
        \Drupal::logger('product')
          ->error('ERROR: Cannot create product. The "name:version" for this product is greater than 254 characters: %name %version', array(
            '%name' => $product['catalog_product']['info']['name'],
            '%version' => $product['catalog_product']['info']['version']
          ));
        return NULL;
      }
    }

    if (isset($xname)) {
      // find if there is an existing node for this API (maybe at old version)
      // using x-ibm-name from swagger doc
      // if so then clone it and base new node on that.
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'product')->condition('product_name.value', $xname)->sort('nid', 'ASC');
      $nids = $query->execute();
    }

    if (isset($nids) && !empty($nids)) {
      $nid = array_shift($nids);
      $oldnode = Node::load($nid);
    }
    if (isset($oldnode) && $oldnode->id()) {
      $existing_product_tags = $oldnode->apic_tags->getValue();
      if ($existing_product_tags && is_array($existing_product_tags)) {
        foreach ($existing_product_tags as $tag) {
          if (isset($tag['target_id'])) {
            $oldtags[] = $tag['target_id'];
          }
        }
      }

      // duplicate node
      $node = $oldnode->createDuplicate();

      // wipe all our fields to ensure they get set to new values
      $node->set('apic_tags', array());

      $node->set('apic_hostname', $hostvariable);
      $node->set('apic_provider_id', $siteconfig->getOrgId());
      $node->set('apic_catalog_id', $siteconfig->getEnvId());
      $node->set('apic_description', NULL);
      $node->set('apic_url', NULL);
      $node->set('apic_version', NULL);
      $node->set('apic_ref', NULL);
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
      $node = Node::create(array(
        'type' => 'product',
        'title' => $product['catalog_product']['info']['name'],
        'apic_hostname' => $hostvariable,
        'apic_provider_id' => $siteconfig->getOrgId(),
        'apic_catalog_id' => $siteconfig->getEnvId()
      ));
    }

    // get the update method to do the update for us
    $node = $this->update($node, $product, 'internal');

    if (isset($oldtags)) {
      $currenttags = $node->get('apic_tags')->getValue();
      if (!is_array($currenttags)) {
        $currenttags = array();
      }
      foreach ($oldtags as $tid) {
        if (isset($tid)) {
          $found = FALSE;
          foreach ($currenttags as $currentvalue) {
            if (isset($currentvalue['target_id']) && $currentvalue['target_id'] == $tid) {
              $found = TRUE;
            }
          }
          if ($found == FALSE) {
            $currenttags[] = array('target_id' => $tid);
          }
        }
      }
      $node->set('apic_tags', $currenttags);
      $node->save();
    }

    if (!$moduleHandler->moduleExists('workbench_moderation')) {
      $node->setPublished(TRUE);
      $node->save();
    }


    if (isset($node)) {
      // Calling all modules implementing 'hook_product_create':
      $moduleHandler->invokeAll('product_create', array(
        'node' => $node,
        'data' => $product
      ));
      // invoke rules
      if ($moduleHandler->moduleExists('rules')) {
        // Set the args twice on the event: as the main subject but also in the
        // list of arguments.
        $event = new ProductPublishEvent($node, ['product' => $node]);
        $event_dispatcher = \Drupal::service('event_dispatcher');
        $event_dispatcher->dispatch(ProductPublishEvent::EVENT_NAME, $event);
      }

      \Drupal::logger('product')->notice('Product @product created', array('@product' => $node->getTitle()));
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $node->id());
    return $node->id();
  }

  /**
   * Update an existing Product
   *
   * @param $node
   * @param $product
   * @param string $event
   * @return NodeInterface|null
   */
  public function update(NodeInterface $node, $product, $event = 'content_refresh') {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    if (isset($node)) {
      $utils = \Drupal::service('ibm_apim.utils');
      $siteconfig = \Drupal::service('ibm_apim.site_config');
      $moduleHandler = \Drupal::service('module_handler');
      $hostvariable = $siteconfig->getApimHost();
      $config = \Drupal::config('ibm_apim.settings');
      $language_list = array_keys(\Drupal::languageManager()->getLanguages(LanguageInterface::STATE_ALL));

      if (isset($product) && isset($product['catalog_product']['info']) && isset($product['catalog_product']['info']['name']) && isset($product['catalog_product']['info']['version'])) {
        if (strlen($product['catalog_product']['info']['name'] . ':' . $product['catalog_product']['info']['version']) > 254) {
          // if product reference is too long then bomb out
          \Drupal::logger('product')
            ->error('ERROR: Cannot update product. The "name:version" for this product is greater than 254 characters: %name %version', array(
              '%name' => $product['catalog_product']['info']['name'],
              '%version' => $product['catalog_product']['info']['version']
            ));
          return NULL;
        }
      }

      $node->setTitle($utils->truncate_string($product['catalog_product']['info']['title']));
      if (isset($product['catalog_product']['info']['x-ibm-languages']['title']) && !empty($product['catalog_product']['info']['x-ibm-languages']['title'])) {
        foreach ($product['catalog_product']['info']['x-ibm-languages']['title'] as $lang => $lang_array) {
          $lang = $utils->convert_lang_name_to_drupal($lang);
          // if its one of our locales or the root of one of our locales
          foreach ($language_list as $lang_list_key => $lang_list_value) {
            if ($lang == $lang_list_key || $lang == substr($lang_list_key, 0, count($lang))) {
              if (!$node->hasTranslation($lang)) {
                $translation = $node->addTranslation($lang, array('title' => $utils->truncate_string($product['catalog_product']['info']['x-ibm-languages']['title'][$lang])));
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
      $node->set("apic_hostname", $hostvariable);
      $node->set("apic_provider_id", $siteconfig->getOrgId());
      $node->set("apic_catalog_id", $siteconfig->getEnvId());
      $node->set("product_id", $product['id']);
      $node->set("product_state", mb_strtolower($product['state']));
      $node->set("apic_ref", $product['catalog_product']['info']['name'] . ':' . $product['catalog_product']['info']['version']);
      $node->set("product_name", $product['catalog_product']['info']['name']);
      $node->set("apic_version", $product['catalog_product']['info']['version']);

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
      $node->set("apic_description", [
        'value' => $product['catalog_product']['info']['description'],
        'format' => $format
      ]);
      if (isset($product['catalog_product']['info']['x-ibm-languages']['description']) && !empty($product['catalog_product']['info']['x-ibm-languages']['description'])) {
        foreach ($product['catalog_product']['info']['x-ibm-languages']['description'] as $lang => $lang_array) {
          $lang = $utils->convert_lang_name_to_drupal($lang);
          // if its one of our locales or the root of one of our locales
          foreach ($language_list as $lang_list_key => $lang_list_value) {
            if ($lang == $lang_list_key || $lang == substr($lang_list_key, 0, count($lang))) {
              if (!$node->hasTranslation($lang)) {
                $translation = $node->addTranslation($lang, array('apic_description' => $product['catalog_product']['info']['x-ibm-languages']['description'][$lang]));
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
        foreach ($product['catalog_product']['info']['x-ibm-languages']['summary'] as $lang => $lang_array) {
          $lang = $utils->convert_lang_name_to_drupal($lang);
          // if its one of our locales or the root of one of our locales
          foreach ($language_list as $lang_list_key => $lang_list_value) {
            if ($lang == $lang_list_key || $lang == substr($lang_list_key, 0, count($lang))) {
              if (!$node->hasTranslation($lang)) {
                $translation = $node->addTranslation($lang, array(
                  'apic_summary' => $utils->truncate_string($product['catalog_product']['info']['x-ibm-languages']['summary'][$lang]),
                  1000
                ));
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
        'format' => 'plaintext'
      ]);

      if (!isset($product['catalog_product']['info']['contact'])) {
        $product['catalog_product']['info']['contact'] = array(
          'name' => "",
          'email' => "",
          'url' => ""
        );
      }
      if (!isset($product['catalog_product']['info']['contact']['name'])) {
        $product['catalog_product']['info']['contact']['name'] = "";
      }
      if (!isset($product['catalog_product']['info']['contact']['email'])) {
        $product['catalog_product']['info']['contact']['email'] = "";
      }
      if (!isset($product['catalog_product']['info']['contact']['url'])) {
        $product['catalog_product']['info']['contact']['url'] = "";
      }
      $node->set("product_contact_name", $product['catalog_product']['info']['contact']['name']);
      $node->set("product_contact_email", $product['catalog_product']['info']['contact']['email']);
      $node->set("product_contact_url", $product['catalog_product']['info']['contact']['url']);
      if (!isset($product['catalog_product']['info']['license'])) {
        $product['catalog_product']['info']['license'] = array(
          'name' => "",
          'url' => ""
        );
      }
      $node->set("product_license_name", $product['catalog_product']['info']['license']['name']);
      $node->set("product_license_url", $product['catalog_product']['info']['license']['url']);
      if (!isset($product['catalog_product']['info']['termsOfService']) || empty($product['catalog_product']['info']['termsOfService'])) {
        $product['catalog_product']['info']['termsOfService'] = '';
      }
      $node->set("product_terms_of_service", $product['catalog_product']['info']['termsOfService']);
      $node->set('product_visibility', array());
      if (isset($product['catalog_product']['visibility'])) {
        foreach ($product['catalog_product']['visibility'] as $type => $visibility) {
          $node->product_visibility[] = serialize(array($type => $visibility));
        }
      }
      else {
        $node->set('product_visibility', array());
      }
      // default to product visibility being enabled to avoid bugs in apim where the value is not getting set correctly
      if (isset($product['catalog_product']['visibility']['view']) && array_key_exists('enabled', $product['catalog_product']['visibility']['view']) && $product['catalog_product']['visibility']['view']['enabled'] == FALSE) {
        $node->set("product_view_enabled", 0);
      }
      else {
        $node->set("product_view_enabled", 1);
      }
      if (isset($product['catalog_product']['visibility']['subscribe']['enabled']) && $product['catalog_product']['visibility']['subscribe']['enabled'] == TRUE) {
        $node->set("product_subscribe_enabled", 1);
      }
      else {
        $node->set("product_subscribe_enabled", 0);
      }
      if (isset($product['catalog_product']['visibility']['view']['type']) && mb_strtolower($product['catalog_product']['visibility']['view']['type']) == 'public') {
        $node->set("product_visibility_public", 1);
      }
      else {
        $node->set("product_visibility_public", 0);
      }
      if (isset($product['catalog_product']['visibility']['view']['type']) && mb_strtolower($product['catalog_product']['visibility']['view']['type']) == 'authenticated') {
        $node->set("product_visibility_authenticated", 1);
      }
      else {
        $node->set("product_visibility_authenticated", 0);
      }
      $product_visibility_custom_orgs = array();
      if (isset($product['catalog_product']['visibility']['view']['type']) && $product['catalog_product']['visibility']['view']['type'] == 'custom' && isset($product['catalog_product']['visibility']['view']['org_urls'])) {
        foreach ($product['catalog_product']['visibility']['view']['org_urls'] as $org_url) {
          $product_visibility_custom_orgs[] = $org_url;
        }
      }
      $node->set('product_visibility_custom_orgs', $product_visibility_custom_orgs);
      $product_visibility_custom_tags = array();
      if (isset($product['catalog_product']['visibility']['view']['type']) && $product['catalog_product']['visibility']['view']['type'] == 'custom' && isset($product['catalog_product']['visibility']['view']['tags'])) {
        foreach ($product['catalog_product']['visibility']['view']['tags'] as $tag) {
          $product_visibility_custom_tags[] = $tag;
        }
      }
      $node->set('product_visibility_custom_tags', $product_visibility_custom_tags);
      $node->set('apic_url', $product['url']);
      if (isset($product['billing_url'])) {
        $node->set('product_billing_url', $product['billing_url']);
      }
      if (isset($product['catalog_product']['plans'])) {
        // merge in the supersedes / superseded-by info which is outside the product doc
        if (isset($product['supersedes'])) {
          foreach ($product['supersedes'] as $supersedes_array) {
            $prod_url = $supersedes_array['product_url'];
          }
          foreach ($supersedes_array['plans'] as $supersede_plan) {
            $source_plan = $supersede_plan['source'];
            $target_plan = $supersede_plan['target'];
            if (isset($product['catalog_product']['plans'][$target_plan])) {
              if (!is_array($product['catalog_product']['plans'][$target_plan]['supersedes'])) {
                $product['catalog_product']['plans'][$target_plan]['supersedes'] = [];
              }
              $product['catalog_product']['plans'][$target_plan]['supersedes'][] = [
                "product_url" => $prod_url,
                "plan" => $source_plan
              ];
            }
          }
        }
        if (isset($product['superseded_by'])) {
          $prod_url = $product['superseded_by']['product_url'];
          foreach ($product['superseded_by']['plans'] as $supersede_plan) {
            $source_plan = $supersede_plan['source'];
            $target_plan = $supersede_plan['target'];
            if (isset($product['catalog_product']['plans'][$source_plan])) {
              $product['catalog_product']['plans'][$source_plan]['superseded-by'] = [
                "product_url" => $prod_url,
                "plan" => $target_plan
              ];
            }
          }
        }
        $node->set('product_plans', array());
        foreach ($product['catalog_product']['plans'] as $planName => $plan) {
          $plan['name'] = $planName;
          $node->product_plans[] = serialize($plan);
        }
      }
      else {
        $node->set('product_plans', array());
      }
      $node->set('product_apis', array());
      if (isset($product['catalog_product']['apis'])) {
        foreach ($product['catalog_product']['apis'] as $api) {
          $node->product_apis[] = serialize($api);
        }
      }
      else {
        $node->set('product_apis', array());
      }
      $node->set('product_data', \yaml_emit($product['catalog_product'], YAML_UTF8_ENCODING));
      $node->save();

      // need to trigger save of all apis in order to build up ACL
      if (isset($product['catalog_product']['apis']) && !empty($product['catalog_product']['apis'])) {
        $apiRefs = array();
        $apiNids = array();
        foreach ($product['catalog_product']['apis'] as $key => $prodref) {
          $apiRefs[$key] = $prodref['name'];
        }
        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'api');
        $query->condition('status', 1);
        $query->condition('apic_ref.value', $apiRefs, 'IN');
        $results = $query->execute();
        if (isset($results) && !empty($results)) {
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
      $categories_enabled = $config->get('categories')['enabled'];
      if (isset($product['catalog_product']['info']['categories']) && $categories_enabled) {
        $this->productTaxonomy->process_categories($product, $node);
      }

      // if invoked from the create code then dont invoke the update event - will be invoked from create instead
      if (isset($node) && $event != 'internal') {
        \Drupal::logger('product')->notice('Product @product updated', array('@product' => $node->getTitle()));

        // Calling all modules implementing 'hook_product_update':
        $moduleHandler->invokeAll('product_update', array(
          'node' => $node,
          'data' => $product
        ));
        // invoke rules
        if ($moduleHandler->moduleExists('rules')) {

          $event_dispatcher = \Drupal::service('event_dispatcher');
          // Set the args twice on the event: as the main subject but also in the
          // list of arguments.
          if (isset($event) && $utils->endsWith($event, 'replace')) {
            $event = new ProductReplaceEvent($node, ['product' => $node]);
            $event_dispatcher->dispatch(ProductReplaceEvent::EVENT_NAME, $event);
          }
          else {
            if (isset($event) && $utils->endsWith($event, 'supersede')) {
              $event = new ProductSupersedeEvent($node, ['product' => $node]);
              $event_dispatcher->dispatch(ProductSupersedeEvent::EVENT_NAME, $event);
            }
            else {
              if (isset($event) && $utils->endsWith($event, 'restageFromDraft')) {
                $event = new ProductRestageEvent($node, ['product' => $node]);
                $event_dispatcher->dispatch(ProductRestageEvent::EVENT_NAME, $event);
              }
              else {
                if (isset($event) && $utils->endsWith($event, 'deprecate')) {
                  $event = new ProductDeprecateEvent($node, ['product' => $node]);
                  $event_dispatcher->dispatch(ProductDeprecateEvent::EVENT_NAME, $event);
                }
                else {
                  if (!isset($event) || $utils->endsWith($event, 'update')) {
                    $event = new ProductUpdateEvent($node, ['product' => $node]);
                    $event_dispatcher->dispatch(ProductUpdateEvent::EVENT_NAME, $event);
                  }
                }
              }
            }
          }
        }
      }
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $node->id());
      return $node;
    }
    else {
      \Drupal::logger('product')->error('Update product: no node provided.', array());
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      return NULL;
    }
  }

  /**
   * Create a new product if one doesnt already exist for that Product reference
   * Update one if it does
   *
   * @param $product
   * @param $event
   * @return bool
   */
  public function createOrUpdate($product, $event) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'product');
    $query->condition('apic_ref.value', $product['catalog_product']['info']['name'] . ':' . $product['catalog_product']['info']['version']);

    $nids = $query->execute();

    if (isset($nids) && !empty($nids)) {
      $nid = array_shift($nids);
      $node = Node::load($nid);
      $this->update($node, $product, $event);
      $createdOrUpdated = FALSE;
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
   * @param $nid
   * @param $event
   */
  public static function deleteNode($nid, $event) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $nid);
    $moduleHandler = \Drupal::service('module_handler');

    $node = Node::load($nid);

    // Calling all modules implementing 'hook_product_delete':
    $moduleHandler->invokeAll('product_delete', array('node' => $node));
    \Drupal::logger('product')->notice('Product @product deleted', array('@product' => $node->getTitle()));

    // invoke rules
    if ($moduleHandler->moduleExists('rules')) {
      // Set the args twice on the event: as the main subject but also in the
      // list of arguments.
      $event = new ProductDeleteEvent($node, ['product' => $node]);
      $event_dispatcher = \Drupal::service('event_dispatcher');
      $event_dispatcher->dispatch(ProductDeleteEvent::EVENT_NAME, $event);
    }
    $node->delete();
    unset($node);

    \Drupal::logger('product')->notice('delete product nid=@prod', array('@prod' => $nid));

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * @return string - product icon for a given name
   *
   * @param $name
   * @return string
   */
  public static function getRandomImageName($name) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $name);
    $asInt = 0;
    for ($i = 0; $i < mb_strlen($name); $i++) {
      $asInt += ord($name[$i]);
    }
    $digit = $asInt % 19;
    if ($digit == 0) {
      $digit = 1;
    }
    $num = str_pad($digit, 2, 0, STR_PAD_LEFT);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $num);
    return "product_" . $num . ".png";
  }

  /**
   * @return string - path to placeholder image for a given name
   *
   * @param $name
   * @return string
   */
  public static function getPlaceholderImage($name) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $name);
    $returnValue = Url::fromUri('internal:/' . drupal_get_path('module', 'product') . '/images/' . Product::getRandomImageName($name))
      ->toString();
    \Drupal::moduleHandler()->alter('product_getplaceholderimage', $returnValue);
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $returnValue;
  }

  /**
   * @return string - path to placeholder image for a given name
   *
   * @param $name
   * @return string
   */
  public static function getPlaceholderImageURL($name) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $name);
    $rawImage = Product::getRandomImageName($name);
    $returnValue = base_path() . drupal_get_path('module', 'product') . '/images/' . $rawImage;
    \Drupal::moduleHandler()->alter('product_getplaceholderimageurl', $returnValue);
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    return $returnValue;
  }

  /**
   * Check if we have access to specified product Node
   * @param $node
   * @return bool
   */
  public static function checkAccess($node) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $found = FALSE;
    $view_enabled = TRUE;
    if (isset($node)) {

      // if view disabled then no one has access
      if ($node->product_view_enabled->value != 1) {
        $view_enabled = FALSE;
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

        $appresults = $query->execute();

        if (isset($appresults) && !empty($appresults)) {
          $nids = array();
          foreach ($appresults as $item) {
            $nids[] = $item;
          }
          $nodes = Node::loadMultiple($nids);
          if ($nodes) {
            foreach ($nodes as $app) {
              foreach ($app->application_subscriptions->getValue() as $nextSub) {
                $subs[] = unserialize($nextSub['value']);
              }
              if (is_array($subs)) {
                foreach ($subs as $sub) {
                  if (isset($sub['product']) && $sub['product'] == $node->apic_ref->value) {
                    $found = TRUE;
                  }
                }
              }
            }
          }
        }
      }
      if ($view_enabled == TRUE) {
        $node_custom_orgs = $node->product_visibility_custom_orgs->getValue();
        $node_custom_tags = $node->product_visibility_custom_tags->getValue();
        // check public nodes
        if ($node->product_visibility_public->value == 1) {
          $found = TRUE;
        }
        elseif ($node->product_visibility_authenticated->value == 1 && !\Drupal::currentUser()->isAnonymous()) {
          $found = TRUE;
        }
        elseif (isset($node_custom_orgs) && isset($myorg['url'])) {
          foreach ($node_custom_orgs as $customorg) {
            if (isset($customorg['value']) && $customorg['value'] == $myorg['url']) {
              $found = TRUE;
            }
          }
        }
        elseif (isset($node_custom_tags) && isset($myorg['url'])) {
          $query = \Drupal::entityQuery('node');
          $query->condition('type', 'consumerorg');
          $query->condition('consumerorg_url.value', $myorg['url']);

          $consumerorgresults = $query->execute();
          if (isset($consumerorgresults) && !empty($consumerorgresults)) {
            $nid = array_shift($consumerorgresults);
            $consumerorg = Node::load($nid);
            $tags = $consumerorg->consumerorg_tags->getValue();
            if (isset($tags) && is_array($tags) && count($tags) > 0) {
              foreach ($node_custom_tags as $customtag) {
                if (isset($customtag['value']) && in_array($customtag['value'], $tags)) {
                  $found = TRUE;
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
   * @return array
   */
  public static function listProducts() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $returnnids = array();
    $userUtils = \Drupal::service('ibm_apim.user_utils');

    // users with right perms get to see all
    if ($userUtils->explicitUserAccess('edit any product content')) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'product');
      $query->condition('status', 1);
      $query->condition('product_state.value', 'published');

      $results = $query->execute();
      if (isset($results) && !empty($results)) {
        $nids = array_values($results);
        if (!empty($nids)) {
          $returnnids = array_merge($returnnids, $nids);
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
      if (isset($results) && !empty($results)) {
        $nids = array_values($results);
        if (!empty($nids)) {
          $returnnids = array_merge($returnnids, $nids);
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
        if (isset($results) && !empty($results)) {
          $nids = array_values($results);
          if (!empty($nids)) {
            $returnnids = array_merge($returnnids, $nids);
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
          if (isset($results) && !empty($results)) {
            $nids = array_values($results);
            if (!empty($nids)) {
              $returnnids = array_merge($returnnids, $nids);
            }
          }

          $query = \Drupal::entityQuery('node');
          $query->condition('type', 'consumerorg');
          $query->condition('consumerorg_id.value', $myorg['id']);
          $consumerorgresults = $query->execute();
          if (isset($consumerorgresults) && !empty($consumerorgresults)) {
            $consumerorgnid = array_shift($consumerorgresults);

            if (!empty($consumerorgnid)) {
              $consumerorg = Node::load($consumerorgnid);
              $tags = $consumerorg->consumerorg_tags->getValue();
              if (isset($tags) && is_array($tags) && count($tags) > 0) {
                $query = \Drupal::entityQuery('node');
                $query->condition('type', 'product');
                $query->condition('status', 1);
                $query->condition('product_state.value', 'published');
                $query->condition('product_view_enabled.value', 1);
                $query->condition('product_visibility_custom_tags.value', $tags, 'IN');
                $results = $query->execute();
                if (isset($results) && !empty($results)) {
                  $nids = array_values($results);
                  if (!empty($nids)) {
                    $returnnids = array_merge($returnnids, $nids);
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
          if (isset($results) && !empty($results)) {
            $nids = array_values($results);
            $nodes = Node::loadMultiple($nids);
            if ($nodes) {
              foreach ($nodes as $app) {
                $subs = array();
                foreach ($app->application_subscriptions->getValue() as $nextSub) {
                  $subs[] = unserialize($nextSub['value']);
                }
                if (isset($subs)) {
                  foreach ($subs as $sub) {
                    if (isset($sub['product'])) {
                      $query = \Drupal::entityQuery('node');
                      $query->condition('type', 'product');
                      $query->condition('status', 1);
                      $query->condition('apic_ref.value', $sub['product']);
                      $results = $query->execute();
                      if (isset($results) && !empty($results)) {
                        $nids = array_values($results);
                        $returnnids = array_merge($returnnids, $nids);
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }
    $returnValue = array_reverse(array_unique($returnnids, SORT_NUMERIC));
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    return $returnValue;
  }

  /**
   * A list of all the IBM created fields for this content type
   *
   * @return array
   */
  public static function getIBMFields() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $ibmfields = array(
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
      'product_visibility_public'
    );
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $ibmfields;
  }

  /**
   * Get all the doc pages that are listed as being linked to a given Product NID
   * @param $nid
   * @return array
   */
  public static function getLinkedPages($nid) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $nid);
    $finalnids = array();
    $docs = array();
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'page');
    $query->condition('status', 1);
    $query->condition('allproducts.value', 1);
    $results = $query->execute();
    if (isset($results) && !empty($results)) {
      $finalnids = array_values($results);

    }
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'page');
    $query->condition('status', 1);
    $query->condition('prodref.target_id', $nid);
    $results = $query->execute();
    if (isset($results) && !empty($results)) {
      $nids = array_values($results);
      $finalnids = array_merge($finalnids, $nids);
    }
    // process the nodes and build an array of the info we need
    $finalnids = array_unique($finalnids, SORT_NUMERIC);
    if (isset($finalnids) && !empty($finalnids)) {
      $nodes = Node::loadMultiple($finalnids);
      if (isset($nodes)) {
        foreach ($nodes as $node) {
          $docs[] = array(
            'title' => $node->getTitle(),
            'nid' => $node->id(),
            'url' => $node->toUrl()->toString()
          );
        }
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $docs);
    return $docs;
  }

  /**
   * Get subscription owners for a given plan reference ('product:version:plan')
   * To include all plans for a given product then simply specify 'product:version'
   *
   * @param $plan
   * @return array
   */
  public function getSubscribingOwners($plan = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $plan);

    $retValue = $this->getSubscribers($plan, 'owners');

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $retValue);
    return $retValue;
  }

  /**
   * Get all consumer organization members subscribed to a given plan reference ('product:version:plan')
   * To include all plans for a given product then simply specify 'product:version'
   *
   * @param $plan
   * @return array
   */
  public function getSubscribingMembers($plan = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $plan);

    $retValue = $this->getSubscribers($plan, 'members');

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $retValue);
    return $retValue;
  }

  /**
   * Get subscribers for a given plan reference ('product:version:plan')
   * To include all plans for a given product then simply specify 'product:version'
   *
   * @param $plan
   * @param string $type
   * @return array
   */
  public function getSubscribers($plan = NULL, $type = 'members') {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, array('plan' => $plan, 'type' => $type));

    $orgs = array();
    // get subscribed apps
    if (isset($plan)) {
      $parts = explode(':', $plan);
      $product = $parts[0];
      $version = $parts[1];
      $planname = $parts[2];

      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'application');
      $results = $query->execute();
      if (isset($results) && !empty($results)) {
        $nids = array_values($results);
        $nodes = Node::loadMultiple($nids);
        if (isset($nodes)) {
          foreach ($nodes as $node) {
            $subs = [];
            foreach ($node->application_subscriptions->getValue() as $nextSub) {
              $subs[] = unserialize($nextSub['value']);
            }
            if (is_array($subs)) {
              foreach ($subs as $sub) {
                if (isset($sub['product']) && $sub['product'] == $product . ':' . $version && (!isset($planname) || (isset($planname) && isset($sub['plan']) && $sub['plan'] == $planname))) {
                  $orgs[] = $node->application_orgid->value;
                }
              }
            }
          }
        }
      }
    }
    $recipients = array();
    // get users in those orgs
    if (isset($orgs) && is_array($orgs)) {
      foreach ($orgs as $org) {
        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'consumerorg');
        $query->condition('consumerorg_id.value', $org);
        $results = $query->execute();
        if (isset($results) && !empty($results)) {
          $nids = array_values($results);
          $nodes = Node::loadMultiple($nids);
          if (isset($nodes) && is_array($nodes)) {
            foreach ($nodes as $node) {
              $org_recipients = array();
              if ($type == 'members') {
                $serialized_members = $node->consumerorg_members->value;
                if (isset($serialized_members)) {
                  $members = unserialize($serialized_members);
                  foreach ($members as $member) {
                    if (isset($member['email'])) {
                      $org_recipients[] = $member['email'];
                    }
                  }
                }
              }
              $org_recipients[] = $node->consumerorg_owner->value;
              $recipients[] = implode(',', $org_recipients);
            }
          }
        }
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $recipients);
    return $recipients;
  }

  /**
   * Get a list of product nodes that include a given API reference
   *
   * @param null $apiref
   * @return array
   */
  public static function getProductsContainingAPI($apiref = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $apiref);

    $products = array();
    if (isset($apiref)) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'product');
      $query->condition('product_apis', $apiref, 'CONTAINS');
      $results = $query->execute();
      if (isset($results) && !empty($results)) {
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
   * @return string (JSON)
   */
  public function getProductAsJson($url) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, array('url' => $url));
    $output = NULL;
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'product');
    $query->condition('apic_url.value', $url);

    $nids = $query->execute();

    if (isset($nids) && !empty($nids)) {
      $nid = array_shift($nids);
      $node = Node::load($nid);
      $moduleHandler = \Drupal::service('module_handler');
      if ($moduleHandler->moduleExists('serialization')) {
        $serializer = \Drupal::service('serializer');
        $output = $serializer->serialize($node, 'json', ['plugin_id' => 'entity']);
      }
      else {
        \Drupal::logger('product')->notice('getProductAsJson: serialization module not enabled', array());
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $output;
  }
}
