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

namespace Drupal\ibm_apim\Rest\Payload;

use Drupal\consumerorg\ApicType\ConsumerOrg;
use Drupal\ibm_apim\Rest\MeResponse;
use Drupal\consumerorg\ApicType\Member;
use Drupal\consumerorg\ApicType\Role;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Rest\Exception\RestResponseParseException;
use Drupal\ibm_apim\Rest\Interfaces\RestResponseInterface;
use Drupal\ibm_apim\Service\ApimUtils;
use Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface;
use Drupal\ibm_apim\Service\Utils;
use Psr\Log\LoggerInterface;

/**
 * Response to GET /me?expanded=true.
 */
class MeResponseReader extends RestResponseReader {

  /**
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * @var \Drupal\ibm_apim\Service\ApimUtils
   */
  private $apimUtils;

  /**
   * @var \Drupal\ibm_apim\Service\Utils
   */
  private $utils;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface
   */
  private $registryService;


  /**
   * MeResponseReader constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   * @param \Drupal\pathauto\MessengerInterface $messenger
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\ibm_apim\Service\ApimUtils $apim_utils
   * @param \Drupal\ibm_apim\Service\Utils $utils
   * @param UserRegistryServiceInterface $registry_service
   */
  public function __construct(ModuleHandlerInterface $module_handler,
                              ConfigFactoryInterface $config_factory,
                              MessengerInterface $messenger,
                              LoggerInterface $logger,
                              ApimUtils $apim_utils,
                              Utils $utils,
                              UserRegistryServiceInterface $registry_service) {
    parent::__construct($module_handler, $config_factory, $messenger);
    $this->logger = $logger;
    $this->apimUtils = $apim_utils;
    $this->utils = $utils;
    $this->registryService = $registry_service;
  }

  /**
   * Read the HTTP response body and pull out the fields we care about.
   *
   * @param $response
   * @param null $response_object
   *
   * @return \Drupal\auth_apic\Rest\MeResponse
   * @throws \Drupal\ibm_apim\Rest\Exception\RestResponseParseException
   */
  public function read($response, $response_object = NULL): ?RestResponseInterface {

    // Create a new, specific object for the API response.
    $me_response = new MeResponse();

    parent::read($response, $me_response);
    // If we don't have a 200 then bail out early.
    if ($me_response->getCode() !== 200) {
      return $me_response;
    }

    if ($me_response->getData() === NULL) {
      throw new RestResponseParseException('No data on response from GET /me with 200 response');
    }
    $data = $me_response->getData();

    $user = new ApicUser();

    $username = $data['username'] ?? NULL;
    $user->setUsername($username);

    $firstName = $data['first_name'] ?? NULL;
    $user->setFirstname($firstName);

    $lastName = $data['last_name'] ?? NULL;
    $user->setLastname($lastName);

    $mail = $data['email'] ?? NULL;
    $user->setMail($mail);

    $state = $data['state'] ?? NULL;
    $user->setState($state);

    $idp = $data['identity_provider'] ?? NULL;
    $user->setApicIdp($idp);
    if ($idp !== NULL) {
      $user->setApicUserRegistryUrl($this->registryService->getRegistryContainingIdentityProvider($idp)->getUrl());
    }
    else {
      $this->logger->warning('unable to set registry_url from me response.');
    }

    if (isset($data['url'])) {
      // 'adjust' the url so it isn't fully qualified
      $url = $this->apimUtils->removeFullyQualifiedUrl($data['url']);
    }
    else {
      $url = NULL;
    }
    $user->setUrl($url);

    $consumerOrgsJson = $data['org_info'] ?? [];
    $consumerOrgs = [];
    foreach ($consumerOrgsJson as $org_json) {
      // Should be three parts - org, roles and permissions
      $org_part = $org_json['org'];
      $org_roles_part = $org_json['roles'];
      $org_permissions_part = $org_json['permissions'];
      if (isset($org_part['url'])) {
        // 'adjust' the url so it isn't fully qualified
        $org_part['url'] = $this->apimUtils->removeFullyQualifiedUrl($org_part['url']);
      }

      // process org part
      $org = $this->createConsumerOrgFromMeResponseJSON($org_json);

      if ($org->getUrl() === NULL) {
        $this->logger->error('Invalid consumer organization. No url property found in response.');
      }

      // process roles
      $org_role = $org_roles_part[0];
      $role_object = new Role();
      $role_object->setName($org_role['name']);
      $role_object->setTitle($org_role['title']);
      $role_object->setOrgUrl($org->getUrl());
      $role_object->setUrl($this->apimUtils->removeFullyQualifiedUrl($org_role['url']));

      $role_object->setPermissions($org_permissions_part);
      $org->addRole($role_object);

      // the user must be a member of this org so set up membership
      $membership = new Member();
      //$membership->addRole($role_object);
      $membership->setUrl($this->apimUtils->removeFullyQualifiedUrl($org_json['member_url']));
      $membership->setState('enabled');
      $membership->setRoleUrls([$this->apimUtils->removeFullyQualifiedUrl($org_role['url'])]);

      // do not set the consumer org on the user contained in a member, we don't have it at this point.
      $member_user = clone $user;
      $membership->setUser($member_user);
      $membership->setUserUrl($this->apimUtils->removeFullyQualifiedUrl($member_user->getUrl()));

      $org->addMember($membership);

      $consumerOrgs[$org->getUrl()] = $org;


    }
    $user->setConsumerorgs($consumerOrgs);

    // in some registries (e.g. LDAP) there is no user stored.
    // we need to deal with this otherwise it causes merry hell
    // with drupal validation.
    $userMail = $user->getMail();
    if ($userMail === '' || $userMail === NULL) {
      $random_prefix = $this->utils->random_num();
      $user->setMail($random_prefix . 'noemailinregistry@example.com');
    }

    $me_response->setUser($user);

    return $me_response;
  }

  /**
   * Create a ConsumerOrg object from a JSON string representation
   * e.g. as returned by GET /me
   * Note, this doesn't handle roles, permissions or members as they are a different format in that response.
   *
   * @param $json
   *
   * @return ConsumerOrg
   */
  private function createConsumerOrgFromMeResponseJSON($json): ConsumerOrg {

    ibm_apim_entry_trace(__FUNCTION__, $json);

    $org = new ConsumerOrg();

    if (\is_string($json)) {
      $json = json_decode($json, 1);
    }

    if (isset($json['org']['name'])) {
      $org->setName($json['org']['name']);
    }
    if (isset($json['org']['title'])) {
      $org->setTitle($json['org']['title']);
    }
    if (isset($json['org']['summary'])) {
      $org->setSummary($json['org']['summary']);
    }
    if (isset($json['org']['id'])) {
      $org->setId($json['org']['id']);
    }
    if (isset($json['org']['state'])) {
      $org->setState($json['org']['state']);
    }
    if (isset($json['org']['created_at'])) {
      $org->setCreatedAt($json['org']['created_at']);
    }
    if (isset($json['org']['updated_at'])) {
      $org->setUpdatedAt($json['org']['updated_at']);
    }
    if (isset($json['org']['url'])) {
      $org->setUrl($this->apimUtils->removeFullyQualifiedUrl($json['org']['url']));
    }
    if (isset($json['org']['owner_url'])) {
      $org->setOwnerUrl($this->apimUtils->removeFullyQualifiedUrl($json['org']['owner_url']));
    }

    ibm_apim_exit_trace(__FUNCTION__, $org);
    return $org;

  }

}
