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

namespace Drupal\auth_apic\Controller;

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
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\encrypt\EncryptServiceInterface;
use Drupal\encrypt\EncryptionProfileManagerInterface;

class ApicOidcAzCodeController extends ControllerBase {

  use StringTranslationTrait;

  /**
   * @var \Drupal\ibm_apim\Service\Utils
   */
  protected Utils $utils;

  /**
   * @var \Drupal\auth_apic\UserManagement\ApicLoginServiceInterface
   */
  protected ApicLoginServiceInterface $loginService;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface
   */
  protected UserRegistryServiceInterface $userRegistryService;

  /**
   * @var \Drupal\ibm_apim\Service\ApimUtils
   */
  protected ApimUtils $apimUtils;

  /**
   * @var \Drupal\ibm_apim\Service\SiteConfig
   */
  protected SiteConfig $siteConfig;

  /**
   * @var \Psr\Log\LoggerInterface;
   */
  protected LoggerInterface $logger;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected PrivateTempStore $authApicSessionStore;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected RequestStack $requestService;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface
   */
  protected ManagementServerInterface $mgmtServer;

  /**
   * @var Drupal\encrypt\EncryptServiceInterface
   */
  protected EncryptServiceInterface $encryption;

  /**
   * @var \Drupal\encrypt\EncryptionProfileManagerInterface
   */
  protected EncryptionProfileManagerInterface $profileManager;

