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

namespace Drupal\apic_app\Service;

use Drupal\ibm_apim\ApicRest;
use Drupal\ibm_apim\Service\ApimUtils;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\ibm_apim\Service\Utils;
use Drupal\node\Entity\Node;
use Throwable;

class ApplicationRestService implements ApplicationRestInterface {

  /**
   * @var \Drupal\apic_app\Service\ApplicationService
   */
  protected ApplicationService $applicationService;

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
  protected UserUtils $userUtils;

  /**
   * @var \Drupal\ibm_apim\Service\ApimUtils
   */
  protected ApimUtils $apimUtils;

  /**
   * @var \Drupal\apic_app\Service\SubscriptionService
   */
  protected SubscriptionService $subscriptionService;

  /**
   * @var \Drupal\ibm_apim\Service\Utils
   */
  protected Utils $utils;

  /**
   * ApplicationRestService constructor.
   *
   * @param \Drupal\apic_app\Service\ApplicationService $applicationService
   * @param \Drupal\ibm_apim\Service\UserUtils $userUtils
   * @param \Drupal\ibm_apim\Service\ApimUtils $apimUtils
   * @param \Drupal\apic_app\Service\SubscriptionService $subscriptionService
   */
  public function __construct(ApplicationService $applicationService, 
                              UserUtils $userUtils, 
                              ApimUtils $apimUtils, 
                              SubscriptionService $subscriptionService,
                              Utils $utils
                              ) {
    $this->applicationService = $applicationService;
    $this->userUtils = $userUtils;
    $this->apimUtils = $apimUtils;
    $this->subscriptionService = $subscriptionService;
    $this->utils = $utils;
  }
  /**
   * @inheritDoc
   * @throws Exception
   */
  public function getApplicationDetails($url): ?\stdClass {
    return $this->doGet($url);
  }

  /**
   * @inheritDoc
   * @throws Exception
   */
  public function postApplication($url, $requestBody): ?\stdClass {
    return $this->doPost($url, $requestBody);
  }

  /**
   * @inheritDoc
   * @throws Exception
   */
  public function deleteApplication($url): ?\stdClass {
    // invalidate any nodes cached for this consumer org (e.g. apis with an app list)
    $this->applicationService->invalidateCaches();
    return $this->doDelete($url);
  }

  /**
   * @inheritDoc
   * @throws Exception
   */
  public function promoteApplication($url, $requestBody): ?\stdClass {
    return $this->doPatch($url, $requestBody);
  }

  /**
   * @inheritDoc
   * @throws Exception
   */
  public function patchApplication($url, $requestBody): ?\stdClass {
    return $this->doPatch($url, $requestBody);
  }

  /**
   * @inheritDoc
   * @throws Exception
   */
  public function postCredentials($url, $requestBody): ?\stdClass {
    // invalidate any nodes cached for this consumer org (e.g. apis with an app list)
    $this->applicationService->invalidateCaches();
    return $this->doPost($url, $requestBody);
  }

  /**
   * @inheritDoc
   * @throws Exception
   */
  public function deleteCredentials($url): ?\stdClass {
    // invalidate any nodes cached for this consumer org (e.g. apis with an app list)
    $this->applicationService->invalidateCaches();
    return $this->doDelete($url);
  }

  /**
   * @inheritDoc
   * @throws Exception
   */
  public function patchCredentials($url, $requestBody): ?\stdClass {
    // invalidate any nodes cached for this consumer org (e.g. apis with an app list)
    $this->applicationService->invalidateCaches();
    return $this->doPatch($url, $requestBody);
  }

  /**
   * @inheritDoc
   * @throws Exception
   */
  public function patchSubscription($url, $requestBody): ?\stdClass {
    return $this->doPatch($url, $requestBody);
  }

  /**
   * @inheritDoc
   * @throws Exception
   */
  public function postClientId($url, $requestBody): ?\stdClass {
    // invalidate any nodes cached for this consumer org (e.g. apis with an app list)
    $this->applicationService->invalidateCaches();
    return $this->doPost($url, $requestBody);
  }

  /**
   * @inheritDoc
   * @throws Exception
   */
  public function postClientSecret($url, $requestBody): ?\stdClass {
    // invalidate any nodes cached for this consumer org (e.g. apis with an app list)
    $this->applicationService->invalidateCaches();
    return $this->doPost($url, $requestBody);
  }

