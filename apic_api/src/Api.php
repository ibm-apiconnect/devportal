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

namespace Drupal\apic_api;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\product\Product;
use Exception;
use Throwable;

/**
 * Class to work with the API content type, takes input from the JSON returned by
 * IBM API Connect
 */
class Api {

  protected $apicTaxonomy;

  protected $utils;

  public function __construct() {
    $this->apicTaxonomy = \Drupal::service('ibm_apim.taxonomy');
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
   * @throws \JsonException
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
      $api = json_decode($api, TRUE, 512, JSON_THROW_ON_ERROR);
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
      $nids = $query->accessCheck()->execute();
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

      $existingApiTags = $this->apicTaxonomy->separate_categories($totalTags, $existingCategories);
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
      $node->save();

      // update any apirefs on basic pages
      $this->updateBasicPageRefs($oldNode->id(), $node->id());

      // wipe all our fields to ensure they get set to new values
      $node->set('apic_tags', []);
      $node->set('uid', 1);
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
      $node->set('apic_created_at', NULL);
      $node->set('apic_updated_at', NULL);
    }
    else {
      $node = Node::create([
        'type' => 'api',
        'title' => $this->utils->truncate_string($api['consumer_api']['info']['title']),
        'apic_hostname' => $hostVariable,
        'apic_provider_id' => $configService->getOrgId(),
        'apic_catalog_id' => $configService->getEnvId(),
        'uid' => 1
      ]);
    }

    // get the update method to do the update for us
    $node = $this->update($node, $api, 'internal');

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
      // Calling all modules implementing 'hook_apic_api_create':
      $moduleHandler->invokeAll('apic_api_create', ['node' => $node, 'data' => $api]);

      $config = \Drupal::config('ibm_apim.settings');
      $autoCreateForum = (boolean) $config->get('autocreate_apiforum');
      if ($autoCreateForum === TRUE) {
        if (!isset($api['consumer_api']['info']['description'])) {
          $api['consumer_api']['info']['description'] = '';
        }
        $this->apicTaxonomy->create_api_forum($this->utils->truncate_string($api['consumer_api']['info']['title']), $api['consumer_api']['info']['description']);
      }

      \Drupal::logger('apic_api')->notice('API @api @version created', [
        '@api' => $node->getTitle(),
        '@version' => $node->apic_version->value,
      ]);

