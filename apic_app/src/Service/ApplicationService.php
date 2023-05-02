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

namespace Drupal\apic_app\Service;

use Drupal\apic_app\Entity\ApplicationCredentials;
use Drupal\apic_app\Entity\ApplicationSubscription;
use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\TempStore\TempStoreException;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\file\Entity\File;
use Drupal\ibm_apim\Service\ApimUtils;
use Drupal\ibm_apim\Service\EventLogService;
use Drupal\ibm_apim\Service\SiteConfig;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\ibm_apim\Service\Utils;
use Drupal\ibm_event_log\ApicType\ApicEvent;
use Drupal\product\Product;
use Drupal\ibm_apim\Service\ProductPlan;
use Symfony\Component\Serializer\Serializer;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Class to work with the Application content type, takes input from the JSON returned by
 * IBM API Connect
 */
class ApplicationService {

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
  protected UserUtils $userUtils;

  /**
   * @var \Drupal\ibm_apim\Service\ApimUtils
   */
  protected ApimUtils $apimUtils;

  /**
   * @var \Drupal\ibm_apim\Service\Utils
   */
  protected Utils $utils;

  /**
   * @var \Drupal\ibm_apim\Service\EventLogService
   */
  protected EventLogService $eventLogService;

  /**
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected ModuleHandler $moduleHandler;

  /**
   * @var \Drupal\apic_app\Service\CredentialsService
   */
  protected CredentialsService $credentialsService;

  /**
   * @var \Drupal\jsonapi\Serializer\Serializer
   */
  protected Serializer $serializer;

  /**
   * @var \Drupal\ibm_apim\Service\SiteConfig
   */
  protected SiteConfig $siteConfig;

  /**
   * @var \Drupal\ibm_apim\Service\ProductPlan
   */
  protected ProductPlan $productPlan;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected EntityTypeManager $entityTypeManager;