  /**
   * @inheritDoc
   * @throws Exception
   */
  public function postSubscription($url, $requestBody): ?\stdClass {
    return $this->doPost($url, $requestBody);
  }

  /**
   * @inheritDoc
   * @throws Exception
   */
  public function deleteSubscription($url): ?\stdClass {
    return $this->doDelete($url);
  }

  /**
   * @inheritDoc
   * @throws Exception
   */
  public function postSecret($url, $requestBody): ?\stdClass{
    return $this->doPut($url, $requestBody);
  }

  /**
   * Triggers the get request to be made to apim
   *
   * @param $url
   *
   * @return \stdClass|null
   * @throws Exception
   */
  private function doGet($url): ?\stdClass {
    return ApicRest::get($url);
  }

  /**
   * Triggers the post request to be made to apim
   *
   * @param $url
   * @param $requestBody
   *
   * @return \stdClass|null
   * @throws Exception
   */
  private function doPost($url, $requestBody): ?\stdClass {
    return ApicRest::post($url, $requestBody);
  }

  /**
   * Triggers the put request to be made to apim
   *
   * @param $url
   * @param $requestBody
   *
   * @return \stdClass|null
   * @throws Exception
   */
  private function doPut($url, $requestBody): ?\stdClass {
    return ApicRest::put($url, $requestBody);
  }

  /**
   * Triggers the delete request to be made to apim
   *
   * @param $url
   *
   * @return \stdClass|null
   * @throws Exception
   */
  private function doDelete($url): ?\stdClass {
    return ApicRest::delete($url);
  }

