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

namespace Drupal\ibm_apim\Service\Mocks;

use Drupal\auth_apic\JWTToken;
use Drupal\auth_apic\Rest\MeResponse;
use Drupal\consumerorg\ApicType\ConsumerOrg;
use Drupal\consumerorg\ApicType\Member;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Rest\Payload\RestResponseReader;
use Drupal\ibm_apim\Rest\RestResponse;
use Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface;
use Drupal\ibm_apim\Service\SiteConfig;


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
   * Store the username for this apim.
   *
   * Used for logging etc.
   *
   * @var string
   */
  protected $username;

  /**
   * @inheritdoc
   */
  public function __construct(PrivateTempStoreFactory $temp_store_factory, SiteConfig $config) {
    $this->sessionStore = $temp_store_factory->get('ibm_apim');
  }

  /**
   * {@inheritdoc}
   */
  public function setAuth(ApicUser $user) : void{
    $this->sessionStore->set('auth', 'this.is.a.mock.bearer.token');
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
  public function updateMe(ApicUser $user) : ?MeResponse{
    \Drupal::logger('apictest')->error('Implementation of MockAPIMServer::updateMe() is missing!');
    return NULL;
  }

  /**
   * @inheritdoc
   */
  public function deleteMe() {
    // TODO: Implement deleteMe() method.
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
    $reader = new RestResponseReader();

    return $reader->read($response);
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
      'group_urls' => '/groups/1'
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
    \Drupal::logger('apictest')->error('Implementation of MockAPIMServer::patchConsumerOrg() is missing!');
    return NULL;
  }

  /**
   * @inheritDoc
   */
  public function deleteConsumerOrg(ConsumerOrg $org) {
    \Drupal::logger('apictest')->error('Implementation of MockAPIMServer::deleteConsumerOrg() is missing!');
    return NULL;
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
}
