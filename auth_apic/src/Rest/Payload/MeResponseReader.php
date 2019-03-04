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

namespace Drupal\auth_apic\Rest\Payload;

use Drupal\auth_apic\Rest\MeResponse;
use Drupal\consumerorg\ApicType\Member;
use Drupal\consumerorg\ApicType\Role;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Rest\Exception\RestResponseParseException;
use Drupal\ibm_apim\Rest\Payload\RestResponseReader;
use Drupal\ibm_apim\Rest\Interfaces\RestResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Response to GET /me?expanded=true.
 */
class MeResponseReader extends RestResponseReader {

  /**
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  private $apimUtils;

  private $utils;

  private $consumerOrgService;

  /**
   * MeResponseReader constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   */
  public function __construct(LoggerInterface $logger) {
    parent::__construct();
    $this->logger = $logger;
    $this->apimUtils = \Drupal::service('ibm_apim.apim_utils');
    $this->utils = \Drupal::service('ibm_apim.utils');
    $this->consumerOrgService = \Drupal::service('ibm_apim.consumerorg');
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
      $org = $this->consumerOrgService->createFromMeResponseJSON($org_json);

      if ($org->getUrl() === NULL) {
        $this->logger->error('Invalid consumer organization. No url property found in response.');
      }

      // process roles
      $org_role = $org_roles_part[0];
      $role_object = new Role();
      $role_object->setName($org_role['name']);
      $role_object->setTitle($org_role['title']);
      $role_object->setOrgUrl($org->getUrl());
      $role_object->setUrl($org_role['url']);

      $role_object->setPermissions($org_permissions_part);
      $org->addRole($role_object);

      // the user must be a member of this org so set up membership
      $membership = new Member();
      //$membership->addRole($role_object);
      $membership->setUrl($org_json['member_url']);
      $membership->setState('enabled');
      $membership->setRoleUrls([$org_role['url']]);
      $membership->setUser($user);
      $membership->setUserUrl($this->apimUtils->removeFullyQualifiedUrl($user->getUrl()));
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

}
