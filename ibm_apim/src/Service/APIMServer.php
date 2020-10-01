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

use Drupal\auth_apic\JWTToken;
use Drupal\ibm_apim\Rest\MeResponse;
use \Drupal\Core\Session\AccountProxyInterface;
use \Drupal\Core\Messenger\MessengerInterface;
use Drupal\ibm_apim\Rest\Payload\MeResponseReader;
use Drupal\ibm_apim\Rest\Payload\TokenResponseReader;
use Drupal\consumerorg\ApicType\ConsumerOrg;
use Drupal\consumerorg\ApicType\Member;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\ibm_apim\ApicRest;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Rest\Exception\RestResponseParseException;
use Drupal\ibm_apim\Rest\Payload\RestResponseReader;
use Drupal\ibm_apim\Rest\RestResponse;
use Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface;
use Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface;
use Psr\Log\LoggerInterface;
use \Drupal\user\Entity\User;

/**
 * API Connect Management Server REST apis.
 */
class APIMServer implements ManagementServerInterface {

  /**
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $sessionStore;

  /**
   * @var \Drupal\ibm_apim\Service\SiteConfig
   */
  protected $siteConfig;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @var \Drupal\ibm_apim\Service\ApicUserService
   */
  protected $userService;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface
   */
  protected $registryService;

  /**
   * @var \Drupal\ibm_apim\Rest\Payload\RestResponseReader
   */
  protected $restResponseReader;

  /**
   * @var \Drupal\ibm_apim\Rest\Payload\TokenResponseReader
   */
  protected $tokenResponseReader;

  /**
   * @var \Drupal\ibm_apim\Rest\Payload\MeResponseReader
   */
  protected $meResponseReader;

  /**
   * @var \Drupal\ibm_apim\Service\ApimUtils
   */
  protected $apim_utils;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $current_user;

