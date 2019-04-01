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

use Drupal\Core\State\State;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface;
use Drupal\user\Entity\User;
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
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger
   * @param \Drupal\core\State\State $state
   *   State service.
   * @param UserRegistryServiceInterface $user_registry_service
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
   * Create an ApicUser from a user registration form.
   *
   * @param array $form_values
   *   Values from form state.
   *
   * @return ApicUser
   *   ApicUser.
   */
  public function parseRegisterForm($form_values): ApicUser {

    $user = new ApicUser();

    if (isset($form_values['name'])) {
      $user->setUsername($form_values['name']);
    }
    if (isset($form_values['mail']->value)) {
      $user->setMail($form_values['mail']->value);
    }

    if (isset($form_values['pass']['pass1'])) {
      $user->setPassword($form_values['pass']['pass1']);
    }
    elseif (isset($form_values['pw_no_policy'])) {
      $user->setPassword($form_values['pw_no_policy']);
    }

    if (isset($form_values['first_name'][0]['value'])) {
      $user->setFirstname($form_values['first_name'][0]['value']);
    }

    if (isset($form_values['last_name'][0]['value'])) {
      $user->setLastname($form_values['last_name'][0]['value']);
    }
    if (isset($form_values['consumerorg'])) {
      $user->setOrganization($form_values['consumerorg']);
    }

    return $user;
  }

  /**
   * @param User $account
   *
   * @return \Drupal\ibm_apim\ApicType\ApicUser
   */
  public function parseDrupalAccount($account): ApicUser {

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

    if (isset($account->apic_url->value)) {
      $user->setUrl($account->apic_url->value);
    }

    return $user;

  }

  /**
   * Get JSON payload for a user.
   *
   * @param \Drupal\ibm_apim\ApicType\ApicUser $user
   *
   * @return string
   *        JSON representation of the user.
   */
  public function getUserJSON(ApicUser $user): string {
    $data = [];

    if ($user->getApicUserRegistryUrl() !== NULL) {
      $data['realm'] = $this->userRegistryService->get($user->getApicUserRegistryUrl())->getRealm();
    }

    if ($user->getUsername() !== NULL) {
      $data['username'] = $user->getUsername();
    }
    if ($user->getPassword() !== NULL) {
      $data['password'] = $user->getPassword();
    }
    if ($user->getFirstname() !== NULL) {
      $data['first_name'] = $user->getFirstname();
    }
    if ($user->getLastname() !== NULL) {
      $data['last_name'] = $user->getLastname();
    }
    if ($user->getMail() !== NULL) {
      $data['email'] = $user->getMail();
    }
    if ($user->getUrl() !== NULL) {
      $data['url'] = $user->getUrl();
    }

    return json_encode($data);
  }

  /**
   * Get fields in format required for drupal DB.
   *
   * @param \Drupal\ibm_apim\ApicType\ApicUser $user
   *
   * @return array
   */
  public function getUserAccountFields(ApicUser $user): array {
    $data = [];

    $data['first_name'] = $user->getFirstname();
    $data['last_name'] = $user->getLastname();
    $data['pass'] = $user->getPassword();
    $data['email'] = $user->getMail();
    $data['mail'] = $user->getMail();
    $data['consumer_organization'] = $user->getOrganization();
    $data['apic_url'] = $user->getUrl();
    $data['apic_user_registry_url'] = $user->getApicUserRegistryUrl();
    $data['apic_idp'] = $user->getApicIdp();
    $data['apic_state'] = $user->getState();

    return $data;
  }

  /**
   * Build an ApicUser object from json payload.
   * Note this function does not create the user in the DB. That has to be done as a subsequent step by the caller.
   *
   * @param $payload
   *
   * @return \Drupal\ibm_apim\ApicType\ApicUser
   */
  public function getUserFromJSON($payload): ApicUser {
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
      $this->logger->notice('User exists: %user', ['%user' => $apicuser['username']]);
    }
    else {
      $this->logger->notice('User does not exist: %user', ['%user' => $apicuser['username']]);
    }

    $user = new ApicUser();
    $user->setFirstname($apicuser['first_name']);
    $user->setLastname($apicuser['last_name']);
    $user->setMail($apicuser['email']);
    $user->setUsername($apicuser['username']);
    $user->setUrl($apicuser['url']);
    $user->setApicUserRegistryUrl($apicuser['user_registry_url']);
    $user->setState($apicuser['state']);
    //
    //      $this->logger->notice('Creating apic user %user', array('%user' => $apicuser['username']));
    //      $this->userManager->registerApicUser($create_user->getUsername(), $this->getUserAccountFields($create_user));

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $user->getUsername());
    }
    return $user;

  }

  /**
   * @param string $viewMode
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getCustomUserFields($viewMode = 'default'): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $components = \Drupal::entityTypeManager()
      ->getStorage('entity_form_display')
      ->load('user.user.' . $viewMode)
      ->getComponents();
    $keys = array_keys($components);
    $coreFields = [
      'nid',
      'uuid',
      'vid',
      'langcode',
      'type',
      'revision_timestamp',
      'revision_uid',
      'revision_log',
      'status',
      'title',
      'uid',
      'created',
      'changed',
      'promote',
      'sticky',
      'default_langcode',
      'revision_default',
      'revision_translation_affected',
      'metatag',
      'path',
      'menu_link',
      'content_translation_source',
      'content_translation_outdated',
      'mail',
      'name',
      'pass',
      'roles',
      'current_pass',
      'account',
      'notify',
    ];
    $ibmFields = [
      'consumer_organization',
      'first_name',
      'last_name',  'apic_catalog_id',
      'apic_hostname',
      'apic_pathalias',
      'apic_provider_id',
      'apic_rating',
      'apic_tags',
      'consumerorg_id',
      'consumerorg_invites',
      'consumerorg_memberlist',
      'consumerorg_members',
      'consumerorg_name',
      'consumerorg_owner',
      'consumerorg_roles',
      'consumerorg_tags',
      'consumerorg_url',
    ];
    $merged = array_merge($coreFields, $ibmFields);
    $fields = array_diff($keys, $merged);
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $fields);
    return $fields;
  }
}
