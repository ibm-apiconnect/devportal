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

namespace Drupal\apic_app\Service;

use Drupal\apic_app\Event\SubscriptionCreateEvent;
use Drupal\node\Entity\Node;

use Drupal\ibm_apim\ApicRest;
use Drupal\apic_app\Application;
use Drupal\apic_app\Subscription;
use Drupal\apic_app\Event\ApplicationCreateEvent;

class ApplicationRestService implements ApplicationRestInterface {

  /**
   * @inheritDoc
   */
  public function getApplicationDetails($url) {
    return $this->doGet($url);
  }

  /**
   * @inheritDoc
   */
  public function postApplication($url, $requestBody) {
    return $this->doPost($url, $requestBody);
  }

  /**
   * @inheritDoc
   */
  public function deleteApplication($url) {
    // invalidate any nodes cached for this consumer org (e.g. apis with an app list)
    Application::invalidateCaches();
    return $this->doDelete($url);
  }

  /**
   * @inheritDoc
   */
  public function promoteApplication($url, $requestBody) {
    return $this->doPatch($url, $requestBody);
  }

  /**
   * @inheritDoc
   */
  public function patchApplication($url, $requestBody) {
    return $this->doPatch($url, $requestBody);
  }

  /**
   * @inheritDoc
   */
  public function postCredentials($url, $requestBody) {
    // invalidate any nodes cached for this consumer org (e.g. apis with an app list)
    Application::invalidateCaches();
    return $this->doPost($url, $requestBody);
  }

  /**
   * @inheritDoc
   */
  public function deleteCredentials($url) {
    return $this->doDelete($url);
  }

  /**
   * @inheritDoc
   */
  public function patchCredentials($url, $requestBody) {
    return $this->doPatch($url, $requestBody);
  }

  /**
   * @inheritDoc
   */
  public function patchSubscription($url, $requestBody) {
    return $this->doPatch($url, $requestBody);
  }

  /**
   * @inheritDoc
   */
  public function postClientId($url, $requestBody) {
    return $this->doPost($url, $requestBody);
  }

  /**
   * @inheritDoc
   */
  public function postClientSecret($url, $requestBody) {
    return $this->doPost($url, $requestBody);
  }

  /**
   * @inheritDoc
   */
  public function postSubscription($url, $requestBody) {
    return $this->doPost($url, $requestBody);
  }

  /**
   * @inheritDoc
   */
  public function deleteSubscription($url) {
    return $this->doDelete($url);
  }

  /**
   * @inheritDoc
   */
  public function postSecret($url, $requestBody) {
    return $this->doPut($url, $requestBody);
  }

  /**
   * Triggers the get request to be made to apim
   *
   * @param $url
   * @return mixed
   */
  private function doGet($url) {
    return ApicRest::get($url);
  }

  /**
   * Triggers the post request to be made to apim
   *
   * @param $url
   * @param $requestBody
   * @return mixed
   */
  private function doPost($url, $requestBody) {
    return ApicRest::post($url, $requestBody);
  }

  /**
   * Triggers the put request to be made to apim
   *
   * @param $url
   * @param $requestBody
   * @return mixed
   */
  private function doPut($url, $requestBody) {
    return ApicRest::put($url, $requestBody);
  }

  /**
   * Triggers the delete request to be made to apim
   *
   * @param $url
   * @return mixed
   */
  private function doDelete($url) {
    return ApicRest::delete($url);
  }

  /**
   * Triggers the patch request to be made to apim
   *
   * @param $url
   * @param $requestBody
   * @return mixed
   */
  private function doPatch($url, $requestBody) {
    return ApicRest::patch($url, $requestBody);
  }