  /**
   * ApicOidcAzCodeController constructor.
   *
   * @param \Drupal\ibm_apim\Service\Utils $utils
   * @param \Drupal\auth_apic\UserManagement\ApicLoginServiceInterface $login_service
   * @param \Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface $user_registry_service
   * @param \Drupal\ibm_apim\Service\ApimUtils $apim_utils
   * @param \Drupal\ibm_apim\Service\SiteConfig $site_config
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $sessionStoreFactory
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_service
   * @param \Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface $mgmtServer
   * @param \Drupal\Core\Messenger\Messenger $messenger
   */
  public function __construct(
    Utils                        $utils,
    ApicLoginServiceInterface    $login_service,
    UserRegistryServiceInterface $user_registry_service,
    ApimUtils                    $apim_utils,
    SiteConfig                   $site_config,
    LoggerInterface              $logger,
    PrivateTempStoreFactory      $sessionStoreFactory,
    RequestStack                 $request_service,
    ManagementServerInterface    $mgmtServer,
    Messenger                    $messenger,
    EncryptServiceInterface $encryption,
    EncryptionProfileManagerInterface $profileManager,
  ) {
    $this->apimUtils = $apim_utils;
    $this->siteConfig = $site_config;
    $this->logger = $logger;
    $this->utils = $utils;
    $this->userRegistryService = $user_registry_service;
    $this->loginService = $login_service;
    $this->authApicSessionStore = $sessionStoreFactory->get('auth_apic_storage');
    $this->requestService = $request_service;
    $this->mgmtServer = $mgmtServer;
    $this->messenger = $messenger;
    $this->encryption = $encryption;
    $this->profileManager = $profileManager;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return \Drupal\auth_apic\Controller\ApicOidcAzCodeController|static
   */
  public static function create(ContainerInterface $container): ApicOidcAzCodeController {
    /** @noinspection PhpParamsInspection */
    return new static(
      $container->get('ibm_apim.utils'),
      $container->get('auth_apic.login'),
      $container->get('ibm_apim.user_registry'),
      $container->get('ibm_apim.apim_utils'),
      $container->get('ibm_apim.site_config'),
      $container->get('logger.channel.auth_apic'),
      $container->get('tempstore.private'),
      $container->get('request_stack'),
      $container->get('ibm_apim.mgmtserver'),
      $container->get('messenger'),
      $container->get('encryption'),
      $container->get('encrypt.encryption_profile.manager')
    );
  }

  // This handles the redirect from APIM to authenticate as part of normal OIDC flow
  // It will look something like this:
  // https://portal.apimdev1084.hursley.ibm.com/demo/dev/ibm_apim/oauth2/redirect?
  //   code=601e0142-55c2-406e-98e3-10ba1fa3f2e8
  //   &state=YToxOntzOjEyOiJyZWdpc3RyeV91cmwiO3M6NjY6Ii9jb25zdW1lci1hcGkvdXNlci1yZWdpc3RyaWVzL2ZmZDdkMGQ3LTViZTUtNDc3ZC1hYjdkLWFlZDAwNjFlZGJiMCI7fQ,,
  // There could also be an error in a response from APIM, which we need to handle
  /**
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
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

  /**
   * @return string|null
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function validateOidcRedirect(): ?string {
    $error = $this->requestService->getCurrentRequest()->query->get('error');
    if (!empty($error)) {
      $errordes = $this->requestService->getCurrentRequest()->query->get('error_description');
      $this->messenger->addError($this->t('Error while authenticating user. Please contact your system administrator. Error: @error Description: @errordes', [
        '@error' => $error,
        '@errordes' => $errordes,
      ]));
      $this->logger->error('validateOidcRedirect error: @errordes', ['@errordes' => $errordes]);
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
    try {
      $decrypted_key = $this->encryption->decrypt($stateReceived, $this->profileManager->getEncryptionProfile('socialblock'));
      $encrypted_data = $this->authApicSessionStore->get($decrypted_key);
    } catch (\Exception $e) {
      $this->logger->error('validateOidcRedirect error: Failed to decrypt state. @errordes', ['@errordes' => $stateReceived]);
    }
    // key to retrieve what we need from state.
    if (isset($encrypted_data)) {
      try {
        $stateObj = $this->encryption->decrypt($encrypted_data, $this->profileManager->getEncryptionProfile('socialblock'));
      } catch (\Exception $e) {
        $this->logger->error('validateOidcRedirect error: Failed to decrypt data. @errordes', ['@errordes' => $encrypted_data]);
      }
    }
    if (isset($stateObj)) {
      $stateObj = unserialize($stateObj, ['allowed_classes' => FALSE]);
      $this->authApicSessionStore->delete($stateReceived);
      // Clear the JWT from the session as we're done with it now
      $this->authApicSessionStore->delete('invitation_object');
      $this->authApicSessionStore->delete('action');
      $redirectToValue = $this->authApicSessionStore->get('redirect_to') ?? '';
      $redirectTo = !empty($redirectToValue) && $this->utils->startsWith($redirectToValue, base_path()) ? $redirectToValue : ltrim($redirectToValue,'/');
      $redirect_location = $this->loginService->loginViaAzCode($authCode, $stateObj['registry_url']);
      if ($redirect_location === 'ERROR') {
        $this->messenger->addError($this->t('Error while authenticating user. Please contact your system administrator.'));
        $redirect_location = '<front>';
      }
      else {
        if ($redirect_location === 'APPROVAL') {
          $this->messenger->addStatus($this->t('Your account was created successfully and is pending approval. You will receive an email with further instructions.'));
          $redirect_location = '<front>';
        }
        else {
          if (!empty($redirectTo)) {
            $this->requestService->getCurrentRequest()->query->set('destination', $redirectTo);
            $this->authApicSessionStore->delete('redirect_to');
          }
        }
      }

      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return $redirect_location;
    }
    $this->logger->error('validateOidcRedirect error: Could not get state. @errordes', ['@errordes' => $stateReceived]);
    $this->messenger->addError($this->t('Error while authenticating user. Please contact your system administrator.'));
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    return '<front>';
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
  /**
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   */
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
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    return new TrustedRedirectResponse($redirectLocation, 302);
  }

