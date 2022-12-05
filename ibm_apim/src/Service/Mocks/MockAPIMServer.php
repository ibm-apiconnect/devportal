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

namespace Drupal\ibm_apim\Service\Mocks;

use Drupal\auth_apic\JWTToken;
use Drupal\consumerorg\ApicType\ConsumerOrg;
use Drupal\consumerorg\ApicType\Member;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Rest\MeResponse;
use Drupal\ibm_apim\Rest\Payload\MeResponseReader;
use Drupal\ibm_apim\Rest\Payload\RestResponseReader;
use Drupal\ibm_apim\Rest\RestResponse;
use Drupal\ibm_apim\Rest\UsersRegisterResponse;
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
  protected RestResponseReader $restResponseReader;


  /**
   * Store the username for this apim.
   *
   * Used for logging etc.
   *
   * @var string|null
   */
  protected ?string $username;

  /**
   * @var \Drupal\ibm_apim\Rest\Payload\MeResponseReader
   */
  private MeResponseReader $meResponseReader;

  /**
   * MockAPIMServer constructor.
   *
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   * @param \Drupal\ibm_apim\Rest\Payload\RestResponseReader $rest_response_reader
   * @param \Drupal\ibm_apim\Rest\Payload\MeResponseReader $me_response_reader
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory,
                              RestResponseReader $rest_response_reader,
                              MeResponseReader $me_response_reader) {
    $this->sessionStore = $temp_store_factory->get('ibm_apim');
    $this->restResponseReader = $rest_response_reader;
    $this->meResponseReader = $me_response_reader;
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
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function getMe($auth = NULL): MeResponse {

    $response = [];
    $response['firstName'] = 'mockFirstName';
    $response['lastName'] = 'mockLastName';
    $response['consumerorgs'] = ['mockconsumerorg'];
    $response['mail'] = 'mock@example.com';
    $response['lastLogin'] = time();
    $response['status'] = 1;

    return $this->meResponseReader->read($response);
  }

  /**
   * {@inheritdoc}
   */
  public function updateMe(ApicUser $user, $auth = 'user'): MeResponse {
    \Drupal::logger('apictest')->error('Implementation of MockAPIMServer::updateMe() is missing!');
    return new MeResponse();
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
  public function postUsersRegister(ApicUser $user) {
    \Drupal::logger('apictest')->error('Implementation of MockAPIMServer::postUsersRegister() is missing!');
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function orgInvitationsRegister(JWTToken $obj, ApicUser $invitedUser): ?RestResponse {
    $response = new RestResponse();

    $response->setCode(201);

    return $response;
  }

  public function postPaymentMethod($org, $requestBody) {
    $response = new RestResponse();
    $response->setData([
      "id" => "",
      "billing_url" => "",
      "ipayment_method_type_urld" => "",
      "ipayment_method_type_urld" => "",
      "updated_at" => "",
      "created_at" => "",
      "url" => "",
      "configuration" => []
    ]);
    $response->setCode(200);

    return $response;
  }

  public function deletePaymentMethod($paymentMethodUrl) {
    $response = new RestResponse();
    $response->setCode(200);

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function forgotPassword(string $username, ?string $realm) {
    $response = new RestResponse();

    $response->setCode(200);
    $response->setData(['forgotPassword mock response']);

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function resetPassword(JWTToken $obj, string $password): ?RestResponse {
    \Drupal::logger('apictest')->error('Implementation of MockAPIMServer::resetPassword() is missing!');
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function changePassword($old_password, $new_password): ?RestResponse {
    \Drupal::logger('apictest')->error('Implementation of MockAPIMServer::changePassword() is missing!');
    return NULL;
  }

  /**
   * {@inheritdoc}
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

  /**
   * {@inheritdoc}
   */
  public function postSignOut(): RestResponse {
    \Drupal::logger('apictest')->error('Implementation of MockAPIMServer::postSignOut() is missing!');
    return new RestResponse();
  }

  /**
   * {@inheritdoc}
   */
  public function acceptInvite(JWTToken $token, ApicUser $acceptingUser, ?string $orgTitle) {
    $response = new RestResponse();

    if ($orgTitle) {
      $this->createConsumerOrg(new ConsumerOrg());
    }
    $response->setCode(201);
    return $response;
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
      'group_urls' => ['/groups/1'],
      'url' => "/consumer-api/orgs/c534c180-88ee-43fa-86d1-15a7a93a3951",
      'roles' => [
        ["type" => "role",
        "api_version" => "2.0.0",
        "id" => "f66bf6d9-3098-45bf-abf2-0cc5088c4ba1",
        "name" => "administrator",
        "title" => "Administrator",
        "summary" => "Administers the app developer organization",
        "created_at" => "2022-05-03T15:48:41.000Z",
        "updated_at" => "2022-05-03T15:48:41.000Z",
        "org_url" => "/consumer-api/orgs/c534c180-88ee-43fa-86d1-15a7a93a3951",
        "url" =>"/consumer-api/orgs/c534c180-88ee-43fa-86d1-15a7a93a3951/roles/f66bf6d9-3098-45bf-abf2-0cc5088c4ba1",
        "permission_urls" => [
          "/consumer-api/consumer/permissions/org/d8d1880e-ebd9-4ee2-baeb-2f82ebed70ac",
          "/consumer-api/consumer/permissions/org/3e1652f1-e2f5-4d4f-9f58-2e11c312a148",
          "/consumer-api/consumer/permissions/org/752e7ec8-583c-4ae6-8c58-a2e56116e38a",
          "/consumer-api/consumer/permissions/org/b0e7b96f-f49b-4c3b-a0c5-d72042eb4372",
          "/consumer-api/consumer/permissions/org/01b72da2-ad89-4b2a-b2b2-c245151c7732",
          "/consumer-api/consumer/permissions/org/e9b21e62-96ad-4bca-b00d-702bf00d06f6",
          "/consumer-api/consumer/permissions/org/e120f488-5921-45a8-a936-d34d4686ab8c",
          "/consumer-api/consumer/permissions/consumer/81c86a24-c9e1-4fb5-a4e2-a137eef079c7",
          "/consumer-api/consumer/permissions/consumer/e63e4ebc-8787-4901-8f42-16dba9aa8edd",
          "/consumer-api/consumer/permissions/consumer/47d90d81-5080-4882-b270-778334fdbf50",
          "/consumer-api/consumer/permissions/consumer/e20ca0b3-a2f0-419f-9a99-177c6050c98d",
          "/consumer-api/consumer/permissions/consumer/2c9d4ee3-9348-4159-972f-69ea1ffda5ab",
          "/consumer-api/consumer/permissions/consumer/bc1a91ee-ed73-4a79-bfa1-3dae1f277d08",
          "/consumer-api/consumer/permissions/consumer/04ee1b04-4f3a-4061-8b99-5fce9a4f346c"
          ]
        ]
      ],
      'members' => [
        [
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
            'url' => 'consumer-api/user-registries/57ba20c4-bec2-4166-852b-fe4d798e5029/users/9bfd584a-b907-4209-869e-9ef481f470ad',
            'state' => 'enabled',
            'identity_provider' => 'dev-idp',
            'username' => 'andremember',
            'email' => 'andremember@example.com',
            'first_name' => 'andremember',
            'last_name' => 'andresson',
            'user_registry_url' => '/consumer-api/user-registries/57ba20c4-bec2-4166-852b-fe4d798e5029',
          ],
          'role_urls' => [
            '/consumer-api/orgs/c534c180-88ee-43fa-86d1-15a7a93a3951/roles/f66bf6d9-3098-45bf-abf2-0cc5088c4ba1',
          ],
          'created_at' => '2019-07-03T10:10:20.403Z',
          'updated_at' => '2019-07-03T10:10:20.403Z',
          'org_url' => '/consumer-api/orgs/c534c180-88ee-43fa-86d1-15a7a93a3951',
          'url' => '/consumer-api/orgs/c534c180-88ee-43fa-86d1-15a7a93a3958/members/cfc5ecd8-342d-4ae6-a1b9-0e49cb836c1b',
          'user_url' => '/andre_one@example.com',
        ],
      ],
    ];
    $response->setData($data);

    return $response;
  }

  /**
   * @inheritDoc
   */
  public function postTransferConsumerOrg(ConsumerOrg $org, string $newOwnerUrl, ?string $role = NULL) {
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

  /**
   * {@inheritdoc}
   */
  public function get($url) {
    $utils = \Drupal::service('ibm_apim.utils');
    $host = \Drupal::service('ibm_apim.apim_utils')->getHostUrl();

    $exp = '/consumer-api/oauth2/authorize?client_id=clientId' .
      '&state=czozOiJrZXkiOw==' .
      '&redirect_uri=/ibm_apim/oauth2/redirect' .
      '&realm=consumer:orgId:envId/trueRealm' .
      '&response_type=code' .
      '&action=';
    if ($utils->startsWith($url, $exp)) {
      $loc = $host . URL::fromRoute('auth_apic.azredir')->toString(TRUE)->getGeneratedUrl() .
        '?state=czozOiJrZXkiOw==_apimstate&' .
        'code=valid&' .
        'scope=scope';
      $result = new \stdClass();
      $result->headers = ['location' => $loc];
      $result->code = 302;
      $result->data = '';
      return $this->restResponseReader->read($result);
    }

    $exp = '/consumer-api/oauth2/redirect?state=apimstate' .
      '&code=valid' .
      '&scope=scope' .
      '&redirect_uri=';


    if ($utils->startsWith($url, $exp) && $utils->endsWith($url, '%2Fibm_apim%2Foidcredirect')) {
      $loc = $host . URL::fromRoute('auth_apic.azcode')->toString(TRUE)->getGeneratedUrl() .
        '?code=valid&' .
        'state=czozOiJrZXkiOw==';
      $result = new \stdClass();
      $result->headers = ['Location' => $loc];
      $result->code = 302;
      $result->data = '';
      return $this->restResponseReader->read($result);
    }

    if ($url === '/consumer-orgs/1234/5678/982318734') {
      $result = new \stdClass();
      $result->headers = [''];
      $result->data = ['metadata' => [
        'field_singletext' => "{\"value\" : \"singleVal\"}",
        'field_multitext' => "[{\"value\" : \"first multi val\"}, {\"value\" : \"theSecond\"}"
        ]];
        $result->code = 200;
      return $this->restResponseReader->read($result);
    }
  }

}