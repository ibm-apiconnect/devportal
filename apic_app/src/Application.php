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

namespace Drupal\apic_app;

use Drupal\apic_app\Entity\ApplicationCredentials;
use Drupal\apic_app\Entity\ApplicationSubscription;
use Drupal\apic_app\Event\ApplicationCreateEvent;
use Drupal\apic_app\Event\ApplicationDeleteEvent;
use Drupal\apic_app\Event\ApplicationUpdateEvent;
use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Class to work with the Application content type, takes input from the JSON returned by
 * IBM API Connect
 */
class Application {

  /**
   * @param $app
   * @param string $event
   * @param null $formState
   *
   * @return int|string|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function create($app, $event = 'publish', $formState = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $moduleHandler = \Drupal::service('module_handler');

    $node = Node::create([
      'type' => 'application',
    ]);

    // get the update method to do the update for us
    $node = self::update($node, $app, 'internal', $formState);
    if (isset($node)) {

      self::invokeAppCreateHook($app, $node);

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
   */
  public static function update(NodeInterface $node, $app, $event = 'content_refresh', $formState = NULL): ?NodeInterface {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $returnValue = NULL;

    // store a copy of the app we receive in case we need to send a complete set of data in hooks
    $create_hook_app = $app;
    if (isset($node)) {
      // Don't store client_secret!
      if (isset($app['client_secret'])) {
        unset($app['client_secret']);
      }
      $utils = \Drupal::service('ibm_apim.utils');
      $apimUtils = \Drupal::service('ibm_apim.apim_utils');
      $siteConfig = \Drupal::service('ibm_apim.site_config');
      $hostVariable = $siteConfig->getApimHost();

      if (isset($app['title'])) {
        $node->setTitle($utils->truncate_string($app['title']));
      }
      elseif (isset($app['name'])) {
        $node->setTitle($utils->truncate_string($app['name']));
      }
      else {
        $node->setTitle('No name');
      }
      $node->setPromoted(NodeInterface::NOT_PROMOTED);
      $node->set('apic_hostname', $hostVariable);
      $node->set('apic_provider_id', $siteConfig->getOrgId());
      $node->set('apic_catalog_id', $siteConfig->getEnvId());
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
      $node->set('apic_summary', $app['summary']);
      if (isset($app['consumer_org_url'])) {
        $app['consumer_org_url'] = $apimUtils->removeFullyQualifiedUrl($app['consumer_org_url']);
        $node->set('application_consumer_org_url', $app['consumer_org_url']);
        $appOrgUrl = $app['consumer_org_url'];
      }
      elseif (isset($app['org_url'])) {
        $app['org_url'] = $apimUtils->removeFullyQualifiedUrl($app['org_url']);
        $node->set('application_consumer_org_url', $app['org_url']);
        $appOrgUrl = $app['org_url'];
      } else {
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
      $node->set('apic_url', $apimUtils->removeFullyQualifiedUrl($app['url']));
      if (!isset($app['state']) || (strtolower($app['state']) !== 'enabled' && strtolower($app['state']) !== 'disabled')) {
        $app['state'] = 'enabled';
      }
      $node->set('apic_state', strtolower($app['state']));
      if (!isset($app['lifecycle_state']) || (strtoupper($app['lifecycle_state']) !== 'DEVELOPMENT' && strtoupper($app['lifecycle_state']) !== 'PRODUCTION')) {
        $app['lifecycle_state'] = 'PRODUCTION';
      }
      $node->set('application_lifecycle_state', strtoupper($app['lifecycle_state']));
      if (isset($app['lifecycle_pending'])) {
        $node->set('application_lifecycle_pending', $app['lifecycle_pending']);
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
            $cred['url'] = $apimUtils->removeFullyQualifiedUrl($cred['url']);
          } else {
            $cred['url'] = $app['url'] . '/credentials/' . $cred['id'];
          }
          if (!isset($cred['name'])) {
            $cred['name'] = '';
          }
          if (!isset($cred['title'])) {
            $cred['title'] = '';
          }
          $creds[] = [
            'id' => $cred['id'],
            'client_id' => $cred['client_id'],
            'name' => $cred['name'],
            'title' => $cred['title'],
            'app_url' => $app['url'],
            'summary' => $cred['summary'],
            'consumerorg_url' => $appOrgUrl,
            'url' => $cred['url'],
          ];
        }
      }
      elseif (isset($app['client_id'], $app['app_credential_urls']) && sizeof($app['app_credential_urls']) === 1) {
        $creds = [];
        // If this is app create we will have client_id but no app_credentials array - fudge
        $cred_url = $apimUtils->removeFullyQualifiedUrl($app['app_credential_urls'][0]);
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
        ];
      }
      $node->save();
      $credsService = \Drupal::service('apic_app.credentials');
      $node = $credsService->createOrUpdateCredentialsList($node, $creds);