  /**
   * @return mixed|string
   */
  public function validateApimOidcRedirect() {
    $error = $this->requestService->getCurrentRequest()->query->get('error');
    if (!empty($error)) {
      $errordes = $this->requestService->getCurrentRequest()->query->get('error_description');
      $this->messenger->addError($this->t('Error while authenticating user. Please contact your system administrator. Error: @error Description: @errordes', [
        '@error' => $error,
        '@errordes' => $errordes,
      ]));
      $this->logger->error('validateApimOidcRedirect error: @errordes', ['@errordes' => $errordes]);

      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }
    $state = $this->requestService->getCurrentRequest()->query->get('state');
    if (empty($state)) {
      $this->messenger->addError($this->t('Error: Missing state parameter. Contact your system administrator.'));
      $this->logger->error('validateApimOidcRedirect error: Missing state parameter');

      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }
    $authCode = $this->requestService->getCurrentRequest()->query->get('code');
    if (empty($authCode)) {
      $this->messenger->addError($this->t('Error: Missing authorization code parameter. Contact your system administrator.'));
      $this->logger->error('validateApimOidcRedirect error: Missing authorization code parameter');

      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }

    // Pull out the apim state and only use that
    $apimState = NULL;
    if (($pos = strpos($state, "_")) !== FALSE) {
      $apimState = substr($state, $pos + 1);
      $state = substr($state, 0, $pos);
    }
    else {
      $this->messenger->addError($this->t('Error: Invalid state parameter. Contact your system administrator.'));
      $this->logger->error('validateApimOidcRedirect error: Invalid state parameter: @state', ['@state' => $state]);

      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }
    $stateReceived = unserialize($this->utils->base64_url_decode($state), ['allowed_classes' => FALSE]);
    if (is_string($stateReceived)) {
      try {
        $decrypted_key = $this->encryption->decrypt($stateReceived, $this->profileManager->getEncryptionProfile('socialblock'));
        $encrypted_data = $this->authApicSessionStore->get($decrypted_key);
      } catch (\Exception $e) {
        $this->logger->error('validateApimOidcRedirect error: Failed to decrypt state. @errordes', ['@errordes' => $stateReceived]);
      }
    }
    if (!isset($encrypted_data)) {
      $this->messenger->addError($this->t('Error: Invalid state parameter. Contact your system administrator.'));
      $this->logger->error('validateApimOidcRedirect error: Invalid state parameter: @state', ['@state' => $state]);

      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }

    $url = '/consumer-api/oauth2/redirect?';
    $parameters = $this->requestService->getCurrentRequest()->query->all();
    unset($parameters['q']);
    $parameters['state'] = $apimState;
    $url .= http_build_query($parameters, '', '&', PHP_QUERY_RFC3986);


    $response = $this->mgmtServer->get($url);
    $headers = $response->getHeaders();
    $code = $response->getCode();
    $headers = array_change_key_case($headers, CASE_LOWER);
    if (!isset($code)) {
      $redirect_location = 'ERROR';
      $this->logger->error('validateApimOidcRedirect error: Response code not set');
    }
    elseif ($code !== 302) {
      $redirect_location = 'ERROR';
      $this->logger->error('validateApimOidcRedirect error: Response code @code', ['@code' => $code]);
    }
    elseif (!isset($headers['location'])) {
      $redirect_location = 'ERROR';
      $this->logger->error('validateApimOidcRedirect error: Location header not set');
    }
    else {
      $redirect_location = $headers['location'];
    }

    if ($redirect_location === 'ERROR') {

      $this->messenger->addError($this->t('Error: Error while authenticating user. Contact your system administrator.'));

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
  /**
   * @return \Symfony\Component\HttpFoundation\RedirectResponse|null
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function processApimOidcAz(): ?\Symfony\Component\HttpFoundation\RedirectResponse {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__);
    }

    $redirectLocation = $this->validateApimOidcAz();
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    if ($redirectLocation === '<front>') {
      return $this->redirect($redirectLocation);
    }

    return new TrustedRedirectResponse($redirectLocation, 302);
  }

  /**
   * @return string|null
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function validateApimOidcAz(): ?string {
    $clientId = $this->requestService->getCurrentRequest()->query->get('client_id');
    if (empty($clientId)) {
      $this->messenger->addError($this->t('Error: Missing Client ID parameter. Contact your system administrator.'));
      $this->logger->error('validateApimOidcAz error: Missing client_id parameter');

      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }
    $state = $this->requestService->getCurrentRequest()->query->get('state');
    if (empty($state)) {
      $this->messenger->addError($this->t('Error: Missing state parameter. Contact your system administrator.'));
      $this->logger->error('validateApimOidcAz error: Missing state parameter');

      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }
    $redirectUri = $this->requestService->getCurrentRequest()->query->get('redirect_uri');
    if (empty($redirectUri)) {
      $this->messenger->addError($this->t('Error: Missing redirect uri parameter. Contact your system administrator.'));
      $this->logger->error('validateApimOidcAz error: Missing redirect_uri parameter');

      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }
    $realm = $this->requestService->getCurrentRequest()->query->get('realm');
    if (empty($realm)) {
      $this->messenger->addError($this->t('Error: Missing realm parameter. Contact your system administrator.'));
      $this->logger->error('validateApimOidcAz error: Missing realm parameter');

      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }
    $responseType = $this->requestService->getCurrentRequest()->query->get('response_type');
    if (empty($responseType) || $responseType !== 'code') {
      $this->messenger->addError($this->t('Error: Missing or incorrect response type parameter. Contact your system administrator.'));
      if (empty($responseType)) {
        $this->logger->error('validateApimOidcAz error: Missing response_type parameter');
      }
      else {
        $this->logger->error('validateApimOidcAz error: Incorrect response_type parameter: @responseType', ['@responseType' => $responseType]);
      }
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }
    $invitationScope = $this->requestService->getCurrentRequest()->query->get('invitation_scope');
    $title = $this->requestService->getCurrentRequest()->query->get('title');

    //Validates the state parameter
    $stateReceived = unserialize($this->utils->base64_url_decode($state), ['allowed_classes' => FALSE]);
    if (is_string($stateReceived)) {
      try {
        $decrypted_key = $this->encryption->decrypt($stateReceived, $this->profileManager->getEncryptionProfile('socialblock'));
        $encrypted_data = $this->authApicSessionStore->get($decrypted_key);
      } catch (\Exception $e) {
        $this->logger->error('validateApimOidcAz error: Failed to decrypt state. @errordes', ['@errordes' => $stateReceived]);
      }
    }
    if (!isset($encrypted_data)) {
      $this->messenger->addError($this->t('Error: Invalid state parameter. Contact your system administrator.'));
      $this->logger->error('validateApimOidcAz error: Invalid state parameter: @state', ['@state' => $state]);

      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }

    try {
      $stateObj = $this->encryption->decrypt($encrypted_data, $this->profileManager->getEncryptionProfile('socialblock'));
      $stateObj = unserialize($stateObj, ['allowed_classes' => FALSE]);
      $userRegistry = $this->userRegistryService->get($stateObj['registry_url']);
    } catch (\Exception $e) {
      $this->logger->error('validateApimOidcAz error: Failed to decrypt data. @errordes', ['@errordes' => $encrypted_data]);
    }
    if (!isset($userRegistry)) {
      $this->messenger->addError($this->t('Error while authenticating user. Please contact your system administrator.'));
      $this->logger->error('validateApimOidcAz error: Invalid user registry url: @registryUrl', ['@registryUrl' => $stateObj['registry_url']]);

      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }
    if ($realm !== $userRegistry->getRealm()) {
      $this->messenger->addError($this->t('Error while authenticating user. Please contact your system administrator.'));
      $this->logger->error('validateApimOidcAz error: Invalid realm parameter: @realm does not match @getrealm', [
        '@realm' => $realm,
        '@getrealm' => $userRegistry->getRealm(),
      ]);

      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }

    if ($clientId !== $this->siteConfig->getClientId()) {
      $this->messenger->addError($this->t('Error: Invalid client_id parameter. Contact your system administrator.'));
      $this->logger->error('validateApimOidcAz error: Invalid client_id parameter: @clientId does not match @getclientId', [
        '@clientId' => $clientId,
        '@getclientId' => $this->siteConfig->getClientId(),
      ]);

      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }

    if (!isset($GLOBALS['__PHPUNIT_BOOTSTRAP']) && \Drupal::hasContainer()) {
      $route = URL::fromRoute('auth_apic.azcode')->toString(TRUE)->getGeneratedUrl();
    }
    else {
      $route = '/incorrectRoute';
    }
    $host = $this->apimUtils->getHostUrl();
    $expectedRedirectUri = $host . $route;
    if ($redirectUri !== $expectedRedirectUri && $redirectUri !== '/ibm_apim/oauth2/redirect') {
      $this->messenger->addError($this->t('Error while authenticating user. Please contact your system administrator.'));
      $this->logger->error('validateApimOidcAz error: Invalid redirect_uri parameter: @redirectUri does not match @expectedRedirectUri', [
        '@redirectUri' => $redirectUri,
        '@expectedRedirectUri' => $expectedRedirectUri,
      ]);
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return '<front>';
    }

    $invitation_object = $this->authApicSessionStore->get('invitation_object');
    $action = $this->authApicSessionStore->get('action');
    $url = '/consumer-api/oauth2/authorize?client_id=' . $clientId .
      '&state=' . $state .
      '&redirect_uri=' . $redirectUri .
      '&realm=' . $realm .
      '&response_type=' . $responseType;
    if (isset($invitation_object)) {
      $url .= '&token=' . $invitation_object->getDecodedJwt();
    }
    if (isset($action) && ($action === 'signin' || $action === 'signup')) {
      $url .= '&action=' . $action;
    }
    if (isset($invitationScope) && $invitationScope === 'consumer-org' && isset($title)) {
      $url .= '&invitation_scope=consumer-org&title=' . urlencode($title);
    }


    $response = $this->mgmtServer->get($url);
    if ($response === NULL) {
      $redirect_location = 'ERROR';
      $this->logger->error('validateApimOidcAz error: Response not set');
    }
    else {
      $headers = $response->getHeaders();
      $code = $response->getCode();
      $headers = array_change_key_case($headers, CASE_LOWER);
      if (!isset($code)) {
        $redirect_location = 'ERROR';
        $this->logger->error('validateApimOidcAz error: Response code not set');
      }
      elseif ($code !== 302) {
        $redirect_location = 'ERROR';
        $this->logger->error('validateApimOidcAz error: Response code @code', ['@code' => $code]);
      }
      elseif (!isset($headers['location'])) {
        $redirect_location = 'ERROR';
        $this->logger->error('validateApimOidcAz error: Location header not set');
      }
      else {
        $redirect_location = $headers['location'];
      }
    }

    if ($redirect_location === 'ERROR') {
      $this->messenger->addError($this->t('Error: Error while authenticating user. Contact your system administrator.'));
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
      }
      else {
        $query_array['redirect_uri'] = $host . '/route';
      }
      if (isset($url_array['port'])) {
        $fixed_redirect = $url_array['scheme'] . '://' . $url_array['host'] . ':' . $url_array['port'] . $url_array['path'] . '?' . http_build_query($query_array, '', '&', PHP_QUERY_RFC3986);
      }
      else {
        $fixed_redirect = $url_array['scheme'] . '://' . $url_array['host'] . $url_array['path'] . '?' . http_build_query($query_array, '', '&', PHP_QUERY_RFC3986);
      }
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return $fixed_redirect;
    }

    $this->messenger->addError($this->t('Error while authenticating user. Please contact your system administrator.'));
    $this->logger->error('validateApimOidcAz error: Failed to parse redirect: @redirect_location', ['@redirect_location' => $redirect_location]);

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    return '<front>';
  }

}