  /**
   * Triggers the patch request to be made to apim
   *
   * @param $url
   * @param $requestBody
   *
   * @return \stdClass|null
   * @throws Exception
   */
  private function doPatch($url, $requestBody): ?\stdClass {
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
   *
   * @return mixed|\stdClass|null
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws Exception
   */
  public function createApplication($name, $summary, $oauthUrls, $certificate = NULL, $formState = NULL): ?\stdClass {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $result = NULL;

    $org_url = $this->userUtils->getCurrentConsumerOrg()['url'];


    if ($name === NULL || empty($name)) {
      \Drupal::messenger()->addError(t('ERROR: Title is a required field.'));
    }
    else {
      $url = $org_url . '/apps';
      if ($summary === NULL) {
        $summary = '';
      }
      if ($oauthUrls === NULL) {
        $oauthUrls = [];
      }
      if (!\is_array($oauthUrls)) {
        $oauthUrls = [$oauthUrls];
      }

      $data = [
        'title' => $name,
        'summary' => $summary,
      ];

      if (!empty($oauthUrls)) {
        $data['redirect_endpoints'] = $oauthUrls;
      }

      $ibm_apim_application_certificates = \Drupal::state()->get('ibm_apim.application_certificates');
      if ($ibm_apim_application_certificates) {
        $certificate = trim($certificate);
        if ($certificate !== NULL && !empty($certificate)) {
          $data['application_public_certificate_entry'] = $certificate;
        }
      }
      $config = \Drupal::config('ibm_apim.settings');
      if ((boolean) $config->get('show_placeholder_images')) {
        $rawImage = $this->applicationService->getRandomImageName($name);
        $application_image_url = $_SERVER['HTTP_HOST'] . base_path() . \Drupal::service('extension.list.module')->getPath('apic_app') . '/images/' . $rawImage;
        if ($_SERVER['HTTPS'] === 'on') {
          $application_image_url = 'https://' . $application_image_url;
        }
        else {
          $application_image_url = 'http://' . $application_image_url;
        }
        $data['image_endpoint'] = $application_image_url;
      }

      $customFields = $this->applicationService->getCustomFields();
      $customFieldValues = $this->utils->handleFormCustomFields($customFields, $formState);
      if (!empty($customFieldValues)) {
        foreach ($customFieldValues as $customField => $value) {
          $customFieldValues[$customField] = json_encode($value, JSON_THROW_ON_ERROR);
        }
        $data['metadata'] = $customFieldValues;
      }

      $result = $this->postApplication($url, json_encode($data, JSON_THROW_ON_ERROR));

      if ($result !== NULL && $result->code >= 200 && $result->code < 300) {
        $data = ['client_id' => $result->data['client_id'], 'client_secret' => $result->data['client_secret']];
        // alter hook (pre-invoke)
        $moduleHandler = \Drupal::moduleHandler();
        $moduleHandler->alter('apic_app_modify_create', $data, $result->data['id'], $formState);
        $result->data['client_id'] = $data['client_id'];
        $result->data['client_secret'] = $data['client_secret'];

        \Drupal::messenger()->addMessage(t('Application created successfully.'));
        $current_user = \Drupal::currentUser();
        \Drupal::logger('apic_app')->notice('Application @appName created by @username', [
          '@appName' => $name,
          '@username' => $current_user->getAccountName(),
        ]);

        $app_data = $result->data;

        $nid = $this->applicationService->createOrUpdateReturnNid($app_data, 'create', $formState);

        // Insert nid in to results so that callers don't have to do a db query to find it
        $result->data['nid'] = $nid;

        // invalidate any nodes cached for this consumer org (e.g. apis with an app list)
        $this->applicationService->invalidateCaches();
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
   *
   * @return mixed
   * @throws Throwable
   */
  public function subscribeToPlan($appUrl = NULL, $planId = NULL): ?\stdClass {

    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, ['appUrl' => $appUrl, 'planId' => $planId]);
    $result = NULL;
    if ($appUrl !== NULL && $planId !== NULL) {

      $url = $appUrl . '/subscriptions';

      $parts = explode(':', $planId);
      [$productUrl, $planName] = $parts;

      // 'adjust' the product url if it isn't in the format that the consumer-api expects
      $fullProductUrl = $this->apimUtils->createFullyQualifiedUrl($productUrl);

      $data = [
        'product_url' => $fullProductUrl,
        'plan' => $planName,
      ];

      $result = $this->postSubscription($url, json_encode($data, JSON_THROW_ON_ERROR));

      if ($result !== NULL && $result->code >= 200 && $result->code < 300 && (!isset($result->data) || (isset($result->data) && !isset($result->data['errors'])))) {
        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'application');
        $query->condition('apic_url.value', $appUrl);
        $dbNids = $query->execute();
        if ($dbNids !== NULL && !empty($dbNids)) {
          $appNid = array_shift($dbNids);
          $node = Node::load($appNid);

          $currentUser = \Drupal::currentUser();
          \Drupal::logger('apic_app')->notice('Application @appname requested subscription to @plan by @username', [
            '@appname' => $node->getTitle(),
            '@plan' => $productUrl . ':' . $planName,
            '@username' => $currentUser->getAccountName(),
          ]);

          $subId = $result->data['id'] ?? '';
          // Calling all modules implementing 'hook_apic_app_subscribe':
          \Drupal::moduleHandler()->invokeAll('apic_app_subscribe', [
            'node' => $node,
            'data' => $result->data,
            'appId' => $appUrl,
            'product_url' => $productUrl,
            'plan' => $planName,
            'subId' => $subId,
          ]);

          // Create subscription in our database if no approval was required
          if (isset($result->data)) {
            $sub = $result->data;
            $state = $sub['state'] ?? 'enabled';
            try {
              // TODO set billingUrl correctly
              $billingUrl = NULL;
              $org_url = $this->userUtils->getCurrentConsumerOrg()['url'];
              $this->subscriptionService->create($appUrl, $sub['id'], $productUrl, $sub['plan'], $org_url, $state, $billingUrl, $result->data);
            } catch (Throwable $e) {

            }
          }
          $this->applicationService->invalidateCaches();
        }
      }
    }
    else {
      \Drupal::messenger()->addError(t('ERROR: Both the application URL and plan ID must be specified.'));
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $result;
  }

  /**
   * A function to retrieve the details for a specified application from the public portal API
   * This basically maps what we get from the portal api over to what we expect from the content_refresh or webhook apis
   *
   * @param string|null $appUrl
   *
   * @return array|null|string
   * @throws Exception
   */
  public function fetchFromAPIC(?string $appUrl = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $appUrl);
    $returnApp = NULL;
    if ($appUrl === 'new') {
      return '';
    }
    $org = $this->userUtils->getCurrentConsumerOrg();
    $consumerOrg = $org['url'];

    if (!isset($consumerOrg)) {
      \Drupal::messenger()->addError('Consumer organization not set.');
      return NULL;
    }

    $result = $this->getApplicationDetails($appUrl);

    if (isset($result, $result->data) && !isset($result->data['errors'])) {
      $returnApp = $result->data;
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnApp);
    return $returnApp;
  }

}
