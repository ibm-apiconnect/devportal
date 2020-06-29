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

namespace Drupal\auth_apic\Controller;

use Drupal\auth_apic\Service\Interfaces\OidcStateServiceInterface;
use Drupal\auth_apic\UserManagement\ApicLoginServiceInterface;
use Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\ibm_apim\Service\ApimUtils;
use Drupal\ibm_apim\Service\SiteConfig;
use Drupal\ibm_apim\Service\Utils;
use Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface;
use Drupal\Core\Url;
use Drupal\session_based_temp_store\SessionBasedTempStoreFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ApicOidcAzCodeController extends ControllerBase {

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
   * @var
   */
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
    ManagementServerInterface $mgmtServer
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
      $container->get('ibm_apim.mgmtserver')
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
      if (function_exists('drupal_set_message')) {
        drupal_set_message(t('Error while authenticating user. Please contact your system administrator. Error: ' . $error . ' Description: ' . $errordes), 'error');
      }
      $this->logger->error('validateOidcRedirect error: ' . $errordes);
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }
    $authCode = $this->requestService->getCurrentRequest()->query->get('code');
    if (empty($authCode)) {
      if (function_exists('drupal_set_message')) {
        drupal_set_message(t('Error: Missing authorization code parameter. Contact your system administrator.'), 'error');
      }
      $this->logger->error('validateOidcRedirect error: Missing authorization code parameter');
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }
    $state = $this->requestService->getCurrentRequest()->query->get('state');
    if (empty($state)) {
      if (function_exists('drupal_set_message')) {
        drupal_set_message(t('Error: Missing state parameter. Contact your system administrator.'), 'error');
      }
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
        if (function_exists('drupal_set_message')) {
          drupal_set_message(t('Error while authenticating user. Please contact your system administrator.'), 'error');
        }
        $redirect_location = '<front>';
      }
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return $redirect_location;
    } else {
      if (function_exists('drupal_set_message')) {
        drupal_set_message(t('Error while authenticating user. Please contact your system administrator.'), 'error');
      }
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }
  }

  // This handles a redirect from Google to proxy on to APIM as part of nested flow
  // It will look something like this:
  // https://portal.apimdev1084.hursley.ibm.com/demo/dev/ibm_apim/oidcredirect?
  //   state=601e0142-55c2-406e-98e3-10ba1fa3f2e8
  //   &code=4/sQH7tb-Rtu808KTGGSSTcodZRYQ2JntVql-DlqhLg9gX7BUTHsvd71KI15Lvahn3SM-_J_nAWPHuIsBu8YHq6M0
  //   &scope=email+profile+openid+https://www.googleapis.com/auth/userinfo.profile+https://www.googleapis.com/auth/userinfo.email
  //   &authuser=0
  //   &session_state=56b448705c0ddec28f20b0d36c34fd109e58661a..f13a
  //   &prompt=none
  // There could also be an error in a response from APIM, which we need to handle
  public function processApimOidcRedirect(): ?\Symfony\Component\HttpFoundation\RedirectResponse {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__);
    }
    $redirectLocation = $this->validateApimOidcRedirect();
    if ($redirectLocation === '<front>') {
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return $this->redirect($redirectLocation);
    } else {
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return new TrustedRedirectResponse($redirectLocation, 302);
    }
  }

  public function validateApimOidcRedirect() {
    $error = $this->requestService->getCurrentRequest()->query->get('error');
    if (!empty($error)) {
      $errordes = $this->requestService->getCurrentRequest()->query->get('error_description');
      if (function_exists('drupal_set_message')) {
        drupal_set_message(t('Error while authenticating user. Please contact your system administrator. Error: ' . $error . ' Description: ' . $errordes), 'error');
      }
      $this->logger->error('validateApimOidcRedirect error: ' . $errordes);

      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }
    $state = $this->requestService->getCurrentRequest()->query->get('state');
    if (empty($state)) {
      if (function_exists('drupal_set_message')) {
        drupal_set_message(t('Error: Missing state parameter. Contact your system administrator.'), 'error');
      }
      $this->logger->error('validateApimOidcRedirect error: Missing state parameter');

      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }
    $authCode = $this->requestService->getCurrentRequest()->query->get('code');
    if (empty($authCode)) {
      if (function_exists('drupal_set_message')) {
        drupal_set_message(t('Error: Missing authorization code parameter. Contact your system administrator.'), 'error');
      }
      $this->logger->error('validateApimOidcRedirect error: Missing authorization code parameter');

      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }

    // Pull out the apim state and only use that
    $apimstate = null;
    if (($pos = strpos($state, "_")) !== FALSE) {
      $apimstate = substr($state, $pos + 1);
      $state = substr($state, 0, $pos);
    } else {
      if (function_exists('drupal_set_message')) {
        drupal_set_message(t('Error: Invalid state parameter. Contact your system administrator.'), 'error');
      }
      $this->logger->error('validateApimOidcRedirect error: Invalid state parameter: ' . $state);

      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }
    $stateReceived = unserialize($this->utils->base64_url_decode($state));
    $stateObj = null;
    if (is_string($stateReceived)) {
      $stateObj = $this->oidcStateService->get($stateReceived);
    }
    if (!isset($stateObj)) {
      if (function_exists('drupal_set_message')) {
        drupal_set_message(t('Error: Invalid state parameter. Contact your system administrator.'), 'error');
      }
      $this->logger->error('validateApimOidcRedirect error: Invalid state parameter: ' . $state);

      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }

    $url = '/consumer-api/oauth2/redirect?';
    $parameters = $this->requestService->getCurrentRequest()->query->all();
    unset($parameters['q']);
    $parameters['state'] = $apimstate;
    $url .= http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);

    $response = (array) $this->mgmtServer->get($url);
    if (!isset($response['code'])) {
      $redirect_location = 'ERROR';
      $this->logger->error('validateApimOidcRedirect error: Response code not set');
    } else if ($response['code'] !== 302) {
      $redirect_location = 'ERROR';
      $this->logger->error('validateApimOidcRedirect error: Response code ' . $response['code']);
    } else if (!isset($response['headers']['Location'])) {
      $redirect_location = 'ERROR';
      $this->logger->error('validateApimOidcRedirect error: Location header not set');
    } else {
      $redirect_location = $response['headers']['Location'];
    }

    if ($redirect_location === 'ERROR') {
      if (function_exists('drupal_set_message')) {
        drupal_set_message(t('Error: Error while authenticating user. Contact your system administrator.'), 'error');
      }
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }
    return $redirect_location;
  }

  // Example request:
  // https://portal.apimdev1084.hursley.ibm.com/demo/dev/ibm_apim/oauth2/authorize?
  // client_id=6e0f2b75-ce33-4199-8109-fb33d118216b&
  // state=YToxOntzOjEyOiJyZWdpc3RyeV91cmwiO3M6NjY6Ii9jb25zdW1lci1hcGkvdXNlci1yZWdpc3RyaWVzL2ZmZDdkMGQ3LTViZTUtNDc3ZC1hYjdkLWFlZDAwNjFlZGJiMCI7fQ,,&
  // redirect_uri=https://portal.apimdev1084.hursley.ibm.com/demo/dev/ibm_apim/oidcredirect&
  // realm=consumer:285b1f76-5ff9-490b-a544-80de306e8595:f940cc2e-1663-4a77-8b79-2f5c835719de/google-oidc&
  // response_type=code
  public function processApimOidcAz(): ?\Symfony\Component\HttpFoundation\RedirectResponse {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__);
    }

    $redirectLocation = $this->validateApimOidcAz();
    if ($redirectLocation === '<front>') {
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return $this->redirect($redirectLocation);
    } else {
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return new TrustedRedirectResponse($redirectLocation, 302);
    }
  }

  public function validateApimOidcAz() {
    $clientId = $this->requestService->getCurrentRequest()->query->get('client_id');
    if (empty($clientId)) {
      if (function_exists('drupal_set_message')) {
        drupal_set_message(t('Error: Missing Client ID parameter. Contact your system administrator.'), 'error');
      }
      $this->logger->error('validateApimOidcAz error: Missing client_id parameter');

      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }
    $state = $this->requestService->getCurrentRequest()->query->get('state');
    if (empty($state)) {
      if (function_exists('drupal_set_message')) {
        drupal_set_message(t('Error: Missing state parameter. Contact your system administrator.'), 'error');
      }
      $this->logger->error('validateApimOidcAz error: Missing state parameter');

      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }
    $redirectUri = $this->requestService->getCurrentRequest()->query->get('redirect_uri');
    if (empty($redirectUri)) {
      if (function_exists('drupal_set_message')) {
        drupal_set_message(t('Error: Missing redirect uri parameter. Contact your system administrator.'), 'error');
      }
      $this->logger->error('validateApimOidcAz error: Missing redirect_uri parameter');

      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }
    $realm = $this->requestService->getCurrentRequest()->query->get('realm');
    if (empty($realm)) {
      if (function_exists('drupal_set_message')) {
        drupal_set_message(t('Error: Missing realm parameter. Contact your system administrator.'), 'error');
      }
      $this->logger->error('validateApimOidcAz error: Missing realm parameter');

      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }
    $responseType = $this->requestService->getCurrentRequest()->query->get('response_type');
    if (empty($responseType) || $responseType !== 'code') {
      if (function_exists('drupal_set_message')) {
        drupal_set_message(t('Error: Missing or incorrect response type parameter. Contact your system administrator.'), 'error');
      }
      if (empty($responseType)) {
        $this->logger->error('validateApimOidcAz error: Missing response_type parameter');
      } else {
        $this->logger->error('validateApimOidcAz error: Incorrect response_type parameter: ' . $responseType);
      }
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }

    //Validates the state parameter
    $stateReceived = unserialize($this->utils->base64_url_decode($state));
    $stateObj = null;
    if (is_string($stateReceived)) {
      $stateObj = $this->oidcStateService->get($stateReceived);
    }
    if (!isset($stateObj)) {
      if (function_exists('drupal_set_message')) {
        drupal_set_message(t('Error: Invalid state parameter. Contact your system administrator.'), 'error');
      }
      $this->logger->error('validateApimOidcAz error: Invalid state parameter: ' . $state);

      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }


    $userRegistry = $this->userRegistryService->get($stateObj['registry_url']);
    if (!isset($userRegistry)) {
      if (function_exists('drupal_set_message')) {
        drupal_set_message(t('Error while authenticating user. Please contact your system administrator.'), 'error');
      }
      $this->logger->error('validateApimOidcAz error: Invalid user registry url: ' . $stateObj['registry_url']);

      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }
    if ($realm !== $userRegistry->getRealm()) {
      if (function_exists('drupal_set_message')) {
        drupal_set_message(t('Error while authenticating user. Please contact your system administrator.'), 'error');
      }
      $this->logger->error('validateApimOidcAz error: Invalid realm parameter: ' . $realm . ' does not match ' . $userRegistry->getRealm());

      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }

    if ($clientId !== $this->siteConfig->getClientId()) {
      if (function_exists('drupal_set_message')) {
        drupal_set_message(t('Error: Invalid client_id parameter. Contact your system administrator.'), 'error');
      }
      $this->logger->error('validateApimOidcAz error: Invalid client_id parameter: ' . $clientId . ' does not match ' . $this->siteConfig->getClientId());

      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }
    $route = null;
    if (!isset($GLOBALS['__PHPUNIT_BOOTSTRAP']) && \Drupal::hasContainer()) {
      $route = URL::fromRoute('auth_apic.azcode')->toString(TRUE)->getGeneratedUrl();
    } else {
      $route = '/incorrectRoute';
    }
    $host = $this->apimUtils->getHostUrl();
    $expectedRedirectUri = $host . $route;
    if ($redirectUri !== $expectedRedirectUri && $redirectUri !== '/ibm_apim/oauth2/redirect') {
      if (function_exists('drupal_set_message')) {
        drupal_set_message(t('Error while authenticating user. Please contact your system administrator.'), 'error');
      }
      $this->logger->error('validateApimOidcAz error: Invalid redirect_uri parameter: ' . $redirectUri . ' does not match ' . $expectedRedirectUri);

      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }

    $invitation_object = $this->authApicSessionStore->get('invitation_object');
    $url = '/consumer-api/oauth2/authorize?client_id=' . $clientId .
      '&state=' . $state .
      '&redirect_uri=' . $redirectUri .
      '&realm=' . $realm .
      '&response_type=' . $responseType;
    if (isset($invitation_object)) {
      $url .= '&token=' . $invitation_object->getDecodedJwt();
    }

    $response = (array) $this->mgmtServer->get($url);

    if (!isset($response['code'])) {
      $redirect_location = 'ERROR';
      $this->logger->error('validateApimOidcAz error: Response code not set');
    } else if ($response['code'] !== 302) {
      $redirect_location = 'ERROR';
      $this->logger->error('validateApimOidcAz error: Response code ' . $response['code']);
    } else if (!isset($response['headers']['Location'])) {
      $redirect_location = 'ERROR';
      $this->logger->error('validateApimOidcAz error: Location header not set');
    } else {
      $redirect_location = $response['headers']['Location'];
    }

    if ($redirect_location === 'ERROR') {
      if (function_exists('drupal_set_message')) {
        drupal_set_message(t('Error: Error while authenticating user. Contact your system administrator.'), 'error');
      }
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }

    // The redirect from APIM will look like this:
    // https://accounts.google.com/o/oauth2/v2/auth?
    //   scope=openid%20profile%20email
    //   &response_type=code
    //   &client_id=562139354078-ll3m8sk17u8q2tsbudikt6r6emhjik4j.apps.googleusercontent.com
    //   &state=601e0142-55c2-406e-98e3-10ba1fa3f2e8
    //   &redirect_uri=https://apimdev1083.hursley.ibm.com/consumer-api/oidcredirect
    $url_array = parse_url($redirect_location);
    if (!empty($url_array['query'])) {
      parse_str($url_array['query'], $query_array);
      if (!isset($GLOBALS['__PHPUNIT_BOOTSTRAP']) && \Drupal::hasContainer()) {
        $query_array['redirect_uri'] = $host . URL::fromRoute('auth_apic.azredir')->toString(TRUE)->getGeneratedUrl();
      } else {
        $query_array['redirect_uri'] = $host . '/route';
      }
      if (isset($url_array['port'])) {
        $fixed_redirect = $url_array['scheme'] . '://' . $url_array['host']. ':' .$url_array['port'] . $url_array['path'] . '?' . http_build_query($query_array, '', '&', PHP_QUERY_RFC3986);
      } else {
        $fixed_redirect = $url_array['scheme'] . '://' . $url_array['host'] . $url_array['path'] . '?' . http_build_query($query_array, '', '&', PHP_QUERY_RFC3986);
      }
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return $fixed_redirect;
    } else {
      if (function_exists('drupal_set_message')) {
        drupal_set_message(t('Error while authenticating user. Please contact your system administrator.'), 'error');
      }
      $this->logger->error('validateApimOidcAz error: Failed to parse redirect: ' . $redirect_location);

      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }
  }
}
