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

namespace Drupal\apic_api;

use Drupal\apic_api\ApiTags\ApiTags;
use Drupal\apic_api\Event\ApiCreateEvent;
use Drupal\apic_api\Event\ApiDeleteEvent;
use Drupal\apic_api\Event\ApiUpdateEvent;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\ibm_apim\ApicRest;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\product\Product;
use Exception;

/**
 * Class to work with the API content type, takes input from the JSON returned by
 * IBM API Connect
 */
class Api {

  protected $apiTaxonomy;
  protected $utils;

  public function __construct() {
    $this->apiTaxonomy = \Drupal::service('apic_api.taxonomy');
    $this->utils = \Drupal::service('ibm_apim.utils');
  }

  /**
   * Create a new Api
   *
   * @param $api
   * @param string $event
   * @return int|null|string
   */
  public function create($api, $event = 'publish') {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $moduleHandler = \Drupal::service('module_handler');
    $config_service = \Drupal::service('ibm_apim.site_config');
    $hostvariable = $config_service->getApimHost();

    $oldtags = NULL;

    if(is_string($api)) {
      $api = json_decode($api, TRUE);
    }

    if (isset($api['consumer_api']['info']) && isset($api['consumer_api']['info']['x-ibm-name'])) {
      $xibmname = $api['consumer_api']['info']['x-ibm-name'];
    }
    if (isset($api['consumer_api']['definitions']) && empty($api['consumer_api']['definitions'])) {
      unset($api['consumer_api']['definitions']);
    }

    if (isset($xibmname)) {
      // find if there is an existing node for this API (maybe at old version)
      // using x-ibm-name from swagger doc
      // if so then clone it and base new node on that.
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'api')->condition('api_xibmname.value', $xibmname)->sort('nid', 'ASC');
      $nids = $query->execute();
    }

    if (isset($nids) && !empty($nids)) {
      $nid = array_shift($nids);
      $oldnode = Node::load($nid);
    }

