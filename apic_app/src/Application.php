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

namespace Drupal\apic_app;

use Drupal\apic_app\Event\ApplicationCreateEvent;
use Drupal\apic_app\Event\ApplicationDeleteEvent;
use Drupal\apic_app\Event\ApplicationUpdateEvent;
use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Class to work with the Application content type, takes input from the JSON returned by
 * IBM API Connect
 */
class Application {

  /**
   * Create a new Application
   *
   * @param $app
   * @param string $event
   *
   * @return int|null|string
   */
  public static function create($app, $event = 'publish') {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $moduleHandler = \Drupal::service('module_handler');

    $node = Node::create([
      'type' => 'application',
    ]);

    // get the update method to do the update for us
    $node = Application::update($node, $app, 'internal');
    if (isset($node)) {
      // Calling all modules implementing 'hook_apic_app_create':
      $moduleHandler->invokeAll('apic_app_create', [$node, $app]);

      \Drupal::logger('apic_app')->notice('Application @app created', ['@app' => $node->getTitle()]);
      if ($moduleHandler->moduleExists('rules')) {
        // Set the args twice on the event: as the main subject but also in the
        // list of arguments.
        $event = new ApplicationCreateEvent($node, ['application' => $node]);
        $event_dispatcher = \Drupal::service('event_dispatcher');
        $event_dispatcher->dispatch(ApplicationCreateEvent::EVENT_NAME, $event);
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $node->id();
  }

  /**
   * Update an existing Application
   *
   * @param $node
   * @param $app
   * @param string $event
   *
   * @return NodeInterface|null
   */
  public static function update(NodeInterface $node, $app, $event = 'content_refresh') {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    if (isset($node)) {
      $utils = \Drupal::service('ibm_apim.utils');
      $apim_utils = \Drupal::service('ibm_apim.apim_utils');
      $siteconfig = \Drupal::service('ibm_apim.site_config');
      $hostvariable = $siteconfig->getApimHost();

      if (isset($app['title'])) {
        $node->setTitle($utils->truncate_string($app['title']));
      }
      elseif (isset($app['name'])) {
        $node->setTitle($utils->truncate_string($app['name']));
      }
      else {
        $node->setTitle('No name');
      }
      $node->setPromoted(NODE_NOT_PROMOTED);
      $node->set("apic_hostname", $hostvariable);
      $node->set("apic_provider_id", $siteconfig->getOrgId());
      $node->set("apic_catalog_id", $siteconfig->getEnvId());
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
        $path_url = $apim_utils->removeFullyQualifiedUrl($app['consumer_org_url']);
        $node->set('application_consumer_org_url', $path_url);
      } elseif (isset($app['org_url'])) {
        $path_url = $apim_utils->removeFullyQualifiedUrl($app['org_url']);
        $node->set('application_consumer_org_url', $path_url);
      }

      $converted_enabled = ($app['state'] === "enabled") ? 'true' : 'false';
      $node->set('application_enabled', $converted_enabled);
      $endpoints = array();
      if (isset($app['redirect_endpoints'])) {
        foreach ($app['redirect_endpoints'] as $redirectUrl) {
          $endpoints = $redirectUrl;
        }
      }
      $node->set('application_redirect_endpoints', $endpoints);
      $node->set('apic_url', $app['url']);
      if (!isset($app['state']) || (strtolower($app['state']) != 'enabled' && strtolower($app['state']) != 'disabled')) {
        $app['state'] = 'enabled';
      }
      $node->set('apic_state', strtolower($app['state']));
      if (!isset($app['lifecycle_state']) || (strtoupper($app['lifecycle_state']) != 'DEVELOPMENT' && strtoupper($app['lifecycle_state']) != 'PRODUCTION')) {
        $app['lifecycle_state'] = 'PRODUCTION';
      }
      $node->set('application_lifecycle_state', strtoupper($app['lifecycle_state']));
      if (isset($app['lifecycle_pending'])) {
        $node->set('application_lifecycle_pending', $app['lifecycle_pending']);
      }
      else {
        $node->set('application_lifecycle_pending', NULL);
      }
      if (isset($app['app_credentials']) && !empty($app['app_credentials'])) {
        $creds = array();
        // do not store client secrets
        foreach ($app['app_credentials'] as $key => $cred) {
          if (isset($cred['client_secret'])) {
            unset($cred['client_secret']);
            unset($app['app_credentials'][$key]['client_secret']);
          }
          if (!isset($cred['summary'])) {
            $cred['summary'] = '';
          }
          if (isset($cred['url'])) {
            $cred['url'] = $apim_utils->removeFullyQualifiedUrl($cred['url']);
          }
          $creds[] = serialize($cred);
        }
        $node->set('application_credentials', $creds);
      } elseif(isset($app['client_id']) && isset($app['app_credential_urls']) && sizeof($app['app_credential_urls']) == 1) {
        // If this is app create we will have client_id but no app_credentials array - fudge
        $cred_url = $apim_utils->removeFullyQualifiedUrl($app['app_credential_urls'][0]);
        $credential = array('client_id' => $app['client_id'], 'url' => $cred_url);
        $path = parse_url($credential['url'])['path'];
        $parts = explode("/", $path);
        $credential['id'] = array_pop($parts);

        $node->set('application_credentials', array(0 => serialize($credential)));
      }
      else {
        $node->set('application_credentials', array());
      }

      $node->set('application_data', serialize($app));
      $node->save();
      if (isset($node) && $event != 'internal') {
        \Drupal::logger('apic_app')->notice('Application @app updated', ['@app' => $node->getTitle()]);

        // Calling all modules implementing 'hook_apic_app_update':
        $moduleHandler = \Drupal::service('module_handler');
        $moduleHandler->invokeAll('apic_app_update', [$node, $app]);

        if ($moduleHandler->moduleExists('rules')) {
          // Set the args twice on the event: as the main subject but also in the
          // list of arguments.
          $event = new ApplicationUpdateEvent($node, ['application' => $node]);
          $event_dispatcher = \Drupal::service('event_dispatcher');
          $event_dispatcher->dispatch(ApplicationUpdateEvent::EVENT_NAME, $event);
        }
      }
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      return $node;
    }
    else {
      \Drupal::logger('apic_app')->error('Update application: no node provided.', []);
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      return NULL;
    }
  }

  /**
   * Create a new application if one doesnt already exist for that App reference
   * Update one if it does
   *
   * @param $app
   * @param $event
   *
   * @return bool
   */
  public static function createOrUpdate($app, $event) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'application');
    $query->condition('application_id.value', $app['id']);