      $node->set('application_data', serialize($app));

      if ($formState !== NULL && !empty($formState) && $node !== NULL) {
        $customFields = self::getCustomFields();
        $customFieldValues = \Drupal::service('ibm_apim.user_utils')->handleFormCustomFields($customFields, $formState);
        foreach ($customFieldValues as $customField => $value) {
          $node->set($customField, $value);
        }
      } elseif (!empty($app['metadata'])) {
        $customFields = self::getCustomFields();
        foreach ($customFields as $customField) {
          if (isset($app['metadata'][$customField])) {
            $value = json_decode($app['metadata'][$customField], true);
            $node->set($customField, $value);
          }
        }
      }

      // ensure this application links to all its subscriptions
      $query = \Drupal::entityQuery('apic_app_application_subs');
      $query->condition('app_url', $app['url']);
      $entityIds = $query->execute();
      $newArray = [];
      if (isset($entityIds) && !empty($entityIds)) {
        foreach ($entityIds as $entityId) {
          $newArray[] = ['target_id' => $entityId];
        }
      }
      $node->set('application_subscription_refs', $newArray);

      $node->save();
      if ($node !== NULL && $event !== 'internal') {
        $moduleHandler = \Drupal::service('module_handler');
        // we have support for calling create hook here as well because of timing issues with webhooks coming in and sending us down
        // the update path in createOrUpdate even when the initial user action was create
        if ($event === 'create') {
          \Drupal::logger('apic_app')->notice('Application @app created (update path)', ['@app' => $node->getTitle()]);

          self::invokeAppCreateHook($create_hook_app, $node);
        }
        else {
          \Drupal::logger('apic_app')->notice('Application @app updated', ['@app' => $node->getTitle()]);

          // Calling all modules implementing 'hook_apic_app_update':
          $moduleHandler->invokeAll('apic_app_update', [$node, $app]);

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
  public static function createOrUpdate($app, $event, $formState = NULL): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'application');
    $query->condition('application_id.value', $app['id']);

    $nids = $query->execute();

    if (isset($nids) && !empty($nids)) {
      $nid = array_shift($nids);
      $node = Node::load($nid);
      if ($node !== NULL) {
        self::update($node, $app, $event, $formState);
        $createdOrUpdated = FALSE;
      }
      else {
        // no existing node for this App so create one
        self::create($app, $event, $formState);
        $createdOrUpdated = TRUE;
      }
    }
    else {
      // no existing node for this App so create one
      self::create($app, $event, $formState);
      $createdOrUpdated = TRUE;
    }
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
  public static function createOrUpdateReturnNid($app, $event, $formState): ?string {
    $apimUtils = \Drupal::service('ibm_apim.apim_utils');
    $nid = NULL;

    if (isset($app['url'])) {
      $app['url'] = $apimUtils->removeFullyQualifiedUrl($app['url']);
    }
    if (isset($app['org_url'])) {
      $app['org_url'] = $apimUtils->removeFullyQualifiedUrl($app['org_url']);
    }
    if (isset($app['app_credential_urls'])) {
      foreach ($app['app_credential_urls'] as $key => $url){
        $app['app_credential_urls'][$key] = $apimUtils->removeFullyQualifiedUrl($url);
      }
    }

    self::createOrUpdate($app, $event, $formState);
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'application');
    $query->condition('application_id.value', $app['id']);
    $nids = $query->execute();

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
  public static function deleteNode(int $nid, string $event = 'internal'): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $nid);

    $node = Node::load($nid);
    if ($node !== NULL) {

      $moduleHandler = \Drupal::service('module_handler');

      // Calling all modules implementing 'hook_apic_app_pre_delete':
      $hookData = self::createAppHookData($node);
      $moduleHandler->invokeAll('apic_app_pre_delete', [
          'node' => $node,
          'data' => $hookData,
        ]);
      // TODO: invoke pre delete rule here

      // Delete all subscription entities for this application
      $query = \Drupal::entityQuery('apic_app_application_subs');
      $query->condition('app_url.value', $node->apic_url->value);

      $entityIds = $query->execute();
      if (isset($entityIds) && !empty($entityIds)) {
        $subEntities = ApplicationSubscription::loadMultiple($entityIds);
        foreach ($subEntities as $subEntity) {
          $subEntity->delete();
        }
      }
      // Delete all credentials entities for this application
      $query = \Drupal::entityQuery('apic_app_application_creds');
      $query->condition('app_url.value', $node->apic_url->value);

      $entityIds = $query->execute();
      if (isset($entityIds) && !empty($entityIds)) {
        $subEntities = ApplicationCredentials::loadMultiple($entityIds);
        foreach ($subEntities as $subEntity) {
          $subEntity->delete();
        }
      }

      $node->delete();

      // Calling all modules implementing 'hook_apic_app_post_delete':
      $moduleHandler->invokeAll('apic_app_post_delete', [
          'data' => $hookData
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
  public static function deleteById($id = NULL, $event): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $id);
    $returnValue = FALSE;
    if (isset($id)) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'application');
      $query->condition('application_id.value', $id);

      $nids = $query->execute();

      if (isset($nids) && !empty($nids)) {
        $nid = array_shift($nids);
        self::deleteNode($nid, $event);
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
  public static function deleteByUrl($url = NULL, $event): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $url);
    $returnValue = FALSE;
    if (isset($url)) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'application');
      $query->condition('apic_url.value', $url);

