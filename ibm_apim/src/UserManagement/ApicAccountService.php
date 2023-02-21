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

/**
 * @file
 * Contains \Drupal\ibm_apim\UserManagement\ApicAccountService.
 */

namespace Drupal\ibm_apim\UserManagement;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Service\ApicUserService;
use Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface;
use Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;

/**
 * Service to link ApicAuth authentication with Drupal users.
 */
class ApicAccountService implements ApicAccountInterface {

  /**
   * Logger service.
   *
   * @var \Psr\Log\LoggerInterface;
   */
  protected $logger;

  /**
   * Management server.
   *
   * @var \Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface
   */
  protected $mgmtServer;

  /**
   * @var \Drupal\ibm_apim\Service\ApicUserService
   */
  protected $userService;

  /**
   * @var LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface
   */
  protected $userStorage;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * ApicAccountService constructor.
   *
   * @param \Psr\Log\LoggerInterface|\Psr\Log\LoggerInterface $logger
   * @param \Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface $mgmt_interface
   * @param ApicUserService $user_service
   * @param LanguageManagerInterface $language_manager
   * @param ApicUserStorageInterface $user_storage
   * @param Messenger $messenger
   *
   */
  public function __construct(LoggerInterface $logger,
                              ManagementServerInterface $mgmt_interface,
                              ApicUserService $user_service,
                              LanguageManagerInterface $language_manager,
                              ApicUserStorageInterface $user_storage,
                              Messenger $messenger) {
    $this->logger = $logger;
    $this->mgmtServer = $mgmt_interface;
    $this->userService = $user_service;
    $this->languageManager = $language_manager;
    $this->userStorage = $user_storage;
    $this->messenger = $messenger;
  }

  /**
   * @inheritDoc
   */
  public function registerApicUser(ApicUser $user): ?EntityInterface {
    if (\function_exists('ibm_apim_entry_trace')) {
      $username = $user->getUsername() != NULL ? $user->getUsername() : NULL;
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $username);
    }
    $returnValue = NULL;

    if ($user->getUsername() !== NULL && $user->getApicUserRegistryUrl() !== NULL) {
      if ($this->userStorage->load($user) === NULL) {
        $returnValue = $this->userStorage->register($user);
      }
      else {
        $this->logger->error('unable to register user, already exists.');
      }
    }
    else {
      $this->logger->error('unable to register user, need at least a username and registry details to register user.');
    }

    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    }
    return $returnValue;
  }

  /**
   *
   * @param \Drupal\ibm_apim\ApicType\ApicUser $user
   *
   * @return \Drupal\user\UserInterface|null
   */
  public function createOrUpdateLocalAccount(ApicUser $user): ?UserInterface {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    // Load this username, this will show if drupal record exists for this user.
    $account = $this->userStorage->load($user);

    // If user doesn't already exist in the drupal db, create them.
    if ($account === NULL) {
      $this->logger->notice('Registering new account in drupal database (username=@username)', ['@username' => $user->getUsername()]);
      $account = $this->userStorage->register($user);
    }
    $returnedAccount = $this->updateLocalAccount($user);
    if (isset($returnedAccount)) {
      $account = $returnedAccount;
    }


    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    return $account;
  }

  /**
   * @inheritDoc
   */
  public function updateLocalAccount(ApicUser $user) {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $user);
    }

    if ($user->getUsername() === 'admin') {
      $account = User::load(1);
    }
    else {
      $account = $this->userStorage->load($user);
    }

    if ($account === FALSE || $account === NULL) {
      // No matching account was found. Probably we don't have someone with this username. Log and ignore.
      $this->logger->notice("Attempted to update account data for user with username '@username' but we didn't find this user.", [
        '@username' => $user->getUsername(),
      ]);

      if (!isset($GLOBALS['__PHPUNIT_BOOTSTRAP']) && \Drupal::hasContainer()) {
        $this->messenger->addError(t('Failed to update your account data. Contact your site administrator.'));
      }

      $this->logger->error("Failed to update local account data for username '@username'.", [
        '@username' => $user->getUsername(),
      ]);

    }
    else {
        $account->set('first_name', $user->getFirstname());
        $account->set('last_name', $user->getLastname());
      // some user registries don't have a mail address and we store a known value to
      // identify this case and still be valid in Drupal so don't overwrite it with NULL
      if ($user->getMail() !== NULL && $user->getMail() !== '') {
        $account->set('mail', $user->getMail());
      }
      else {
        $this->logger->notice('updateLocalAccount - email address not available. Not updating to maintain what is already in the database');
      }
      $account->set('apic_user_registry_url', $user->getApicUserRegistryUrl());
      $account->set('registry_url', $user->getApicUserRegistryUrl());
      $account->set('apic_url', $user->getUrl());
      $account->set('apic_state', $user->getState());

      //Add the custom fields to the user
      $customFields = $this->userService->getMetadataFields();
      if (!empty($customFields)) {
        $metadata = $user->getMetadata();
        \Drupal::service('ibm_apim.utils')->saveCustomFields($account, $customFields, $metadata, TRUE);
      }

      // For all non-admin users, don't store their password in our database.
      if ((int) $account->id() !== 1) {
        $account->setPassword(NULL);
      }

      $account->save();
    }
    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    return $account;
  }

  /**
   * @inheritDoc
   */
  public function updateLocalAccountRoles(ApicUser $user, array $roles): bool {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    if ($user->getUsername() === 'admin') {
      $account = User::load(1);
    }
    else {
      $account = $this->userStorage->load($user);
    }

    // Splat all of the old roles
    $existingRoles = $account->getRoles();
    foreach ($existingRoles as $role) {
      $account->removeRole($role);
    }

    // Add all of the new roles
    unset($roles['authenticated']);          // This isn't a 'proper' role so remove it
    foreach ($roles as $role) {
      if ($role !== 'authenticated') {
        $account->addRole($role);
      }
    }

    $account->save();

    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function updateApicAccount(ApicUser $user): ?ApicUser {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $apic_me = $this->mgmtServer->updateMe($user);
    $returnValue = $apic_me->getUser();
    if ((int) $apic_me->getCode() !== 200) {

      // The management server rejected our update. Log the error.
      if (!isset($GLOBALS['__PHPUNIT_BOOTSTRAP']) && \Drupal::hasContainer()) {
        $this->messenger->addError(t('There was an error while saving your account data. Contact your site administrator.'));
      }

      $errors = $apic_me->getErrors();
      if (\is_array($errors)) {
        if (empty($errors)) {
          $errors = '';
        }
        else {
          $errors = implode(', ', $errors);
        }
      }

      $this->logger->error('Failed to update a user in the management server. Response code was @code and error message was @error', [
        '@code' => $apic_me->getCode(),
        '@error' => $errors,
      ]);
      $returnValue = null;
    }

    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    }
    return $returnValue;
  }


  /**
   * @param \Drupal\user\Entity\User $user
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setDefaultLanguage($user): void {
    if ($user !== NULL) {
      $language = $this->languageManager->getCurrentLanguage()->getId();
      if ($language === NULL) {
        $language = $this->languageManager->getDefaultLanguage()->getId();
      }
      if ($language === NULL) {
        $language = 'en';
      }
      $user->set('langcode', $language);
      $user->set('preferred_langcode', $language);
      $user->save();
    }
  }



}
