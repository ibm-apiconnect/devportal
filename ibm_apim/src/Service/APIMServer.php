<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2019
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Service;

use Drupal\auth_apic\JWTToken;
use Drupal\auth_apic\Rest\Payload\MeResponseReader;
use Drupal\auth_apic\Rest\Payload\TokenResponseReader;
use Drupal\consumerorg\ApicType\ConsumerOrg;
use Drupal\consumerorg\ApicType\Member;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\ibm_apim\ApicRest;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Rest\Exception\RestResponseParseException;
use Drupal\ibm_apim\Rest\Payload\RestResponseReader;
use Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface;
use Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface;
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
   * @param UserRegistryServiceInterface $registry_service
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
   * @param \Drupal\ibm_apim\ApicType\ApicUser $user
   *
   * @return bool
   */
  public function setAuth(ApicUser $user): bool {

    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $user);

    //$token = $this->getAuth($user);
    $token = $user->getBearerToken();
    \Drupal::service('tempstore.private')->get('ibm_apim')->set('auth', $token);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return TRUE;
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

    if (empty($user_registry)) {
      \Drupal::logger('auth_apic')
        ->error('Failed to find user registry with URL @regurl for user @username.',
          ['@regurl' => $user_registry_url, '@username' => $user->getUsername()]);
      drupal_set_message(t('Unable to authorize your request. Contact the site administrator.'), 'error');
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
      $reader = new TokenResponseReader();
      $token_response = NULL;

      try {
        $token_response = $reader->read($response);

      } catch (RestResponseParseException $exception) {
        $this->logger->error('failure parsing POST /token response: ' . $exception->getMessage());
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
        return NULL;
      }

      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      return $token_response;
    }
  }

  /**
   * @param null|string $auth
   *
   * @return \Drupal\auth_apic\Rest\MeResponse
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function getMe($auth = NULL): \Drupal\auth_apic\Rest\MeResponse {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $response = ApicRest::get('/me?expand=true', $auth);
    $meResponseReader = new MeResponseReader($this->logger);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $meResponseReader->read($response);
  }

  /**
   * @param \Drupal\ibm_apim\ApicType\ApicUser $user
   *
   * @return \Drupal\auth_apic\Rest\MeResponse
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function updateMe(ApicUser $user): \Drupal\auth_apic\Rest\MeResponse {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    // 'username' is a Drupal field and will make the mgmt node barf so remove it
    $username = $user->getUsername();
    $user->setUsername(NULL);
    // apim can't handle password being set here either
    $user->setPassword(NULL);

    $response = ApicRest::put('/me', $this->userService->getUserJSON($user));

    $user->setUsername($username);
    $meResponseReader = new MeResponseReader($this->logger);
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $meResponseReader->read($response);
  }

  /**
   * @return \Drupal\ibm_apim\Rest\RestResponse|null
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function deleteMe(): ?\Drupal\ibm_apim\Rest\RestResponse {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $url = '/me';
    $result = ApicRest::delete($url);
    $reader = new RestResponseReader();

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $reader->read($result);
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
    $reader = new RestResponseReader();

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $reader->read($result);
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
      $data = json_decode($this->userService->getUserJSON($invitedUser));
    }
    else {
      $data['user'] = json_decode($this->userService->getUserJSON($invitedUser));
      $data['org'] = ['title' => $invitedUser->getOrganization()];
    }

    $post_body = json_encode($data);

    $result = ApicRest::json_http_request($token->getUrl() . '/register', 'POST', $headers, $post_body);
    $reader = new RestResponseReader();

    $restResponse = $reader->read($result);

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
      $data['user'] = json_decode($this->userService->getUserJSON($acceptingUser));
      if ($orgTitle !== NULL) {
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
    $response = ApicRest::post($url, json_encode($data));

    $reader = new RestResponseReader();

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $reader->read($response);
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

    $site_config = \Drupal::service('ibm_apim.site_config');

    $headers = [
      'Content-Type: application/json',
      'Accept: application/json',
      'Authorization: Bearer ' . $jwt->getDecodedJwt(),
      'X-IBM-Consumer-Context: ' . $site_config->getOrgId() . '.' . $site_config->getEnvId(),
      'X-IBM-Client-Id: ' . $site_config->getClientId(),
      'X-IBM-Client-Secret: ' . $site_config->getClientSecret(),
    ];

    $data = ['password' => $password];
    $body = json_encode($data);

    $response = ApicRest::json_http_request($jwt->getUrl(), 'POST', $headers, $body);
    $reader = new RestResponseReader();

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $reader->read($response);
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
    $reader = new RestResponseReader();

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $reader->read($response);
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

    $registry_service = \Drupal::service('ibm_apim.user_registry');

    $data = [];
    $data['realm'] = $registry_service->get($new_user->getApicUserRegistryUrl())->getRealm();
    $data['username'] = $new_user->getUsername();
    $data['password'] = $new_user->getPassword();
    $data['email'] = $new_user->getMail();
    $data['first_name'] = $new_user->getFirstname();
    $data['last_name'] = $new_user->getLastname();
    $data['org'] = $new_user->getOrganization();

    $response = ApicRest::post($url, json_encode($data), 'clientid');
    $reader = new RestResponseReader();

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $reader->read($response);
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
    $response = ApicRest::post($url, json_encode($data));
    $reader = new RestResponseReader();

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $reader->read($response);
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
    $apim_utils = \Drupal::service('ibm_apim.apim_utils');
    if ($role !== NULL) {
      $data['role_urls'] = [$apim_utils->createFullyQualifiedUrl($role)];
    }
    $response = ApicRest::post($org->getUrl() . '/member-invitations', json_encode($data));
    $reader = new RestResponseReader();

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $reader->read($response);
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
    $reader = new RestResponseReader();

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $reader->read($response);
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
    $reader = new RestResponseReader();

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $reader->read($response);
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
    $reader = new RestResponseReader();

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $reader->read($response);
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
    $reader = new RestResponseReader();

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $reader->read($response);
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
    $reader = new RestResponseReader();

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $reader->read($response);
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
    $reader = new RestResponseReader();

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $reader->read($response);
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
    $reader = new RestResponseReader();

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $reader->read($response);
  }

}
