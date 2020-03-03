<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2020
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\State\StateInterface;
use Drupal\ibm_apim\Service\Interfaces\PermissionsServiceInterface;
use Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;
use Drupal\ibm_apim\External\Json;

/**
 * Functionality for handling configuration updates
 */
class SiteConfig {

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  private $state;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $configFactory;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface
   */
  private $urService;

  /**
   * @var \Drupal\ibm_apim\Service\Billing
   */
  private $billService;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\PermissionsServiceInterface
   */
  private $permsService;

  /**
   * @var \Drupal\ibm_apim\Service\AnalyticsService
   */
  private $analyticsService;

  /**
   * @var \Drupal\ibm_apim\Service\TlsClientProfilesService
   */
  private $tlsProfilesService;

  /**
   * @var \Drupal\ibm_apim\Service\Group
   */
  private $groupService;

  /**
   * @var \Drupal\ibm_apim\Service\VendorExtension
   */
  private $vendorExtService;

  /**
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  private $menuLinkManager;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  private $messenger;

  public function __construct(StateInterface $state, ConfigFactoryInterface $config_factory,
                              LoggerInterface $logger, UserRegistryServiceInterface $urService, Billing $billService,
                              PermissionsServiceInterface $permsService, AnalyticsService $analyticsService,
                              TlsClientProfilesService $tlsProfilesService, Group $groupService, VendorExtension $vendorExtService, MenuLinkManagerInterface $menuLinkManager,
                              Messenger $messenger) {
    $this->state = $state;
    $this->configFactory = $config_factory;
    $this->logger = $logger;
    $this->urService = $urService;
    $this->billService = $billService;
    $this->permsService = $permsService;
    $this->analyticsService = $analyticsService;
    $this->tlsProfilesService = $tlsProfilesService;
    $this->groupService = $groupService;
    $this->vendorExtService = $vendorExtService;
    $this->menuLinkManager = $menuLinkManager;
    $this->messenger = $messenger;
  }

  /**
   * Get the APIm config. This function should only be called from inside this class.
   *
   * @return array empty if an error occurs otherwise an array of the apim config.
   */
  protected function get(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $catalog_config = $this->state->get('ibm_apim.site_config');
    if (!isset($catalog_config)) {
      $catalog_config = [];
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, !empty($catalog_config));
    return $catalog_config;
  }

