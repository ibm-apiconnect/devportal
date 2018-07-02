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

use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\Core\State\State;
use Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface;
use Psr\Log\LoggerInterface;

/**
 * Factory for ApicUser objects.
 */
class ApicUserService {

  private $logger;
  private $state;
  private $userRegistryService;

  /**
   * ApicUserManager constructor.
   *
   * @param Psr\Log\LoggerInterface $logger
   *   Logger
   * @param \Drupal\core\State\State $state
   *   State service.
   * @param UserRegistryService $user_registry_service
   *   User registry service.
   */
  public function __construct(LoggerInterface $logger,
                              State $state,
                              UserRegistryServiceInterface $user_registry_service) {
    $this->logger = $logger;
    $this->state = $state;
    $this->userRegistryService = $user_registry_service;
   }

  /**
   * Create an ApicUser from a user registeration form.
   *
   * @param array $form_values
   *   Values from form state.
   *
   * @return ApicUser
   *   ApicUser.
   */
  public function parseRegisterForm($form_values) {

    $user = new ApicUser();

    if (isset($form_values['name'])) {
      $user->setUsername($form_values['name']);
    }
    if (isset($form_values['mail']) && isset($form_values['mail']->value)) {
      $user->setMail($form_values['mail']->value);
    }

    if (isset($form_values['pass']) && isset($form_values['pass']['pass1'])) {
      $user->setPassword($form_values['pass']['pass1']);
    }
    else if (isset($form_values['pw_no_policy'])) {
      $user->setPassword($form_values['pw_no_policy']);
    }

    if (isset($form_values['first_name']) && isset($form_values['first_name'][0]) && isset($form_values['first_name'][0]['value'])) {
      $user->setFirstname($form_values['first_name'][0]['value']);
    }

    if (isset($form_values['last_name']) && isset($form_values['last_name'][0]) && isset($form_values['last_name'][0]['value'])) {
      $user->setLastname($form_values['last_name'][0]['value']);
    }
    if (isset($form_values['consumerorg'])) {
      $user->setOrganization($form_values['consumerorg']);
    }

    return $user;
  }

  public function parseDrupalAccount($account) {

    $user = new ApicUser();

    if (isset($account->get('first_name')->getValue()[0]['value']) && $account->get('first_name')->getValue()[0]['value'] !== NULL) {
      $user->setFirstname($account->get('first_name')->getValue()[0]['value']);
    }

    if (isset($account->get('last_name')->getValue()[0]['value']) && $account->get('last_name')->getValue()[0]['value'] !== NULL) {
      $user->setLastname($account->get('last_name')->getValue()[0]['value']);
    }

    if (isset($account->get('mail')->getValue()[0]['value']) && $account->get('mail')->getValue()[0]['value'] !== NULL) {
      $user->setMail($account->get('mail')->getValue()[0]['value']);
    }

    if (isset($account->get('name')->getValue()[0]['value']) && $account->get('name')->getValue()[0]['value'] !== NULL) {
      $user->setUsername($account->get('name')->getValue()[0]['value']);
    }

    if (isset($account->get('pass')->getValue()[0]['value']) && $account->get('pass')->getValue()[0]['value'] !== NULL) {
      $user->setPassword($account->get('pass')->getValue()[0]['value']);
    }

    if (isset($account->apic_url) && isset($account->apic_url->value)) {
      $user->setUrl($account->apic_url->value);
    }

    return $user;

  }

  /**
   * Get JSON payload for a user.
   *
   * @return string
   *   JSON representation of the user.
   */
  public function getUserJSON(ApicUser $user) {
    $data = array();

    if ($user->getApicUserRegistryURL() != NULL) {
      $data['realm'] = $this->userRegistryService->get($user->getApicUserRegistryURL())->getRealm();
    }

    if ($user->getUsername() != NULL) {
      $data['username'] = $user->getUsername();
    }
    if ($user->getPassword() != NULL) {
      $data['password'] = $user->getPassword();
    }
    if ($user->getFirstName() != NULL) {
      $data['first_name'] = $user->getFirstname();
    }
    if ($user->getLastName() != NULL) {
      $data['last_name'] = $user->getLastname();
    }
    if ($user->getMail() != NULL) {
      $data['email'] = $user->getMail();
    }
    if ($user->getUrl() != NULL) {
      $data['url'] = $user->getUrl();
    }

    return json_encode($data);
  }

  /**
   * Get fields in format required for drupal DB.
   *
   * @return array
   *   associative array of fields.
   */
  public function getUserAccountFields(ApicUser $user) {
    $data = array();

    $data['first_name'] = $user->getFirstname();
    $data['last_name'] = $user->getLastname();
    $data['pass'] = $user->getPassword();
    $data['email'] = $user->getMail();
    $data['mail'] = $user->getMail();
    $data['consumer_organization'] = $user->getOrganization();
    $data['realm'] = $this->userRegistryService->get($user->getApicUserRegistryURL())->getRealm();
    $data['apic_url'] = $user->getUrl();
    $data['apic_user_registry_url'] = $user->getApicUserRegistryURL();
    $data['apic_idp'] = $user->getApicIDP();

    // check whether we are in a unit test env.
    if (!isset($GLOBALS['__PHPUNIT_BOOTSTRAP']) && \Drupal::hasContainer()) {
      $readOnlyIdp = !$this->userRegistryService->get($user->getApicUserRegistryURL())->isUserManaged();
    }
    else {
      $readOnlyIdp = 0;
    }

    // If using readonly IDP then the user should be activated immediately.
    if ($readOnlyIdp == 1) {
      $data['status'] = 1;
    }
    else {
      $data['status'] = 0;
    }

    return $data;
  }

  /**
   * Build an ApicUser object from json payload.
   * Note this function does not create the user in the DB. That has to be done as a subsequent step by the caller.
   * @param $payload
   *
   * @return \Drupal\ibm_apim\ApicType\ApicUser
   */
  public function getUserFromJSON($payload) {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    if (is_string($payload)) {
      $apicuser = json_decode($payload, TRUE);
    }
    else {
      $apicuser = $payload;
    }

    if (isset($apicuser['user'])) {
      $apicuser = $apicuser['user'];
    }

    // check if user already exists in Drupal DB
    $user = user_load_by_name($apicuser['username']);
    if (isset($user)) {
      $this->logger->notice("User exists: %user", array("%user" => $apicuser['username']));
    }
    else {
      $this->logger->notice("User does not exist: %user", array("%user" => $apicuser['username']));
    }

    $user = new ApicUser();
    $user->setFirstName($apicuser['first_name']);
    $user->setLastName($apicuser['last_name']);
    $user->setMail($apicuser['email']);
    $user->setUsername($apicuser['username']);
    $user->setUrl($apicuser['url']);
    $user->setApicUserRegistryURL($apicuser['user_registry_url']);
    $user->setState($apicuser['state']);
//
//      $this->logger->notice('Creating apic user %user', array("%user" => $apicuser['username']));
//      $this->userManager->registerApicUser($create_user->getUsername(), $this->getUserAccountFields($create_user));
    
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $user->getUsername());
    }
    return $user;

  }



}