      $nids = $query->execute();

      if (isset($nids) && !empty($nids)) {
        $nid = array_shift($nids);
        self::deleteNode($nid, $event);
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
   * A function to retrieve the details for a specified application from the public portal API
   * This basically maps what we get from the portal api over to what we expect from the content_refresh or webhook apis
   *
   * @param $appUrl
   *
   * @return array|null|string
   */
  public static function fetchFromAPIC($appUrl = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $appUrl);
    $returnApp = NULL;
    if ($appUrl === 'new') {
      return '';
    }
    $userUtils = \Drupal::service('ibm_apim.user_utils');
    $org = $userUtils->getCurrentConsumerOrg();
    $consumerOrg = $org['url'];

    if (!isset($consumerOrg)) {
      \Drupal::messenger()->addError('Consumer organization not set.');
      return NULL;
    }

    $result = \Drupal::service('apic_app.rest_service')->getApplicationDetails($appUrl);

    if (isset($result, $result->data) && !isset($result->data['errors'])) {
      $returnApp = $result->data;
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnApp);
    return $returnApp;
  }

  /**
   * @param $name
   *
   * @return string - application icon for a given name
   *
   * @return string
   */
  public static function getRandomImageName($name): string {
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
  public static function getPlaceholderImage($name): string {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $name);
    $placeholderImage = Url::fromUri('internal:/' . drupal_get_path('module', 'apic_app') . '/images/' . self::getRandomImageName($name))
      ->toString();
    \Drupal::moduleHandler()->alter('apic_app_modify_getplaceholderimage', $placeholderImage);
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
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public static function getImageForApp(Node $node, $name = NULL): string {
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
      $rawImage = self::getRandomImageName($name);
      $appImage = base_path() . drupal_get_path('module', 'apic_app') . '/images/' . $rawImage;
      \Drupal::moduleHandler()->alter('apic_app_modify_getimageforapp', $appImage);
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
  public static function listApplications(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $nids = [];
    // user has access to everything
    $userUtils = \Drupal::service('ibm_apim.user_utils');
    if ($userUtils->explicitUserAccess('edit any application content')) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'application');

      $results = $query->execute();
    }
    elseif (isset($userUtils->getCurrentConsumerOrg()['url'])) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'application');
      $query->condition('application_consumer_org_url.value', $userUtils->getCurrentConsumerOrg()['url']);

