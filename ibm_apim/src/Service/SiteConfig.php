<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2024
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Menu\MenuLinkManagerInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\State\StateInterface;
use Drupal\ibm_apim\External\Json;
use Drupal\ibm_apim\Service\Interfaces\PermissionsServiceInterface;
use Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;
use Drush\Drush;
use Throwable;

/**
 * Functionality for handling configuration updates
 */
class SiteConfig {

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  private StateInterface $state;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private ConfigFactoryInterface $configFactory;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  private LoggerInterface $logger;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface
   */
  private UserRegistryServiceInterface $urService;

  /**
   * @var \Drupal\ibm_apim\Service\Billing
   */
  private Billing $billService;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\PermissionsServiceInterface
   */
  private PermissionsServiceInterface $permsService;

  /**
   * @var \Drupal\ibm_apim\Service\AnalyticsService
   */
  private AnalyticsService $analyticsService;

  /**
   * @var \Drupal\ibm_apim\Service\TlsClientProfilesService
   */
  private TlsClientProfilesService $tlsProfilesService;

  /**
   * @var \Drupal\ibm_apim\Service\Group
   */
  private Group $groupService;

  /**
   * @var \Drupal\ibm_apim\Service\VendorExtension
   */
  private VendorExtension $vendorExtService;

  /**
   * @var \Drupal\Core\Menu\MenuLinkManagerInterface
   */
  private MenuLinkManagerInterface $menuLinkManager;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  private Messenger $messenger;

  /**
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  private ModuleInstallerInterface $moduleInstaller;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  private ModuleHandlerInterface $moduleHandler;

  /**
   * SiteConfig constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface $urService
   * @param \Drupal\ibm_apim\Service\Billing $billService
   * @param \Drupal\ibm_apim\Service\Interfaces\PermissionsServiceInterface $permsService
   * @param \Drupal\ibm_apim\Service\AnalyticsService $analyticsService
   * @param \Drupal\ibm_apim\Service\TlsClientProfilesService $tlsProfilesService
   * @param \Drupal\ibm_apim\Service\Group $groupService
   * @param \Drupal\ibm_apim\Service\VendorExtension $vendorExtService
   * @param \Drupal\Core\Menu\MenuLinkManagerInterface $menuLinkManager
   * @param \Drupal\Core\Messenger\Messenger $messenger
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
   */
  public function __construct(StateInterface $state, ConfigFactoryInterface $config_factory,
                              LoggerInterface $logger, UserRegistryServiceInterface $urService, Billing $billService,
                              PermissionsServiceInterface $permsService, AnalyticsService $analyticsService,
                              TlsClientProfilesService $tlsProfilesService, Group $groupService, VendorExtension $vendorExtService, MenuLinkManagerInterface $menuLinkManager,
                              Messenger $messenger, ModuleHandlerInterface $module_handler,
                              ModuleInstallerInterface $module_installer) {
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
    $this->moduleHandler = $module_handler;
    $this->moduleInstaller = $module_installer;
  }

