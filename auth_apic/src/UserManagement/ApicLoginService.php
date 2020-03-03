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

namespace Drupal\auth_apic\UserManagement;


use Drupal\auth_apic\UserManagerResponse;
use Drupal\consumerorg\Service\ConsumerOrgLoginInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\UserManagement\ApicAccountInterface;
use Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface;
use Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface;
use Drupal\ibm_apim\Service\SiteConfig;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;

class ApicLoginService implements ApicLoginServiceInterface {

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface
   */
  private $mgmtServer;

  /**
   * @var \Drupal\ibm_apim\UserManagement\ApicAccountInterface
   */
  private $accountService;

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
  private $userUtils;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface
   */
  private $userStorage;

  /**
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected $tempStore;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * @var \Drupal\ibm_apim\Service\SiteConfig
   */
  private $siteConfig;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private $currentUser;

  /**
   * @var \Drupal\user\UserStorageInterface
   */
  private $drupalUser;

  /**
   * @var \Drupal\consumerorg\Service\ConsumerOrgLoginInterface
   */
  private $consumerorgLogin;

  public function __construct(ManagementServerInterface $mgmt_interface,
                              ApicAccountInterface $account_service,
                              UserUtils $user_utils,
                              ApicUserStorageInterface $user_storage,
                              PrivateTempStoreFactory $temp_store_factory,
                              LoggerInterface $logger,
                              SiteConfig $site_config,
                              AccountProxyInterface $current_user,
                              EntityTypeManagerInterface $entity_type_manager,
                              ConsumerOrgLoginInterface $consumerorg_login_service) {
    $this->mgmtServer = $mgmt_interface;
    $this->accountService = $account_service;
    $this->userUtils = $user_utils;
    $this->userStorage = $user_storage;
    $this->tempStore = $temp_store_factory->get('ibm_apim');
    $this->logger = $logger;
    $this->siteConfig = $site_config;
    $this->currentUser = $current_user;
    $this->drupalUser = $entity_type_manager->getStorage('user');
    $this->consumerorgLogin = $consumerorg_login_service;
  }

