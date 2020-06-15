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

namespace Drupal\ibm_apim\Service;


use Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;
use Drupal\user\Entity\User;

class ApicUserStorage implements ApicUserStorageInterface {

  /**
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $userStorage;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface
   */
  private $registryService;

  /**
   * @var \Drupal\ibm_apim\Service\ApicUserService
   */
  private $userService;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  public function __construct(EntityTypeManagerInterface $entity_type_manager,
                              UserRegistryServiceInterface $registry_service,
                              ApicUserService $user_service,
                              LoggerInterface $logger) {

    $this->userStorage = $entity_type_manager->getStorage('user');
    $this->registryService = $registry_service;
    $this->userService = $user_service;
    $this->logger = $logger;
  }


  /**
   * @inheritdoc
   */
  public function register(ApicUser $user): ?EntityInterface {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    if ($user->getUsername() === NULL || $user->getApicUserRegistryUrl() === NULL) {
      throw new \Exception('User could not be registered both a username and registry_url are required.');
    }

    $name = $user->getUsername();
    $registry = $user->getApicUserRegistryUrl();
    $mail = $user->getMail();

    $account_search = $this->userStorage->loadByProperties(['name' => $name, 'registry_url' => $registry]);
    if ($account = reset($account_search)) {
      throw new \Exception(sprintf('User could not be registered. There is already an account with username "%1s" in "%2s" registry.', $name, $registry));
    }

    if (!empty($mail)) { 
      $account_search = $this->userStorage->loadByProperties(['mail' => $mail]);
      if ($account = reset($account_search)) {
        throw new \Exception(sprintf('User could not be registered. There is already an account with email "%1s".', $mail));
      }
    }

    $fields = $this->userService->getUserAccountFields($user);

    // ensure we don't store the password:
    if (isset($fields['pass'])) {
      unset($fields['pass']);
    }

    // Set up the account data to be used for the user entity.
    $account_data = array_merge(
      [
        'name' => $name,
        'init' => $name,
        'status' => 1,
        'access' => (int) $_SERVER['REQUEST_TIME'],
      ],
      $fields
    );
    $account = $this->userStorage->create($account_data);

    $account->enforceIsNew();
    $account->save();
    $this->logger->notice('Registration of apic user %name completed.',
      [
        '%name' => $name,
      ]
    );

    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    return $account;
  }

  public function load(ApicUser $user, bool $check_legacy_field = FALSE): ?EntityInterface {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    if ($user->getApicUserRegistryUrl() === NULL) {
      throw new \Exception('Registry url is missing, unable to load user.');
    }

    $returnValue = NULL;
    $this->logger->debug('loading %name in registry %registry', ['%name'=> $user->getUsername(), '%registry' => $user->getApicUserRegistryUrl()]);

    $users = $this->userStorage->loadByProperties([
      'name' => $user->getUsername(),
      'registry_url' => $user->getApicUserRegistryUrl()
    ]);
    $this->logger->debug('loaded %num users', ['%num'=> \sizeof($users)]);


    if (\sizeof($users) > 1) {
      throw new \Exception(sprintf('Multiple users (%d) returned matching username "%s" in registry_url "%s"', \sizeof($users), $user->getUsername(), $user->getApicUserRegistryUrl()));
    }
    elseif ($check_legacy_field && \sizeof($users) === 0) {
      $this->logger->debug('no users found based on name and registry_url, trying name and apic_user_registry_url');
      $users = $this->userStorage->loadByProperties([
        'name' => $user->getUsername(),
        'apic_user_registry_url' => $user->getApicUserRegistryUrl()
      ]);
      $this->logger->debug('loaded %num users using name and apic_user_registry_url', ['%num'=> \sizeof($users)]);

      if (\sizeof($users) > 1) {
        throw new \Exception(sprintf('Multiple users (%d) returned matching username "%s" in apic_user_registry_url "%s"', \sizeof($users), $user->getUsername(), $user->getApicUserRegistryUrl()));
      }
      elseif (\sizeof($users) === 1){
        $this->logger->debug('updating registry_url based on apic_user_registry_url');
        $account = \reset($users);
        $user_to_update = User::load($account->id());
        if ($user_to_update->hasField('apic_user_registry_url') && $user_to_update->get('apic_user_registry_url')->value !== NULL) {
          $this->logger->notice('updating user %uid registry_url with %apic_user_registry_url', [
            '%uid' =>$user_to_update->id(),
            '%apic_user_registry_url' => $user_to_update->get('apic_user_registry_url')->value
          ]);
          $user_to_update->set('registry_url', $user_to_update->get('apic_user_registry_url')->value);
          $user_to_update->save();
        }
        $users = [$account];
      }

    }
    $returnValue = \sizeof($users) === 1 ? reset($users) : NULL;

    if (\function_exists('ibm_apim_exit_trace')) {
      $ret = $returnValue !== NULL ? $user->getUsername() . '(' . $user->getApicUserRegistryUrl() . ')' : NULL;
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $ret);
    }
    return $returnValue;
  }

  public function loadByUsername(string $username): ?EntityInterface {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $returnValue = NULL;
    $this->logger->debug('loading %name', ['%name'=> $username]);

    $users = $this->userStorage->loadByProperties([
      'name' => $username
    ]);
    if (\sizeof($users) > 1) {
      throw new \Exception(sprintf('Multiple users (%d) returned matching username "%s" unable to continue.', \sizeof($users), $username));
    }
    $this->logger->debug('loaded %num users', ['%num'=> \sizeof($users)]);
    $returnValue = $users ? reset($users) : NULL;

    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $username);
    }
    return $returnValue;
  }

  public function loadUserByEmailAddress(string $email): ?EntityInterface {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $returnValue = NULL;
    $this->logger->debug('loading by email: %mail', ['%mail'=> $email]);

    $users = $this->userStorage->loadByProperties([
      'mail' => $email,
    ]);
    if (\sizeof($users) > 1) {
      throw new \Exception(sprintf('Multiple users (%d) returned matching email "%s" unable to continue.', \sizeof($users), $email));
    }
    $this->logger->debug('loaded by email %num users', ['%num'=> \sizeof($users)]);
    $returnValue = $users ? reset($users) : NULL;

    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    return $returnValue;
  }

  public function userLoginFinalize(UserInterface $account): UserInterface {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $account->getAccountName());
    }

    user_login_finalize($account);
    $this->logger->notice('APIC login of user %name complete.', ['%name' => $account->getAccountName()]);

    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $account->getAccountName());
    }
    return $account;
  }

  public function loadUserByUrl($url): ?AccountInterface {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $returnValue = NULL;
    $this->logger->debug('loading user by url %url', ['%url'=> $url]);

    $users = $this->userStorage->loadByProperties([
      'apic_url' => $url
    ]);
    if (\sizeof($users) > 1) {
      throw new \Exception(sprintf('Multiple users (%d) with url "%s" unable to continue.', \sizeof($users), $url));
    }
    $this->logger->debug('loaded %num users', ['%num'=> \sizeof($users)]);
    $returnValue = $users ? reset($users) : NULL;

    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $url);
    }
    return $returnValue;
  }
}
