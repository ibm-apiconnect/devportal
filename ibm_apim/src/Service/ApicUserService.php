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

namespace Drupal\ibm_apim\Service;

use Drupal\Core\Messenger\Messenger;
use Drupal\Core\State\State;
use Drupal\field\Entity\FieldConfig;
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

  protected $messenger;

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
                              UserRegistryServiceInterface $user_registry_service,
                              Messenger $messenger) {
    $this->logger = $logger;
    $this->state = $state;
    $this->userRegistryService = $user_registry_service;
    $this->messenger = $messenger;
  }

  /**
   * Create an ApicUser from a user registration form.
   *
   * @param $form_state
   *   The form state.
   *
   * @return ApicUser
   *   ApicUser.
   */
  public function parseRegisterForm($form_state): ApicUser {

    $user = new ApicUser();
    $form_values = $form_state->getUserInput();

    if (isset($form_values['name'])) {
      $user->setUsername($form_values['name']);
    }
    if (isset($form_values['mail'][0]['value'])) {
      $user->setMail($form_values['mail'][0]['value']);
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

    if (isset($account->get('registry_url')->getValue()[0]['value']) && $account->get('registry_url')->getValue()[0]['value'] !== NULL) {
      $user->setApicUserRegistryUrl($account->get('registry_url')->getValue()[0]['value']);
    }

    if (isset($account->apic_url->value)) {
      $user->setUrl($account->apic_url->value);
    }

    $customFields = $this->getCustomUserFields();
    foreach ($customFields as $field) {
      $value = $account->get($field)->getValue();
      if (isset($value)) {
        $user->addCustomField($field, $value);
      }
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
  public function getUserJSON(ApicUser $user, $auth = 'user'): string {
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

    $customFields = $user->getCustomFields();
    if (!empty($customFields)) {
      foreach ($customFields as $customField => $value) {
        $customFields[$customField] = json_encode($value);
      }

      $apic_me = \Drupal::service('ibm_apim.mgmtserver')->getMe($auth);
      $getMeUser = $apic_me->getUser();
      if (isset($getMeUser)) {
        $data['metadata'] = array_merge($getMeUser->getMetadata(), $customFields);
      }
      else {
        $this->messenger->addError(t('Your account was created/updated with errors. Please make sure your information was correctly saved in your account.'));
        $this->logger->error((int) $apic_me->getCode() . ' code received while trying to retrieve user metadata.');
      }
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
    $data['registry_url'] = $user->getApicUserRegistryUrl();
    $data['apic_idp'] = $user->getApicIdp();
    $data['apic_state'] = $user->getState();
    //Currently only sets known fields, null fields wont be included
    $customFields = $user->getCustomFields();
    if (isset($customFields)) {
      foreach ($customFields as $field => $value) {
        $data[$field] = $value;
      }
    }

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

    $user = new ApicUser();
    $user->setFirstname($apicuser['first_name']);
    $user->setLastname($apicuser['last_name']);
    $user->setMail($apicuser['email']);
    $user->setUsername($apicuser['username']);
    $user->setUrl($apicuser['url']);
    $user->setApicUserRegistryUrl($apicuser['user_registry_url']);
    $user->setState($apicuser['state']);
    $customFields = $this->getCustomUserFields();
    foreach ($customFields as $field) {
      $value = $apicuser['metadata'][$field];
      $user->addCustomField($field, json_decode($value, TRUE));
    }
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
    $fields = [];
    $entity = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load('user.user.' . $viewMode);
    if ($entity !== NULL) {
      $components = $entity->getComponents();
      $keys = array_keys($components);
      $coreFields = [
        'nid',
        'uuid',
        'vid',
        'langcode',
        'language',
        'timezone',
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
        'registry_url',
        'avatars_avatar_generator',
        'avatars_user_picture',
        'field_last_password_reset',
        'field_password_expiration',
      ];
      $ibmFields = [
        'consumer_organization',
        'first_name',
        'last_name',
        'apic_catalog_id',
        'apic_hostname',
        'apic_pathalias',
        'apic_provider_id',
        'apic_rating',
        'apic_tags',
        'apic_realm',
        'apic_state',
        'apic_url',
        'apic_user_registry_url',
        'consumerorg_id',
        'consumerorg_invites',
        'consumerorg_memberlist',
        'consumerorg_members',
        'consumerorg_name',
        'consumerorg_owner',
        'consumerorg_roles',
        'consumerorg_tags',
        'consumerorg_url',
        'consumerorg_def_payment_ref',
        'consumerorg_payment_method_refs',
        'codesnippet',
        'user_picture',
      ];
      $merged = array_merge($coreFields, $ibmFields);
      $fields = array_diff($keys, $merged);
    }

    // make sure we only include actual custom fields so check there is a field config
    foreach ($fields as $key => $field) {
      $fieldConfig = FieldConfig::loadByName('user', 'user', $field);
      if ($fieldConfig === NULL) {
        unset($fields[$key]);
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $fields);
    return $fields;
  }

}