  /**
   * @inheritDoc
   */
  public function login(ApicUser $user): UserManagerResponse {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $loginResponse = new UserManagerResponse();

    // At this point we are unauthenticated in Drupal so we DON'T want to
    // store the token in the private session store (FALSE)
    $token_retrieved = $this->mgmtServer->getAuth($user);
    if (!$token_retrieved) {
      $this->logger->error('unable to retrieve bearer token on login.');
      $loginResponse->setSuccess(FALSE);
      $loginResponse->setMessage(t('Unable to retrieve bearer token, please contact the system administrator.'));
      if (\function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return $loginResponse;
    }

    $apic_me = $this->mgmtServer->getMe($token_retrieved->getBearerToken());
    $meuser = $apic_me->getUser();
    $meuser->setApicUserRegistryUrl($user->getApicUserRegistryUrl());

    if ((int) $apic_me->getCode() !== 200) {
      $this->logger->error('failed to authenticate with APIM server');
      // Not successfully authenticated with management server, do not sign in.
      $loginResponse->setSuccess(FALSE);
      $loginResponse->setMessage(serialize($apic_me->getData()));
    }
    else {
      if ($meuser !== NULL && !$this->userLoginPermitted($meuser)) {
        $this->logger->error('Login failed - login is not permitted.');
        $loginResponse->setSuccess(FALSE);
      }
      else {
        // happy days, go and create an account and log the user in.

        // in all cases check whether anything has been updated in the account.
        // the response from the management server is what we need to store.
        // Pull the existing account out of the drupal db or create it if it doesn't exist yet
        $account = $this->accountService->createOrUpdateLocalAccount($meuser);

        // need to check the user isn't blocked in the portal database
        if (!$account->isBlocked()) {

          $this->userStorage->userLoginFinalize($account);

          $this->logger->notice('@username [UID=@uid] logged in.', [
            '@username' => $meuser->getUsername(),
            '@uid' => $account->get('uid')->value,
          ]);

          // Now we have called userLoginFinalize we are logged in to drupal and
          // have a new private tempstore for this user so we need to setAuth again
          $user->setBearerToken($token_retrieved->getBearerToken());
          $this->mgmtServer->setAuth($user);
          if (!isset($GLOBALS['__PHPUNIT_BOOTSTRAP']) && \Drupal::hasContainer()) {
            $this->tempStore->set('expires_in', (int) $token_retrieved->getExpiresIn());
          }

          $this->processMeConsumerOrgs($meuser, $account);

          $loginResponse->setSuccess(TRUE);
          $loginResponse->setUid($account->get('uid')->value);
        }
        else {
          // user blocked in the portal database
          $this->logger->error('attempted login by blocked user: @username [UID=@uid].', [
            '@username' => $user->getUsername(),
            '@uid' => $account->get('uid')->value,
          ]);
          $loginResponse->setSuccess(FALSE);
          $loginResponse->setUid($account->get('uid')->value);
        }
      }
    }
    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    return $loginResponse;
  }

  private function processMeConsumerOrgs(ApicUser $meuser, UserInterface $account) {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $apicMeConsumerorgs = $meuser->getConsumerorgs();

    if (empty($apicMeConsumerorgs)) {
      // user has no consumer orgs, ensure we reset any data
      $this->logger->notice('no consumer orgs set on login');
      $account->set('consumerorg_url', NULL);
      $account->save();
      $this->userUtils->setCurrentConsumerorg(NULL);
    }
    else {
      // If this is the first sign in for this user, user_consumerorg_url will not be populated.
      // We rely on this field to be able to set the current dev org and populate the consumerorg selector.
      $consumerorg_urls = [];
      foreach ($apicMeConsumerorgs as $nextApicConsumerorg) {
        $consumerorg_urls[] = ['value' => $nextApicConsumerorg->getUrl()];
      }

      // Update field and save
      $account->set('consumerorg_url', $consumerorg_urls);
      $account->save();

      // We may not have a consumerorg in our database for this user. Check and create as required.
      foreach ($apicMeConsumerorgs as $nextApicConsumerorg) {
        $this->consumerorgLogin->createOrUpdateLoginOrg($nextApicConsumerorg, $meuser);
      }

      $this->userUtils->setCurrentConsumerorg();
      $this->userUtils->setOrgSessionData();

    }

    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
  }

  /**
   * Check whether a user can be logged in:
   *
   *  - validate the user retrieved from apim
   *  - check a number of restrictions on uniqueness of users in the site.
   *
   * @param \Drupal\ibm_apim\ApicType\ApicUser $meuser
   *
   * @return bool
   */
  private function userLoginPermitted(ApicUser $meuser): bool {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $meuser->getUsername());
    }
    $permitted = TRUE;