  /**
   * CredentialsService constructor.
   *
   * @param \Drupal\ibm_apim\Service\UserUtils $userUtils
   * @param \Drupal\ibm_apim\Service\ApimUtils $apimUtils
   * @param \Drupal\ibm_apim\Service\Utils $utils
   * @param \Drupal\ibm_apim\Service\EventLogService $eventLogService
   * @param \Drupal\Core\Extension\ModuleHandler $moduleHandler
   * @param \Drupal\apic_app\Service\CredentialsService $credentialsService
   * @param \Symfony\Component\Serializer\Serializer $serializer
   * @param \Drupal\ibm_apim\Service\SiteConfig $siteConfig
   * @param \Drupal\ibm_apim\Service\ProductPlan $productPlan
   * @param \Drupal\Core\Entity\EntityTypeManager $entityTypeManager
   */
  public function __construct(UserUtils $userUtils,
                              ApimUtils $apimUtils,
                              Utils $utils,
                              EventLogService $eventLogService,
                              ModuleHandler $moduleHandler,
                              CredentialsService $credentialsService,
                              Serializer $serializer,
                              SiteConfig $siteConfig,
                              ProductPlan $productPlan,
                              EntityTypeManager $entityTypeManager) {
    $this->userUtils = $userUtils;
    $this->apimUtils = $apimUtils;
    $this->utils = $utils;
    $this->eventLogService = $eventLogService;
    $this->moduleHandler = $moduleHandler;
    $this->credentialsService = $credentialsService;
    $this->serializer = $serializer;
    $this->siteConfig = $siteConfig;
    $this->productPlan = $productPlan;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * @param $app
   * @param string $event
   * @param null $formState
   *
   * @return int|string|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   */
  public function create($app, $event = 'publish', $formState = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $node = Node::create([
      'type' => 'application',
      'uid' => 1
    ]);

    // get the update method to do the update for us
    $node = $this->update($node, $app, 'internal', $formState);
    if (isset($node)) {

      $this->invokeAppCreateHook($app, $node);

      \Drupal::logger('apic_app')->notice('Application @app created', ['@app' => $node->getTitle()]);
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $node->id());
    return $node->id();
  }

  /**
   * Update an existing Application
   *
   * @param \Drupal\node\NodeInterface $node
   * @param $app
   * @param string $event
   * @param $formState
   *
   * @return \Drupal\node\NodeInterface|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   */
  public function update(NodeInterface $node, $app, $event = 'content_refresh', $formState = NULL): ?NodeInterface {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $returnValue = NULL;

    // store a copy of the app we receive in case we need to send a complete set of data in hooks
    $create_hook_app = $app;
    if (isset($node)) {
      // Don't store client_secret!
      if (isset($app['client_secret'])) {
        unset($app['client_secret']);
      }

      $hostVariable = $this->siteConfig->getApimHost();

      // Need to set this before we calculate the old hash, as it is repesented as a
      // different type (string vs int etc.) when set compared to when queried from the DB
      // Being as they are always the same value anyway they should always match between old and new
      $node->setPromoted(NodeInterface::NOT_PROMOTED);

      $existingNodeHash = $this->utils->generateNodeHash($node, 'old-app');

      if (isset($app['title'])) {
        $node->setTitle($this->utils->truncate_string($app['title']));
      }
      elseif (isset($app['name'])) {
        $node->setTitle($this->utils->truncate_string($app['name']));
      }
      else {
        $node->setTitle('No name');
      }
      $node->set('apic_hostname', $hostVariable);
      $node->set('apic_provider_id', $this->siteConfig->getOrgId());
      $node->set('apic_catalog_id', $this->siteConfig->getEnvId());
      $node->set('application_id', $app['id']);
      // need to update this when apim supports it
      $node->set('application_client_type', 'confidential');
      if (!isset($app['name']) || empty($app['name'])) {
        $app['name'] = '';
      }
      $node->set('application_name', $app['name']);
      // ensure summary is at least set to empty string
      if (!isset($app['summary']) || empty($app['summary'])) {
        $app['summary'] = '';
      }
      $this->utils->setNodeValue($node, 'apic_summary', $app['summary']);
      if (isset($app['consumer_org_url'])) {
        $app['consumer_org_url'] = $this->apimUtils->removeFullyQualifiedUrl($app['consumer_org_url']);
        $node->set('application_consumer_org_url', $app['consumer_org_url']);
        $appOrgUrl = $app['consumer_org_url'];
      }
      elseif (isset($app['org_url'])) {
        $app['org_url'] = $this->apimUtils->removeFullyQualifiedUrl($app['org_url']);
        $node->set('application_consumer_org_url', $app['org_url']);
        $appOrgUrl = $app['org_url'];
      }
      else {
        $appOrgUrl = '';
      }

      $converted_enabled = ($app['state'] === 'enabled') ? 'true' : 'false';
      $node->set('application_enabled', $converted_enabled);
      $endpoints = [];
      if (isset($app['redirect_endpoints'])) {
        foreach ($app['redirect_endpoints'] as $redirectUrl) {
          $endpoints[] = $redirectUrl;
        }
      }
      $node->set('application_redirect_endpoints', $endpoints);
      $node->set('apic_url', $this->apimUtils->removeFullyQualifiedUrl($app['url']));
      if (!isset($app['state']) || (strtolower($app['state']) !== 'enabled' && strtolower($app['state']) !== 'disabled')) {
        $app['state'] = 'enabled';
      }
      $node->set('apic_state', strtolower($app['state']));
      if (!isset($app['lifecycle_state']) || (strtoupper($app['lifecycle_state']) !== 'DEVELOPMENT' && strtoupper($app['lifecycle_state']) !== 'PRODUCTION')) {
        $app['lifecycle_state'] = 'PRODUCTION';
      }
      $node->set('application_lifecycle_state', strtoupper($app['lifecycle_state']));
      if (isset($app['lifecycle_state_pending'])) {
        $node->set('application_lifecycle_pending', $app['lifecycle_state_pending']);
      }
      else {
        $node->set('application_lifecycle_pending', NULL);
      }
      $creds = [];
      if (isset($app['app_credentials']) && !empty($app['app_credentials'])) {
        // do not store client secrets
        foreach ($app['app_credentials'] as $key => $cred) {
          if (isset($cred['client_secret'])) {
            unset($cred['client_secret'], $app['app_credentials'][$key]['client_secret']);
          }
          if (!isset($cred['summary'])) {
            $cred['summary'] = '';
          }
          if (isset($cred['url'])) {
            $cred['url'] = $this->apimUtils->removeFullyQualifiedUrl($cred['url']);
          }
          else {
            $cred['url'] = $app['url'] . '/credentials/' . $cred['id'];
          }
          if (!isset($cred['name'])) {
            $cred['name'] = '';
          }
          if (!isset($cred['title'])) {
            $cred['title'] = '';
          }
          $newCred = [
            'id' => $cred['id'],
            'client_id' => $cred['client_id'],
            'name' => $cred['name'],
            'title' => $cred['title'],
            'app_url' => $app['url'],
            'summary' => $cred['summary'],
            'consumerorg_url' => $appOrgUrl,
            'url' => $cred['url'],
          ];
          if (isset($cred['created_at'])) {
            $newCred['created_at'] = $cred['created_at'];
          }
          if (isset($cred['updated_at'])) {
            $newCred['updated_at'] = $cred['updated_at'];
          }
          $creds[] = $newCred;
        }
      }
      elseif (isset($app['client_id'], $app['app_credential_urls']) && sizeof($app['app_credential_urls']) === 1) {
        $creds = [];
        // If this is app create we will have client_id but no app_credentials array - fudge
        $cred_url = $this->apimUtils->removeFullyQualifiedUrl($app['app_credential_urls'][0]);
        $credential = ['client_id' => $app['client_id'], 'url' => $cred_url];
        $path = parse_url($credential['url'])['path'];
        $parts = explode('/', $path);
        $credential['id'] = array_pop($parts);

        $creds[] = [
          'id' => $credential['id'],
          'client_id' => $credential['client_id'],
          'name' => '',
          'title' => '',
          'app_url' => $app['url'],
          'summary' => '',
          'consumerorg_url' => $appOrgUrl,
          'url' => $credential['url'],
          'created_at' => $app['created_at'],
          'updated_at' => $app['updated_at'],
        ];
      }
      if (array_key_exists('created_at', $app) && is_string($app['created_at'])) {
        // store as epoch, incoming format will be like 2021-02-26T12:18:59.000Z
        $timestamp = strtotime($app['created_at']);
        if ($timestamp < 2147483647 && $timestamp > 0) {
          $node->set('apic_created_at', strval($timestamp));
        } else {
          $node->set('apic_created_at', strval(time()));
        }
      } else {
        $node->set('apic_created_at', strval(time()));
      }
      if (array_key_exists('updated_at', $app) && is_string($app['updated_at'])) {
        // store as epoch, incoming format will be like 2021-02-26T12:18:59.000Z
        $timestamp = strtotime($app['updated_at']);
        if ($timestamp < 2147483647 && $timestamp > 0) {
          $node->set('apic_updated_at', strval($timestamp));
        } else {
          $node->set('apic_updated_at', strval(time()));
        }
      } else {
        $node->set('apic_updated_at', strval(time()));
      }
      $node = $this->credentialsService->createOrUpdateCredentialsList($node, $creds, FALSE);

      $node->set('application_data', serialize($app));

      if ($formState !== NULL && !empty($formState)) {
        $customFields = $this->getCustomFields();
        $customFieldValues = $this->utils->handleFormCustomFields($customFields, $formState);
        $this->utils->saveCustomFields($node, $customFields, $customFieldValues, FALSE, FALSE);
      }
      elseif (!empty($app['metadata'])) {
        $customFields = $this->getCustomFields();
        $this->utils->saveCustomFields($node, $customFields, $app['metadata'], TRUE, FALSE);
      }

      // ensure this application links to all its subscriptions
      $query = \Drupal::entityQuery('apic_app_application_subs');
      $query->condition('app_url', $app['url']);
      $entityIds = $query->accessCheck()->execute();
      $newArray = [];
      if (isset($entityIds) && !empty($entityIds)) {
        foreach ($entityIds as $entityId) {
          $newArray[] = ['target_id' => $entityId];
        }
      }
      $node->set('application_subscription_refs', $newArray);

      if ($this->utils->hashMatch($existingNodeHash, $node, 'new-app')) {
        if ($event !== 'internal') {
          \Drupal::logger('apic_app')->notice('App @app not updated as the hash matched', ['@app' => $node->getTitle()]);}
      } else {
        $node->save();

        if ($node !== NULL && $event !== 'internal') {
          // we have support for calling create hook here as well because of timing issues with webhooks coming in and sending us down
          // the update path in createOrUpdate even when the initial user action was create
          if ($event === 'create' || $event === 'app_create') {
            \Drupal::logger('apic_app')->notice('Application @app created (update path)', ['@app' => $node->getTitle()]);

            $this->invokeAppCreateHook($create_hook_app, $node);
          } else {
            \Drupal::logger('apic_app')->notice('Application @app updated', ['@app' => $node->getTitle()]);

            // Calling all modules implementing 'hook_apic_app_update':
            $this->moduleHandler->invokeAll('apic_app_update', [$node, $app]);
          }
        }
      }
      $returnValue = $node;
    }
    else {
      \Drupal::logger('apic_app')->error('Update application: no node provided.', []);
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $returnValue;
  }

  /**
   * Create a new application if one doesnt already exist for that App reference
   * Update one if it does
   *
   * @param $app
   * @param $event
   * @param null $formState
   *
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createOrUpdate($app, $event, $formState = NULL): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'application');
    $query->condition('application_id.value', $app['id']);

    $nids = $query->accessCheck()->execute();

    if (isset($nids) && !empty($nids)) {
      $nid = array_shift($nids);
      $node = Node::load($nid);
      if ($node !== NULL) {
        $this->update($node, $app, $event, $formState);
        $createdOrUpdated = FALSE;
      }
      else {
        // no existing node for this App so create one
        $this->create($app, $event, $formState);
        $createdOrUpdated = TRUE;
      }
    }
    else {
      // no existing node for this App so create one
      $this->create($app, $event, $formState);
      $createdOrUpdated = TRUE;
    }

    // Add Activity Feed Event Log
    $eventEntity = new ApicEvent();
    $eventEntity->setArtifactType('application');
    if (\Drupal::currentUser()->isAuthenticated() && (int) \Drupal::currentUser()->id() !== 1) {
      $current_user = User::load(\Drupal::currentUser()->id());
      if ($current_user !== NULL) {
        // we only set the user if we're running as someone other than admin
        // if running as admin then we're likely doing things on behalf of the admin
        // TODO we might want to check if there is a passed in user_url and use that too
        $eventEntity->setUserUrl($current_user->get('apic_url')->value);
      }
    }
    if ($event === 'create' || $event === 'app_create') {
      $eventType = 'create';
      $timestamp = $app['created_at'];
    }
    else {
      $eventType = 'update';
      $timestamp = $app['updated_at'];
    }
    // if timestamp still not set default to current time
    if ($timestamp === NULL) {
      $timestamp = time();
    }
    else {
      // if it is set then ensure its epoch not a string
      // intentionally done this way round since strtotime on null might lead to odd effects
      $timestamp = strtotime($timestamp);
    }
    $eventEntity->setTimestamp((int) $timestamp);
    $eventEntity->setEvent($eventType);
    $eventEntity->setArtifactUrl($app['url']);
    $eventEntity->setAppUrl($app['url']);
    $appOrgUrl = $app['consumer_org_url'] ?? $app['org_url'];
    $eventEntity->setConsumerOrgUrl($appOrgUrl);
    if (isset($app['title'])) {
      $appTitle = $this->utils->truncate_string($app['title']);
    }
    elseif (isset($app['name'])) {
      $appTitle = $this->utils->truncate_string($app['name']);
    }
    else {
      $appTitle = 'No name';
    }
    $eventEntity->setData(['name' => $appTitle]);

    // if this is update then check there is already a create event in the db
    if ($eventType === 'update' && isset($app['created_at']) && $app['created_at'] !== $app['updated_at']) {
      $createEventEntity = clone $eventEntity;
      $timestamp = strtotime($app['created_at']);
      $createEventEntity->setTimestamp((int) $timestamp);
      $createEventEntity->setEvent('create');
      $this->eventLogService->createIfNotExist($createEventEntity);
    }

    $this->eventLogService->createIfNotExist($eventEntity);
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $createdOrUpdated);
    return $createdOrUpdated;
  }

  /**
   * @param $app
   * @param $event
   * @param $formState
   *
   * @return string|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createOrUpdateReturnNid($app, $event, $formState): ?string {
    $nid = NULL;

    if (isset($app['url'])) {
      $app['url'] = $this->apimUtils->removeFullyQualifiedUrl($app['url']);
    }
    if (isset($app['org_url'])) {
      $app['org_url'] = $this->apimUtils->removeFullyQualifiedUrl($app['org_url']);
    }
    if (isset($app['app_credential_urls'])) {
      foreach ($app['app_credential_urls'] as $key => $url) {
        $app['app_credential_urls'][$key] = $this->apimUtils->removeFullyQualifiedUrl($url);
      }
    }

    $this->createOrUpdate($app, $event, $formState);
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'application');
    $query->condition('application_id.value', $app['id']);
    $nids = $query->accessCheck()->execute();

    if (isset($nids) && !empty($nids)) {
      $nid = array_shift($nids);
    }

    return $nid;
  }

  /**
   * Delete an application by NID
   *
   * @param int $nid
   * @param string $event
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteNode(int $nid, string $event = 'internal'): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $nid);

    $node = Node::load($nid);
    if ($node !== NULL) {

      // Calling all modules implementing 'hook_apic_app_pre_delete':
      $hookData = $this->createAppHookData($node);
      $this->moduleHandler->invokeAll('apic_app_pre_delete', [
        'node' => $node,
        'data' => $hookData,
      ]);
      // TODO: invoke pre delete rule here

      // Delete all subscription entities for this application
      $query = \Drupal::entityQuery('apic_app_application_subs');
      $query->condition('app_url.value', $node->apic_url->value);

      $entityIds = $query->accessCheck()->execute();
      if (isset($entityIds) && !empty($entityIds)) {
        foreach (array_chunk($entityIds, 50) as $chunk) {
          $subEntities = ApplicationSubscription::loadMultiple($chunk);
          foreach ($subEntities as $subEntity) {
            $subId = $subEntity->id();
            $this->moduleHandler->invokeAll('apic_app_subscription_pre_delete', ['subId' => $subId]);
            $subEntity->delete();
            $this->moduleHandler->invokeAll('apic_app_subscription_post_delete', ['subId' => $subId]);
          }
        }
      }
      // Delete all credentials entities for this application
      $query = \Drupal::entityQuery('apic_app_application_creds');
      $query->condition('app_url.value', $node->apic_url->value);

      $entityIds = $query->accessCheck()->execute();
      if (isset($entityIds) && !empty($entityIds)) {
        foreach (array_chunk($entityIds, 50) as $chunk) {
          $credEntities = ApplicationCredentials::loadMultiple($chunk);
          foreach ($credEntities as $credEntity) {
            $credId = $credEntity->id();
            $this->moduleHandler->invokeAll('apic_app_credential_pre_delete', ['credId' => $credId]);
            $credEntity->delete();
            $this->moduleHandler->invokeAll('apic_app_credential_post_delete', ['credId' => $credId]);
          }
        }
      }

      // Add Activity Feed Event Log
      $eventEntity = new ApicEvent();
      $eventEntity->setArtifactType('application');
      if (\Drupal::currentUser()->isAuthenticated() && (int) \Drupal::currentUser()->id() !== 1) {
        $current_user = User::load(\Drupal::currentUser()->id());
        if ($current_user !== NULL) {
          // we only set the user if we're running as someone other than admin
          // if running as admin then we're likely doing things on behalf of the admin
          // TODO we might want to check if there is a passed in user_url and use that too
          $eventEntity->setUserUrl($current_user->get('apic_url')->value);
        }
      }

      $eventEntity->setEvent('delete');
      $eventEntity->setArtifactUrl($node->apic_url->value);
      $eventEntity->setAppUrl($node->apic_url->value);
      $eventEntity->setConsumerOrgUrl($node->application_consumer_org_url->value);
      $appTitle = $this->utils->truncate_string($node->getTitle());
      $eventEntity->setData(['name' => $appTitle]);
      $this->eventLogService->createIfNotExist($eventEntity);

      $node->delete();

      // Calling all modules implementing 'hook_apic_app_post_delete':
      $this->moduleHandler->invokeAll('apic_app_post_delete', [
        'data' => $hookData,
      ]);

      \Drupal::logger('apic_app')->notice('Application @app deleted', ['@app' => $node->getTitle()]);
      unset($node);
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Delete an application by Application ID
   *
   * @param null $id
   * @param $event
   *
   * @return bool
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteById($id, $event): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $id);
    $returnValue = FALSE;
    if (isset($id)) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'application');
      $query->condition('application_id.value', $id);

      $nids = $query->accessCheck()->execute();

      if (isset($nids) && !empty($nids)) {
        $nid = array_shift($nids);
        $this->deleteNode($nid, $event);
        \Drupal::messenger()->addMessage(t('Deleted application @app', ['@app' => $id]));
        $returnValue = TRUE;
      }
      else {
        \Drupal::messenger()->addWarning(t('DeleteApplication could not find application @app', ['@app' => $id]));
        $returnValue = FALSE;
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $returnValue;
  }

  /**
   * Delete an application by Application URL
   *
   * @param null $url
   * @param $event
   *
   * @return bool
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function deleteByUrl($url, $event): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $url);
    $returnValue = FALSE;
    if (isset($url)) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'application');
      $query->condition('apic_url.value', $url);

      $nids = $query->accessCheck()->execute();

      if (isset($nids) && !empty($nids)) {
        $nid = array_shift($nids);
        $this->deleteNode($nid, $event);
        \Drupal::messenger()->addMessage(t('Deleted application @app', ['@app' => $url]));
        $returnValue = TRUE;
      }
      else {
        \Drupal::messenger()->addWarning(t('DeleteApplication could not find application @app', ['@app' => $url]));
        $returnValue = FALSE;
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $returnValue;
  }

  /**
   * @param $name
   *
   * @return string - application icon for a given name
   *
   * @return string
   */
  public function getRandomImageName($name): string {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $name);
    $asInt = 0;
    $strLength = mb_strlen($name);
    for ($i = 0; $i < $strLength; $i++) {
      $asInt += ord($name[$i]);
    }
    $digit = $asInt % 19;
    if ($digit === 0) {
      $digit = 1;
    }
    $num = str_pad($digit, 2, 0, STR_PAD_LEFT);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $num);
    return 'app_' . $num . '.png';
  }