      $nodeId = $node->id();
    }
    else {
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
      $existingNodeHash = $this->utils->generateNodeHash($node, 'old-api');
      $returnValue = NULL;
      \Drupal::moduleHandler()->loadInclude('apic_api', 'inc', 'apic_api.utils');
      \Drupal::moduleHandler()->loadInclude('apic_api', 'inc', 'apic_api.files');
      $configService = \Drupal::service('ibm_apim.site_config');
      $hostVariable = $configService->getApimHost();
      $moduleHandler = \Drupal::service('module_handler');
      $config = \Drupal::config('ibm_apim.settings');
      $languageList = array_keys(\Drupal::languageManager()->getLanguages(LanguageInterface::STATE_ALL));

      // filter out any retired apis and remove them
      if (array_key_exists('state', $api) && $api['state'] === 'retired') {
        \Drupal::logger('apic_api')->notice('Update api: retired API @apiName @version, deleting it.', [
          '@apiName' => $api['name'],
          '@version' => $api['consumer_api']['info']['version'],
        ]);
        self::deleteNode($node->id(), 'retired_api');
        unset($node);
      }
      else {
        $truncated_title = $this->utils->truncate_string($api['consumer_api']['info']['title']);
        // title must be set, if not fall back on name
        if (isset($truncated_title) && !empty($truncated_title)) {
          $node->setTitle($truncated_title);
        }
        else {
          $node->setTitle($api['name']);
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
        if ($moduleHandler->moduleExists('ghmarkdown')) {
          $format = 'ghmarkdown';
        }
        else {
          $format = 'full_html';
        }
        $description = $api['consumer_api']['info']['description'];
        if ($description && $description != '') {
          $node->set('apic_description', ['value' => $description, 'format' => $format]);
        }
        // OAI3.1 has summary, so check for that first then fallback on x-ibm-summary
        $summaryField = 'x-ibm-summary';
        if (isset($api['consumer_api']['info']['summary']) && !empty($api['consumer_api']['info']['summary'])) {
          $summaryField = 'summary';
        }
        // ensure summary is at least set to empty string
        if (!isset($api['consumer_api']['info'][$summaryField]) || empty($api['consumer_api']['info'][$summaryField])) {
          $api['consumer_api']['info'][$summaryField] = '';
        }
        $summary = $this->utils->truncate_string($api['consumer_api']['info'][$summaryField], 1000);
        if ($summary != '') {
          $node->set('apic_summary', [
            'value' => $this->utils->truncate_string($api['consumer_api']['info'][$summaryField], 1000),
            'format' => 'plaintext',
          ]);
        } else {
          $node->set('apic_summary', []);
        }

        $apiType = 'rest';
        if (isset($api['consumer_api']['x-ibm-configuration']['type'])) {
          $lowerType = mb_strtolower($api['consumer_api']['x-ibm-configuration']['type']);
          if ($lowerType === 'rest' || $lowerType === 'wsdl' || $lowerType === 'oauth' || $lowerType === 'graphql') {
            $apiType = $lowerType;
          }
          elseif ($lowerType === 'asyncapi' || isset($api['consumer_api']['asyncapi'])) {
            if (isset($api['consumer_api']['servers'])) {
              $protocol = array_values($api['consumer_api']['servers'])[0]['protocol'];
              $lowerProtocol = mb_strtolower($protocol);
              if ($lowerProtocol === 'kafka' || $lowerProtocol === 'kafka-secure') {
                $apiType = 'kafka';
              }
              elseif ($lowerProtocol === 'ibmmq' || $lowerProtocol === 'ibmmq-secure' || $lowerProtocol === 'amqp' || $lowerProtocol === 'amqps') {
                $apiType = 'mq';
              }
              else {
                $apiType = 'asyncapi';
              }
            }
            else {
              $apiType = 'asyncapi';
            }
          }
        }
        $node->set('api_protocol', $apiType);
        $node->set('apic_url', $api['url']);

        if (isset($api['created_at'])) {
          // store as epoch, incoming format will be like 2021-02-26T12:18:59.000Z
          $node->set('apic_created_at', strval(strtotime($api['created_at'])));
        }
        if (isset($api['updated_at'])) {
          // store as epoch, incoming format will be like 2021-02-26T12:18:59.000Z
          $node->set('apic_updated_at', strval(strtotime($api['updated_at'])));
        }

        if (!isset($api['consumer_api']['x-ibm-configuration']) || empty($api['consumer_api']['x-ibm-configuration'])) {
          $api['consumer_api']['x-ibm-configuration'] = '';
        }
        $node->set('api_ibmconfiguration', serialize($api['consumer_api']['x-ibm-configuration']));
        $oaiVersion = '2';
        if (isset($api['consumer_api']['openapi']) && $this->utils->startsWith($api['consumer_api']['openapi'], '3.')) {
          $oaiVersion = '3';
        }
        elseif (isset($api['consumer_api']['asyncapi'])) {
          $oaiVersion = 'asyncapi2';
          \Drupal::state()->set('ibm_apim.asyncapis_present', TRUE);
        }
        $node->set('api_oaiversion', $oaiVersion);

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
            $api['encoded_consumer_api'] = base64_encode(json_encode($api['consumer_api'], JSON_THROW_ON_ERROR));
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
            elseif (isset($api['consumer_api']['channels'])) {
              foreach ($api['consumer_api']['channels'] as $path) {
                foreach ($path as $verb => $operation) {
                  if (isset($path['tags'])) {
                    foreach ($path['tags'] as $tag) {
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
        } catch (Throwable $e) {
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
                  $existingDocFile = File::load($existingDoc['target_id']);
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
                $fileEntity = File::load($deleteFid);
                if ($fileEntity !== NULL) {
                  $fileEntity->delete();
                }
              }
              $node->set('apic_attachments', $attachments);
            }

            if ($wsdlContentType === 'application/zip') {
              $data = apic_api_get_string_from_zip(base64_decode($api['wsdl']['content']));
            }
            $serialized = Xss::filter(serialize($data));
            if (($data !== NULL && !empty($data)) && ($node->api_wsdl->value !== $serialized)) {
              try {
                $node->set('api_wsdl', $serialized);
              } catch (Throwable $e) {
                \Drupal::logger('apic_api')
                  ->notice('Save of WSDL to database failed with: %data', ['%data' => $e->getMessage()]);
              }
            }
          }
        }

        // API Categories
        $categoriesEnabled = (boolean) $config->get('categories')['enabled'];
        if ($categoriesEnabled === TRUE && isset($api['consumer_api']['x-ibm-configuration']['categories'])) {
          $this->apicTaxonomy->process_api_categories($api, $node);
        }

        $phaseTagging = (boolean) $config->get('autotag_with_phase');
        if ($phaseTagging === TRUE && isset($api['consumer_api']['x-ibm-configuration']['phase'])) {
          $this->apicTaxonomy->process_phase_tag($node, $api['consumer_api']['x-ibm-configuration']['phase']);
        }
        // enable application certificates if we find an API that uses it
        if (isset($api['consumer_api']['x-ibm-configuration']['application-authentication']['certificate']) && (boolean) $api['consumer_api']['x-ibm-configuration']['application-authentication']['certificate'] === TRUE) {
          \Drupal::state()->set('ibm_apim.application_certificates', TRUE);
        }

        if ($this->utils->hashMatch($existingNodeHash, $node, 'new-api')) {
          if ($event !== 'internal') {
            \Drupal::logger('apic_api')->notice('Update api: No update required as the hash matched for @apiName @version', [
              '@apiName' => $api['name'],
              '@version' => $api['consumer_api']['info']['version'],
            ]);
          }
        } else {
          if (isset($api['consumer_api']['info']['x-ibm-languages']['title']) && !empty($api['consumer_api']['info']['x-ibm-languages']['title'])) {
            foreach ($api['consumer_api']['info']['x-ibm-languages']['title'] as $lang => $langArray) {
              $lang = $this->utils->convert_lang_name_to_drupal($lang);
              if (\in_array($lang, $languageList, FALSE)) {
                $truncated_title = $this->utils->truncate_string($api['consumer_api']['info']['x-ibm-languages']['title'][$lang]);
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

          if (isset($api['consumer_api']['info']['x-ibm-languages']['description']) && !empty($api['consumer_api']['info']['x-ibm-languages']['description'])) {
            foreach ($api['consumer_api']['info']['x-ibm-languages']['description'] as $lang => $langArray) {
              $lang = $this->utils->convert_lang_name_to_drupal($lang);
              // if its one of our locales or the root of one of our locales
              foreach ($languageList as $langListKey => $langListValue) {
                if (\in_array($lang, $languageList, FALSE)) {
                  if (!$node->hasTranslation($lang)) {
                    // ensure the translation has a title as its a required field
                    $translation = $node->addTranslation($lang, [
                      'title' => $truncated_title,
                      'apic_description' => [ 'value' => $this->utils->truncate_string($api['consumer_api']['info']['x-ibm-languages']['description'][$lang]), 'format' => $format ],
                    ]);
                    $translation->save();
                  }
                  else {
                    $translation = $node->getTranslation($lang);
                    // ensure the translation has a title as its a required field
                    if ($translation->getTitle() === NULL || $translation->getTitle() === "") {
                      $translation->setTitle($truncated_title);
                    }
                    $translation->set('apic_description', [
                      'value' => $this->utils->truncate_string($api['consumer_api']['info']['x-ibm-languages']['description'][$lang]),
                      'format' => $format,
                    ])->save();
                  }
                }
              }
            }
          }

          if (isset($api['consumer_api']['info']['x-ibm-languages'][$summaryField]) && !empty($api['consumer_api']['info']['x-ibm-languages'][$summaryField])) {
            foreach ($api['consumer_api']['info']['x-ibm-languages'][$summaryField] as $lang => $langArray) {
              $lang = $this->utils->convert_lang_name_to_drupal($lang);
              // if its one of our locales or the root of one of our locales
              foreach ($languageList as $langListKey => $langListValue) {
                if (\in_array($lang, $languageList, FALSE)) {
                  if (!$node->hasTranslation($lang)) {
                    // ensure the translation has a title as its a required field
                    $translation = $node->addTranslation($lang, [
                      'title' => $truncated_title,
                      'apic_summary' => $this->utils->truncate_string($api['consumer_api']['info']['x-ibm-languages'][$summaryField][$lang], 1000),
                    ]);
                    $translation->save();
                  }
                  else {
                    $translation = $node->getTranslation($lang);
                    // ensure the translation has a title as its a required field
                    if ($translation->getTitle() === NULL || $translation->getTitle() === "") {
                      $translation->setTitle($truncated_title);
                    }
                    $translation->set('apic_summary', $this->utils->truncate_string($api['consumer_api']['info']['x-ibm-languages'][$summaryField][$lang], 1000))
                      ->save();
                  }
                }
              }
            }
          }

          $node->save();

          if ($event !== 'internal') {
            // Calling all modules implementing 'hook_apic_api_update':
            $moduleHandler->invokeAll('apic_api_update', ['node' => $node, 'data' => $api]);

            \Drupal::logger('apic_api')->notice('API @api @version updated', [
              '@api' => $node->getTitle(),
              '@version' => $node->apic_version->value,
            ]);
          }
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
   * @throws \Drupal\Core\Entity\EntityStorageException|\JsonException
   */
  public function createOrUpdate($api, $event): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $api['consumer_api']['info']['x-ibm-name'] . ':' . $api['consumer_api']['info']['version']);
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'api');
    $query->condition('apic_ref.value', $api['consumer_api']['info']['x-ibm-name'] . ':' . $api['consumer_api']['info']['version']);

    $nids = $query->accessCheck()->execute();

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
      \Drupal::logger('apic_api')->notice('API @api @version deleted', [
        '@api' => $node->getTitle(),
        '@version' => $node->apic_version->value,
      ]);

      // Calling all modules implementing 'hook_apic_api_delete':
      $moduleHandler->invokeAll('apic_api_delete', ['node' => $node]);

      $node->delete();
      unset($node);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * @param $name
   *
   * @return string - api icon for a given name
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
   * @param $name
   *
   * @return string - path to placeholder image for a given name
   *
   * @return string
   */
  public static function getPlaceholderImage($name): string {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $name);
    $returnValue = Url::fromUri('internal:/' . \Drupal::service('extension.list.module')->getPath('apic_api') . '/images/' . self::getRandomImageName($name))
      ->toString();
    \Drupal::moduleHandler()->alter('api_getplaceholderimage', $returnValue);
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
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
    $returnValue = base_path() . \Drupal::service('extension.list.module')->getPath('apic_api') . '/images/' . $rawImage;
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
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function checkAccess($node): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $node->id());
    $userUtils = \Drupal::service('ibm_apim.user_utils');
    // allow those with permission to bypass check
    if ($userUtils->explicitUserAccess('edit any api content')) {
      return TRUE;
    }
    $productNids = Product::listProducts();
    $found = FALSE;
    foreach (array_chunk($productNids, 50) as $chunk) {
      $productNodes = Node::loadMultiple($chunk);
      foreach ($productNodes as $productNode) {
        foreach ($productNode->product_apis->getValue() as $arrayValue) {
          $api = unserialize($arrayValue['value'], ['allowed_classes' => FALSE]);
          if (isset($api['name']) && $api['name'] === $node->apic_ref->value) {
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
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
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

      $results = $query->accessCheck()->execute();
    }
    else {
      $refArray = [];
      $productNids = Product::listProducts();
      foreach (array_chunk($productNids, 50) as $chunk) {
        $productNodes = Node::loadMultiple($chunk);
        foreach ($productNodes as $productNode) {
          foreach ($productNode->product_apis->getValue() as $arrayValue) {
            $apis = unserialize($arrayValue['value'], ['allowed_classes' => FALSE]);
            foreach ($apis as $prodRef) {
              $refArray[] = $prodRef['name'];
            }
          }
        }
      }

      if ($refArray !== NULL && \is_array($refArray) && count($refArray) > 0) {
        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'api');
        $query->condition('status', 1);
        $query->condition('apic_ref.value', $refArray, 'IN');
        $results = $query->accessCheck()->execute();
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
      'apic_created_at',
      'apic_updated_at',
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
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public static function getLinkedPages($nid): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $nid);
    $finalNids = [];
    $docs = [];
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'page');
    $query->condition('status', 1);
    $query->condition('allapis.value', 1);
    $results = $query->accessCheck()->execute();
    if ($results !== NULL && !empty($results)) {
      $finalNids = array_values($results);
    }
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'page');
    $query->condition('status', 1);
    $query->condition('apiref.target_id', $nid);
    $results = $query->accessCheck()->execute();
    if ($results !== NULL && !empty($results)) {
      $nids = array_values($results);
      $finalNids = array_merge($finalNids, $nids);
    }
    // process the nodes and build an array of the info we need
    $finalNids = array_unique($finalNids, SORT_NUMERIC);
    if ($finalNids !== NULL && !empty($finalNids)) {
      foreach (array_chunk($finalNids, 50) as $chunk) {
        $nodes = Node::loadMultiple($chunk);
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

    $nids = $query->accessCheck()->execute();

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
   * Returns an array representation of an API for returning to drush
   *
   * @param $url
   *
   * @return array
   */
  public static function getApiForDrush($url): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, ['url' => $url]);
    $output = NULL;
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'api');
    $query->condition('apic_url.value', $url);

    $nids = $query->accessCheck()->execute();

    if ($nids !== NULL && !empty($nids)) {
      $nid = array_shift($nids);
      $node = Node::load($nid);
      if ($node !== NULL) {
        $output['url'] = $url;
        $output['id'] = $node->api_id->value;
        $output['name'] = $node->api_xibmname->value;
        $output['version'] = $node->apic_version->value;
        $output['title'] = $node->getTitle();
        $output['state'] = $node->api_state->value;
        $output['type'] = $node->api_protocol->value;
        $output['summary'] = $node->apic_summary->value;
        $output['description'] = $node->apic_description->value;
        $output['created_at'] = $node->apic_created_at->value;
        $output['updated_at'] = $node->apic_updated_at->value;
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $output;
  }

  /**
   * Returns the base64encoded string of an API document for returning to drush
   * Intentionally uses the base64encoded doc to avoid PHP messing up [] and {}
   *
   * @param $url
   *
   * @return string
   */
  public static function getApiDocumentForDrush($url): string {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, ['url' => $url]);
    $output = '';
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'api');
    $query->condition('apic_url.value', $url);

    $nids = $query->accessCheck()->execute();

    if ($nids !== NULL && !empty($nids)) {
      $nid = array_shift($nids);
      $node = Node::load($nid);
      if ($node !== NULL) {
        $output = $node->api_encodedswagger->value;
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
          $this->apicTaxonomy->process_api_categories($api, $node);
          $node->save();
        }
      }
    }
  }

  /**
   * Update any basic page references to point to the new node
   *
   * @param $oldNid
   * @param $newNid
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateBasicPageRefs($oldNid, $newNid): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    if (isset($oldNid, $newNid)) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'page')->condition('apiref', $oldNid, 'CONTAINS');
      $nids = $query->accessCheck()->execute();
      if ($nids !== NULL && !empty($nids)) {
        foreach ($nids as $nid) {
          $node = Node::load($nid);
          if ($node !== NULL) {
            $newArray = $node->apiref->getValue();
            foreach ($newArray as $key => $value) {
              if ($value['target_id'] === (string) $oldNid) {
                $newArray[$key]['target_id'] = (string) $newNid;
              }
            }
            $node->set('apiref', $newArray);
            $node->save();
          }
        }
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

}