    if (isset($oldnode) && $oldnode->id()) {
      $existing_api_tags = $oldnode->get('apic_tags')->getValue();
      if ($existing_api_tags && is_array($existing_api_tags)) {
        foreach ($existing_api_tags as $tag) {
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
      $node->set('apic_provider_id', $config_service->getOrgId());
      $node->set('apic_catalog_id', $config_service->getEnvId());
      $node->set('apic_summary', NULL);
      $node->set('apic_description', NULL);
      $node->set('apic_url', NULL);
      $node->set('apic_ref', NULL);
      $node->set('apic_version', NULL);
      $node->set('api_id', NULL);
      $node->set('api_xibmname', NULL);
      $node->set('api_protocol', NULL);
      $node->set('api_oaiversion', NULL);
      $node->set('api_ibmconfiguration', NULL);
      $node->set('api_wsdl', NULL);
      $node->set('api_swagger', NULL);
      $node->set('api_swaggertags', NULL);
      $node->set('api_state', NULL);
    }
    else {
      $node = Node::create(array(
        'type' => 'api',
        'title' => $this->utils->truncate_string($api['consumer_api']['info']['title']),
        'apic_hostname' => $hostvariable,
        'apic_provider_id' => $config_service->getOrgId(),
        'apic_catalog_id' => $config_service->getEnvId()
      ));
    }

    // get the update method to do the update for us
    $node = $this->update($node, $api, 'internal');

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
      // Calling all modules implementing 'hook_apic_api_create':
      $moduleHandler->invokeAll('apic_api_create', array('node' => $node, 'data' => $api));
      // invoke rules
      if ($moduleHandler->moduleExists('rules')) {
        // Set the args twice on the event: as the main subject but also in the
        // list of arguments.
        $event = new ApiCreateEvent($node, ['api' => $node]);
        $event_dispatcher = \Drupal::service('event_dispatcher');
        $event_dispatcher->dispatch(ApiCreateEvent::EVENT_NAME, $event);
      }
    }

    $config = \Drupal::config('ibm_apim.settings');
    $autocreateForum = $config->get('autocreate_apiforum');
    if ($autocreateForum) {
      if (!isset($api['consumer_api']['info']['description'])) {
        $api['consumer_api']['info']['description'] = '';
      }
      $this->apiTaxonomy->create_api_forum($this->utils->truncate_string($api['consumer_api']['info']['title']), $api['consumer_api']['info']['description']);
    }

    \Drupal::logger('apic_api')->notice('API @api created', array('@api' => $node->getTitle()));

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $node->id());
    return $node->id();
  }

  /**
   * Update an existing API
   *
   * @param $node
   * @param $api
   * @param string $event
   * @return NodeInterface|null
   */
  public function update(NodeInterface $node, $api, $event = 'content_refresh') {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    if (isset($node)) {
      module_load_include('inc', 'apic_api', 'apic_api.utils');
      module_load_include('inc', 'apic_api', 'apic_api.tags');
      module_load_include('inc', 'apic_api', 'apic_api.files');
      $config_service = \Drupal::service('ibm_apim.site_config');
      $hostvariable = $config_service->getApimHost();
      $moduleHandler = \Drupal::service('module_handler');
      $config = \Drupal::config('ibm_apim.settings');
      $language_list = array_keys(\Drupal::languageManager()->getLanguages(LanguageInterface::STATE_ALL));

      $node->setTitle($this->utils->truncate_string($api['consumer_api']['info']['title']));
      if (isset($api['consumer_api']['info']['x-ibm-languages']['title']) && !empty($api['consumer_api']['info']['x-ibm-languages']['title'])) {
        foreach ($api['consumer_api']['info']['x-ibm-languages']['title'] as $lang => $lang_array) {
          $lang = $this->utils->convert_lang_name_to_drupal($lang);
          if (in_array($lang, $language_list)) {
            if (!$node->hasTranslation($lang)) {
              $translation = $node->addTranslation($lang, array('title' => $this->utils->truncate_string($api['consumer_api']['info']['x-ibm-languages']['title'][$lang])));
              $translation->save();
            }
            else {
              $node->getTranslation($lang)
                ->setTitle($this->utils->truncate_string($api['consumer_api']['info']['x-ibm-languages']['title'][$lang]))
                ->save();
            }
          }
        }
      }
      $node->set("apic_hostname", $hostvariable);
      $node->set("apic_provider_id", $config_service->getOrgId());
      $node->set("apic_catalog_id", $config_service->getEnvId());
      $node->set('api_id', $api['id']);
      $node->set('apic_version', $api['consumer_api']['info']['version']);

      // if unset, default state to online
      if(!isset($api['state']) || empty($api['state'])) {
        $api['state'] = 'online';
      }
      // if set, make sure it is one of the valid options and default to online if not
      $state = strtolower($api['state']);
      if ($state !== 'online' && $state !== 'offline' && $state !== 'archived') {
        $api['state'] = 'online';
      }
      $node->set('api_state', $state);

      if (isset($api['consumer_api']['definitions']) && empty($api['consumer_api']['definitions'])) {
        unset($api['consumer_api']['definitions']);
      }

      // ensure description is at least set to empty string
      if (!isset($api['consumer_api']['info']['description']) || empty($api['consumer_api']['info']['description'])) {
        $api['consumer_api']['info']['description'] = '';
      }
      if (isset($api['consumer_api']['info']['x-ibm-languages']['description']) && !empty($api['consumer_api']['info']['x-ibm-languages']['description'])) {
        foreach ($api['consumer_api']['info']['x-ibm-languages']['description'] as $lang => $lang_array) {
          $lang = $this->utils->convert_lang_name_to_drupal($lang);
          // if its one of our locales or the root of one of our locales
          foreach ($language_list as $lang_list_key => $lang_list_value) {
            if ($lang == $lang_list_key || $lang == substr($lang_list_key, 0, count($lang))) {
              if (!$node->hasTranslation($lang)) {
                $translation = $node->addTranslation($lang, array('apic_description' => $this->utils->truncate_string($api['consumer_api']['info']['x-ibm-languages']['description'][$lang])));
                $translation->save();
              }
              else {
                $node->getTranslation($lang)
                  ->set('apic_description', $this->utils->truncate_string($api['consumer_api']['info']['x-ibm-languages']['description'][$lang]))
                  ->save();
              }
            }
          }
        }
      }
      if ($moduleHandler->moduleExists('ghmarkdown')) {
        $format = 'ghmarkdown';
      }
      else {
        $format = 'full_html';
      }
      $node->set('apic_description', ['value' => $api['consumer_api']['info']['description'], 'format' => $format]);
      // ensure summary is at least set to empty string
      if (!isset($api['consumer_api']['info']['x-ibm-summary']) || empty($api['consumer_api']['info']['x-ibm-summary'])) {
        $api['consumer_api']['info']['x-ibm-summary'] = '';
      }
      if (isset($api['consumer_api']['info']['x-ibm-languages']['x-ibm-summary']) && !empty($api['consumer_api']['info']['x-ibm-languages']['x-ibm-summary'])) {
        foreach ($api['consumer_api']['info']['x-ibm-languages']['x-ibm-summary'] as $lang => $lang_array) {
          $lang = $this->utils->convert_lang_name_to_drupal($lang);
          // if its one of our locales or the root of one of our locales
          foreach ($language_list as $lang_list_key => $lang_list_value) {
            if ($lang == $lang_list_key || $lang == substr($lang_list_key, 0, count($lang))) {
              if (!$node->hasTranslation($lang)) {
                $translation = $node->addTranslation($lang, array('apic_summary' => $this->utils->truncate_string($api['consumer_api']['info']['x-ibm-languages']['x-ibm-summary'][$lang], 1000)));
                $translation->save();
              }
              else {
                $node->getTranslation($lang)
                  ->set('apic_summary', $this->utils->truncate_string($api['consumer_api']['info']['x-ibm-languages']['x-ibm-summary'][$lang], 1000))
                  ->save();
              }
            }
          }
        }
      }
      $node->set('apic_summary', [
        'value' => $this->utils->truncate_string($api['consumer_api']['info']['x-ibm-summary'], 1000),
        'format' => 'plaintext'
      ]);

      $apitype = 'rest';
      if (isset($api['consumer_api']['x-ibm-configuration']['type'])) {
        $lower_type = mb_strtolower($api['consumer_api']['x-ibm-configuration']['type']);
        if ($lower_type == 'rest' || $lower_type == 'wsdl' || $lower_type == 'oauth') {
          $apitype = $lower_type;
        }
      }
      $node->set('api_protocol', $apitype);
      $node->set('apic_url', $api['url']);

      if (!isset($api['consumer_api']['x-ibm-configuration']) || empty($api['consumer_api']['x-ibm-configuration'])) {
        $api['consumer_api']['x-ibm-configuration'] = '';
      }
      $node->set('api_ibmconfiguration', serialize($api['consumer_api']['x-ibm-configuration']));
      $oaiversion = 2;
      if (isset($api['consumer_api']['openapi']) && $this->utils->startsWith($api['consumer_api']['openapi'], '3.')) {
        $oaiversion = 3;
      }
      $node->set('api_oaiversion', $oaiversion);
      $node->save();

      try {
        // if empty securityDefinitions and others then needs to be object not array
        // for now just remove them
        foreach (array('securityDefinitions', 'responses', 'parameters', 'definitions', 'paths') as $key) {
          if (array_key_exists($key, $api['consumer_api']) && is_array($api['consumer_api'][$key]) && empty($api['consumer_api'][$key])) {
            unset($api['consumer_api'][$key]);
          }
        }
        $node->set('api_swagger', serialize(apic_api_remove_empty_elements($api['consumer_api'])));

        $node->set('api_swaggertags', array());
        if (isset($api['consumer_api']['tags'])) {
          $tags = array();
          foreach ($api['consumer_api']['tags'] as $tag) {
            if (isset($tag['name'])) {
              $tags[] = $tag['name'];
            }
          }
          if (isset($api['consumer_api']['paths'])) {
            foreach ($api['consumer_api']['paths'] as $path) {
              foreach ($path as $verb => $operation) {
                if (isset($operation['tags'])) {
                  foreach ($operation['tags'] as $tag) {
                    $tags[] = $tag;
                  }
                }
              }
            }
          }
          $tags = array_unique($tags);
          $node->set('api_swaggertags', $tags);
        }
        if (isset($api['consumer_api']['info'])) {
          if (isset($api['consumer_api']['info']['x-ibm-name'])) {
            $xibmname = $api['consumer_api']['info']['x-ibm-name'];
            $node->set('api_xibmname', $xibmname);
          }
          if (isset($api['consumer_api']['info']['version'])) {
            $xversion = $api['consumer_api']['info']['version'];
            $node->set('apic_version', $xversion);
          }
          if (isset($api['consumer_api']['info']['x-ibm-name']) && isset($api['consumer_api']['info']['version'])) {
            $api_ref = $api['consumer_api']['info']['x-ibm-name'] . ':' . $api['consumer_api']['info']['version'];
            $node->set('apic_ref', $api_ref);
          }
        }
        $node->save();
      } catch (Exception $e) {
        \Drupal::logger('apic_api')
          ->notice('Update of Open API document to database failed with: %data', array('%data' => $e->getMessage()));
      }
      // if SOAP API then go get the wsdl too
      if (isset($api['consumer_api']['x-ibm-configuration']['type']) && (mb_strtolower($api['consumer_api']['x-ibm-configuration']['type']) == 'wsdl')) {
        $wsdl_content_type = $api['wsdl']['content_type'];

        if (!isset($wsdl_content_type)) {
          $wsdl_content_type = 'application/wsdl';
        }

        if (isset($api['wsdl']['content']) && !empty($api['wsdl']['content'])) {
          $data = base64_decode($api['wsdl']['content']);
          $filename = $api['consumer_api']['info']['x-ibm-name'] . '_' . $api['consumer_api']['info']['version'];
          if ($wsdl_content_type == 'application/wsdl') {
            $filename .= '.wsdl';
          } else {
            $filename .= '.zip';
          }
          $file_temp = apic_api_save_wsdl($api['id'], $data, $filename);
          if (isset($file_temp)) {
            $updated = FALSE;
            $delete_fid = -1;
            $attachments = $node->apic_attachments->getValue();
            if (isset($attachments)) {
              foreach ($attachments as $key => $existingdoc) {
                $existingdoc_file = \Drupal\file\Entity\File::load($existingdoc['target_id']);
                if(isset($existingdoc_file)){
                  $existingdoc_file_uri=$existingdoc_file->getFileUri();
                  if (isset($existingdoc_file_uri)) {
                    $parts = explode('/', $existingdoc_file_uri);
                    if (in_array('apiwsdl', $parts) && $file_temp->id() !== $existingdoc['target_id']) {
                      if (isset($existingdoc['description'])) {
                        $description = $existingdoc['description'];
                      }
                      else {
                        $description = '';
                      }
                      if($updated == FALSE){
                        $delete_fid = $existingdoc['target_id'];
                        $attachments[$key] = array(
                          'target_id' => $file_temp->id(),
                          'display' => 1,
                          'description' => $description
                        );
                        $updated = TRUE;
                      }
                    } else if(in_array('apiwsdl', $parts) && $file_temp->id() == $existingdoc['target_id']) {
                        $updated = TRUE;
                    }
                  }
                } else {
                  unset($attachments[$key]);
                }
              }
            }
            if ($updated == FALSE) {
              $attachments[] = array(
                'target_id' => $file_temp->id(),
                'display' => 1
              );
            } else {
              if ($delete_fid !== -1 && $delete_fid!==$file_temp->id()) {
                \Drupal\file\Entity\File::load($delete_fid)->delete();
              }
            }
            $node->set('apic_attachments', $attachments);
            $node->save();
          }

          if ($wsdl_content_type == 'application/zip') {
            $data = apic_api_get_string_from_zip(base64_decode($api['wsdl']['content']));
          }
          $serialized = Xss::filter(serialize($data));
          if ((isset($data) && !empty($data)) && ($node->api_wsdl->value != $serialized)) {
            try {
              $node->set('api_wsdl', $serialized);
              $node->save();
            } catch (Exception $e) {
              \Drupal::logger('apic_api')
                ->notice('Save of WSDL to database failed with: %data', array('%data' => $e->getMessage()));
            }
          }
        }
      }

      // API Categories
      $categories_enabled = $config->get('categories')['enabled'];
      if (isset($api['consumer_api']['x-ibm-configuration']['categories']) && $categories_enabled) {
        $this->apiTaxonomy->process_categories($api, $node);
      }

      $phase_tagging = $config->get('autotag_with_phase');
      if ($phase_tagging) {
        if (isset($api['consumer_api']['x-ibm-configuration']['phase'])) {
          $this->apiTaxonomy->process_phase_tag($node, $api['consumer_api']['x-ibm-configuration']['phase']);
        }
      }
      // enable application certificates if we find an API that uses it
      if (isset($api['consumer_api']['x-ibm-configuration']['application-authentication']['certificate']) && $api['consumer_api']['x-ibm-configuration']['application-authentication']['certificate'] == TRUE) {
        \Drupal::state()->set('ibm_apim.application_certificates', TRUE);
      }

      if (isset($node) && $event != 'internal') {
        // Calling all modules implementing 'hook_apic_api_update':
        $moduleHandler->invokeAll('apic_api_update', array('node' => $node, 'data' => $api));

        if ($moduleHandler->moduleExists('rules')) {
          // Set the args twice on the event: as the main subject but also in the
          // list of arguments.
          $event = new ApiUpdateEvent($node, ['api' => $node]);
          $event_dispatcher = \Drupal::service('event_dispatcher');
          $event_dispatcher->dispatch(ApiUpdateEvent::EVENT_NAME, $event);
        }
      }

      if ($event != 'internal') {
        \Drupal::logger('apic_api')->notice('API @api updated', array('@api' => $node->getTitle()));
      }
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      return $node;
    }
    else {
      \Drupal::logger('apic_api')->error('Update api: no node provided.', array());
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      return NULL;
    }
  }

  /**
   * Create a new API if one doesnt already exist for that API reference
   * Update one if it does
   *
   * @param $api
   * @param $event
   * @return bool
   */
  public function createOrUpdate($api, $event) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $api['consumer_api']['info']['x-ibm-name'] . ':' . $api['consumer_api']['info']['version']);
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'api');
    $query->condition('apic_ref.value', $api['consumer_api']['info']['x-ibm-name'] . ':' . $api['consumer_api']['info']['version']);

    $nids = $query->execute();

    if (isset($nids) && !empty($nids)) {
      $nid = array_shift($nids);
      $node = Node::load($nid);
      $this->update($node, $api, $event);
      $createdOrUpdated = FALSE;
    }
    else {
      // no existing node for this API so create one
      $this->create($api, $event);
      $createdOrUpdated = TRUE;
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $createdOrUpdated);
    return $createdOrUpdated;
  }

  /**
   * Delete an API by NID
   * @param $nid
   * @param $event
   */
  public static function deleteNode($nid, $event) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $nid);
    $moduleHandler = \Drupal::service('module_handler');

    $node = Node::load($nid);
    \Drupal::logger('apic_api')->notice('API @api deleted', array('@api' => $node->getTitle()));

    // Calling all modules implementing 'hook_apic_api_delete':
    $moduleHandler->invokeAll('apic_api_delete', array('node' => $node));

    if ($moduleHandler->moduleExists('rules')) {
      // Set the args twice on the event: as the main subject but also in the
      // list of arguments.
      $event = new ApiDeleteEvent($node, ['api' => $node]);
      $event_dispatcher = \Drupal::service('event_dispatcher');
      $event_dispatcher->dispatch(ApiDeleteEvent::EVENT_NAME, $event);
    }
    $node->delete();
    unset($node);
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * @return string - api icon for a given name
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
    return "api_" . $num . ".png";
  }

  /**
   * @return string - path to placeholder image for a given name
   *
   * @param $name
   * @return string
   */
  public static function getPlaceholderImage($name) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $name);
    $returnValue = Url::fromUri('internal:/' . drupal_get_path('module', 'apic_api') . '/images/' . Api::getRandomImageName($name))
      ->toString();
    \Drupal::moduleHandler()->alter('api_getplaceholderimage', $returnValue);
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
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
    $rawImage = Api::getRandomImageName($name);
    $returnValue = base_path() . drupal_get_path('module', 'apic_api') . '/images/' . $rawImage;
    \Drupal::moduleHandler()->alter('api_getplaceholderimageurl', $returnValue);
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    return $returnValue;
  }

  /**
   * Check if we have access to the specified API
   * @param $node
   * @return bool
   */
  public static function checkAccess($node) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $node->id());
    $userUtils = \Drupal::service('ibm_apim.user_utils');
    // allow those with permission to bypass check
    if ($userUtils->explicitUserAccess('edit any api content')) {
      return TRUE;
    }
    $productnids = Product::listProducts();
    $productnodes = Node::loadMultiple($productnids);
    $found = FALSE;
    foreach ($productnodes as $productnode) {
      $apis = array();
      foreach($productnode->product_apis->getValue() as $arrayValue) {
        $apis[] = json_decode($arrayValue['value']);
      }
      if (!empty($apis)) {
        $prodrefs = unserialize($apis);
        foreach ($prodrefs as $prodref) {
          if ($prodref['name'] == $node->apic_ref->value) {
            $found = TRUE;
          }
        }
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $found);
    return $found;
  }

  /**
   * Return list of NIDs for all APIs the current user can access
   * @return array
   */
  public static function listApis() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $nids = array();
    // user has access to everything
    $userUtils = \Drupal::service('ibm_apim.user_utils');

    if ($userUtils->explicitUserAccess('edit any api content')) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'api');
      $query->condition('status', 1);

      $results = $query->execute();
    }
    else {
      $productnids = Product::listProducts();
      $productnodes = Node::loadMultiple($productnids);
      $refarray = array();
      foreach ($productnodes as $productnode) {
        $apis = array();
        foreach($productnode->product_apis->getValue() as $arrayValue) {
          $apis[] = json_decode($arrayValue['value']);
        }
        if (!empty($apis)) {
          $prodrefs = unserialize($apis);
          foreach ($prodrefs as $prodref) {
            $refarray[] = $prodref['name'];
          }
        }
      }

      if (isset($refarray) && is_array($refarray) && count($refarray) > 0) {
        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'api');
        $query->condition('status', 1);
        $query->condition('apic_ref.value', $refarray, 'IN');
        $results = $query->execute();
      }
    }
    if (isset($results) && !empty($results)) {
      $nids = array_values($results);
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $nids);
    return $nids;
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
      'apic_ref',
      'apic_version',
      'apic_image',
      'apic_attachments',
      'api_ibmconfiguration',
      'api_id',
      'api_oaiversion',
      'api_protocol',
      'api_soapversion',
      'api_state',
      'api_swagger',
      'api_swaggertags',
      'api_wsdl',
      'api_xibmname'
    );
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $ibmfields;
  }

  /**
   * Get all the doc pages that are listed as being linked to a given API NID
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
    $query->condition('allapis.value', 1);
    $results = $query->execute();
    if (isset($results) && !empty($results)) {
      $finalnids = array_values($results);

    }
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'page');
    $query->condition('status', 1);
    $query->condition('apiref.target_id', $nid);
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
            'url' => $node->toUrl()->toString(),
            'extractPortalContent' => TRUE
          );
        }
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $docs);
    return $docs;
  }

  /**
   * Get subscription owners for a given API NID
   *
   * @param $apinid
   * @return array
   */
  public function getSubscribingOwners($apinid = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $apinid);

    $retValue = $this->getSubscribers($apinid, 'owners');

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $retValue);
    return $retValue;
  }

  /**
   * Get all consumer organization members subscribed to a given API NID
   *
   * @param $apinid
   * @return array
   */
  public function getSubscribingMembers($apinid = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $apinid);

    $retValue = $this->getSubscribers($apinid, 'members');

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $retValue);
    return $retValue;
  }

  /**
   * Get subscribers for a given API NID
   *
   * @param $apinid
   * @param string $type
   * @return array
   */
  public function getSubscribers($apinid = NULL, $type = 'members') {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, array('apinid' => $apinid, 'type' => $type));

    $orgs = array();
    // get products containing this api
    if (isset($apinid)) {
      $api = Node::load($apinid);
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'product');
      $query->condition('product_apis', $api->apic_ref->value);
      $results = $query->execute();
      if (isset($results) && !empty($results)) {
        $prod_nids = array_values($results);

        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'application');
        $results = $query->execute();
        if (isset($results) && !empty($results)) {
          $appnids = array_values($results);
          $appnodes = Node::loadMultiple($appnids);
        }
        // get subscribed apps to those products
        $products = Node::loadMultiple($prod_nids);
        foreach ($products as $product) {
          if (isset($product)) {
            if (isset($appnodes)) {
              foreach ($appnodes as $app) {
                $subs = [];
                foreach ($app->application_subscriptions->getValue() as $nextSub) {
                  $subs[] = unserialize($nextSub['value']);
                }
                if (is_array($subs)) {
                  foreach ($subs as $sub) {
                    if (isset($sub['product']) && $sub['product'] == $product->apic_ref->value) {
                      $orgs[] = $app->application_orgid->value;
                    }
                  }
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
                $serialized_members = $node->consumerorg_members[$node->language][0]['value'];
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
   * Returns a JSON representation of an API
   *
   * @param $url
   * @return string (JSON)
   */
  public function getApiAsJson($url) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, array('url' => $url));
    $output = null;
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'api');
    $query->condition('apic_url.value', $url);

    $nids = $query->execute();

    if (isset($nids) && !empty($nids)) {
      $nid = array_shift($nids);
      $node = Node::load($nid);
      $moduleHandler = \Drupal::service('module_handler');
      if ($moduleHandler->moduleExists('serialization')) {
        $serializer = \Drupal::service('serializer');
        $output = $serializer->serialize($node, 'json', ['plugin_id' => 'entity']);
      } else {
        \Drupal::logger('api')->notice('getApiAsJson: serialization module not enabled', array());
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $output;
  }
}
