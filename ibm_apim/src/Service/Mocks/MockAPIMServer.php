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

namespace Drupal\ibm_apim\Service\Mocks;

use Drupal\auth_apic\JWTToken;
use Drupal\consumerorg\ApicType\ConsumerOrg;
use Drupal\consumerorg\ApicType\Member;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Rest\MeResponse;
use Drupal\ibm_apim\Rest\Payload\RestResponseReader;
use Drupal\ibm_apim\Rest\RestResponse;
use Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface;


/**
 * API Connect Management Server REST apis.
 */
class MockAPIMServer implements ManagementServerInterface {

  /**
   * Temp store for session data.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $sessionStore;

  /**
   * @var \Drupal\ibm_apim\Rest\Payload\RestResponseReader
   */
  protected $restResponseReader;


  /**
   * Store the username for this apim.
   *
   * Used for logging etc.
   *
   * @var string
   */
  protected $username;

  /**
   * MockAPIMServer constructor.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   * @param \Drupal\ibm_apim\Rest\Payload\RestResponseReader $rest_response_reader
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory,
                              RestResponseReader $rest_response_reader) {
    $this->sessionStore = $temp_store_factory->get('ibm_apim');
    $this->restResponseReader = $rest_response_reader;
  }

  /**
   * @inheritdoc
   */
  public function getAuth(ApicUser $user) {
    \Drupal::logger('apictest')->error('Implementation of MockAPIMServer::getAuth() is missing!');
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getMe($auth = NULL) :MeResponse{

    $response = [];
    $response['firstName'] = 'mockFirstName';
    $response['lastName'] = 'mockLastName';
    $response['consumerorgs'] = ['mockconsumerorg'];
    $response['mail'] = 'mock@example.com';
    $response['lastLogin'] = time();
    $response['status'] = 1;

    // TODO this seems wrong - MeResponse doesnt take any args?
    return new MeResponse($response);
  }

  /**
   * {@inheritdoc}
   */
  public function updateMe(ApicUser $user, $auth =  'user') : ?MeResponse{
    \Drupal::logger('apictest')->error('Implementation of MockAPIMServer::updateMe() is missing!');
    return NULL;
  }

  /**
   * @inheritdoc
   */
  public function deleteMe(): ?RestResponse {
    $response = new RestResponse();

    $response->setCode(200);

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function postUsersRegister(ApicUser $user) : ?\Drupal\auth_apic\Rest\UsersRegisterResponse{
    \Drupal::logger('apictest')->error('Implementation of MockAPIMServer::postUsersRegister() is missing!');
    return NULL;
  }

  public function orgInvitationsRegister(JWTToken $obj, ApicUser $invitedUser) {
    $response = new RestResponse();

    $response->setCode(204);

    return $response;
  }

  public function forgotPassword($username, $realm) {
    $response = new RestResponse();

    $response->setCode(200);
    $response->setData('forgotPassword mock response');

    return $response;
  }

  public function resetPassword(JWTToken $obj, $password) : ?RestResponse{
    \Drupal::logger('apictest')->error('Implementation of MockAPIMServer::resetPassword() is missing!');
    return NULL;
  }

  public function changePassword($old_password, $new_password) : ?RestResponse{
    \Drupal::logger('apictest')->error('Implementation of MockAPIMServer::changePassword() is missing!');
    return NULL;
  }

  /**
   * @param \Drupal\ibm_apim\ApicType\ApicUser $new_user
   *
   * @return \Drupal\ibm_apim\Rest\RestResponse|mixed|null
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function postSignUp(ApicUser $new_user) {
    $registry_service = \Drupal::service('ibm_apim.user_registry');

    $data = [];
    $data['realm'] = $registry_service->get($new_user->getApicUserRegistryUrl())->getRealm();
    $data['username'] = $new_user->getUsername();
    $data['password'] = $new_user->getPassword();
    $data['email'] = $new_user->getMail();
    $data['first_name'] = $new_user->getFirstname();
    $data['last_name'] = $new_user->getLastname();
    $data['org'] = $new_user->getOrganization();

    $response = new \stdClass();
    $response->code = 204;
    $response->data = $data;
    $response->headers = ['X-ApicTest' => 'FakeHeader'];

    return $this->restResponseReader->read($response);
  }

  public function acceptInvite(JWTToken $token, ApicUser $acceptingUser, $orgTitle) {
    \Drupal::logger('apictest')->error('Implementation of MockAPIMServer::acceptInvite() is missing!');
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function createConsumerOrg(ConsumerOrg $org) {

    $response = new RestResponse();

    $response->setCode(201);
    $data = [
      'url' => '/org/1',
      'id' => '123',
      'owner_url' => '/user/1',
      'group_urls' => '/groups/1',
      'members' => [[
        'type' => 'member',
        'api_version' => '2.0.0',
        'id' => 'cfc5ecd8-342d-4ae6-a1b9-0e49cb836c1b',
        'name' => 'andremember',
        'title' => 'andremember',
        'state' => 'enabled',
        'user' => [
          'type' => 'user',
          'api_version' => '2.0.0',
          'id' => '9bfd584a-b907-4209-869e-9ef481f470ad',
          'name' => 'andremember',
          'title' => 'andremember',
          'url' => '/consumer-api/user-registries/57ba20c4-bec2-4166-852b-fe4d798e5029/users/9bfd584a-b907-4209-869e-9ef481f470ad',
          'state' => 'enabled',
          'identity_provider' => 'dev-idp',
          'username' => 'andremember',
          'email' => 'andremember@example.com',
          'first_name' => 'andremember',
          'last_name' => 'andresson',
          'user_registry_url' => '/consumer-api/user-registries/57ba20c4-bec2-4166-852b-fe4d798e5029',
        ],
        'role_urls' => [
          '/consumer-api/orgs/c534c180-88ee-43fa-86d1-15a7a93a3958/roles/b8b957dc-15fb-4420-805a-3a7db9eb0fe9',
        ],
        'created_at' => '2019-07-03T10:10:20.403Z',
        'updated_at' => '2019-07-03T10:10:20.403Z',
        'org_url' => '/consumer-api/orgs/c534c180-88ee-43fa-86d1-15a7a93a3958',
        'url' => '/consumer-api/orgs/c534c180-88ee-43fa-86d1-15a7a93a3958/members/cfc5ecd8-342d-4ae6-a1b9-0e49cb836c1b',
      ]],
    ];
    $response->setData($data);

    return $response;
  }

  /**
   * @inheritDoc
   */
  public function postTransferConsumerOrg(ConsumerOrg $org, string $newOwnerUrl, $role = NULL) {
    \Drupal::logger('apictest')->error('Implementation of MockAPIMServer::postTransferConsumerOrg() is missing!');
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function postMemberInvitation(ConsumerOrg $org, string $email_address, string $role = NULL) {
    $response = new RestResponse();
    $response->setCode(201);
    $response->setData(['id' => 'abcde']);
    return $response;
  }

  /**
   * @inheritDoc
   */
  public function deleteMemberInvitation(ConsumerOrg $org, string $inviteId) {
    \Drupal::logger('apictest')->error('Implementation of MockAPIMServer::deleteMemberInvitation() is missing!');
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function resendMemberInvitation(ConsumerOrg $org, string $inviteId) {
    \Drupal::logger('apictest')->error('Implementation of MockAPIMServer::resendMemberInvitation() is missing!');
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function patchConsumerOrg(ConsumerOrg $org, array $data) {
    $response = new RestResponse();
    $response->setCode(200);
    return $response;
  }

  /**
   * @inheritDoc
   */
  public function deleteConsumerOrg(ConsumerOrg $org) {
    $response = new RestResponse();

    $response->setCode(200);

    return $response;
  }

  /**
   * @inheritDoc
   */
  public function patchMember(Member $member, array $data) {
    \Drupal::logger('apictest')->error('Implementation of MockAPIMServer::patchMember() is missing!');
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function deleteMember(Member $member) {
    \Drupal::logger('apictest')->error('Implementation of MockAPIMServer::deleteMember() is missing!');
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function activateFromJWT(JWTToken $jwt): RestResponse {
    \Drupal::logger('apictest')->error('Implementation of MockAPIMServer::activateFromJWT() is missing!');
    return new RestResponse();
  }


  public function get($url) {
    $host = \Drupal::service('ibm_apim.apim_utils')->getHostUrl();
    $exp = '/consumer-api/oauth2/authorize?client_id=clientId' .
      '&state=czozOiJrZXkiOw==' .
      '&redirect_uri=/ibm_apim/oauth2/redirect' .
      '&realm=consumer:orgId:envId/trueRealm' .
      '&response_type=code';
    if ($url == $exp) {
      $loc = $host . URL::fromRoute('auth_apic.azredir')->toString(TRUE)->getGeneratedUrl() .
        '?state=czozOiJrZXkiOw==_apimstate&' .
        'code=valid&' .
        'scope=scope';
      return ['code' => 302, 'headers' => ['Location' => $loc]];
    }

    $exp = '/consumer-api/oauth2/redirect?state=apimstate' .
      '&code=valid' .
      '&scope=scope' .
      '&redirect_uri=';


    $utils = \Drupal::service('ibm_apim.utils');
    if ($utils->startsWith($url, $exp) && $utils->endsWith($url,'%2Fibm_apim%2Foidcredirect')) {
      $loc = $host . URL::fromRoute('auth_apic.azcode')->toString(TRUE)->getGeneratedUrl() .
        '?code=valid&' .
        'state=czozOiJrZXkiOw==';
      return ['code' => 302, 'headers' => ['Location' => $loc]];
    }
  }
}