    $nids = $query->execute();

    if (isset($nids) && !empty($nids)) {
      $nid = array_shift($nids);
      $node = Node::load($nid);
      $changedTime = $node->getChangedTime();
      if (!isset($app['timestamp']) || (isset($changedTime) && ($changedTime < $app['timestamp']))) {
        Application::update($node, $app, $event);
      }
      else {
        \Drupal::logger('apic_app')
          ->notice('Application::createOrUpdate - ETag not set skipping update for node id %nid.', ['%nid' => $node->id()]);
      }
      $createdOrUpdated = FALSE;
    }
    else {
      // no existing node for this App so create one
      Application::create($app, $event);
      $createdOrUpdated = TRUE;
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $createdOrUpdated;
  }

  /**
   * Delete an application by NID
   *
   * @param $nid
   * @param $event
   */
  public static function deleteNode($nid, $event) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $nid);

    $node = Node::load($nid);
    $app_title = $node->getTitle();
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('rules')) {
      // Set the args twice on the event: as the main subject but also in the
      // list of arguments.
      $event = new ApplicationDeleteEvent($node, ['application' => $node]);
      $event_dispatcher = \Drupal::service('event_dispatcher');
      $event_dispatcher->dispatch(ApplicationDeleteEvent::EVENT_NAME, $event);
    }
    $node->delete();
    \Drupal::logger('apic_app')->notice('Application @app deleted', ['@app' => $node->getTitle()]);
    unset($node);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Delete an application credential by id
   *
   * @param $appURL
   * @param $credId
   * @return bool
   */
  public static function deleteCredential($appURL, $credId) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $appURL);
    $returnValue = FALSE;

    if (isset($appURL) && isset($credId)) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'application');
      $query->condition('apic_url.value', $appURL);

      $nids = $query->execute();

      if (isset($nids) && !empty($nids)) {
        $nid = array_shift($nids);
        $node = Node::load($nid);
        $newcreds = [];
        if (!empty($node->application_credentials->getValue())) {
          foreach($node->application_credentials->getValue() as $arrayValue){
            $unserialized = unserialize($arrayValue['value']);
            if (!isset($unserialized['id']) || $unserialized['id'] != $credId) {
              $newcreds[] = serialize($unserialized);
            }
          }
          $node->set('application_credentials', $newcreds);
        }
        $node->save();

        \Drupal::logger('apic_app')->notice('Deleted credential from @app', ['@app' => $appURL]);
        $returnValue = TRUE;
      }
      else {
        \Drupal::logger('apic_app')->notice('DeleteCredential could not find application @app', ['@app' => $appURL]);
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    return $returnValue;
  }

  /**
   * Create or update an application credential
   *
   * @param $appURL
   * @param $cred
   * @return bool
   */
  public static function createOrUpdateCredential($appURL, $cred) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $appURL);
    $returnValue = FALSE;

    if (isset($appURL) && isset($cred)) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'application');
      $query->condition('apic_url.value', $appURL);

      $nids = $query->execute();

      if (isset($nids) && !empty($nids)) {
        $apim_utils = \Drupal::service('ibm_apim.apim_utils');
        $nid = array_shift($nids);
        $node = Node::load($nid);
        $newcreds = [];
        if (!empty($node->application_credentials->getValue())) {
          foreach($node->application_credentials->getValue() as $arrayValue){
            $unserialized = unserialize($arrayValue['value']);
            if (!isset($unserialized['id']) || $unserialized['id'] != $cred['id']) {
              $newcreds[] = serialize($unserialized);
            }
          }
        }
        if (isset($cred['client_secret'])) {
          unset($cred['client_secret']);
        }
        if (!isset($cred['summary'])) {
          $cred['summary'] = '';
        }
        if (!isset($cred['title'])) {
          $cred['title'] = $cred['id'];
        }
        if (isset($cred['url'])) {
          $cred['url'] = $apim_utils->removeFullyQualifiedUrl($cred['url']);
        }
        $newcreds[] = serialize($cred);
        $node->set('application_credentials', $newcreds);
        $node->save();

        \Drupal::logger('apic_app')->notice('Deleted credential from @app', ['@app' => $appURL]);
        $returnValue = TRUE;
      }
      else {
        \Drupal::logger('apic_app')->notice('DeleteCredential could not find application @app', ['@app' => $appURL]);
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    return $returnValue;
  }

  /**
   * Delete an application by Application ID
   *
   * @param $id
   * @param $event
   *
   * @return bool
   */
  public static function deleteById($id = NULL, $event) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $id);
    if (isset($id)) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'application');
      $query->condition('application_id.value', $id);

      $nids = $query->execute();

      if (isset($nids) && !empty($nids)) {
        $nid = array_shift($nids);
        Application::deleteNode($nid, $event);
        drupal_set_message(t('Deleted application @app', ['@app' => $id]), 'success');
        return TRUE;
      }
      else {
        drupal_set_message(t('DeleteApplication could not find application @app', ['@app' => $id]), 'warning');
        return FALSE;
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return FALSE;
  }

  /**
   * Delete an application by Application URL
   *
   * @param $url
   * @param $event
   *
   * @return bool
   */
  public static function deleteByUrl($url = NULL, $event) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $url);
    if (isset($id)) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'application');
      $query->condition('apic_url.value', $url);

      $nids = $query->execute();

      if (isset($nids) && !empty($nids)) {
        $nid = array_shift($nids);
        Application::deleteNode($nid, $event);
        drupal_set_message(t('Deleted application @app', ['@app' => $url]), 'success');
        return TRUE;
      }
      else {
        drupal_set_message(t('DeleteApplication could not find application @app', ['@app' => $url]), 'warning');
        return FALSE;
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return FALSE;
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
    if ($appUrl == 'new') {
      return '';
    }
    else {
      //$appUrl = Html::escape($appUrl);
    }
    $userUtils = \Drupal::service('ibm_apim.user_utils');
    $org = $userUtils->getCurrentConsumerOrg();
    $consumerOrg = $org['url'];

    $returnValue = NULL;
    if (!isset($consumerOrg)) {
      drupal_set_message("Consumer organization not set.", 'error');
      return NULL;
    }

    $result = \Drupal::service('apic_app.rest_service')->getApplicationDetails($appUrl);

    if (isset($result) && isset($result->data) && !isset($result->data['errors'])) {
      $app_data = $result->data;
    }
    if (isset($app_data)) {
      $returnValue = [];
      $returnValue['orgID'] = $app_data['orgID'];
      $returnValue['id'] = $app_data['id'];
      $returnValue['name'] = $app_data['name'];
      $returnValue['description'] = $app_data['description'];
      $returnValue['app_credentials'] = $app_data['app_credentials'];
      if (isset($app_data['imageURL'])) {
        $returnValue['imageURL'] = $app_data['imageURL'];
      }
      $returnValue['public'] = $app_data['public'];
      $returnValue['enabled'] = $app_data['enabled'];
      if (isset($app_data['updatedAt'])) {
        $returnValue['updatedAt'] = $app_data['updatedAt'];
      }
      $returnValue['consumer_org_url'] = $app_data['consumer_org_url'];
      if (!isset($returnValue['consumer_org_url'])) {
        $org = $userUtils->getCurrentConsumerOrg();
        if (isset($org['url'])) {
          $returnValue['consumer_org_url'] = $org['url'];
        }
      }
      $returnValue['state'] = $app_data['state'];
      $returnValue['type'] = $app_data['type'];
      $returnValue['promoteto'] = $app_data['promoteTo'];
      $returnValue['oauthRedirectURI'] = $app_data['oauthRedirectURI'];
      if (isset($app_data['certificate'])) {
        $returnValue['certificate'] = $app_data['certificate'];
      }
      else {
        $returnValue['certificate'] = '';
      }
      $returnValue['url'] = $app_data['url'];
    }
    $returnApp = $returnValue;

    $returnApp['subscriptions'] = [];
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnApp);
    return $returnApp;
  }

  /**
   * @return string - application icon for a given name
   *
   * @param $name
   *
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
    return "app_" . $num . ".png";
  }

  /**
   * @return string - path to placeholder image for a given name
   *
   * @param $name
   *
   * @return string
   */
  public static function getPlaceholderImage($name) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $name);
    $placeholderImage = Url::fromUri('internal:/' . drupal_get_path('module', 'apic_app') . '/images/' . Application::getRandomImageName($name))
      ->toString();
    \Drupal::moduleHandler()->alter('apic_app_getplaceholderimage', $placeholderImage);
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $placeholderImage);
    return $placeholderImage;
  }

  /**
   * Get the URL to the image for an application node
   * Optional second parameter of name to allow for updating the app name, need the new image before the node has been updated
   *
   * @param $node
   * @param $name
   * @return $this|\Drupal\Core\Url|string
   */
  public static function getImageForApp($node, $name = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, array('id' => $node->id(), 'name' => $name));
    $fid = $node->application_image->getValue();
    $config = \Drupal::config('ibm_apim.settings');
    if (isset($fid) && !empty($fid) && isset($fid[0]['target_id'])) {
      $file = File::load($fid[0]['target_id']);

      if(isset($file)) {
        $returnValue = $file->toUrl()->toUriString();
      }
    }
    if(!isset($returnValue) && $config->get('show_placeholder_images')) {
      if (!isset($name) || empty($name)) {
        $name = $node->getTitle();
      }
      $rawImage = Application::getRandomImageName($name);
      $appImage = base_path() . drupal_get_path('module', 'apic_app') . '/images/' . $rawImage;
      \Drupal::moduleHandler()->alter('apic_app_getimageforapp', $appImage);
    }
    else {
      $appImage = '';
    }

    // apim expects fully qualified urls to image files
    if(strpos($appImage, 'https://') !== 0) {
      $appImage = $_SERVER['HTTP_HOST'] . $appImage;
      if($_SERVER['HTTPS'] == 'on') {
        $appImage = "https://" . $appImage;
      } else {
        $appImage = "http://" . $appImage;
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
  public static function listApplications() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $nids = [];
    // user has access to everything
    $userUtils = \Drupal::service('ibm_apim.user_utils');
    if ($userUtils->explicitUserAccess('edit any application content')) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'application');

      $results = $query->execute();
    }
    else {
      if (isset($userUtils->getCurrentConsumerOrg()['url'])) {
        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'application');
        $query->condition('application_consumer_org_url.value', $userUtils->getCurrentConsumerOrg()['url']);

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
    $ibmfields = [
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
      'application_credentials',
      'application_subscriptions',
      'application_client_type',
      'application_name',
      'application_lifecycle_state',
      'application_lifecycle_pending',
    ];
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $ibmfields;
  }

  /**
   * Get a list of all the custom fields on this content type
   *
   * @return array
   */
  public static function getCustomFields() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $core_fields = ['title', 'vid', 'status', 'nid', 'revision_log', 'created'];
    $components = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load('node.application.default')
      ->getComponents();
    $keys = array_keys($components);
    $ibmfields = Application::getIBMFields();
    $merged = array_merge($core_fields, $ibmfields);
    $diff = array_diff($keys, $merged);
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $diff);
    return $diff;
  }

  /**
   * return sub array for a node
   *
   * @param $node
   * @return array
   */
  public static function getSubscriptions($node) {
    $subscriptions = array();
    foreach ($node->application_subscriptions->getValue() as $appSub) {
      $subscriptions[] = unserialize($appSub['value']);
    }
    $subarray = [];
    $moduleHandler = \Drupal::service('module_handler');
    if (isset($subscriptions) && is_array($subscriptions)) {
      $config = \Drupal::config('ibm_apim.settings');
      $ibm_apim_show_placeholder_images = $config->get('show_placeholder_images');
      foreach ($subscriptions as $sub) {
        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'product');
        $query->condition('apic_url.value', $sub['product_url']);
        $nids = $query->execute();

        if (isset($nids) && !empty($nids)) {
          $nid = array_shift($nids);
          $product = Node::load($nid);
          $fid = $product->apic_image->getValue();
          $product_image_url = NULL;
          $cost = t('Free');
          if (isset($fid) && !empty($fid) && isset($fid[0]['target_id'])) {
            $file = \Drupal\file\Entity\File::load($fid[0]['target_id']);
            $product_image_url = $file->toUrl()->toUriString();
          }
          else {
            if ($ibm_apim_show_placeholder_images && $moduleHandler->moduleExists('product')) {
              $rawImage = \Drupal\product\Product::getRandomImageName($product->getTitle());
              $product_image_url = base_path() . drupal_get_path('module', 'product') . '/images/' . $rawImage;
            }
          }
          $superseding_product = null;
          $plan_title = null;
          if ($moduleHandler->moduleExists('product')) {
            $productPlans = array();
            foreach ($product->product_plans->getValue() as $arrayValue) {
              $product_plan = unserialize($arrayValue['value']);
              $productPlans[$product_plan['name']] = $product_plan;
            }
            if (isset($productPlans[$sub['plan']])) {
              $thisPlan = $productPlans[$sub['plan']];
              if (!isset($thisPlan['billing-model'])) {
                $thisPlan['billing-model'] = [];
              }
              $cost = product_parse_billing($thisPlan['billing-model']);
              $plan_title = $productPlans[$sub['plan']]['title'];
            }
            if(isset($productPlans[$sub['plan']]['superseded-by'])) {
              $superseded_by_producturl = $productPlans[$sub['plan']]['superseded-by']['product_url'];
              $superseded_by_plan = $productPlans[$sub['plan']]['superseded-by']['plan'];
              $utils = \Drupal::service('ibm_apim.utils');
              $superseded_by_ref = $utils->base64_url_encode($superseded_by_producturl . ':' . $superseded_by_plan);
              $superseded_by_title = null;
              $superseded_by_version = null;

              $query = \Drupal::entityQuery('node');
              $query->condition('type', 'product');
              $query->condition('status', 1);
              $query->condition('apic_url.value', $superseded_by_producturl);
              $results = $query->execute();
              $full_plan_title = null;
              if (isset($results) && !empty($results)) {
                $nid = array_shift($results);
                $full_product = Node::load($nid);
                $product_yaml = yaml_parse($full_product->product_data->value);
                $superseded_by_title = $product_yaml['info']['title'];
                $superseded_by_version = $product_yaml['info']['version'];
                $full_productPlans = array();
                foreach ($full_product->product_plans->getValue() as $arrayValue) {
                  $full_product_plan = unserialize($arrayValue['value']);
                  $full_productPlans[$full_product_plan['name']] = $full_product_plan;
                }
                if (isset($full_productPlans[$full_product_plan])) {
                  $full_plan_title = $full_productPlans[$full_product_plan]['title'];
                }
              }
              if (!isset($full_plan_title) || empty($full_plan_title)) {
                $full_plan_title = Html::escape($superseded_by_plan);
              }

              $superseding_product = [
                "product_ref" => $superseded_by_ref,
                "plan" => $superseded_by_plan,
                "plan_title" => $full_plan_title,
                "product_title" => $superseded_by_title,
                "product_version" => $superseded_by_version
              ];
            }
          }
          if (!isset($plan_title) || empty($plan_title)) {
            $plan_title = Html::escape($sub['plan']);
          }
          $subarray[] = [
            'product_title' => Html::escape($product->getTitle()),
            'product_version' => Html::escape($product->apic_version->value),
            'product_nid' => $nid,
            'product_image' => $product_image_url,
            'plan_name' => Html::escape($sub['plan']),
            'plan_title' => Html::escape($plan_title),
            'state' => Html::escape($sub['state']),
            'subId' => Html::escape($sub['id']),
            'cost' => $cost,
            'superseded_by_product' => $superseding_product,
          ];
        }
      }
    }
    return $subarray;
  }

  /**
   * Invalidate caches for the current consumer org
   * Used to ensure the application list is correct when new apps are added etc
   */
  public static function invalidateCaches() {
    $current_user = \Drupal::currentUser();
    if (!$current_user->isAnonymous() && $current_user->id() != 1) {
      $user_utils = \Drupal::service('ibm_apim.user_utils');
      $org = $user_utils->getCurrentConsumerOrg();
      $tags = ['consumerorg:' . Html::cleanCssIdentifier($org['url'])];
      Cache::invalidateTags($tags);
    }
  }

  /**
   * Returns a JSON representation of an application
   *
   * @param $url
   * @return string (JSON)
   */
  public function getApplicationAsJson($url) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, array('url' => $url));
    $output = null;
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
      } else {
        \Drupal::logger('apic_app')->notice('getApplicationAsJson: serialization module not enabled', array());
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $output;
  }
}
