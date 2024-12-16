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

namespace Drupal\auth_apic\Service;

use Drupal\auth_apic\JWTToken;
use Drupal\auth_apic\Service\Interfaces\OidcRegistryServiceInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\ibm_apim\ApicType\UserRegistry;
use Drupal\ibm_apim\Service\ApimUtils;
use Drupal\ibm_apim\Service\Utils;
use Drupal\ibm_apim\Service\SiteConfig;
use Psr\Log\LoggerInterface;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\encrypt\EncryptServiceInterface;
use Drupal\encrypt\EncryptionProfileManagerInterface;
use Drupal\Component\Datetime\Time;

/**
 * Provide consistent information about the provider type of oidc registries
 *
 * Class OidcRegistryService
 *
 * @package Drupal\auth_apic\Service
 */
class OidcRegistryService implements OidcRegistryServiceInterface {

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * @var \Drupal\ibm_apim\Service\Utils
   */
  protected Utils $utils;

  /**
   * @var \Drupal\ibm_apim\Service\ApimUtils
   */
  protected ApimUtils $apimUtils;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected PrivateTempStore $authApicSessionStore;

  /**
   * @var \Drupal\Component\Datetime\Time
   */
  protected Time $time;

  /**
   * @var Drupal\encrypt\EncryptServiceInterface
   */
  protected EncryptServiceInterface $encryption;

  /**
   * @var \Drupal\encrypt\EncryptionProfileManagerInterfacee
   */
  protected EncryptionProfileManagerInterface $profileManager;

  /**
   * @var Drupal\ibm_apim\Service\SiteConfig
   */
  protected SiteConfig $siteConfig;

  public function __construct(StateInterface $state,
                              LoggerInterface $logger,
                              Utils $utils,
                              ApimUtils $apim_utils,
                              ModuleHandlerInterface $module_handler,
                              PrivateTempStoreFactory $sessionStoreFactory,
                              Time $time,
                              EncryptServiceInterface $encryption,
                              EncryptionProfileManagerInterface $profileManager,
                              SiteConfig $siteConfig
                              ) {
    $this->state = $state;
    $this->state = $state;
    $this->logger = $logger;
    $this->utils = $utils;
    $this->apimUtils = $apim_utils;
    $this->moduleHandler = $module_handler;
    $this->authApicSessionStore = $sessionStoreFactory->get('auth_apic_storage');
    $this->time = $time;
    $this->encryption = $encryption;
    $this->profileManager = $profileManager;
    $this->siteConfig = $siteConfig;
  }