  /**
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * APIMServer constructor.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   * @param \Drupal\ibm_apim\Service\SiteConfig $config
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\ibm_apim\Service\ApicUserService $user_service
   * @param \Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface $registry_service
   * @param \Drupal\ibm_apim\Rest\Payload\RestResponseReader $rest_response_reader
   * @param \Drupal\ibm_apim\Rest\Payload\TokenResponseReader $token_response_reader
   * @param \Drupal\ibm_apim\Rest\Payload\MeResponseReader $me_response_reader
   * @param \Drupal\ibm_apim\Service\ApimUtils $apim_utils
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory,
                              SiteConfig $config,
                              LoggerInterface $logger,
                              ApicUserService $user_service,
                              UserRegistryServiceInterface $registry_service,
                              RestResponseReader $rest_response_reader,
                              TokenResponseReader $token_response_reader,
                              MeResponseReader $me_response_reader,
                              ApimUtils $apim_utils,
                              AccountProxyInterface $current_user,
                              MessengerInterface $messenger
  ) {
    $this->sessionStore = $temp_store_factory->get('ibm_apim');
    $this->siteConfig = $config;
    $this->logger = $logger;
    $this->userService = $user_service;
    $this->registryService = $registry_service;
    $this->restResponseReader = $rest_response_reader;
    $this->tokenResponseReader = $token_response_reader;
    $this->meResponseReader = $me_response_reader;
    $this->apim_utils = $apim_utils;
    $this->current_user = $current_user;
    $this->messenger = $messenger;
  }


  /**
   * @param \Drupal\ibm_apim\ApicType\ApicUser $user
   *
   * @return mixed|null
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function getAuth(ApicUser $user) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $user);

    $user_registry_url = $user->getApicUserRegistryUrl();
    $user_registry = $this->registryService->get($user_registry_url);

    if ($user_registry === null || empty($user_registry)) {
      $this->logger->error('Failed to find user registry with URL @regurl for user @username.',
        ['@regurl' => $user_registry_url, '@username' => $user->getUsername()]);
      $this->messenger->addError(t('Unable to authorize your request. Contact the site administrator.'));
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      return NULL;
    }

    $authcode = $user->getAuthcode();
    if (empty($authcode)) {
      // Normal user login, use password grant type
      $token_request = [
        'realm' => $user_registry->getRealm(),
        'username' => $user->getUsername(),
        'password' => $user->getPassword(),
        'client_id' => $this->siteConfig->getClientId(),
        'client_secret' => $this->siteConfig->getClientSecret(),
        'grant_type' => 'password',
      ];
    }
    else {
      // TODO: Do we need to add a header to include the porg.catalog like 'X-IBM-Consumer-Context: ibm.sandbox'?
      $token_request = [
        // AZ Code login, use authorization code flow
        'realm' => $user_registry->getRealm(),
        'client_id' => $this->siteConfig->getClientId(),
        'client_secret' => $this->siteConfig->getClientSecret(),
        'grant_type' => 'authorization_code',
        'code' => $authcode,
      ];
    }

    $response = ApicRest::post('/token', json_encode($token_request));

    if ($response === NULL) {
      // NULL response = non 200-300 response code, so we will have handled the failure already and
      // logged anything useful in the responses.
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      return NULL;
    }
    else {
      $token_response = NULL;

      try {
        $token_response = $this->tokenResponseReader->read($response);

      } catch (RestResponseParseException $exception) {
        $this->logger->error('failure parsing POST /token response: %message', ['%message' => $exception->getMessage()]);
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
        return NULL;
      }

      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      return $token_response;
    }
  }

  /**
   * @param \Drupal\ibm_apim\ApicType\ApicUser $user
   *
   * @return mixed|null
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function refreshAuth() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    //Check if it's already refreshing
    if ($this->sessionStore->get('isRefreshing')) {
      sleep(1);
      if ($this->sessionStore->get('isRefreshing') !== true && $this->sessionStore->get('auth') !== NULL && 
      $this->sessionStore->get('expires_in') !== NULL && (int) $this->sessionStore->get('expires_in') > time()) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
        return true;
      } else {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
        return false;
      }
    }

    try {
      $this->sessionStore->set('isRefreshing', true);
      $success = false;
      
      \Drupal::logger('ibm_apim')->notice('Refreshing token with @refresh',
      ['@refresh' => $this->sessionStore->get('refresh')]);

      $refresh = $this->sessionStore->get('refresh');
      $refresh_expires_in = $this->sessionStore->get('refresh_expires_in');

      if (!isset($refresh) || (isset($refresh_expires_in) && (int) $refresh_expires_in < time())) {
        $this->logger->error('Invalid refresh token: @refresh expires @expiry',
        ['@refresh' => $refresh, '@expiry' => $refresh_expires_in]);
        return false;
      }

      //Get User Registry
      $user_registry_url = User::load($this->current_user->id())->get('registry_url')->value;
      $user_registry = $this->registryService->get($user_registry_url);
      if ($user_registry === null || empty($user_registry)) {
        $this->logger->error('Failed to find user registry with URL @regurl.',
        ['@regurl' => $user_registry_url]);
        $this->messenger->addError(t('Unable to authorize your request. Contact the site administrator.'));
        return false;
      }
    
    //Generate Request Data
      $token_request = [
        'realm' => $user_registry->getRealm(),
        'client_id' => $this->siteConfig->getClientId(),
        'client_secret' => $this->siteConfig->getClientSecret(),
        'grant_type' => 'refresh_token',
        'refresh_token' => $refresh,
      ];

      //Generate Headers
      $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'X-IBM-Client-Id: ' . $this->siteConfig->getClientId(),
        'X-IBM-Client-Secret: ' . $this->siteConfig->getClientSecret(),
        'X-IBM-Consumer-Context: ' . $this->siteConfig->getOrgId() . '.' . $this->siteConfig->getEnvId(),
      ];

      $response = ApicRest::json_http_request('/token', 'POST', $headers, json_encode($token_request), false, NULL, NULL, TRUE, 'consumer');
      if (!isset($response)) {
        $this->logger->error('Failed to refresh token');
        return false;
      } elseif ((int) $response->code < 200 || (int) $response->code >= 300) {
        $this->logger->error('Refresh token request received @code response',
        ['@code' => $response->code]);
        return false;
      }
      $token_response = $this->tokenResponseReader->read($response);

      $auth = $token_response->getBearerToken();
      $expires_in = $token_response->getExpiresIn();
      $refresh = $token_response->getRefreshToken();
      $refresh_expires_in = $token_response->getRefreshExpiresIn();

      $this->sessionStore->delete('auth');
      $this->sessionStore->delete('expires_in');
      $this->sessionStore->delete('refresh');
      $this->sessionStore->delete('refresh_expires_in');

      if (isset($auth)) {
        $this->sessionStore->set('auth', $auth);
      }
      if (isset($expires_in)) {
        $this->sessionStore->set('expires_in', (int) $expires_in);
      }
      if (isset($refresh)) {
        $this->sessionStore->set('refresh', $refresh);
      }
      if (isset($refresh_expires_in)) {
        $this->sessionStore->set('refresh_expires_in', (int) $refresh_expires_in);
      }
      $success = true; 
    } catch (RestResponseParseException $exception) {
      $this->logger->error('RefreshAuth: failure parsing POST /token response: %message', ['%message' => $exception->getMessage()]);
      return false;
    } finally {
      $this->sessionStore->delete('isRefreshing');
      if ($success) {
        $this->logger->notice('Successfully refreshed the token');
      } else {  
        $this->sessionStore->delete('auth');
        $this->sessionStore->delete('expires_in');
        $this->sessionStore->delete('refresh');
        $this->sessionStore->delete('refresh_expires_in');
      }
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    return true;
  }

  /**
   * @param null|string $auth
   *
   * @return \Drupal\ibm_apim\Rest\MeResponse
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function getMe($auth = NULL): MeResponse {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $response = ApicRest::get('/me?expand=true', $auth);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $this->meResponseReader->read($response);
  }

  /**
   * @param \Drupal\ibm_apim\ApicType\ApicUser $user
   *
   * @return \Drupal\ibm_apim\Rest\MeResponse
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function updateMe(ApicUser $user, $auth = 'user'): MeResponse {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    // 'username' is a Drupal field and will make the mgmt node barf so remove it
    $username = $user->getUsername();
    $user->setUsername(NULL);
    // apim can't handle password being set here either
    $user->setPassword(NULL);
    $registry_url = $user->getApicUserRegistryUrl();
    $user->setApicUserRegistryUrl(NULL);

    $response = ApicRest::put('/me', $this->userService->getUserJSON($user, $auth),$auth);

    $user->setUsername($username);
    $user->setApicUserRegistryUrl($registry_url);
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $this->meResponseReader->read($response);
  }

  /**
   * @return \Drupal\ibm_apim\Rest\RestResponse|null
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function deleteMe(): ?RestResponse {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $url = '/me';
    $result = ApicRest::delete($url);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $this->restResponseReader->read($result);
  }

  /**
   * @param \Drupal\ibm_apim\ApicType\ApicUser $user
   * @param null $auth
   *
   * @return \Drupal\auth_apic\Rest\UsersRegisterResponse|\Drupal\ibm_apim\Rest\RestResponse|null
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function postUsersRegister(ApicUser $user, $auth = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $result = ApicRest::post('/users/register', $this->userService->getUserJSON($user), $auth);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $this->restResponseReader->read($result);
  }

  /**
   * @param \Drupal\auth_apic\JWTToken $token
   * @param \Drupal\ibm_apim\ApicType\ApicUser $invitedUser
   *
   * @return \Drupal\ibm_apim\Rest\RestResponse|null
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function orgInvitationsRegister(JWTToken $token, ApicUser $invitedUser): ?\Drupal\ibm_apim\Rest\RestResponse {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $headers = [
      'Content-Type: application/json',
      'Accept: application/json',
      'Authorization: Bearer ' . $token->getDecodedJwt(),
      'X-IBM-Consumer-Context: ' . $this->siteConfig->getOrgId() . '.' . $this->siteConfig->getEnvId(),
      'X-IBM-Client-Id: ' . $this->siteConfig->getClientId(),
      'X-IBM-Client-Secret: ' . $this->siteConfig->getClientSecret(),
    ];

    $data = [];

    if (empty($invitedUser->getOrganization())) {
      // This is andre invited by another andre and obviously the request body is therefore completely different
      $data = json_decode($this->userService->getUserJSON($invitedUser), false);
    }
    else {
      $data['user'] = json_decode($this->userService->getUserJSON($invitedUser), false);
      $data['org'] = ['title' => $invitedUser->getOrganization()];
    }

    $post_body = json_encode($data);

    $result = ApicRest::json_http_request($token->getUrl() . '/register', 'POST', $headers, $post_body);

    $restResponse = $this->restResponseReader->read($result);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $restResponse);

    return $restResponse;

  }

  /**
   * @param \Drupal\auth_apic\JWTToken $token
   * @param \Drupal\ibm_apim\ApicType\ApicUser $acceptingUser
   * @param null $orgTitle
   *
   * @return \Drupal\ibm_apim\Rest\RestResponse|mixed|null
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function acceptInvite(JWTToken $token, ApicUser $acceptingUser, $orgTitle = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $headers = [
      'Content-Type: application/json',
      'Accept: application/json',
      'Authorization: Bearer ' . $token->getDecodedJwt(),
      'X-IBM-Consumer-Context: ' . $this->siteConfig->getOrgId() . '.' . $this->siteConfig->getEnvId(),
      'X-IBM-Client-Id: ' . $this->siteConfig->getClientId(),
      'X-IBM-Client-Secret: ' . $this->siteConfig->getClientSecret(),
    ];

    $data = [];
    // member invite and org invite (owner) have different payloads.
    if (strpos($token->getUrl(), '/member-invitations/')) {
      $user_registry = $this->registryService->get($acceptingUser->getApicUserRegistryUrl());
      $data['realm'] = $user_registry->getRealm();
      $data['username'] = $acceptingUser->getUsername();
      $data['password'] = $acceptingUser->getPassword();
    }
    else {
      $data['user'] = json_decode($this->userService->getUserJSON($acceptingUser), false);
      if ($orgTitle !== NULL) {
        $data['org'] = ['title' => $orgTitle];
      }
    }

    $post_body = json_encode($data);

    $result = ApicRest::json_http_request($token->getUrl() . '/accept', 'POST', $headers, $post_body);

    $restResponse = $this->restResponseReader->read($result);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $restResponse);

    return $restResponse;
  }

  /**
   * @param string $name
   * @param string $realm
   *
   * @return \Drupal\ibm_apim\Rest\RestResponse|\stdClass|null
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function forgotPassword($name, $realm) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $url = '/request-password-reset';
    $data = [
      'email' => $name,
      'realm' => $realm,
    ];

    // APIM returns a 400 and a specific error message if you submit a username
    // instead of an email address and that username is not a valid user.  This
    // can be used to enumerate valid users.  So, we hide the fact that we got
    // this error message and return a 204 instead (which is what APIM returns
    // if you submit an email address and that email is not a valid user).
    // To save ourselves future hassle, we will actually remove all error
    // messages from the data field (since we never want a message to display
    // to the end user enabling future enumeration vulnerabilities).
    $response = ApicRest::post($url, json_encode($data), 'user',FALSE);
    $responseObject = $this->restResponseReader->read($response);

    $code = $responseObject->getCode();
    $data = $responseObject->getData();
    if ($code === 400) {
      $code = 204;
      $responseObject->setCode($code);
      $data['status'] = 204;
    }
    $data['message'] = array();
    $responseObject->setData($data);
    $responseObject->setErrors(array());

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $responseObject;
  }

  /**
   * @param \Drupal\auth_apic\JWTToken $jwt
   * @param string $password
   *
   * @return \Drupal\ibm_apim\Rest\RestResponse|null
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function resetPassword(JWTToken $jwt, $password): ?\Drupal\ibm_apim\Rest\RestResponse {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $headers = [
      'Content-Type: application/json',
      'Accept: application/json',
      'Authorization: Bearer ' . $jwt->getDecodedJwt(),
      'X-IBM-Consumer-Context: ' . $this->siteConfig->getOrgId() . '.' . $this->siteConfig->getEnvId(),
      'X-IBM-Client-Id: ' . $this->siteConfig->getClientId(),
      'X-IBM-Client-Secret: ' . $this->siteConfig->getClientSecret(),
    ];

    $data = ['password' => $password];
    $body = json_encode($data);

    $response = ApicRest::json_http_request($jwt->getUrl(), 'POST', $headers, $body);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $this->restResponseReader->read($response);
  }

  /**
   * @param $old_password
   * @param $new_password
   *
   * @return \Drupal\ibm_apim\Rest\RestResponse|null
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function changePassword($old_password, $new_password): ?\Drupal\ibm_apim\Rest\RestResponse {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $url = '/me/change-password';

    $data = ['password' => $new_password, 'current_password' => $old_password];
    $response = ApicRest::post($url, json_encode($data), 'user');

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $this->restResponseReader->read($response);
  }

  /**
   * @param \Drupal\ibm_apim\ApicType\ApicUser $new_user
   *
   * @return \Drupal\ibm_apim\Rest\RestResponse|mixed|null
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function postSignUp(ApicUser $new_user) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $url = '/sign-up';

    $data = [];
    $data['realm'] = $this->registryService->get($new_user->getApicUserRegistryUrl())->getRealm();
    $data['username'] = $new_user->getUsername();
    $data['password'] = $new_user->getPassword();
    $data['email'] = $new_user->getMail();
    $data['first_name'] = $new_user->getFirstname();
    $data['last_name'] = $new_user->getLastname();
    $data['org'] = $new_user->getOrganization();
    $response = ApicRest::post($url, json_encode($data), 'clientid');

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $this->restResponseReader->read($response);
  }

  /**
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   *
   * @return \Drupal\ibm_apim\Rest\RestResponse|mixed|null
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function createConsumerOrg(ConsumerOrg $org) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $url = '/orgs';
    $data = ['title' => $org->getName()];

    $customFields = $org->getCustomFields();
    if (!empty($customFields)) {
      $data['metadata'] = [];
      foreach ($customFields as $customField => $value) {
        $data['metadata'][$customField] = json_encode($value);
      }
    }
    $response = ApicRest::post($url, json_encode($data));
    $responseObject = $this->restResponseReader->read($response);

    if ($responseObject !== null) {
      $code = $responseObject->getCode();
      if ($code >= 200 && $code < 400) {
        $data = $responseObject->getData();

        $data['url'] = $this->apim_utils->removeFullyQualifiedUrl($data['url']);
        $data['owner_url'] = $this->apim_utils->removeFullyQualifiedUrl($data['owner_url']);

        if (isset($data['id'])) {
          $roleUrl = '/orgs/' . $data['id'] . '/roles';
          $roleResponse = ApicRest::get($roleUrl);
          $roleResponseObject = $this->restResponseReader->read($roleResponse);
          if ($roleResponseObject !== null && isset($roleResponseObject->getData()['results'])) {
            $rolesArray = $roleResponseObject->getData()['results'];

            foreach($rolesArray as $key => $role) {
              $rolesArray[$key]['url'] = $this->apim_utils->removeFullyQualifiedUrl($rolesArray[$key]['url']);
              $rolesArray[$key]['org_url'] = $this->apim_utils->removeFullyQualifiedUrl($rolesArray[$key]['org_url']);
              foreach ($rolesArray[$key]['permission_urls'] as $permKey => $perm) {
                $rolesArray[$key]['permission_urls'][$permKey] = $this->apim_utils->removeFullyQualifiedUrl($rolesArray[$key]['permission_urls'][$permKey]);
              }
            }
            $data['roles'] = $rolesArray;
          }

          $membersUrl = '/orgs/' . $data['id'] . '/members';
          $membersResponse = ApicRest::get($membersUrl);

          $membersResponseObject = $this->restResponseReader->read($membersResponse);
          if ($membersResponseObject !== null && isset($membersResponseObject->getData()['results'])) {
            $membersArray = $membersResponseObject->getData()['results'];
            foreach($membersArray as $key => $member) {
              $membersArray[$key]['url'] = $this->apim_utils->removeFullyQualifiedUrl($membersArray[$key]['url']);
              $membersArray[$key]['org_url'] = $this->apim_utils->removeFullyQualifiedUrl($membersArray[$key]['org_url']);
              foreach ($membersArray[$key]['role_urls'] as $urlKey => $url) {
                $membersArray[$key]['role_urls'][$urlKey] = $this->apim_utils->removeFullyQualifiedUrl($membersArray[$key]['role_urls'][$urlKey]);
              }

              $membersArray[$key]['user']['url'] = $this->apim_utils->removeFullyQualifiedUrl($membersArray[$key]['user']['url']);
              $membersArray[$key]['user']['user_registry_url'] = $this->apim_utils->removeFullyQualifiedUrl($membersArray[$key]['user']['user_registry_url']);
            }

            $data['members'] = $membersArray;
          }
          $responseObject->setData($data);
        }
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $responseObject;
  }

  /**
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   * @param string $email_address
   * @param string|NULL $role
   *
   * @return \Drupal\ibm_apim\Rest\RestResponse|mixed|null
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function postMemberInvitation(ConsumerOrg $org, string $email_address, string $role = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $data = [
      'email' => $email_address,
    ];
    if ($role !== NULL) {
      $data['role_urls'] = [$this->apim_utils->createFullyQualifiedUrl($role)];
    }
    $response = ApicRest::post($org->getUrl() . '/member-invitations', json_encode($data));

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $this->restResponseReader->read($response);
  }

  /**
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   * @param string $inviteId
   *
   * @return \Drupal\ibm_apim\Rest\RestResponse|mixed|null
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function deleteMemberInvitation(ConsumerOrg $org, string $inviteId) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $response = ApicRest::delete($org->getUrl() . '/member-invitations/' . $inviteId);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $this->restResponseReader->read($response);
  }

  /**
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   * @param string $inviteId
   *
   * @return \Drupal\ibm_apim\Rest\RestResponse|mixed|null
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function resendMemberInvitation(ConsumerOrg $org, string $inviteId) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $response = ApicRest::post($org->getUrl() . '/member-invitations/' . $inviteId . '/regenerate', json_encode(['notify' => TRUE]));

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $this->restResponseReader->read($response);
  }

  /**
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   * @param array $data
   *
   * @return \Drupal\ibm_apim\Rest\RestResponse|mixed|null
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function patchConsumerOrg(ConsumerOrg $org, array $data) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $response = ApicRest::patch($org->getUrl(), json_encode($data));

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $this->restResponseReader->read($response);
  }

  /**
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   *
   * @return \Drupal\ibm_apim\Rest\RestResponse|mixed|null
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function deleteConsumerOrg(ConsumerOrg $org) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $response = ApicRest::delete($org->getUrl());

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $this->restResponseReader->read($response);
  }

  /**
   * @param \Drupal\consumerorg\ApicType\Member $member
   * @param array $data
   *
   * @return \Drupal\ibm_apim\Rest\RestResponse|mixed|null
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function patchMember(Member $member, array $data) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $response = ApicRest::patch($member->getUrl(), json_encode($data));

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $this->restResponseReader->read($response);
  }

  /**
   * @param \Drupal\consumerorg\ApicType\Member $member
   *
   * @return \Drupal\ibm_apim\Rest\RestResponse|mixed|null
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function deleteMember(Member $member) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $response = ApicRest::delete($member->getUrl());

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $this->restResponseReader->read($response);
  }

  /**
   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $org
   * @param string $newOwnerUrl
   * @param null $role
   *
   * @return \Drupal\ibm_apim\Rest\RestResponse|mixed|null
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function postTransferConsumerOrg(ConsumerOrg $org, string $newOwnerUrl, $role = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $data = ['new_owner_member_url' => $newOwnerUrl];
    if ($role !== NULL && !empty($role)) {
      $data['old_owner_new_role_urls'] = [$role];
    }

    $response = ApicRest::post($org->getUrl() . '/transfer-owner', json_encode($data));

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $this->restResponseReader->read($response);
  }

  /**
   * @inheritDoc
   */
  public function activateFromJWT(JWTToken $jwt): RestResponse {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $headers = [
      'Content-Type: application/json',
      'Accept: application/json',
      'Authorization: Bearer ' . $jwt->getDecodedJwt(),
      'X-IBM-Consumer-Context: ' . $this->siteConfig->getOrgId() . '.' . $this->siteConfig->getEnvId(),
      'X-IBM-Client-Id: ' . $this->siteConfig->getClientId(),
      'X-IBM-Client-Secret: ' . $this->siteConfig->getClientSecret(),
    ];

    $mgmt_result = ApicRest::json_http_request($jwt->getUrl(), 'POST', $headers, '');
    $result = $this->restResponseReader->read($mgmt_result);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $result->getCode());
    return $result;
  }

  public function get($url) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $response = $this->restResponseReader->read(ApicRest::get($url));

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $response->getCode());
    return $response;
  }

  public function postPaymentMethod($org, $requestBody) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $url = $org['url'] . '/payment-methods';

    $response = ApicRest::post($url, json_encode($requestBody, JSON_THROW_ON_ERROR));
    $response = $this->restResponseReader->read($response);


    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $response->getCode());
    return $response;
  }

}