      $results = $query->execute();
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
  public static function getIBMFields(): array {
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
      ->load('node.application.default')
      ->getComponents();
    $keys = array_keys($components);
    $ibmFields = self::getIBMFields();
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
   * @throws \Drupal\Core\Entity\EntityMalformedException
   */
  public static function getSubscriptions(Node $node): array {
    $subscriptions = $node->application_subscription_refs->referencedEntities();
    $subArray = [];
    $cost = '';
    $productImageUrl = '';

    $moduleHandler = \Drupal::service('module_handler');
    if (isset($subscriptions) && is_array($subscriptions)) {
      $config = \Drupal::config('ibm_apim.settings');
      $ibmApimShowPlaceholderImages = (boolean) $config->get('show_placeholder_images');
      foreach ($subscriptions as $sub) {
        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'product');
        $query->condition('apic_url.value', $sub->product_url());
        $nids = $query->execute();

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
            elseif ($ibmApimShowPlaceholderImages === TRUE && $moduleHandler->moduleExists('product')) {
              $rawImage = \Drupal\product\Product::getRandomImageName($product->getTitle());
              $productImageUrl = base_path() . drupal_get_path('module', 'product') . '/images/' . $rawImage;
            }
          }
          $supersedingProduct = NULL;
          $planTitle = NULL;
          $planService = \Drupal::service('product.plan');
          if ($moduleHandler->moduleExists('product')) {
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
              $cost = $planService->parseBilling($thisPlan['billing-model']);
              $planTitle = $productPlans[$sub->plan()]['title'];
            }
            if (isset($productPlans[$sub->plan()]['superseded-by'])) {
              $supersededByProductUrl = $productPlans[$sub->plan()]['superseded-by']['product_url'];
              $supersededByPlan = $productPlans[$sub->plan()]['superseded-by']['plan'];
              // dont display a link for superseded-by targets that are what we're already subscribed to
              // apim shouldn't really allow that, but it does, so try to handle it best we can
              if ($supersededByProductUrl !== $sub->product_url() || $supersededByPlan !== $sub->plan() ) {
                $utils = \Drupal::service('ibm_apim.utils');
                $supersededByRef = $utils->base64_url_encode($supersededByProductUrl . ':' . $supersededByPlan);
                $supersededByTitle = NULL;
                $supersededByVersion = NULL;

                $query = \Drupal::entityQuery('node');
                $query->condition('type', 'product');
                $query->condition('status', 1);
                $query->condition('apic_url.value', $supersededByProductUrl);
                $results = $query->execute();
                $fullPlanTitle = NULL;
                if (isset($results) && !empty($results)) {
                  $nid = array_shift($results);
                  $fullProduct = Node::load($nid);
                  if ($fullProduct !== NULL) {
                    $productYaml = yaml_parse($fullProduct->product_data->value);
                    $supersededByTitle = $productYaml['info']['title'];
                    $supersededByVersion = $productYaml['info']['version'];
                    $fullProductPlans = [];
                    $fullProductPlan = '';
                    if ($fullProduct !== NULL) {
                      foreach ($fullProduct->product_plans->getValue() as $arrayValue) {
                        $fullProductPlan = unserialize($arrayValue['value'], ['allowed_classes' => FALSE]);
                        $fullProductPlans[$fullProductPlan['name']] = $fullProductPlan;
                      }
                    }
                    if (isset($fullProductPlans[$fullProductPlan])) {
                      $fullPlanTitle = $fullProductPlans[$fullProductPlan]['title'];
                    }
                  }
                }
                if (!isset($fullPlanTitle) || empty($fullPlanTitle)) {
                  $fullPlanTitle = Html::escape($supersededByPlan);
                }

                $supersedingProduct = [
                  'product_ref' => $supersededByRef,
                  'plan' => $supersededByPlan,
                  'plan_title' => $fullPlanTitle,
                  'product_title' => $supersededByTitle,
                  'product_version' => $supersededByVersion,
                ];
              }
            }
          }
          if (!isset($planTitle) || empty($planTitle)) {
            $planTitle = Html::escape($sub->plan());
          }
          $subArray[] = [
            'product_title' => Html::escape($product->getTitle()),
            'product_version' => Html::escape($product->apic_version->value),
            'product_nid' => $nid,
            'product_image' => $productImageUrl,
            'plan_name' => Html::escape($sub->plan()),
            'plan_title' => Html::escape($planTitle),
            'state' => Html::escape($sub->state()),
            'subId' => Html::escape($sub->id()),
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
  public static function invalidateCaches(): void {
    $currentUser = \Drupal::currentUser();
    if (!$currentUser->isAnonymous() && (int) $currentUser->id() !== 1) {
      $userUtils = \Drupal::service('ibm_apim.user_utils');
      $org = $userUtils->getCurrentConsumerOrg();
      $tags = ['consumerorg:' . Html::cleanCssIdentifier($org['url'])];
      Cache::invalidateTags($tags);
    }
  }

  /**
   * the apic_app_create hook can be fired via the ui code or webhooks, because of the timing windows we
   * need to ensure consistent contents.
   *
   * @param $app
   * @param \Drupal\node\NodeInterface $node
   */
  private static function invokeAppCreateHook($app, NodeInterface $node): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $app['id']);
    $moduleHandler = \Drupal::service('module_handler');

    // ensure we have the client_id & client_secret at the root of the app object so we are consistent regardless
    // of where we get the data from.
    if (!isset($app['client_id']) && isset($app['app_credentials'][0]['client_id'])) {
      $app['client_id'] = $app['app_credentials'][0]['client_id'];
    }
    if (!isset($app['client_secret']) && isset($app['app_credentials'][0]['client_secret'])) {
      $app['client_secret'] = $app['app_credentials'][0]['client_secret'];
    }
    // Calling all modules implementing 'hook_apic_app_create':
    $moduleHandler->invokeAll('apic_app_create', [$node, $app]);
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Build an array of useful data from an application node.
   *
   * @param NodeInterface $node
   *
   * @return array
   */
  private static function createAppHookData(NodeInterface $node): array {

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
      foreach ($node->get('application_credentials_refs') as  $ref) {
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
  public function getApplicationAsJson($url): string {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, ['url' => $url]);
    $output = NULL;
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'application');
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
        \Drupal::logger('apic_app')->notice('getApplicationAsJson: serialization module not enabled', []);
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $output;
  }
}