  /**
   * @param \Drupal\ibm_apim\ApicType\UserRegistry $registry
   * @param \Drupal\auth_apic\JWTToken|null $invitation_object
   *
   * @return array|null
   */
  public function getOidcMetadata(UserRegistry $registry, JWTToken $invitation_object = NULL): ?array {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    if ($registry !== NULL && $registry->getRegistryType() !== 'oidc') {
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, 'non oidc registry');
      }
      $this->logger->warning('attempt to get metadata from non-oidc registry');
      return NULL;
    }
    $info = [];
    $info['az_url'] = $this->getOidcUrl($registry, $invitation_object);
    $info['image'] = $this->getImageAsSvg($registry);

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    return $info;
  }

  /**
   * Get authorization url for oidc registry.
   * That is the url on apim to initiate the oidc dance.
   *
   * @param \Drupal\ibm_apim\ApicType\UserRegistry $registry
   * @param \Drupal\auth_apic\JWTToken|NULL $invitation_object
   *
   * @return string|null
   * @throws \Drupal\encrypt\Exception\EncryptException
   */
  private function getOidcUrl(UserRegistry $registry, JWTToken $invitation_object = NULL): ?string {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $client_id = $this->siteConfig->getClientId();

    if ($client_id === NULL) {
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, 'no client id');
      }
      $this->logger->warning('unable to retrieve site client id to build oidc authentication url');
      return NULL;
    }

    $state_obj = [];
    $state_obj['registry_url'] = $registry->getUrl();

    if (isset($invitation_object)) {
      // add invitation information to state object (potentially sensitive)
      $state_obj['invitation_object'] = serialize($invitation_object);
      $state_obj['created'] = time();
    }
    $key = $this->time->getCurrentTime() . ':' . $state_obj['registry_url'];
    $encrypted_key = $this->encryption->encrypt($key, $this->profileManager->getEncryptionProfile('socialblock'));
    $encrypted_data = $this->encryption->encrypt(serialize($state_obj), $this->profileManager->getEncryptionProfile('socialblock'));

    $this->authApicSessionStore->set($key, $encrypted_data);
    $state_param = $this->utils->base64_url_encode(serialize($encrypted_key));

    $host = $this->apimUtils->getHostUrl();

    if (!isset($GLOBALS['__PHPUNIT_BOOTSTRAP']) && \Drupal::hasContainer()) {
      $route = URL::fromRoute('auth_apic.azcode')->toString();
    }
    else {
      $route = '/test/env';
    }

    $redirect_uri = $host . $route;

    if ($registry->isRedirectEnabled()) {
      $url = $host . URL::fromRoute('auth_apic.az')->toString();
    }
    else {
      $url = $this->apimUtils->getOidcRedirectEndpoint('/consumer-api/oauth2/authorize');
    }

    $url .= '?client_id=' . $client_id;
    $url .= '&state=' . $state_param;
    $url .= '&redirect_uri=' . $redirect_uri;
    $url .= '&realm=' . $registry->getRealm(); // TODO: need to support multiple IDPS.
    $url .= '&response_type=code';
    if (isset($invitation_object)) {
      $url .= '&token=' . $invitation_object->getDecodedJwt();
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $url);
    }
    return $url;
  }

  /**
   * Get image to show as icon in forms as svg.
   * Attempts to match on a provider type, or returns a default image.
   *
   * @param \Drupal\ibm_apim\ApicType\UserRegistry $registry
   *
   * @return array|null
   */
  private function getImageAsSvg(UserRegistry $registry): ?array {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    //default image
    $image['html'] = '<svg width="18" height="18" viewBox="0 0 32 32" fill-rule="evenodd">
      <path d="M16 6.4c3.9 0 7 3.1 7 7s-3.1 7-7 7-7-3.1-7-7 3.1-7 7-7zm0-2c-5 0-9 4-9 9s4 9 9 9 9-4 9-9-4-9-9-9z"></path>
      <path d="M16 0C7.2 0 0 7.2 0 16s7.2 16 16 16 16-7.2 16-16S24.8 0 16 0zm7.3 24.3H8.7c-1.2 0-2.2.5-2.8 1.3C3.5 23.1 2 19.7 2 16 2 8.3 8.3 2 16 2s14 6.3 14 14c0 3.7-1.5 7.1-3.9 9.6-.6-.8-1.7-1.3-2.8-1.3z"></path>
      </svg>';

    if ($this->moduleHandler->moduleExists('social_media_links')) {
      switch ($registry->getProviderType()) {
        case 'facebook':
          $image['html'] = '<i class="fa fa-brands fa-facebook-f" aria-hidden="true" style="font-size: 18px;"></i>';
          $image['class'] = 'fa-facebook';
          break;
        case 'slack':
          $image['html'] = '<i class="fa fa-brands fa-slack" aria-hidden="true" style="font-size: 19px;"></i>';
          $image['class'] = 'fa-slack';
          break;
        case 'twitter':
          $image['html'] = '<i class="fa fa-brands fa-twitter" aria-hidden="true" style="font-size: 19px;"></i>';
          $image['class'] = 'fa-twitter';
          break;
        case 'windows_live':
          $image['html'] = '<i class="fa fa-brands fa-windows" aria-hidden="true" style="font-size: 17px;"></i>';
          $image['class'] = 'fa-windows';
          break;
        case 'linkedin':
          $image['html'] = '<i class="fa fa-brands fa-linkedin" aria-hidden="true" style="font-size: 20px;"></i>';
          $image['class'] = 'fa-linkedin-square';
          break;
        case 'google':
          $image['html'] = '<i class="fa fa-brands fa-google" aria-hidden="true" style="font-size: 18px;"></i>';
          $image['class'] = 'fa-google';
          break;
        case 'github':
          $image['html'] = '<i class="fa fa-brands fa-github" aria-hidden="true" style="font-size: 21px;"></i>';
          $image['class'] = 'fa-github';
          break;
      }
    }
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $registry->getProviderType());
    }
    return $image;

  }


}