  /**
   * @param $name
   *
   * @return string - path to placeholder image for a given name
   *
   * @return string
   */
  public function getPlaceholderImage($name): string {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $name);
    $placeholderImage = Url::fromUri('internal:/' . \Drupal::service('extension.list.module')->getPath('apic_app') . '/images/' . $this->getRandomImageName($name))
      ->toString();
    $this->moduleHandler->alter('apic_app_modify_getplaceholderimage', $placeholderImage);
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $placeholderImage);
    return $placeholderImage;
  }

  /**
   * Get the URL to the image for an application node
   * Optional second parameter of name to allow for updating the app name, need the new image before the node has been
   * updated
   *
   * @param Node $node
   * @param null $name
   *
   * @return string
   */
  public function getImageForApp(Node $node, $name = NULL): string {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, ['id' => $node->id(), 'name' => $name]);
    $fid = $node->application_image->getValue();
    $config = \Drupal::config('ibm_apim.settings');
    if (isset($fid[0]['target_id'])) {
      $file = File::load($fid[0]['target_id']);

      if (isset($file)) {
        $returnValue = $file->createFileUrl();
      }
    }
    if (!isset($returnValue) && (boolean) $config->get('show_placeholder_images')) {
      if (!isset($name) || empty($name)) {
        $name = $node->getTitle();
      }
      $rawImage = $this->getRandomImageName($name);
      $appImage = base_path() . \Drupal::service('extension.list.module')->getPath('apic_app') . '/images/' . $rawImage;
      $this->moduleHandler->alter('apic_app_modify_getimageforapp', $appImage);
    }
    else {
      $appImage = '';
    }

    // apim expects fully qualified urls to image files
    if (strpos($appImage, 'https://') !== 0) {
      $appImage = $_SERVER['HTTP_HOST'] . $appImage;
      if ($_SERVER['HTTPS'] === 'on') {
        $appImage = 'https://' . $appImage;
      }
      else {
        $appImage = 'http://' . $appImage;
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $appImage);
    return $appImage;
  }

  /**
   * Returns a list of node ids for the applications the current user can access
   *
   * @return array
   */
  public function listApplications(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $nids = [];
    // user has access to everything
    if ($this->userUtils->explicitUserAccess('edit any application content')) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'application');

      $results = $query->accessCheck()->execute();
    }
    elseif (isset($this->userUtils->getCurrentConsumerOrg()['url'])) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'application');
      $query->condition('application_consumer_org_url.value', $this->userUtils->getCurrentConsumerOrg()['url']);

      $results = $query->accessCheck()->execute();
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
  public function getIBMFields(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $ibmFields = [
      'apic_hostname',
      'apic_provider_id',
      'apic_catalog_id',
      'apic_summary',
      'apic_url',
      'apic_state',
      'application_image',
      'application_id',
      'application_consumer_org_url',
      'application_enabled',
      'application_redirect_endpoints',
      'application_data',
      'application_credentials_refs',
      'application_subscriptions',
      'application_subscription_refs',
      'application_client_type',
      'application_name',
      'application_lifecycle_state',
      'application_lifecycle_pending',
      'apic_created_at',
      'apic_updated_at',
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
  public function getCustomFields(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $coreFields = ['title', 'vid', 'status', 'nid', 'revision_log', 'created', 'url_redirects'];
    $components = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load('node.application.default')
      ->getComponents();
    $keys = array_keys($components);
    $ibmFields = $this->getIBMFields();
    $merged = array_merge($coreFields, $ibmFields);
    $diff = array_diff($keys, $merged);

    // make sure we only include actual custom fields so check there is a field config
    foreach ($diff as $key => $field) {
      $fieldConfig = FieldConfig::loadByName('node', 'application', $field);
      if ($fieldConfig === NULL) {
        unset($diff[$key]);
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $diff);
    return $diff;
  }

  /**
   * return sub array for a node
   *
   * @param Node $node
   *
   * @return array
   */
  public function getSubscriptions(Node $node): array {
    $subscriptions = $node->application_subscription_refs->referencedEntities();
    $subArray = [];
    $cost = '';
    $productImageUrl = '';

    if (isset($subscriptions) && is_array($subscriptions)) {
      $config = \Drupal::config('ibm_apim.settings');
      $ibmApimShowPlaceholderImages = (boolean) $config->get('show_placeholder_images');
      foreach ($subscriptions as $sub) {
        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'product');
        $query->condition('apic_url.value', $sub->product_url());
        $nids = $query->accessCheck()->execute();

        if (isset($nids) && !empty($nids)) {
          $nid = array_shift($nids);
          $product = Node::load($nid);
          if ($product !== NULL) {
            $fid = $product->apic_image->getValue();
            $productImageUrl = NULL;
            $cost = t('Free');
            if (isset($fid[0]['target_id'])) {
              $file = File::load($fid[0]['target_id']);
              if ($file !== NULL) {
                $productImageUrl = $file->createFileUrl();
              }
            }
            elseif ($ibmApimShowPlaceholderImages === TRUE && $this->moduleHandler->moduleExists('product')) {
              $rawImage = Product::getRandomImageName($product->getTitle());
              $productImageUrl = base_path() . \Drupal::service('extension.list.module')->getPath('product') . '/images/' . $rawImage;
            }
          }
          $supersedingProduct = NULL;
          $planTitle = NULL;
          if ($this->moduleHandler->moduleExists('product')) {
            $productPlans = [];
            foreach ($product->product_plans->getValue() as $arrayValue) {
              $productPlan = unserialize($arrayValue['value'], ['allowed_classes' => FALSE]);
              $productPlans[$productPlan['name']] = $productPlan;
            }
            if (isset($productPlans[$sub->plan()])) {
              $thisPlan = $productPlans[$sub->plan()];
              if (!isset($thisPlan['billing-model'])) {
                $thisPlan['billing-model'] = [];
              }
              $cost = $this->productPlan->parseBilling($thisPlan['billing-model']);
              $planTitle = $productPlans[$sub->plan()]['title'];

              if (isset($thisPlan['superseded-by'])) {
                $supersededByProductUrl = $thisPlan['superseded-by']['product_url'];
                $supersededByPlan = $thisPlan['superseded-by']['plan'];
                // dont display a link for superseded-by targets that are what we're already subscribed to
                // apim shouldn't really allow that, but it does, so try to handle it best we can
                if ($supersededByProductUrl !== $sub->product_url() || $supersededByPlan !== $sub->plan()) {
                  $supersededByRef = $this->utils->base64_url_encode($supersededByProductUrl . ':' . $supersededByPlan);
                  $supersededByProductTitle = NULL;
                  $supersededByProductVersion = NULL;
                  $supersededByPlanTitle = NULL;

                  $query = \Drupal::entityQuery('node');
                  $query->condition('type', 'product');
                  $query->condition('status', 1);
                  $query->condition('apic_url.value', $supersededByProductUrl);
                  $results = $query->accessCheck()->execute();

                  if (isset($results) && !empty($results)) {
                    $nid = array_shift($results);
                    $supersededByProduct = Node::load($nid);
                    if ($supersededByProduct !== NULL) {
                      $productYaml = yaml_parse($supersededByProduct->product_data->value);
                      $supersededByProductTitle = $productYaml['info']['title'];
                      $supersededByProductVersion = $productYaml['info']['version'];
                      foreach ($supersededByProduct->product_plans->getValue() as $arrayValue) {
                        $supersededByProductPlan = unserialize($arrayValue['value'], ['allowed_classes' => FALSE]);
                        if ($supersededByProductPlan['name'] === $supersededByPlan) {
                          $supersededByPlanTitle = $supersededByProductPlan['title'];
                          break;
                        }
                      }
                    }
                  }
                  if (!isset($supersededByPlanTitle) || empty($supersededByPlanTitle)) {
                    $supersededByPlanTitle = $supersededByPlan;
                  }

                  $supersedingProduct = [
                    'product_ref' => $supersededByRef,
                    'plan' => $supersededByPlan,
                    'plan_title' => $supersededByPlanTitle,
                    'product_title' => $supersededByProductTitle,
                    'product_version' => $supersededByProductVersion,
                  ];
                }
              }
            }
          }
          if (!isset($planTitle) || empty($planTitle)) {
            $planTitle = $sub->plan();
          }
          if (!is_array($supersedingProduct) || in_array(NULL, $supersedingProduct, TRUE)) {
             $supersedingProduct = NULL;
          }

          $subArray[] = [
            'product_title' => $product->getTitle(),
            'product_version' => $product->apic_version->value,
            'product_nid' => $nid,
            'product_image' => $productImageUrl,
            'plan_name' => $sub->plan(),
            'plan_title' => $planTitle,
            'state' => $sub->state(),
            'subId' => $sub->uuid(),
            'cost' => $cost,
            'superseded_by_product' => $supersedingProduct,
          ];
        }
      }
    }
    return $subArray;
  }

  /**
   * Invalidate caches for the current consumer org
   * Used to ensure the application list is correct when new apps are added etc
   */
  public function invalidateCaches(): void {
    $currentUser = \Drupal::currentUser();
    if (!$currentUser->isAnonymous() && (int) $currentUser->id() !== 1) {
      try {
        $org = $this->userUtils->getCurrentConsumerOrg();
        $tags = ['consumerorg:' . Html::cleanCssIdentifier($org['url'])];
        Cache::invalidateTags($tags);
      } catch (TempStoreException | \JsonException $e) {
      }
    }
  }

  /**
   * the apic_app_create hook can be fired via the ui code or webhooks, because of the timing windows we
   * need to ensure consistent contents.
   *
   * @param $app
   * @param \Drupal\node\NodeInterface $node
   */
  private function invokeAppCreateHook($app, NodeInterface $node): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $app['id']);

    // ensure we have the client_id & client_secret at the root of the app object so we are consistent regardless
    // of where we get the data from.
    if (!isset($app['client_id']) && isset($app['app_credentials'][0]['client_id'])) {
      $app['client_id'] = $app['app_credentials'][0]['client_id'];
    }
    if (!isset($app['client_secret']) && isset($app['app_credentials'][0]['client_secret'])) {
      $app['client_secret'] = $app['app_credentials'][0]['client_secret'];
    }
    // Calling all modules implementing 'hook_apic_app_create':
    $this->moduleHandler->invokeAll('apic_app_create', [$node, $app]);
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Build an array of useful data from an application node.
   *
   * @param NodeInterface $node
   *
   * @return array
   */
  private function createAppHookData(NodeInterface $node): array {

    $data = [];
    $data['nid'] = $node->id();

    if ($node->hasField('application_id') && !$node->get('application_id')->isEmpty()) {
      $data['id'] = $node->get('application_id')->value;
    }

    if ($node->hasField('application_name') && !$node->get('application_name')->isEmpty()) {
      $data['name'] = $node->get('application_name')->value;
    }

    if ($node->hasField('apic_url') && !$node->get('apic_url')->isEmpty()) {
      $data['url'] = $node->get('apic_url')->value;
    }

    if ($node->hasField('application_consumer_org_url') && !$node->get('application_consumer_org_url')->isEmpty()) {
      $data['consumerorg_url'] = $node->get('application_consumer_org_url')->value;
    }

    if ($node->hasField('application_credentials_refs') && !$node->get('application_credentials_refs')->isEmpty()) {
      $data['application_credentials_refs'] = [];
      foreach ($node->get('application_credentials_refs') as $ref) {
        $data['application_credentials_refs'][] = $ref->target_id;
      }
    }

    return $data;

  }

  /**
   * Returns a JSON representation of an application
   *
   * @param string $url
   *
   * @return string (JSON)
   */
  public function getApplicationAsJson(string $url): string {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, ['url' => $url]);
    $output = NULL;
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'application');
    $query->condition('apic_url.value', $url);

    $nids = $query->accessCheck()->execute();

    if (isset($nids) && !empty($nids)) {
      $nid = array_shift($nids);
      $node = Node::load($nid);
      if ($this->moduleHandler->moduleExists('serialization')) {
        $output = $this->serializer->serialize($node, 'json', ['plugin_id' => 'entity']);
      }
      else {
        \Drupal::logger('apic_app')->notice('getApplicationAsJson: serialization module not enabled', []);
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $output;
  }

  /**
   * Returns an array representation of an application for returning to drush
   *
   * @param string $url
   *
   * @return array
   */
  public function getApplicationForDrush(string $url): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, ['url' => $url]);
    $output = NULL;
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'application');
    $query->condition('apic_url.value', $url);

    $nids = $query->accessCheck()->execute();

    if ($nids !== NULL && !empty($nids)) {
      $nid = array_shift($nids);
      $node = Node::load($nid);
      if ($node !== NULL) {
        $output['url'] = $url;
        $output['id'] = $node->application_id->value;
        $output['name'] = $node->application_name->value;
        $output['title'] = $node->getTitle();
        $output['state'] = $node->apic_state->value;
        $output['summary'] = $node->apic_summary->value;
        $output['consumer_org_url'] = $node->application_consumer_org_url->value;
        $redirect_endpoints = [];
        foreach ($node->application_redirect_endpoints->getValue() as $redirect_endpoint) {
          if ($redirect_endpoint['value'] !== NULL) {
            $redirect_endpoints[] = $redirect_endpoint['value'];
          }
        }
        $output['redirect_endpoints'] = $redirect_endpoints;
        $output['lifecycle_state'] = $node->application_lifecycle_state->value;
        $output['lifecycle_pending'] = $node->application_lifecycle_pending->value;
        $output['created_at'] = $node->apic_created_at->value;
        $output['updated_at'] = $node->apic_updated_at->value;
        $subs = [];
        $subscriptions = $node->application_subscription_refs->referencedEntities();
        if (isset($subscriptions) && is_array($subscriptions)) {
          foreach ($subscriptions as $sub) {
            $subs[] = [
              'id' => $sub->uuid(),
              'product_url' => $sub->product_url(),
              'plan' => $sub->plan(),
              'state' => $sub->state(),
              'billing_url' => $sub->billing_url(),
              'created_at' => $sub->created_at(),
              'updated_at' => $sub->updated_at(),
            ];
          }
        }
        $output['subscriptions'] = $subs;
        $creds = [];
        $credentials = $node->application_credentials_refs->referencedEntities();
        if (isset($credentials) && is_array($credentials)) {
          foreach ($credentials as $cred) {
            $creds[] = [
              'id' => $cred->uuid(),
              'cred_url' => $cred->cred_url(),
              'title' => $cred->title(),
              'name' => $cred->name(),
              'summary' => $cred->summary(),
              'client_id' => $cred->client_id(),
              'created_at' => $cred->created_at(),
              'updated_at' => $cred->updated_at(),
            ];
          }
        }
        $output['credentials'] = $creds;
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $output;
  }

   /**
   * Given an application check if it is subscribed to the given product and plan
   *
   * @param Node $applicatoin
   * @param string $productNid
   * @param string $planName
   *
   * @return bool
   */
  public function isApplicationSubscribed(Node $application, string $productNid, string $planName): bool {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $productNid, $planName);
    }
    $returnValue = FALSE;
    $product = $this->entityTypeManager->getStorage('node')->load($productNid);
    if ($product !== NULL) {
      $query = $this->entityTypeManager->getStorage('apic_app_application_subs')->getQuery();
      $query->condition('product_url', $product->apic_url->value);
      $query->condition('app_url', $application->apic_url->value);
      $query->condition('plan', $planName);
      $entityIds = $query->accessCheck()->execute();
      if (isset($entityIds) && !empty($entityIds)) {
        $returnValue = TRUE;
      }
    }
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    }
    return $returnValue;
  }
}