  /**
   * Determines whether we have any site config stored.
   *
   * @return bool
   */
  public function isSet(): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $catalog_config = $this->get();

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, !empty($catalog_config));
    return !empty($catalog_config);
  }

  /**
   * get the APIm catalog info
   *
   *
   * @return array empty if an error occurs otherwise an array of the catalog info.
   */
  public function getCatalog(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $catalog_info = $this->state->get('ibm_apim.catalog_info');
    if (!isset($catalog_info)) {
      $catalog_info = [];
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, !empty($catalog_info));
    return $catalog_info;
  }

  /**
   * @param $catalog
   *
   * @throws \Exception
   */
  public function updateCatalog($catalog = NULL): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    if (isset($catalog)) {
      if (is_string($catalog)) {
        $catalog = Json::decode($catalog, TRUE);
      }

      $this->state->set('ibm_apim.catalog_info', $catalog);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * @return null|string
   */
  public function getPlatformApimEndpoint() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $platformApimEndpoint = $this->state->get('ibm_apim.host');

    if (isset($platformApimEndpoint) && !empty($platformApimEndpoint)) {
      if (!preg_match('/^http[s]?:\/\//', $platformApimEndpoint)) {
        $platformApimEndpoint = 'https://' . $platformApimEndpoint;
      }
    } else {
      $platformApiEndpoint = NULL;
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $platformApimEndpoint;
  }

  /**
   * @return mixed|null|string
   */
  public function getApimHost() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $returnValue = NULL;
    $custom_hostname = $this->state->get('ibm_apim.apim_host');
    if (isset($custom_hostname) && !empty($custom_hostname)) {
      $this->logger->debug('getApimHost():: Using custom hostname.');
      $returnValue = $custom_hostname;
    }
    else {
      $host_file = '/web/config/apim_consumer_api_ingress';

      if (file_exists($host_file)) {
        //$this->logger->debug('getApimHost():: Using appliance host file.');
        try {
          $apim_host = trim(file_get_contents($host_file));
          if (isset($apim_host) && !empty($apim_host)) {
            $returnValue = $apim_host;
          }
          else {
            $this->logger->error('/web/config/apim_consumer_api_ingress not set correctly, value: %data.', [
              '%data' => $apim_host,
            ]);
            $returnValue = NULL;
          }
        } catch (\Exception $e) {
          $this->logger->error('Get apim hostname exception: %data.', [
            '%data' => $e->getMessage(),
          ]);
          $returnValue = NULL;
        }
      }
      else {
        $this->logger->notice('getApimHost():: Unable to find hostname via known file on appliance, assuming test environment.');
        $returnValue = 'example.com';
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    return $returnValue;
  }

  /**
   * Utility function to handle different combinations of scheme, host and port in the apim host field
   *
   * @return array|null
   */
  public function parseApimHost(): ?array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $returnValue = NULL;
    $hostvariable = $this->getApimHost();
    if (isset($hostvariable)) {
      // only use parse_url if scheme is set
      $pieces = explode(':', $hostvariable);
      $pieces_c = count($pieces);
      if (count($pieces) > 1) {
        // this will only work is scheme is set
        try {
          $host = parse_url($hostvariable, PHP_URL_HOST);
          $scheme = parse_url($hostvariable, PHP_URL_SCHEME);
          $port = (int) parse_url($hostvariable, PHP_URL_PORT);
          $path = parse_url($hostvariable, PHP_URL_PATH);
        } catch (\Exception $e) {
        }
      }
      if (!isset($host)) {
        // check if we have custom port but no scheme
        // grab last element which must be the port
        if ($pieces_c > 1) {
          $last = array_pop($pieces);
        }
        if ($pieces_c > 1 && isset($last) && (is_int($last) || ctype_digit($last))) {
          $host = implode(':', $pieces);
          $port = (int) $last;
        }
        else {
          $host = $hostvariable;
        }
      }
      if (!isset($port) || $port === 0) {
        $port = 443;
      }
      if (!isset($scheme) || ($scheme !== 'https' && $scheme !== 'http')) {
        $scheme = 'https';
      }
      if (!isset($path)) {
        $path = '';
      }
      $path = rtrim($path, '/');
      $returnValue = [
        'host' => $host,
        'port' => $port,
        'scheme' => $scheme,
        'path' => $path,
        'url' => $scheme . '://' . $host . ':' . $port . $path,
      ];
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    return $returnValue;
  }

  /**
   * @param $config
   *
   * @throws \Exception
   */
  public function update($config = NULL): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $config);

    $updated = false;
    if ($config !== NULL) {
      if (is_string($config)) {
        $config = Json::decode($config, true);
      }

      // TODO : temporary workaround until refactoring work is complete
      // may need to flatten 'catalog_setting' down (resolves difference between BAU and snapshot webhook structure)
      if (!empty($config['catalog_setting']) && is_array($config['catalog_setting'])) {
        $config = array_merge($config, $config['catalog_setting']);
        unset($config['catalog_setting']);
      }

      // clear caches if config different to previous requests
      $current_config = $this->state->get('ibm_apim.site_config');

      if ($current_config === NULL || empty($current_config) || $current_config !== $config) {
        $this->state->set('ibm_apim.site_config', $config);
        $this->getCheckAndStore();
        drupal_flush_all_caches();
        $updated = true;
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $updated);
  }

  /**
   * Delete all stored APIM configuration
   */
  public function deleteAll(): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $this->state->delete('ibm_apim.site_config');
    $this->state->delete('ibm_apim.catalog_info');
    $this->state->delete('ibm_apim.site_client_id');
    $this->state->delete('ibm_apim.site_client_secret');
    $this->state->delete('ibm_apim.selfSignUpEnabled');
    $this->state->delete('ibm_apim.applifecycle_enabled');
    $this->state->delete('ibm_apim.site_namespace');
    $this->state->delete('ibm_apim.apim_host');
    $this->tlsProfilesService->deleteAll();
    $this->analyticsService->deleteAll();
    $this->urService->deleteAll();
    $this->billService->deleteAll();
    $this->permsService->deleteAll();
    $this->groupService->deleteAll();
    $this->vendorExtService->deleteAll();
    drupal_flush_all_caches();

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Get basic APIC config and store it in the session.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getCheckAndStore(): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    // update version number
    $filename = drupal_get_path('profile', 'apim_profile') . '/apic_version.yaml';
    if (file_exists($filename)) {
      $yaml = yaml_parse_file(drupal_get_path('profile', 'apim_profile') . '/apic_version.yaml');
      $this->state->set('ibm_apim.version', ['value' => $yaml['version'], 'description' => $yaml['build']]);
    }

    $config_data = $this->get();

    if (isset($config_data)) {
      // store user registries
      if (isset($config_data['configured_catalog_user_registries'])) {
        $this->urService->updateAll($config_data['configured_catalog_user_registries']);
      }

      if (isset($config_data['user_registry_default_url'])) {
        $this->urService->setDefaultRegistry($config_data['user_registry_default_url']);
      }

      if (isset($config_data['catalog'])) {
        $this->updateCatalog($config_data['catalog']);
      }

      // store billing objects
      if (isset($config_data['billing'])) {
        $this->billService->updateAll($config_data['billing']);
      }

      // store permissions objects
      if (isset($config_data['permissions'])) {
        $this->permsService->updateAll($config_data['permissions']);
      }

      // if selfSignUpEnabled is disabled then disable user registration
      if ($config_data['consumer_self_service_onboarding'] !== NULL && (boolean) $config_data['consumer_self_service_onboarding'] === FALSE) {
        $this->state->set('ibm_apim.selfSignUpEnabled', FALSE);
        $this->configFactory->getEditable('user.settings')->set('register', UserInterface::REGISTER_ADMINISTRATORS_ONLY)->save();
        $this->setCreateAccountLinkEnabled(FALSE);
        // TODO hide create new org link
      }
      elseif ($config_data['consumer_self_service_onboarding'] !== NULL && (boolean) $config_data['consumer_self_service_onboarding'] === TRUE) {
        $this->state->set('ibm_apim.selfSignUpEnabled', TRUE);
        $this->configFactory->getEditable('user.settings')->set('register', UserInterface::REGISTER_VISITORS)->save();
        $this->setCreateAccountLinkEnabled(TRUE);
        // TODO show create new org link
      }

      if (isset($config_data['analytics'])) {
        $this->analyticsService->updateAll($config_data['analytics']);
      }

      if (isset($config_data['tls_client_profiles'])) {
        $this->tlsProfilesService->updateAll($config_data['tls_client_profiles']);
      }

      if (isset($config_data['application_lifecycle'])) {
        $this->state->set('ibm_apim.applifecycle_enabled', $config_data['application_lifecycle']['enabled']);
      }

      //      // if invitationEnabled is disabled then disable developer invitations
      //      // TODO - invitiationEnabled isn't the proper field to use here (see https://github.ibm.com/velox/apim/issues/2179)
      //      if (isset($config_data['invitationEnabled']) && $config_data['invitationEnabled'] == FALSE) {
      //        $this->state->set('ibm_apim.disallow_invitations', 1);
      //      }
      //      else {
      //        $this->state->set('ibm_apim.disallow_invitations', 0);
      //      }
      //      // billing
      //      // TODO - paymentGateways is the old field. We need to know what the new one is (see https://github.ibm.com/velox/apim/issues/2179)
      //      if (isset($config_data['paymentGateways']) && !empty($config_data['paymentGateways'])) {
      //        $this->state->set('ibm_apim.billing_enabled', true);
      //      }
      //      else {
      //        $this->state->set('ibm_apim.billing_enabled', false);
      //      }
      //
      //      else {
      //        $this->configFactory->getEditable('user.settings')->set('register', USER_REGISTER_VISITORS)->save();
      //        $current_selfsignup = $this->state->get('ibm_apim.selfSignUpEnabled');
      //        $this->state->set('ibm_apim.selfSignUpEnabled', 1);
      //        // show create new org link if onboarding just enabled
      //        // this protects against people who have intentionally turned it off and want it to stay that way
      //        if (isset($current_selfsignup) && $current_selfsignup == 0) {
      //          // TODO need to unhide the create org link
      //        }
      //      }
    }
    else {
      // Clear any other messages as until this problem is fixed they will just muddy the water
      $this->messenger->deleteAll();

      // Throw an exception with a useful message so that we stop processing the request here
      throw new \Exception(t('Could not retrieve portal configuration. Please contact your system administrator.', []));
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  private function showDefaultExtraUserfields() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  private function hideDefaultExtraUserfields() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Retrieve client id for this site.
   */
  public function getClientId() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $client_id = $this->state->get('ibm_apim.site_client_id');
    if ($client_id) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $client_id);
      $returnValue = $client_id;
    }
    else {
      $this->logger->error('Unable to retrieve client id');
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      $returnValue = NULL;
    }
    return $returnValue;
  }

  /**
   * Retrieve client secret for this site.
   */
  public function getClientSecret() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $client_secret = $this->state->get('ibm_apim.site_client_secret');
    if ($client_secret) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      $returnValue = $client_secret;
    }
    else {
      $this->logger->error('Unable to retrieve client secret');
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      $returnValue = NULL;
    }
    return $returnValue;
  }

  /**
   * Get current provider organization ID
   *
   * @return string|null
   */
  public function getOrgId(): ?string {
    $orgId = NULL;
    if (isset($this->get()['orgID']) && !empty($this->get()['orgID'])) {
      $orgId = $this->get()['orgID'];
    }
    else {
      $namespace = $this->state->get('ibm_apim.site_namespace');
      $parts = explode('.', $namespace);
      if (isset($parts[0])) {
        $orgId = $parts[0];
      }
    }
    return $orgId;
  }

  /**
   * Get current catalog ID
   *
   * @return string|null
   */
  public function getEnvId(): ?string {
    $envId = NULL;
    if (isset($this->get()['envID']) && !empty($this->get()['envID'])) {
      $envId = $this->get()['envID'];
    }
    else {
      $namespace = $this->state->get('ibm_apim.site_namespace');
      $parts = explode('.', $namespace);
      if (isset($parts[1])) {
        $envId = $parts[1];
      }
    }
    return $envId;
  }

  /**
   * @return boolean
   */
  public function isInCloud(): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $returnValue = NULL;

    $cloud_file = '/web/config/ibm_cloud';

    if (file_exists($cloud_file)) {
      try {
        $isCloud = trim(file_get_contents($cloud_file));
        if (isset($isCloud) && !empty($isCloud)) {
          // cast to boolean
          $returnValue = ($isCloud === 'true' || $isCloud === TRUE);
        }
        else {
          $this->logger->error('/web/config/ibm_cloud not set correctly, value: %data.', [
            '%data' => $isCloud,
          ]);
          $returnValue = FALSE;
        }
      } catch (\Exception $e) {
        $this->logger->error('Get isInCloud exception: %data.', [
          '%data' => $e->getMessage(),
        ]);
        $returnValue = FALSE;
      }
    }
    else {
      $this->logger->notice('isInCloud():: Unable to find isCloud via known file on appliance, assuming false.');
      $returnValue = FALSE;
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    return $returnValue;
  }

  /**
   * @return mixed
   */
  public function isSelfOnboardingEnabled() {
    return $this->state->get('ibm_apim.selfSignUpEnabled', FALSE);
  }

  /**
   * Enables or disables the "Create account" menu link shown on the front page
   * of the portal site.
   *
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  private function setCreateAccountLinkEnabled($enabled): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $register_links = $this->menuLinkManager->loadLinksByRoute('user.register');
    if (isset($register_links)) {
      $register_link = array_pop($register_links);
      if (isset($register_link)) {
        $settings = $register_link->getPluginDefinition();
        if (isset($settings)) {
          $settings['enabled'] = $enabled;
          $this->menuLinkManager->updateDefinition($register_link->getPluginId(), $settings, TRUE);
        }
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}
