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

namespace Drupal\ibm_apim\Service;

use Drupal\auth_apic\JWTToken;
use Drupal\consumerorg\ApicType\ConsumerOrg;
use Drupal\consumerorg\ApicType\Member;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\auth_apic\Rest\Payload\TokenResponseReader;
use Drupal\ibm_apim\Rest\Exception\RestResponseParseException;
use Drupal\auth_apic\Rest\Payload\MeResponseReader;
use Drupal\ibm_apim\Rest\Payload\RestResponseReader;
use Drupal\ibm_apim\ApicRest;
use Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface;
use Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Psr\Log\LoggerInterface;

/**
 * API Connect Management Server REST apis.
 */
class APIMServer implements ManagementServerInterface {

  protected $sessionStore;
  protected $siteConfig;
  protected $logger;
  protected $userService;
  protected $registryService;

  /**
   * Apic Management Server Service.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   Tempstore factory interface for session data..
   * @param \Drupal\ibm_apim\Service\SiteConfig $config
   *   Site config.
   * @param LoggerInterface $logger
   *   Logger
   * @param ApicUserService $user_service
   *   Apic User service.
   * @param UserRegistryService $registry_service
   *   User registry service.
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory,
                              SiteConfig $config,
                              LoggerInterface $logger,
                              ApicUserService $user_service,
                              UserRegistryServiceInterface $registry_service
                              ) {
    $this->sessionStore = $temp_store_factory->get('ibm_apim');
    $this->siteConfig = $config;
    $this->logger = $logger;
    $this->userService = $user_service;
    $this->registryService = $registry_service;
  }

  /**
   * @inheritdoc
   */
  public function setAuth(ApicUser $user){

    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $user);