  /**
   * Registers a new application in the management appliance
   *
   * @param $name
   * @param $summary
   * @param $oauthUrls
   * @param null $certificate
   * @param null $formState
   * @return mixed|null
   */
  public function createApplication($name, $summary, $oauthUrls, $certificate = NULL, $formState = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $result = NULL;

    $org_url = \Drupal::service('ibm_apim.user_utils')->getCurrentConsumerOrg()['url'];


    if (!isset($name) || empty($name)) {
      drupal_set_message(t('ERROR: Title is a required field.'), 'error');
    }
    else {
      $url = $org_url . '/apps';
      if (!isset($summary)) {
        $summary = '';
      }
      if (!isset($oauthUrls)) {
        $oauthUrls = array();
      }
      if (!is_array($oauthUrls)) {
        $oauthUrls = array($oauthUrls);
      }

      $data = array(
        "title" => $name,
        "summary" => $summary
      );

      if (!empty($oauthUrls)) {
        $data["redirect_endpoints"] = $oauthUrls;
      }

      $ibm_apim_application_certificates = \Drupal::state()->get('ibm_apim.application_certificates');
      if ($ibm_apim_application_certificates) {
        $certificate = trim($certificate);
        if (isset($certificate) && !empty($certificate)) {
          $data['application_public_certificate_entry'] = $certificate;
        }
      }
      $config = \Drupal::config('ibm_apim.settings');
      if ($config->get('show_placeholder_images')) {
        $rawImage = Application::getRandomImageName($name);
        $application_image_url = $_SERVER['HTTP_HOST'] . base_path() . drupal_get_path('module', 'apic_app') . '/images/' . $rawImage;
        if ($_SERVER['HTTPS'] == 'on') {
          $application_image_url = "https://" . $application_image_url;
        }
        else {
          $application_image_url = "http://" . $application_image_url;
        }
      }
      else {
        $application_image_url = '';
      }
      $data['image_endpoint'] = $application_image_url;
      $result = $this->postApplication($url, json_encode($data));

      if (isset($result) && $result->code >= 200 && $result->code < 300) {
        $data = $result->data['credentials'];
        // alter hook (pre-invoke)
        \Drupal::moduleHandler()->alter('apic_app_create', $result->data['id'], $data);
        $result->data['credentials'] = $data;

        drupal_set_message(t('Application created successfully.'));
        $current_user = \Drupal::currentUser();
        \Drupal::logger('apic_app')->notice('Application @appname created by @username', array(
          '@appname' => $name,
          '@username' => $current_user->getAccountName()
        ));

        // Don't store client_secret!
        $app_data = $result->data;
        unset($app_data['client_secret']);

        $nid = Application::create($app_data, 'create');
        $node = Node::load($nid);

        if (!empty($formState)) {
          $customfields = Application::getCustomFields();
          foreach ($customfields as $customfield) {
            $value = $formState->getValue($customfield);
            if (is_array($value) && isset($value[0]['value'])) {
              $value = $value[0]['value'];
            }
            $node->set($customfield, $value);
          }
          if (count($customfields) > 0) {
            $node->save();
          }
        }

        // invalidate any nodes cached for this consumer org (e.g. apis with an app list)
        Application::invalidateCaches();

        if (isset($node)) {
          // hook is called above in Application::create()
          $moduleHandler = \Drupal::service('module_handler');
          if ($moduleHandler->moduleExists('rules')) {
            // Set the args twice on the event: as the main subject but also in the
            // list of arguments.
            $event = new ApplicationCreateEvent($node, ['application' => $node]);
            $event_dispatcher = \Drupal::service('event_dispatcher');
            $event_dispatcher->dispatch(ApplicationCreateEvent::EVENT_NAME, $event);
          }

          // Insert nid in to results so that callers don't have to do a db query to find it
          $result->data['nid'] = $nid;
        }
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $result;
  }


  /**
   * Subscribe the given application to the specified plan.
   *
   * @param $appUrl
   * @param $planId
   * @return mixed
   */
  public function subscribeToPlan($appUrl, $planId) {

    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $url = $appUrl . '/subscriptions';

    $parts = explode(':', $planId);
    $product_url = $parts[0];
    $planname = $parts[1];

    // 'adjust' the product url if it isn't in the format that the consumer-api expects
    $full_product_url = \Drupal::service('ibm_apim.apim_utils')->createFullyQualifiedUrl($product_url);

    $data = array(
      "product_url" => $full_product_url,
      'plan' => $planname
    );

    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'product');
    $query->condition('apic_url.value', $product_url);

    $nids = $query->execute();
    $productnid = NULL;
    $productnode = NULL;
    $node = NULL;

    if (isset($nids) && !empty($nids)) {
      $productnid = array_shift($nids);
      $productnode = Node::load($productnid);
    }

    $result = $this->postSubscription($url, json_encode($data));

    if (isset($result) && $result->code >= 200 && $result->code < 300 && (!isset($result->data) || (isset($result->data) && !isset($result->data['errors'])))) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'application');
      $query->condition('apic_url.value', $appUrl);
      $dbnids = $query->execute();
      if (isset($dbnids) && !empty($dbnids)) {
        $appnid = array_shift($dbnids);
        $node = Node::load($appnid);

        $current_user = \Drupal::currentUser();
        \Drupal::logger('apic_app')->notice('Application @appname requested subscription to @plan by @username', array(
          '@appname' => $node->getTitle(),
          '@plan' => $product_url . ':' . $planname,
          '@username' => $current_user->getAccountName()
        ));

        // Calling all modules implementing 'hook_apic_app_subscribe':
        \Drupal::moduleHandler()->invokeAll('apic_app_subscribe', array(
          'node' => $node,
          'data' => $result->data,
          'appId' => $appUrl,
          'planId' => $product_url . ':' . $planname
        ));
      }

      // Create subscription in our database if no approval was required
      if (isset($result->data)) {
        $sub = $result->data;
        $state = 'enabled';
        if (isset($sub['state'])) {
          $state = $sub['state'];
        }
        try {
          // Rules
          $moduleHandler = \Drupal::service('module_handler');
          if ($moduleHandler->moduleExists('rules')) {
            // Set the args twice on the event: as the main subject but also in the
            // list of arguments.
            $event = new SubscriptionCreateEvent($node, $productnode, $planname, $state, ['application' => $node, 'product' => $productnode, 'planName' => $planname, 'state' => $state]);
            $event_dispatcher = \Drupal::service('event_dispatcher');
            $event_dispatcher->dispatch(SubscriptionCreateEvent::EVENT_NAME, $event);
          }

          // TODO set billing_url correctly
          $billing_url = NULL;
          Subscription::create($appUrl, $sub['id'], $product_url, $sub['plan'], $state, $billing_url);
        } catch (\Exception $e) {

        }
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $result;
  }

}