    if ($this->userFromApimIsValid($meuser)) {
      // explicitly ban some usernames.
      $banned_usernames = ['admin', 'anonymous'];

      $prohibited = in_array(strtolower($meuser->getUsername()), $banned_usernames);
      if ($prohibited) {
        $this->logger->error('Login failed because %name user from external registry is prohibited.', ['%name' => $meuser->getUsername()]);
        $permitted = FALSE;
      }
      else {
        // Check whether a user is unique based on email address of users already in the database.
        // The email address needs to be unique across user registries. Usernames do not have to be.
        $existingUserByMail = NULL;
        try {
          $existingUserByMail = $this->userStorage->loadUserByEmailAddress($meuser->getMail());
        } catch (\Exception $e) {
          $this->logger->error('Login failed because there was a problem searching for users based on email: %message', ['%message' => $e->getMessage()]);
          $permitted = FALSE;
        }

        $userRegistryUrl = $meuser->getApicUserRegistryUrl();
        if ($existingUserByMail && (isset($existingUserByMail->registry_url) && $existingUserByMail->get('registry_url')->value !== $userRegistryUrl)) {
          $this->logger->error('Login failed because user with matching email address exists in a different registry.');
          $permitted = FALSE;
        }
      }
    }
    else {
      $this->logger->error('Login failed for %user - user failed validation check based on information from apim.', ['%user' => $meuser->getUsername()]);
      $permitted = FALSE;
    }
    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $permitted);
    }
    return $permitted;
  }

  /**
   * This check is specifically to check that the data we get on login from the management server is valid. This is the response from GET /consumer-api/me?expand=true.
   * Although we store data in the database we deliberately act on the latest information from the management server only on login.
   *
   * This function checks:
   *    * The state of the user.
   *        Possible values for state are enabled, pending or disabled. Only enabled should be allowed to login.
   *        This is not status which is entirely drupal.
   *    * Whether we have a username and registry_url
   *        We need these as the key in our database.
   *
   * @param ApicUser $loginUser
   *
   * @return bool
   */
  private function userFromApimIsValid(ApicUser $loginUser): bool {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $returnValue = FALSE;
    if ($loginUser->getUsername() !== NULL && $loginUser->getApicUserRegistryUrl() !== NULL) {

      $state = $loginUser->getState();

      if ($state !== NULL) {
        if ($state === 'enabled') {
          $returnValue = TRUE; // enabled user... all is good.
        }
        else {
          $this->logger->error('Invalid login attempt for %user, state is %state.', ['%user' => $loginUser->getUsername(), '%state' => $state]);
        }
      }
      else {
        $this->logger->error('Invalid login attempt for %user, apic state cannot be determined.', ['%user' => $loginUser->getUsername()]);
      }

    }
    else {
      $this->logger->error('login attempt with invalid user. Username and registry url needed.');
    }
    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    }
    return $returnValue;
  }

  public function loginViaAzCode($authCode, $registryUrl): string {
    if (\function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    $redirectTo = '<front>';

    $loginUser = new ApicUser();
    $loginUser->setUsername('');
    $loginUser->setPassword('');
    $loginUser->setApicUserRegistryUrl($registryUrl);
    $loginUser->setAuthcode($authCode);

    $apimResponse = $this->login($loginUser);

    if ($apimResponse->success()) {

      $user = $this->drupalUser->load($this->currentUser->id());

      // on first time login we need to pick up the browser language regardless of where we are redirecting to.
      //$user->get('first_time_login')[0]->getValue()['value'] === '1'
      //if (isset($user->first_time_login) && $user->first_time_login->value === '1') {
      if ($user->get('first_time_login') !== NULL && $user->get('first_time_login')->getString() === '1') {
        $this->accountService->setDefaultLanguage($user);
      }

      // check if the user we just logged in is a member of at least one dev org
      $currentCOrg = $this->userUtils->getCurrentConsumerorg();
      if (!isset($currentCOrg)) {
        // if onboarding is enabled, we can redirect to the create org page
        if ($this->siteConfig->isSelfOnboardingEnabled()) {
          $redirectTo = 'consumerorg.create';
        }
        else {
          // we can't help the user, they need to talk to an administrator
          $redirectTo = 'ibm_apim.noperms';
        }
        $message = 'redirect to ' . $redirectTo . ' as no consumer org set';
      }
      elseif ($user->get('first_time_login') !== NULL && $user->get('first_time_login')->getString() === '1') {
        // on first time login we need to redirect to getting started.
        $redirectTo = 'ibm_apim.get_started';
        $user->set('first_time_login', 0);
        $user->save();

        $message = 'successful authentication, first time login redirect to ' . $redirectTo;
      }
      else {
        $message = 'successful authentication, redirect to ' . $redirectTo;
      }
    }
    else {
      $message = 'redirect to front error from apim';
      $redirectTo = 'ERROR';
    }

    if (\function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $message);
    }
    return $redirectTo;
  }
}
