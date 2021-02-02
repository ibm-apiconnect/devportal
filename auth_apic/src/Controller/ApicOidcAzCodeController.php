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

namespace Drupal\auth_apic\Controller;

use Drupal\auth_apic\Service\Interfaces\OidcStateServiceInterface;
use Drupal\auth_apic\UserManagement\ApicLoginServiceInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\ApimUtils;
use Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface;
use Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface;
use Drupal\ibm_apim\Service\SiteConfig;
use Drupal\ibm_apim\Service\Utils;
use Drupal\session_based_temp_store\SessionBasedTempStoreFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class ApicOidcAzCodeController extends ControllerBase {
  use StringTranslationTrait;

  /**
   * @var \Drupal\ibm_apim\Service\Utils
   */
  protected $utils;

  /**
   * @var \Drupal\auth_apic\UserManagement\ApicLoginServiceInterface
   */
  protected $loginService;

  /**
   * @var \Drupal\auth_apic\Service\Interfaces\OidcStateServiceInterface
   */
  protected $oidcStateService;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface
   */
  protected $userRegistryService;

  /**
   * @var \Drupal\ibm_apim\Service\ApimUtils
   */
  protected $apimUtils;

  /**
   * @var \Drupal\ibm_apim\Service\SiteConfig
   */
  protected $siteConfig;

  /**
   * @var \Psr\Log\LoggerInterface;
   */
  protected $logger;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  protected $authApicSessionStore;

  protected $requestService;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface
   */
  protected $mgmtServer;

  public function __construct(
    Utils $utils,
    ApicLoginServiceInterface $login_service,
    OidcStateServiceInterface $oidc_state_service,
    UserRegistryServiceInterface $user_registry_service,
    ApimUtils $apim_utils,
    SiteConfig $site_config,
    LoggerInterface $logger,
    SessionBasedTempStoreFactory $sessionStoreFactory,
    RequestStack $request_service,
    ManagementServerInterface $mgmtServer,
    Messenger $messenger
  ) {
    $this->apimUtils = $apim_utils;
    $this->siteConfig = $site_config;
    $this->logger = $logger;
    $this->utils = $utils;
    $this->userRegistryService = $user_registry_service;
    $this->loginService = $login_service;
    $this->oidcStateService = $oidc_state_service;
    $this->authApicSessionStore = $sessionStoreFactory->get('auth_apic_invitation_token');
    $this->requestService = $request_service;
    $this->mgmtServer = $mgmtServer;
    $this->messenger = $messenger;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ibm_apim.utils'),
      $container->get('auth_apic.login'),
      $container->get('auth_apic.oidc_state'),
      $container->get('ibm_apim.user_registry'),
      $container->get('ibm_apim.apim_utils'),
      $container->get('ibm_apim.site_config'),
      $container->get('logger.channel.auth_apic'),
      $container->get('session_based_temp_store'),
      $container->get('request_stack'),
      $container->get('ibm_apim.mgmtserver'),
      $container->get('messenger')
    );
  }

  // This handles the redirect from APIM to authenticate as part of normal OIDC flow
  // It will look something like this:
  // https://portal.apimdev1084.hursley.ibm.com/demo/dev/ibm_apim/oauth2/redirect?
  //   code=601e0142-55c2-406e-98e3-10ba1fa3f2e8
  //   &state=YToxOntzOjEyOiJyZWdpc3RyeV91cmwiO3M6NjY6Ii9jb25zdW1lci1hcGkvdXNlci1yZWdpc3RyaWVzL2ZmZDdkMGQ3LTViZTUtNDc3ZC1hYjdkLWFlZDAwNjFlZGJiMCI7fQ,,
  // There could also be an error in a response from APIM, which we need to handle
  public function processOidcRedirect(): ?\Symfony\Component\HttpFoundation\RedirectResponse {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__);
    }
    $redirectLocation = $this->validateOidcRedirect();
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    return $this->redirect($redirectLocation);
  }

  public function validateOidcRedirect() {
    $error = $this->requestService->getCurrentRequest()->query->get('error');
    if (!empty($error)) {
      $errordes = $this->requestService->getCurrentRequest()->query->get('error_description');
      $this->messenger->addError($this->t('Error while authenticating user. Please contact your system administrator. Error: ' . $error . ' Description: ' . $errordes));
      $this->logger->error('validateOidcRedirect error: ' . $errordes);
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }
    $authCode = $this->requestService->getCurrentRequest()->query->get('code');
    if (empty($authCode)) {
      $this->messenger->addError($this->t('Error: Missing authorization code parameter. Contact your system administrator.'));
      $this->logger->error('validateOidcRedirect error: Missing authorization code parameter');
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }
    $state = $this->requestService->getCurrentRequest()->query->get('state');
    if (empty($state)) {
      $this->messenger->addError($this->t('Error: Missing state parameter. Contact your system administrator.'));
      $this->logger->error('validateOidcRedirect error: Missing state parameter');
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }

    $stateReceived = unserialize($this->utils->base64_url_decode($state), ['allowed_classes' => FALSE]);
    $stateObj = $this->oidcStateService->get($stateReceived);
    // key to retrieve what we need from state.
    if (isset($stateObj)) {
      $this->oidcStateService->delete($stateReceived);
      // Clear the JWT from the session as we're done with it now
      $this->authApicSessionStore->delete('invitation_object');
      
      $redirect_location = $this->loginService->loginViaAzCode($authCode, $stateObj['registry_url']);
      if ($redirect_location === 'ERROR') {
        $this->messenger->addError($this->t('Error while authenticating user. Please contact your system administrator.'));
        $redirect_location = '<front>';
      } else if ($this->authApicSessionStore->get('redirect_to')) {
        $this->requestService->getCurrentRequest()->query->set('destination', $this->authApicSessionStore->get('redirect_to'));
        $this->authApicSessionStore->delete('redirect_to');
      }

      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return $redirect_location;
    } else {
      $this->messenger->addError($this->t('Error while authenticating user. Please contact your system administrator.'));
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }
  }
}