    //$token = $this->getAuth($user);
    $token = $user->getBearerToken();
    \Drupal::service('tempstore.private')->get('ibm_apim')->set('auth', $token);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return TRUE;
  }

  /**
   * @inheritdoc
   */
  public function getAuth(ApicUser $user) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $user);

    $user_registry_url = $user->getApicUserRegistryUrl();
    $user_registry = $this->registryService->get($user_registry_url);

    if(empty($user_registry)) {
      \Drupal::logger('auth_apic')
        ->error('Failed to find user registry with URL @regurl for user @username.',
                array('@regurl' => $user_registry_url, '@username' => $user->getUsername()));
      drupal_set_message(t('Unable to authorize your request. Contact the site administrator.'), 'error');
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      return NULL;
    }

    $authcode = $user->getAuthcode();
    if(empty($authcode)) {
      // Normal user login, use password grant type
      $token_request = [
        "realm" => $user_registry->getRealm(),
        "username" => $user->getUsername(),
        "password" => $user->getPassword(),
        "client_id" => $this->siteConfig->getClientId(),
        "client_secret" => $this->siteConfig->getClientSecret(),
        "grant_type" => 'password'
      ];
    } else {
      // TODO: Do we need to add a header to include the porg.catalog like 'X-IBM-Consumer-Context: ibm.sandbox'?
      $token_request = [
        // AZ Code login, use authorization code flow
        "realm" => $user_registry->getRealm(),
        "client_id" => $this->siteConfig->getClientId(),
        "client_secret" => $this->siteConfig->getClientSecret(),
        "grant_type" => 'authorization_code',
        "code" => $authcode
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
      $reader = new TokenResponseReader();
      $token_response = NULL;

      try {
        $token_response = $reader->read($response);
      }
      catch (RestResponseParseException $exception) {
        $this->logger->error('failure parsing POST /token response: ' . $exception->getMessage());
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
        return NULL;
      }

      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      return $token_response->getBearerToken();
    }
  }

  /**
   * @inheritdoc
   */
  public function getMe($auth = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $response = ApicRest::get('/me?expand=true', $auth);
    $meResponseReader = new MeResponseReader($this->logger);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $meResponseReader->read($response);
  }

  /**
   * @inheritdoc
   */
  public function updateMe(ApicUser $user) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    // 'username' is a Drupal field and will make the mgmt node barf so remove it
    $username = $user->getUsername();
    $user->setUsername(NULL);

    $response = ApicRest::put('/me', $this->userService->getUserJSON($user));

    $user->setUsername($username);
    $meResponseReader = new MeResponseReader($this->logger);
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $meResponseReader->read($response);
  }

  /**
   * @inheritdoc
   */
  public function deleteMe() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $url = '/me';
    $result = ApicRest::delete($url);
    $reader = new RestResponseReader();

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $reader->read($result);
  }

  /**
   * @inheritdoc
   */
  public function postUsersRegister(ApicUser $user, $auth = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $result = ApicRest::post('/users/register', $this->userService->getUserJson($user), $auth);
    $reader = new RestResponseReader();

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $reader->read($result);
  }

  /**
   * @inheritdoc
   */
  public function orgInvitationsRegister(JWTToken $token, ApicUser $invitedUser) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $headers = array(
      'Content-Type: application/json',
      'Accept: application/json',
      'Authorization: Bearer ' . $token->getDecodedJwt(),
      'X-IBM-Consumer-Context: ' . $this->siteConfig->getOrgId() . '.' . $this->siteConfig->getEnvId(),
      'X-IBM-Client-Id: ' . $this->siteConfig->getClientId(),
      'X-IBM-Client-Secret: ' . $this->siteConfig->getClientSecret(),
    );

    $data = array();

    if(empty($invitedUser->getOrganization())){
      // This is andre invited by another andre and obviously the request body is therefore completely different
      $data = json_decode($this->userService->getUserJSON($invitedUser));
    }
    else {
      $data['user'] = json_decode($this->userService->getUserJSON($invitedUser));
      $data['org'] = array("title"=> $invitedUser->getOrganization());
    }

    $post_body = json_encode($data);

    $result = ApicRest::json_http_request($token->getUrl() . '/register', 'POST', $headers, $post_body);
    $reader = new RestResponseReader();

    $restResponse = $reader->read($result);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $restResponse);

    return $restResponse;

  }

  /**
   * @inheritdoc
   */
  public function acceptInvite(JWTToken $token, ApicUser $acceptingUser, $orgTitle = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $headers = array(
      'Content-Type: application/json',
      'Accept: application/json',
      'Authorization: Bearer ' . $token->getDecodedJwt(),
      'X-IBM-Consumer-Context: ' . $this->siteConfig->getOrgId() . '.' . $this->siteConfig->getEnvId(),
      'X-IBM-Client-Id: ' . $this->siteConfig->getClientId(),
      'X-IBM-Client-Secret: ' . $this->siteConfig->getClientSecret(),
    );

    $data = array();
    // member invite and org invite (owner) have different payloads.
    if (strpos($token->getUrl(), '/member-invitations/')) {
      $user_registry = $this->registryService->get($acceptingUser->getApicUserRegistryUrl());
      $data['realm'] = $user_registry->getRealm();
      $data['username'] = $acceptingUser->getUsername();
      $data['password'] = $acceptingUser->getPassword();
    }
    else {
      $data['user'] = json_decode($this->userService->getUserJSON($acceptingUser));
      if($orgTitle !== NULL) {
        $data['org'] = ['title' => $orgTitle];
      }
    }

    $post_body = json_encode($data);

    $result = ApicRest::json_http_request($token->getUrl() . '/accept', 'POST', $headers, $post_body);
    $reader = new RestResponseReader();

    $restResponse = $reader->read($result);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $restResponse);

    return $restResponse;
  }

  /**
   * @inheritdoc
   */
  public function forgotPassword($name, $realm) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $url = '/request-password-reset';
    $data = array(
      "email" => $name,
      "realm" => $realm
    );
    $response = ApicRest::post($url, json_encode($data));

    $reader = new RestResponseReader();

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $reader->read($response);
  }

  /**
   * @inheritdoc
   */
  public function resetPassword(JWTToken $jwt, $password) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $site_config = \Drupal::service('ibm_apim.site_config');

    $headers = array(
      'Content-Type: application/json',
      'Accept: application/json',
      'Authorization: Bearer ' . $jwt->getDecodedJwt(),
      'X-IBM-Consumer-Context: ' . $site_config->getOrgId() . '.' . $site_config->getEnvId(),
      'X-IBM-Client-Id: ' . $site_config->getClientId(),
      'X-IBM-Client-Secret: ' . $site_config->getClientSecret(),
    );

    $data = array("password" => $password);
    $body = json_encode($data);

    $response = ApicRest::json_http_request($jwt->getUrl(), 'POST', $headers, $body);
    $reader = new RestResponseReader();

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $reader->read($response);
  }

  /**
   * @inheritdoc
   */
  public function changePassword($old_password, $new_password) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $url = '/me/change-password';

    $data = array("password" => $new_password, "current_password" => $old_password);
    $response = ApicRest::post($url, json_encode($data), 'user');
    $reader = new RestResponseReader();

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $reader->read($response);
  }

  /**
   * @inheritdoc
   */
  public function postSignUp(ApicUser $new_user) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $url = "/sign-up";

    $registry_service = \Drupal::service('ibm_apim.user_registry');

    $data = array();
    $data['realm'] = $registry_service->get($new_user->getApicUserRegistryURL())->getRealm();
    $data['username'] = $new_user->getUsername();
    $data['password'] = $new_user->getPassword();
    $data['email'] = $new_user->getMail();
    $data['first_name'] = $new_user->getFirstName();
    $data['last_name'] = $new_user->getLastName();
    $data['org'] = $new_user->getOrganization();

    $response = ApicRest::post($url, json_encode($data), 'clientid');
    $reader = new RestResponseReader();

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $reader->read($response);
  }

  /**
   * @inheritDoc
   */
  public function createConsumerOrg(ConsumerOrg $org) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $url = '/orgs';
    $data = array("title" => $org->getName());
    $response = ApicRest::post($url, json_encode($data));
    $reader = new RestResponseReader();

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $reader->read($response);
  }

  /**
   * @inheritDoc
   */
  public function postMemberInvitation(ConsumerOrg $org, string $email_address, string $role = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $data = array(
      "email" => $email_address
    );
    $apim_utils = \Drupal::service('ibm_apim.apim_utils');
    if (!empty($role) ) {
      $data["role_urls"] = array($apim_utils->createFullyQualifiedUrl($role));
    }
    $response = ApicRest::post($org->getUrl() . '/member-invitations', json_encode($data));
    $reader = new RestResponseReader();

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $reader->read($response);
  }

  /**
   * @inheritDoc
   */
  public function patchConsumerOrg(ConsumerOrg $org, array $data) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $response = ApicRest::patch($org->getUrl(), json_encode($data));
    $reader = new RestResponseReader();

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $reader->read($response);
  }

  /**
   * @inheritDoc
   */
  public function deleteConsumerOrg(ConsumerOrg $org) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $response = ApicRest::delete($org->getUrl());
    $reader = new RestResponseReader();

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $reader->read($response);
  }

  /**
   * @inheritDoc
   */
  public function patchMember(Member $member, array $data) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $response = ApicRest::patch($member->getUrl(), json_encode($data));
    $reader = new RestResponseReader();

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $reader->read($response);
  }

}
