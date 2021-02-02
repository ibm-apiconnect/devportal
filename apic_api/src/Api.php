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

namespace Drupal\apic_api;

use Drupal\apic_api\Event\ApiCreateEvent;
use Drupal\apic_api\Event\ApiDeleteEvent;
use Drupal\apic_api\Event\ApiUpdateEvent;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
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
   *
   * @return int|null|string
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function create($api, $event = 'publish') {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $moduleHandler = \Drupal::service('module_handler');
    $configService = \Drupal::service('ibm_apim.site_config');
    $hostVariable = $configService->getApimHost();

    $oldNode = NULL;
    $oldTags = [];
    $xIbmName = NULL;

    if (\is_string($api)) {
      $api = json_decode($api, TRUE);
    }

    if (isset($api['consumer_api']['info']['x-ibm-name'])) {
      $xIbmName = $api['consumer_api']['info']['x-ibm-name'];
    }
    if (isset($api['consumer_api']['definitions']) && empty($api['consumer_api']['definitions'])) {
      unset($api['consumer_api']['definitions']);
    }

    if ($xIbmName !== NULL) {
      // find if there is an existing node for this API (maybe at old version)
      // using x-ibm-name from swagger doc
      // if so then clone it and base new node on that.
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'api')->condition('api_xibmname.value', $xIbmName)->sort('nid', 'ASC');
      $nids = $query->execute();
      if ($nids !== NULL && !empty($nids)) {
        $nid = array_shift($nids);
        $oldNode = Node::load($nid);
      }
    }

    if ($oldNode !== NULL && $oldNode->id()) {
      $existingCategories = [];
      $oldApiData = unserialize($oldNode->api_swagger->value, ['allowed_classes' => FALSE]);
      if (isset($oldApiData['x-ibm-configuration']['categories'])) {
        $existingCategories = $oldApiData['x-ibm-configuration']['categories'];
      }
      $totalTags = $oldNode->apic_tags->getValue();
      if ($totalTags === NULL) {
        $totalTags = [];
      }

      $existingApiTags = $this->apiTaxonomy->separate_categories($totalTags, $existingCategories);
      $oldTags = [];
      if (is_array($existingApiTags) && !empty($existingApiTags)) {
        foreach ($existingApiTags as $tag) {
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
      $node->set('apic_provider_id', $configService->getOrgId());
      $node->set('apic_catalog_id', $configService->getEnvId());
      $node->set('apic_summary', NULL);
      $node->set('apic_description', NULL);
      $node->set('apic_url', NULL);
      $node->set('apic_ref', NULL);
      $node->set('apic_version', NULL);
      $node->set('apic_pathalias', NULL);
      $node->set('api_id', NULL);
      $node->set('api_xibmname', NULL);
      $node->set('api_protocol', NULL);
      $node->set('api_oaiversion', NULL);
      $node->set('api_ibmconfiguration', NULL);
      $node->set('api_wsdl', NULL);
      $node->set('api_swagger', NULL);
      $node->set('api_encodedswagger', NULL);
      $node->set('api_swaggertags', NULL);
      $node->set('api_state', NULL);
    }
    else {
      $node = Node::create([
        'type' => 'api',
        'title' => $this->utils->truncate_string($api['consumer_api']['info']['title']),
        'apic_hostname' => $hostVariable,
        'apic_provider_id' => $configService->getOrgId(),
        'apic_catalog_id' => $configService->getEnvId(),
      ]);
    }

    // get the update method to do the update for us
    $node = $this->update($node, $api, 'internal');

    if ($node !==NULL && $oldTags !== NULL && !empty($oldTags)) {
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
      // Calling all modules implementing 'hook_apic_api_create':
      $moduleHandler->invokeAll('apic_api_create', ['node' => $node, 'data' => $api]);

      $config = \Drupal::config('ibm_apim.settings');
      $autoCreateForum = (boolean) $config->get('autocreate_apiforum');
      if ($autoCreateForum === TRUE) {
        if (!isset($api['consumer_api']['info']['description'])) {
          $api['consumer_api']['info']['description'] = '';
        }
        $this->apiTaxonomy->create_api_forum($this->utils->truncate_string($api['consumer_api']['info']['title']), $api['consumer_api']['info']['description']);
      }

      \Drupal::logger('apic_api')->notice('API @api @version created', ['@api' => $node->getTitle(), '@version' => $node->apic_version->value]);

      $nodeId = $node->id();
    } else {
      $nodeId = NULL;
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $nodeId);
    return $nodeId;
  }

  /**
   * Update an existing API
   *
   * @param \Drupal\node\NodeInterface $node
   * @param $api
   * @param string $event
   *
   * @return \Drupal\node\NodeInterface|null
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function update(NodeInterface $node, $api, $event = 'content_refresh'): ?NodeInterface {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    if ($node !== NULL) {
      $returnValue = NULL;
      module_load_include('inc', 'apic_api', 'apic_api.utils');
      module_load_include('inc', 'apic_api', 'apic_api.tags');
      module_load_include('inc', 'apic_api', 'apic_api.files');
      $configService = \Drupal::service('ibm_apim.site_config');
      $hostVariable = $configService->getApimHost();
      $moduleHandler = \Drupal::service('module_handler');
      $config = \Drupal::config('ibm_apim.settings');
      $languageList = array_keys(\Drupal::languageManager()->getLanguages(LanguageInterface::STATE_ALL));

      // filter out any retired apis and remove them
      if (array_key_exists('state', $api) && $api['state'] === 'retired') {
        \Drupal::logger('apic_api')->error('Update api: retired API @apiName @version, deleting it.', ['@apiName' => $api['name'], '@version' => $api['consumer_api']['info']['version']]);
        self::deleteNode($node->id(), 'retired_api');
        $node = NULL;
      } else {
        $node->setTitle($this->utils->truncate_string($api['consumer_api']['info']['title']));
        if (isset($api['consumer_api']['info']['x-ibm-languages']['title']) && !empty($api['consumer_api']['info']['x-ibm-languages']['title'])) {
          foreach ($api['consumer_api']['info']['x-ibm-languages']['title'] as $lang => $langArray) {
            $lang = $this->utils->convert_lang_name_to_drupal($lang);
            if (\in_array($lang, $languageList, FALSE)) {
              if (!$node->hasTranslation($lang)) {
                $translation = $node->addTranslation($lang, ['title' => $this->utils->truncate_string($api['consumer_api']['info']['x-ibm-languages']['title'][$lang])]);
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
        $node->set('apic_hostname', $hostVariable);
        $node->set('apic_provider_id', $configService->getOrgId());
        $node->set('apic_catalog_id', $configService->getEnvId());
        $node->set('api_id', $api['id']);
        $node->set('apic_version', $api['consumer_api']['info']['version']);
        if (isset($api['consumer_api']['info']['x-pathalias'])) {
          $node->set('apic_pathalias', $api['consumer_api']['info']['x-pathalias']);
        }

        // if unset, default state to online
        if (!isset($api['state']) || empty($api['state'])) {
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
          foreach ($api['consumer_api']['info']['x-ibm-languages']['description'] as $lang => $langArray) {
            $lang = $this->utils->convert_lang_name_to_drupal($lang);
            // if its one of our locales or the root of one of our locales
            foreach ($languageList as $langListKey => $langListValue) {
              if (\in_array($lang, $languageList, FALSE)) {
                if (!$node->hasTranslation($lang)) {
                  $translation = $node->addTranslation($lang, ['apic_description' => $this->utils->truncate_string($api['consumer_api']['info']['x-ibm-languages']['description'][$lang])]);
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
          foreach ($api['consumer_api']['info']['x-ibm-languages']['x-ibm-summary'] as $lang => $langArray) {
            $lang = $this->utils->convert_lang_name_to_drupal($lang);
            // if its one of our locales or the root of one of our locales
            foreach ($languageList as $langListKey => $langListValue) {
              if (\in_array($lang, $languageList, FALSE)) {
                if (!$node->hasTranslation($lang)) {
                  $translation = $node->addTranslation($lang, ['apic_summary' => $this->utils->truncate_string($api['consumer_api']['info']['x-ibm-languages']['x-ibm-summary'][$lang], 1000)]);
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
          'format' => 'plaintext',
        ]);

        $apiType = 'rest';
        if (isset($api['consumer_api']['x-ibm-configuration']['type'])) {
          $lowerType = mb_strtolower($api['consumer_api']['x-ibm-configuration']['type']);
          if ($lowerType === 'rest' || $lowerType === 'wsdl' || $lowerType === 'oauth' || $lowerType === 'graphql') {
            $apiType = $lowerType;
          }
        }
        $node->set('api_protocol', $apiType);
        $node->set('apic_url', $api['url']);

        if (!isset($api['consumer_api']['x-ibm-configuration']) || empty($api['consumer_api']['x-ibm-configuration'])) {
          $api['consumer_api']['x-ibm-configuration'] = '';
        }
        $node->set('api_ibmconfiguration', serialize($api['consumer_api']['x-ibm-configuration']));
        $oaiVersion = 2;
        if (isset($api['consumer_api']['openapi']) && $this->utils->startsWith($api['consumer_api']['openapi'], '3.')) {
          $oaiVersion = 3;
        }
        $node->set('api_oaiversion', $oaiVersion);
        $node->save();

        try {
          // if empty securityDefinitions and others then needs to be object not array
          // for now just remove them
          foreach (['securityDefinitions', 'responses', 'parameters', 'definitions', 'paths'] as $key) {
            if (array_key_exists($key, $api['consumer_api']) && \is_array($api['consumer_api'][$key]) && empty($api['consumer_api'][$key])) {
              unset($api['consumer_api'][$key]);
            }
          }
          $node->set('api_swagger', serialize(apic_api_remove_empty_elements($api['consumer_api'])));

          // stored as base64 encoded string so can be passed through to explorer without PHP messing up empty objects / arrays
          if (!array_key_exists('encoded_consumer_api', $api) || empty($api['encoded_consumer_api'])) {
            $api['encoded_consumer_api'] = base64_encode(json_encode($api['consumer_api']));
          }
          $node->set('api_encodedswagger', $api['encoded_consumer_api']);

          $node->set('api_swaggertags', []);
          if (isset($api['consumer_api']['tags'])) {
            $tags = [];
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
              $xIbmName = $api['consumer_api']['info']['x-ibm-name'];
              $node->set('api_xibmname', $xIbmName);
            }
            if (isset($api['consumer_api']['info']['version'])) {
              $xVersion = $api['consumer_api']['info']['version'];
              $node->set('apic_version', $xVersion);
            }
            if (isset($api['consumer_api']['info']['x-ibm-name'], $api['consumer_api']['info']['version'])) {
              $apiRef = $api['consumer_api']['info']['x-ibm-name'] . ':' . $api['consumer_api']['info']['version'];
              $node->set('apic_ref', $apiRef);
            }
          }
          $node->save();
        } catch (Exception $e) {
          \Drupal::logger('apic_api')
            ->notice('Update of Open API document to database failed with: %data', ['%data' => $e->getMessage()]);
        }
        // if SOAP API then go get the wsdl too
        if (isset($api['consumer_api']['x-ibm-configuration']['type']) && (mb_strtolower($api['consumer_api']['x-ibm-configuration']['type']) === 'wsdl')) {
          $wsdlContentType = $api['wsdl']['content_type'];

          if ($wsdlContentType === NULL) {
            $wsdlContentType = 'application/wsdl';
          }

          if (isset($api['wsdl']['content']) && !empty($api['wsdl']['content'])) {
            $data = base64_decode($api['wsdl']['content']);
            $fileName = $api['consumer_api']['info']['x-ibm-name'] . '_' . $api['consumer_api']['info']['version'];
            if ($wsdlContentType === 'application/wsdl') {
              $fileName .= '.wsdl';
            }
            else {
              $fileName .= '.zip';
            }
            $fileTemp = apic_api_save_wsdl($api['id'], $data, $fileName);
            if ($fileTemp !== NULL) {
              $updated = FALSE;
              $deleteFid = -1;
              $attachments = $node->apic_attachments->getValue();
              if ($attachments !== NULL) {
                foreach ($attachments as $key => $existingDoc) {
                  $existingDocFile = \Drupal\file\Entity\File::load($existingDoc['target_id']);
                  if ($existingDocFile !== NULL) {
                    $existingDocFileUri = $existingDocFile->getFileUri();
                    if ($existingDocFileUri !== NULL) {
                      $parts = explode('/', $existingDocFileUri);
                      $isWSDL = \in_array('apiwsdl', $parts, FALSE);
                      if ($isWSDL === TRUE && (string) $fileTemp->id() !== (string) $existingDoc['target_id']) {
                        $description = $existingDoc['description'] ?? '';
                        if ($updated === FALSE) {
                          $deleteFid = $existingDoc['target_id'];
                          $attachments[$key] = [
                            'target_id' => $fileTemp->id(),
                            'display' => 1,
                            'description' => $description,
                          ];
                          $updated = TRUE;
                        }
                      }
                      elseif ($isWSDL === TRUE && (string) $fileTemp->id() === (string) $existingDoc['target_id']) {
                        $updated = TRUE;
                      }
                    }
                  }
                  else {
                    unset($attachments[$key]);
                  }
                }
              }
              if ($updated === FALSE) {
                $attachments[] = [
                  'target_id' => $fileTemp->id(),
                  'display' => 1,
                ];
              }
              elseif ($deleteFid !== -1 && $deleteFid !== $fileTemp->id()) {
                $fileEntity = \Drupal\file\Entity\File::load($deleteFid);
                if ($fileEntity !== NULL) {
                  $fileEntity->delete();
                }
              }
              $node->set('apic_attachments', $attachments);
              $node->save();
            }

            if ($wsdlContentType === 'application/zip') {
              $data = apic_api_get_string_from_zip(base64_decode($api['wsdl']['content']));
            }
            $serialized = Xss::filter(serialize($data));
            if (($data !== NULL && !empty($data)) && ($node->api_wsdl->value !== $serialized)) {
              try {
                $node->set('api_wsdl', $serialized);
                $node->save();
              } catch (Exception $e) {
                \Drupal::logger('apic_api')
                  ->notice('Save of WSDL to database failed with: %data', ['%data' => $e->getMessage()]);
              }
            }
          }
        }

        // API Categories
        $categoriesEnabled = (boolean) $config->get('categories')['enabled'];
        if ($categoriesEnabled === TRUE && isset($api['consumer_api']['x-ibm-configuration']['categories'])) {
          $this->apiTaxonomy->process_categories($api, $node);
        }

        $phaseTagging = (boolean) $config->get('autotag_with_phase');
        if ($phaseTagging === TRUE && isset($api['consumer_api']['x-ibm-configuration']['phase'])) {
          $this->apiTaxonomy->process_phase_tag($node, $api['consumer_api']['x-ibm-configuration']['phase']);
        }
        // enable application certificates if we find an API that uses it
        if (isset($api['consumer_api']['x-ibm-configuration']['application-authentication']['certificate']) && (boolean) $api['consumer_api']['x-ibm-configuration']['application-authentication']['certificate'] === TRUE) {
          \Drupal::state()->set('ibm_apim.application_certificates', TRUE);
        }

        if ($node !== NULL && $event !== 'internal') {
          // Calling all modules implementing 'hook_apic_api_update':
          $moduleHandler->invokeAll('apic_api_update', ['node' => $node, 'data' => $api]);

        }

        if ($event !== 'internal') {
          \Drupal::logger('apic_api')->notice('API @api @version updated', ['@api' => $node->getTitle(), '@version' => $node->apic_version->value]);
        }
      }
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      $returnValue = $node;
    }
    else {
      \Drupal::logger('apic_api')->error('Update api: no node provided.', []);
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      $returnValue = NULL;
    }
    return $returnValue;
  }

  /**
   * Create a new API if one doesnt already exist for that API reference
   * Update one if it does
   *
   * @param $api
   * @param $event
   *
   * @return bool
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createOrUpdate($api, $event): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $api['consumer_api']['info']['x-ibm-name'] . ':' . $api['consumer_api']['info']['version']);
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'api');
    $query->condition('apic_ref.value', $api['consumer_api']['info']['x-ibm-name'] . ':' . $api['consumer_api']['info']['version']);

    $nids = $query->execute();

    if ($nids !== NULL && !empty($nids)) {
      $nid = array_shift($nids);
      $node = Node::load($nid);
      if ($node !== NULL) {
        $this->update($node, $api, $event);
        $createdOrUpdated = FALSE;
      }
      else {
        // the existing node must have got deleted, create a new one
        $this->create($api, $event);
        $createdOrUpdated = TRUE;
      }
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
      \Drupal::logger('apic_api')->notice('API @api @version deleted', ['@api' => $node->getTitle(), '@version' => $node->apic_version->value]);

      // Calling all modules implementing 'hook_apic_api_delete':
      $moduleHandler->invokeAll('apic_api_delete', ['node' => $node]);

      $node->delete();
      unset($node);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * @return string - api icon for a given name
   *
   * @param $name
   *
   * @return string
   */
  public static function getRandomImageName($name): string {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $name);
    $asInt = 0;
    $length = mb_strlen($name);
    for ($i = 0; $i < $length; $i++) {
      $asInt += \ord($name[$i]);
    }
    $digit = $asInt % 19;
    if ($digit === 0) {
      $digit = 1;
    }
    $num = str_pad($digit, 2, 0, STR_PAD_LEFT);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $num);
    return 'api_' . $num . '.png';
  }

  /**
   * @return string - path to placeholder image for a given name
   *
   * @param $name
   *
   * @return string
   */
  public static function getPlaceholderImage($name): string {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $name);
    $returnValue = Url::fromUri('internal:/' . drupal_get_path('module', 'apic_api') . '/images/' . self::getRandomImageName($name))
      ->toString();
    \Drupal::moduleHandler()->alter('api_getplaceholderimage', $returnValue);
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    return $returnValue;
  }

  /**
   * @return string - path to placeholder image for a given name
   *
   * @param $name
   *
   * @return string
   */
  public static function getPlaceholderImageURL($name): string {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $name);
    $rawImage = self::getRandomImageName($name);
    $returnValue = base_path() . drupal_get_path('module', 'apic_api') . '/images/' . $rawImage;
    \Drupal::moduleHandler()->alter('api_getplaceholderimageurl', $returnValue);
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    return $returnValue;
  }

  /**
   * Check if we have access to the specified API
   *
   * @param $node
   *
   * @return bool
   */
  public static function checkAccess($node): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $node->id());
    $userUtils = \Drupal::service('ibm_apim.user_utils');
    // allow those with permission to bypass check
    if ($userUtils->explicitUserAccess('edit any api content')) {
      return TRUE;
    }
    $productNids = Product::listProducts();
    $productNodes = Node::loadMultiple($productNids);
    $found = FALSE;
    foreach ($productNodes as $productNode) {
      foreach ($productNode->product_apis->getValue() as $arrayValue) {
        $apis = unserialize($arrayValue['value'], ['allowed_classes' => FALSE]);
        foreach ($apis as $prodRef) {
          if ($prodRef['name'] === $node->apic_ref->value) {
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
   *
   * @return array
   */
  public static function listApis(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $nids = [];
    $results = NULL;
    // user has access to everything
    $userUtils = \Drupal::service('ibm_apim.user_utils');

    if ($userUtils->explicitUserAccess('edit any api content')) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'api');
      $query->condition('status', 1);

      $results = $query->execute();
    }
    else {
      $productNids = Product::listProducts();
      $productNodes = Node::loadMultiple($productNids);
      $refArray = [];
      foreach ($productNodes as $productNode) {
        foreach ($productNode->product_apis->getValue() as $arrayValue) {
          $apis = unserialize($arrayValue['value'], ['allowed_classes' => FALSE]);
          foreach ($apis as $prodRef) {
            $refArray[] = $prodRef['name'];
          }
        }
      }

      if ($refArray !== NULL && \is_array($refArray) && count($refArray) > 0) {
        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'api');
        $query->condition('status', 1);
        $query->condition('apic_ref.value', $refArray, 'IN');
        $results = $query->execute();
      }
    }
    if ($results !== NULL && !empty($results)) {
      $nids = array_values($results);
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $nids);
    return $nids;
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
      ->load('node.api.default')
      ->getComponents();
    $keys = array_keys($components);
    $ibmFields = self::getIBMFields();
    $merged = array_merge($coreFields, $ibmFields);
    $diff = array_diff($keys, $merged);

    // make sure we only include actual custom fields so check there is a field config
    foreach ($diff as $key => $field) {
      $fieldConfig = FieldConfig::loadByName('node', 'api', $field);
      if ($fieldConfig === NULL) {
        unset($diff[$key]);
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $diff);
    return $diff;
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
      'api_encodedswagger',
      'api_swaggertags',
      'api_wsdl',
      'api_xibmname',
    ];
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $ibmFields;
  }

  /**
   * Get all the doc pages that are listed as being linked to a given API NID
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
    $query->condition('allapis.value', 1);
    $results = $query->execute();
    if ($results !== NULL && !empty($results)) {
      $finalNids = array_values($results);
    }
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'page');
    $query->condition('status', 1);
    $query->condition('apiref.target_id', $nid);
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
            'url' => $node->toUrl()->toString(),
            'extractPortalContent' => TRUE,
          ];
        }
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $docs);
    return $docs;
  }

  /**
   * Returns a JSON representation of an API
   *
   * @param $url
   *
   * @return string (JSON)
   */
  public function getApiAsJson($url): string {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, ['url' => $url]);
    $output = NULL;
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'api');
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
        \Drupal::logger('api')->notice('getApiAsJson: serialization module not enabled', []);
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
        $api = unserialize($node->api_swagger->value, ['allowed_classes' => FALSE]);
        if (isset($api['x-ibm-configuration']['categories'])) {
          $this->apiTaxonomy->process_categories($api, $node);
        }
      }
    }
  }
}