  /**
   * Get the APIm config. This function should only be called from inside this class.
   *
   * @return array empty if an error occurs otherwise an array of the apim config.
   */
  public function get(): array {
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
  public function getPlatformApimEndpoint(): ?string {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $platformApimEndpoint = $this->state->get('ibm_apim.host');

    if (isset($platformApimEndpoint) && !empty($platformApimEndpoint)) {
      if (!preg_match('/^http[s]?:\/\//', $platformApimEndpoint)) {
        $platformApimEndpoint = 'https://' . $platformApimEndpoint;
      }
    }
    else {
      $platformApimEndpoint = NULL;
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $platformApimEndpoint;
  }

  /**
   * @return mixed|null|string
   */
  public function getApimHost() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

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
        } catch (Throwable $e) {
          $this->logger->error('Get apim hostname exception: %data.', [
            '%data' => $e->getMessage(),
          ]);
          $returnValue = NULL;
        }
      }
      else {
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
        } catch (Throwable $e) {
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

    $updated = FALSE;
    if ($config !== NULL) {
      if (is_string($config)) {
        $config = Json::decode($config, TRUE);
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
        $updated = TRUE;
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
   * @throws \Exception
   */
  public function getCheckAndStore(): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    // update version number
    $filename = 	\Drupal::service('extension.list.profile')->getPath('apim_profile') . '/apic_version.yaml';
    if (file_exists($filename)) {
      $yaml = yaml_parse_file(	\Drupal::service('extension.list.profile')->getPath('apim_profile') . '/apic_version.yaml');
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
      // default to enabled if not explicitly set
      elseif (!array_key_exists('consumer_self_service_onboarding', $config_data) || ($config_data['consumer_self_service_onboarding'] !== NULL && (boolean) $config_data['consumer_self_service_onboarding'] === TRUE)) {
        $this->state->set('ibm_apim.selfSignUpEnabled', TRUE);
        $this->configFactory->getEditable('user.settings')->set('register', UserInterface::REGISTER_VISITORS)->save();
        $this->setCreateAccountLinkEnabled(TRUE);
        // TODO show create new org link
      }

      // Can Andre invite Andre?
      if (array_key_exists('consumer_org_invitations_enabled', $config_data) && $config_data['consumer_org_invitations_enabled'] === FALSE) {
        $this->state->set('ibm_apim.consumerOrgInvitationEnabled', FALSE);
      }
      else {
        $this->state->set('ibm_apim.consumerOrgInvitationEnabled', TRUE);
      }

      if (array_key_exists('consumer_self_service_onboarding_approval', $config_data) && $config_data['consumer_self_service_onboarding_approval'] === TRUE) {
        $this->state->set('ibm_apim.accountApprovalEnabled', TRUE);
      }
      else {
        $this->state->set('ibm_apim.accountApprovalEnabled', FALSE);
      }

      // if we're given a list of roles to allow use it, else use default
      if (array_key_exists('consumer_org_invitation_roles', $config_data) && is_array($config_data['consumer_org_invitation_roles'])) {
        $this->state->set('ibm_apim.consumerOrgInvitationRoles', $config_data['consumer_org_invitation_roles']);
      }
      else {
        $this->state->set('ibm_apim.consumerOrgInvitationRoles', ['administrator', 'developer', 'viewer']);
      }

      if (isset($config_data['analytics'])) {
        $this->analyticsService->updateAll($config_data['analytics']);
      } else {
         $this->analyticsService->deleteAll();
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

      // If we're running in IBM Cloud then update things accordingly
      if ($this->isInCloud()) {
        $this->setRunningInIBMCloud();
      }
    }
    else {
      // Clear any other messages as until this problem is fixed they will just muddy the water
      $this->messenger->deleteAll();

      // Throw an exception with a useful message so that we stop processing the request here
      throw new \Exception(t('Could not retrieve portal configuration. Please contact your system administrator.', []));
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  private function showDefaultExtraUserfields(): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  private function hideDefaultExtraUserfields(): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Are client_id and client_secret encrypted. This only true once both
   * client_id and client_id_new enc keys have gone and the new enckey
   * exists.
   *
   * @return void
   */
  private function clientIdAndSecretAreEncrypted(): bool {
    $keyName = 'enckey';
    $clientIdkeyName = 'client_id';
    $clientIdNewkeyName = 'client_id_new';
    if (\Drupal::service('key.repository')->getKey($keyName)
      && ! \Drupal::service('key.repository')->getKey($clientIdkeyName)
      && ! \Drupal::service('key.repository')->getKey($clientIdNewkeyName)) {
        return TRUE;
    }
    return FALSE;
  }

  /**
   * Set client id for this site.
   */
  public function setClientId($client_id) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    if ($this->clientIdAndSecretAreEncrypted()) {
      $encryptionProfile = \Drupal::service('ibm_apim.utils')->loadEncryptionProfile('socialblock');
      $encryptionService = \Drupal::service('encryption');
      $client_id = $encryptionService->encrypt($client_id, $encryptionProfile);
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $this->state->set('ibm_apim.site_client_id', $client_id);
  }

  /**
   * Retrieve client id for this site.
   */
  public function getClientId() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $client_id = $this->state->get('ibm_apim.site_client_id');
    if ($client_id) {
      if ($this->clientIdAndSecretAreEncrypted()) {
       $encryptionProfile = \Drupal::service('ibm_apim.utils')->loadEncryptionProfile('socialblock');
       $encryptionService = \Drupal::service('encryption');
       $client_id = $encryptionService->decrypt($client_id, $encryptionProfile);
      }
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
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
   * Set client secret for this site.
   */
  public function setClientSecret($client_secret) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    if ($this->clientIdAndSecretAreEncrypted()) {
      $encryptionProfile = \Drupal::service('ibm_apim.utils')->loadEncryptionProfile('socialblock');
      $encryptionService = \Drupal::service('encryption');
      $client_secret = $encryptionService->encrypt($client_secret, $encryptionProfile);
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $this->state->set('ibm_apim.site_client_secret', $client_secret);
  }

  /**
   * Retrieve client secret for this site.
   */
  public function getClientSecret() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $client_secret = $this->state->get('ibm_apim.site_client_secret');
    if ($client_secret) {
      if ($this->clientIdAndSecretAreEncrypted()) {
        $encryptionProfile = \Drupal::service('ibm_apim.utils')->loadEncryptionProfile('socialblock');
        $encryptionService = \Drupal::service('encryption');
        $client_secret = $encryptionService->decrypt($client_secret, $encryptionProfile);
      }

      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__). NULL;
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
      } catch (Throwable $e) {
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
   * @return mixed
   */
  public function isAccountApprovalsEnabled() {
    return $this->state->get('ibm_apim.accountApprovalEnabled', FALSE);
  }

  /**
   * @return bool
   */
  public function isConsumerOrgInvitationEnabled(): bool {
    return $this->state->get('ibm_apim.consumerOrgInvitationEnabled', TRUE);
  }

  /**
   * @return array
   */
  public function getConsumerOrgInvitationRoles(): array {
    return $this->state->get('ibm_apim.consumerOrgInvitationRoles', ['administrator', 'developer', 'viewer']);
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

  /**
   * This function allows the changing of the encryption key and so re-encrypts all the data in the socialblock encryption profile
   *
   * @param $newClientId
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateEncryptionKey($newKeyName, $IDAndSecretAreAlreadyEncrypted): bool {

    $profileName = $IDAndSecretAreAlreadyEncrypted ? 'socialblockprevious' :  'socialblock';
    $encryptionProfile = \Drupal::service('ibm_apim.utils')->loadEncryptionProfile($profileName);
    $encryptionService = \Drupal::service('encryption');

    // update site_client_id and secret
    $client_id = $this->state->get('ibm_apim.site_client_id');
    $client_secret = $this->state->get('ibm_apim.site_client_secret');

    if ($IDAndSecretAreAlreadyEncrypted) {
      try {
        $client_id = $encryptionService->decrypt($client_id, $encryptionProfile);
        $client_secret = $encryptionService->decrypt($client_secret, $encryptionProfile);
      } catch (Throwable $e) {
        Drush::output()->writeln('Could not decrypt client id and secret with this key (message: ' . $e->getMessage() . ') Will try the next one.');
        return FALSE;
      }

      if ($client_id === NULL || $client_secret === NULL) {
        Drush::output()->writeln('Could not decrypt client id and secret with this key. Will try the next one');
        return FALSE;
      }
    }

    // decrypt socialblock config
    $socialBlockConfig = \Drupal::config('socialblock.settings');
    $data = $socialBlockConfig->get('credentials');
    if (!empty($data)){
      try {
        $unencryptedData = $encryptionService->decrypt($data, $encryptionProfile);
      } catch (Throwable $e) {
        Drush::output()->writeln('Could not decrypt socialblock.settings with this key (message: ' . $e->getMessage() . ') Will try the next one.');
        return FALSE;
      }

      if ($unencryptedData === NULL) {
        Drush::output()->writeln('Could not decrypt credentials with this key. Will try the next one');
        return FALSE;
      }
      $socialBlockSettings = unserialize($unencryptedData, ['allowed_classes' => FALSE]);
    }

    // decrypt payment methods
    $decryptedPayments = [];
    if ($this->moduleHandler->moduleExists('consumerorg')) {
      $ibmApimConfig = \Drupal::config('ibm_apim.settings');
      $paymentEncryptionProfileName = $ibmApimConfig->get('payment_method_encryption_profile');
      // only need to do the decryption / re-encryption if using our socialblock profile
      if (($paymentEncryptionProfileName === 'socialblock') && \Drupal::database()->schema() !== NULL && \Drupal::database()
          ->schema()
          ->tableExists("consumerorg_payment_methods")) {
        $query = \Drupal::entityQuery('consumerorg_payment_method');
        $queryIds = $query->accessCheck()->execute();

        foreach (array_chunk($queryIds, 50) as $chunk) {
          $paymentEntities = \Drupal::entityTypeManager()->getStorage('consumerorg_payment_method')->loadMultiple($chunk);
          if (!empty($paymentEntities)) {
            foreach ($paymentEntities as $paymentEntity) {
              try {
                $unencryptedData = $encryptionService->decrypt($paymentEntity->configuration(), $encryptionProfile);
              } catch (Throwable $e) {
                Drush::output()->writeln('Could not decrypt paymentEntities with this key (message: ' . $e->getMessage() . ') Will try the next one.');
                return FALSE;
              }

              if ($unencryptedData === NULL) {
                Drush::output()->writeln('Could not decrypt paymentEntities with this key. Will try the next one');
                return FALSE;
              }
              $configuration = unserialize($unencryptedData, ['allowed_classes' => FALSE]);
              $decryptedPayments[$paymentEntity->id()] = $configuration;
            }
          }
        }
      }
    }

    if (! $IDAndSecretAreAlreadyEncrypted) {
      \Drupal::service('ibm_apim.utils')->createEncryptionProfile('socialblock', $newKeyName);
    }

    $encryptionProfile = \Drupal::service('ibm_apim.utils')->loadEncryptionProfile('socialblock');

    // re-encrypt client id and secret
    $this->state->set('ibm_apim.site_client_id', $encryptionService->encrypt($client_id, $encryptionProfile));
    $this->state->set('ibm_apim.site_client_secret', $encryptionService->encrypt($client_secret, $encryptionProfile));
    $this->state->delete('ibm_apim.client_id'); // This used to be accidentally created by the scripts

    // re-encrypt socialblock config
    if (isset($socialBlockSettings)) {
      $encryptedSocialBlockSettings = $encryptionService->encrypt(serialize($socialBlockSettings), $encryptionProfile);
      $this->configFactory->getEditable('socialblock.settings')->set('credentials', $encryptedSocialBlockSettings)->save();
    }

    // re-encrypt payment methods
    if (!empty($decryptedPayments) && $this->moduleHandler->moduleExists('consumerorg') && \Drupal::database()
        ->schema() !== NULL && \Drupal::database()->schema()->tableExists("consumerorg_payment_methods")) {
      foreach ($decryptedPayments as $id => $encryptedConfiguration) {
        $configuration = $encryptionService->encrypt(serialize($encryptedConfiguration), $encryptionProfile);
        $query = \Drupal::entityQuery('consumerorg_payment_method');
        $query->condition('id', $id);
        $queryIds = $query->accessCheck()->execute();
        if (isset($queryIds) && !empty($queryIds)) {
          $entityId = array_shift($queryIds);
          $paymentMethodEntity = \Drupal\consumerorg\Entity\PaymentMethod::load($entityId);
          if ($paymentMethodEntity !== NULL) {
            $paymentMethodEntity->set('configuration', $configuration);
            $paymentMethodEntity->save();
          }
        }
      }
    }

    return TRUE;
  }

    /**
   * This function allows the changing of the encryption key and so re-encrypts all the data in the socialblock encryption profile
   *
   * @param $newClientId
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function resetEncryption($newKeyName, $IDAndSecretAreAlreadyEncrypted): void {

    Drush::output()->writeln('ERROR: Failed to decrypt socialblock and payment data. Zeroing it out.');

    // find all payment methods
    $deleteThesePaymentEntities = [];
    if ($this->moduleHandler->moduleExists('consumerorg')) {
      $ibmApimConfig = \Drupal::config('ibm_apim.settings');
      $paymentEncryptionProfileName = $ibmApimConfig->get('payment_method_encryption_profile');
      // only need to do the decryption / re-encryption if using our socialblock profile
      if (($paymentEncryptionProfileName === 'socialblock') && \Drupal::database()->schema() !== NULL && \Drupal::database()
          ->schema()
          ->tableExists("consumerorg_payment_methods")) {
        $query = \Drupal::entityQuery('consumerorg_payment_method');
        $queryIds = $query->accessCheck()->execute();

        foreach (array_chunk($queryIds, 50) as $chunk) {
          $paymentEntities = \Drupal::entityTypeManager()->getStorage('consumerorg_payment_method')->loadMultiple($chunk);
          if (!empty($paymentEntities)) {
            foreach ($paymentEntities as $paymentEntity) {
              $deleteThesePaymentEntities[$paymentEntity->id()] = TRUE;
            }
          }
        }
      }
    }

    // Update encryption profile
    if (! $IDAndSecretAreAlreadyEncrypted) {
      \Drupal::service('ibm_apim.utils')->createEncryptionProfile('socialblock', $newKeyName);
    }

    $encryptionService = \Drupal::service('encryption');
    $encryptionProfile = \Drupal::service('ibm_apim.utils')->loadEncryptionProfile('socialblock');

    // blank client id and secret
    $this->state->set('ibm_apim.site_client_id', $encryptionService->encrypt('', $encryptionProfile));
    $this->state->set('ibm_apim.site_client_secret', $encryptionService->encrypt('', $encryptionProfile));
    $this->state->delete('ibm_apim.client_id'); // This used to be accidentally created by the scripts

    // blank socialblock config
    $socialBlockConfig = \Drupal::config('socialblock.settings');
    $data = $socialBlockConfig->get('credentials');
    if (!empty($data)){
      $encryptedSocialBlockSettings = $encryptionService->encrypt(serialize([]), $encryptionProfile);
      $this->configFactory->getEditable('socialblock.settings')->set('credentials', $encryptedSocialBlockSettings)->save();
    }

    // delete payment methods
    if (!empty($deleteThesePaymentEntities) && $this->moduleHandler->moduleExists('consumerorg') && \Drupal::database()
        ->schema() !== NULL && \Drupal::database()->schema()->tableExists("consumerorg_payment_methods")) {
      foreach ($deleteThesePaymentEntities as $id => $deleteMe) {
        $query = \Drupal::entityQuery('consumerorg_payment_method');
        $query->condition('id', $id);
        $queryIds = $query->accessCheck()->execute();
        if (isset($queryIds) && !empty($queryIds)) {
          $entityId = array_shift($queryIds);
          $paymentMethodEntity = \Drupal\consumerorg\Entity\PaymentMethod::load($entityId);
          if ($paymentMethodEntity !== NULL) {
            $paymentMethodEntity->delete();
          }
        }
      }
    }
  }

  /**
   * Called when we detect we're running in IBM Cloud
   */
  public function setRunningInIBMCloud(): void {
    if ($this->isInCloud()) {
      $this->disableIPSecurityFeatures();
    }
  }

  /**
   * Disable all IP based Security Features if running in IBM Cloud
   */
  public function disableIPSecurityFeatures(): void {
    $modulesToRemove = ['honeypot', 'restrict_by_ip', 'perimeter'];
    foreach ($modulesToRemove as $modToRemove) {
      if ($this->moduleHandler->moduleExists($modToRemove)) {
        $this->logger->notice('Module %module found incompatible with IBM Cloud. Forcing uninstall.', ['%module' => $modToRemove]);
        // uninstalling module but not dependencies.
        try {
          $this->moduleInstaller->uninstall([$modToRemove], TRUE);
        } catch (Throwable $e) {
          $this->logger->warning('Exception while deleting IBM Cloud incompatible module: %module', ['%module' => $modToRemove]);
          $this->logger->debug('%module uninstall exception message : %message', [
            '%module' => $modToRemove,
            '%message' => $e->getMessage(),
          ]);
          // don't stop processing because uninstall of one module has failed.
        } finally {
          $this->logger->notice('Module %module found incompatible with IBM Cloud. Forced uninstall complete.', ['%module' => $modToRemove]);
        }
      }
    }

    // disable the flood IP based rules
    $this->configFactory->getEditable('user.flood')->set('ip_window', 0)->set('ip_limit', 1000)->save();
  }

  /**
   * Return an array of blocklisted modules for this platform
   */
  public function getBlockList(): array {
    $fileName = \Drupal::service('extension.list.module')->getPath('ibm_apim') . '/module_blocklist.json';
    if (file_exists($fileName)) {
      $contents = file_get_contents($fileName);
      $json = json_decode($contents, TRUE);
      return $json["modules"];
    }
    return [];
  }

  /**
   * Return an array of shipped modules for this platform
   *
   * @return array
   */
  public function getShippedModules(): array {
    $fileName = DRUPAL_ROOT . '/original_modules';
    if (file_exists($fileName)) {
      $shippedModules = file($fileName);
      $shippedModules = array_map('trim', $shippedModules);
      return $shippedModules;
    }
    return [];
  }


  public function getInvitationTTL() {
    return $this->state->get('ibm_apim.site_config')["invitation_ttl"];
  }

  public function getSelfServiceOnboardingTTL() {
    $siteConfig = $this->state->get('ibm_apim.site_config');
    if (!empty($siteConfig)) {
      return $siteConfig["consumer_self_service_onboarding_ttl"];
    }

    return '';
  }


  /**
   * Returns an array of modules that are shipped and also installed as a custom module
   *
   * @return array
  */
  public function getModulesBothShippedAndCustomInstalled(): array {
    $doubleInstalledModules = [];
    $shippedModules = $this->getShippedModules();
    $machineNameMap = \Drupal::service('ibm_apim.utils')->mapCustomDirectoriesToMachineName('module');
    foreach ($machineNameMap as $moduleDir => $machineName) {
      if (in_array($machineName, $shippedModules)) {
        array_push($doubleInstalledModules, $machineName);
      }
    }
    return $doubleInstalledModules;
  }

}
