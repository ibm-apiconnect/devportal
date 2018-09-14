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

/**
 * @file
 * Contains \Drupal\apicauth\Service\ApicDrupalAuth.
 */

namespace Drupal\auth_apic\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\State\State;
use Drupal\externalauth\ExternalAuthInterface;
use Drupal\user\Entity\User;
use Psr\Log\LoggerInterface;

use Drupal\auth_apic\JWTToken;
use Drupal\auth_apic\UserManagerResponse;
use Drupal\auth_apic\Service\Interfaces\UserManagerInterface;
use Drupal\consumerorg\Service\ConsumerOrgService;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Service\ApicUserService;
use Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface;
use Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface;
use Drupal\ibm_apim\Service\SiteConfig;
use Drupal\ibm_apim\ApicRest;
use Drupal\ibm_apim\Service\UserUtils;

/**
 * Service to link ApicAuth authentication with Drupal users.
 */
class ApicUserManager implements UserManagerInterface {

  /**
   * Logger service.
   *
   * @var \Psr\Log\LoggerInterface;
   */
  protected $logger;

  /**
   * Connection to the active database.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Used to include the externalauth service from the external_auth module.
   *
   * @var \Drupal\externalauth\ExternalAuthInterface
   */
  protected $externalAuth;

  /**
   * Management server.
   *
   * @var \Drupal\ibm_apim\Service\ManagementServerInterface
   */
  protected $mgmtServer;


  /**
   * Consumerorg adapter.
   * TODO: shouldn't be required... need to remove static calls.
   */
  protected $consumerOrgService;
  protected $state;
  protected $siteConfig;
  protected $userRegistryService;
  protected $userService;
  protected $userUtils;
  protected $moduleHandler;

  protected $provider = "auth_apic";

  /**
   * ApicUserManager constructor.
   *
   * @param \Drupal\auth_apic\Service\Psr\Log\LoggerInterface|\Psr\Log\LoggerInterface $logger
   * @param \Drupal\Core\Database\Connection $database
   * @param \Drupal\externalauth\ExternalAuthInterface $external_auth
   * @param \Drupal\ibm_apim\Service\ManagementServerInterface $mgmt_interface
   * @param \Drupal\consumerorg\Service\ConsumerOrgService $consumer_org_servive
   * @param \Drupal\core\State\State $state
   * @param \Drupal\ibm_apim\Service\SiteConfig $config
   * @param UserRegistryService $user registry_service
   * @param ApicUserService $user_service
   * @param UserUtils $user_utils
   * @param ModuleHandlerInterface $module_handler
   */
  public function __construct(LoggerInterface $logger,
                              Connection $database,
                              ExternalAuthInterface $external_auth,
                              ManagementServerInterface $mgmt_interface,
                              ConsumerOrgService $consumer_org_servive,
                              State $state,
                              SiteConfig $config,
                              UserRegistryServiceInterface $user_registry_service,
                              ApicUserService $user_service,
                              UserUtils $user_utils,
                              ModuleHandlerInterface $module_handler) {
    $this->logger = $logger;
    $this->database = $database;
    $this->externalAuth = $external_auth;
    $this->mgmtServer = $mgmt_interface;
    $this->consumerOrgService = $consumer_org_servive;
    $this->state = $state;
    $this->siteConfig = $config;
    $this->userRegistryService = $user_registry_service;
    $this->userService = $user_service;
    $this->userUtils = $user_utils;
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public function registerInvitedUser(JWTToken $token, ApicUser $invitedUser = NULL) {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $invitationResponse = $this->mgmtServer->orgInvitationsRegister($token, $invitedUser);

    $userMgrResponse = new UserManagerResponse();

    if ($invitationResponse->getCode() == 201) {
      $account = $this->registerApicUser($invitedUser->getUsername(), $this->userService->getUserAccountFields($invitedUser));
      $this->activateUserInDB($account->get('uid')->value);

      $this->logger->notice('invitation processed for %username', [
        '%username' => $invitedUser->getUsername(),
      ]);

      if (!isset($GLOBALS['__PHPUNIT_BOOTSTRAP']) && \Drupal::hasContainer()) {
        $userMgrResponse->setMessage(t('Invitation process complete. Please login to continue.'));
      }
      $userMgrResponse->setSuccess(TRUE);
      $userMgrResponse->setRedirect('<front>');
    }
    else {

      $this->logger->error('Error during account registration:  %error', ['%error' => $invitationResponse->getErrors()[0]]);

      if (!isset($GLOBALS['__PHPUNIT_BOOTSTRAP']) && \Drupal::hasContainer()) {
        $userMgrResponse->setMessage(t('Error during account registration: %error', ['%error' => $invitationResponse->getErrors()[0]]));
      }
      $userMgrResponse->setSuccess(FALSE);
      $userMgrResponse->setRedirect('<front>');
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $userMgrResponse);
    }
    return $userMgrResponse;

  }

  /**
   * {@inheritdoc}
   */
  public function acceptInvite(JWTToken $token, ApicUser $acceptingUser) {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $invitationResponse = $this->mgmtServer->acceptInvite($token, $acceptingUser, $acceptingUser->getOrganization());
    $userMgrResponse = new UserManagerResponse();

    if ($invitationResponse->getCode() == 201) {

      $this->logger->notice('invitation processed for %username', [
        '%username' => $acceptingUser->getUsername(),
      ]);

      if (!isset($GLOBALS['__PHPUNIT_BOOTSTRAP']) && \Drupal::hasContainer()) {
        $userMgrResponse->setMessage(t('Invitation process complete. Please login to continue.'));
      }
      $userMgrResponse->setSuccess(TRUE);
      $userMgrResponse->setRedirect('<front>');
    }
    else {
      $this->logger->error('Error during acceptInvite:  %error' , array('%error' => $invitationResponse->getErrors()[0]));
      if (!isset($GLOBALS['__PHPUNIT_BOOTSTRAP']) && \Drupal::hasContainer()) {
        $userMgrResponse->setMessage(t('Error during account registration: %error' , array('%error' => $invitationResponse->getErrors()[0])));
      }
      $userMgrResponse->setSuccess(FALSE);
      $userMgrResponse->setRedirect('<front>');
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $userMgrResponse);
    }
    return $userMgrResponse;
  }

  /**
   * @inheritDoc
   */
  public function userManagedSignUp(ApicUser $new_user) {
      if (function_exists('ibm_apim_entry_trace')) {
        ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      $mgmtResponse = $this->mgmtServer->postSignUp($new_user);
      $userManagerResponse = new UserManagerResponse();

      if($mgmtResponse == NULL) {
        $userManagerResponse = NULL;
      }
      else if ($mgmtResponse->getCode() === 204) {
        $this->registerApicUser($new_user->getUsername(), $this->userService->getUserAccountFields($new_user));

        $userManagerResponse->setSuccess(TRUE);

        $this->logger->notice('sign-up processed for %username', [
          '%username' => $new_user->getUsername(),
        ]);

        if (!isset($GLOBALS['__PHPUNIT_BOOTSTRAP']) && \Drupal::hasContainer()) {
          $userManagerResponse->setMessage(t('Your account was created successfully. You will receive an email with activation instructions.'));
        }
        $userManagerResponse->setRedirect('<front>');

      }
      // TODO: non 204

      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $userManagerResponse);
      }
      return $userManagerResponse;
  }

  /**
   * @inheritDoc
   */
  public function nonUserManagedSignUp(ApicUser $new_user) {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $mgmtResponse = $this->mgmtServer->getAuth($new_user);

    $userManagerResponse = new UserManagerResponse();

    if ($mgmtResponse) {
      // For !user_managed registries there is no real sign up process so this is just an authentication check.
      // If we have a response then all is good, report this and inform the user to log in.
      $userManagerResponse->setSuccess(TRUE);
      $this->logger->notice('non user managed sign-up processed for %username', [
        '%username' => $new_user->getUsername(),
      ]);

      if (!isset($GLOBALS['__PHPUNIT_BOOTSTRAP']) && \Drupal::hasContainer()) {
        $userManagerResponse->setMessage(t('Your account was created successfully. You may now log in.'));
      }
      $userManagerResponse->setRedirect('<front>');

    }
    else {
      $userManagerResponse->setSuccess(FALSE);
      $this->logger->error('error during sign-up process, no token retrieved.');

      if (!isset($GLOBALS['__PHPUNIT_BOOTSTRAP']) && \Drupal::hasContainer()) {
        $userManagerResponse->setMessage(t('There was an error creating your account. Please contact the system administrator.'));
      }
      $userManagerResponse->setRedirect('<front>');
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $userManagerResponse);
    }
    return $userManagerResponse;
  }


  /**
   * @inheritDoc
   */
  public function login(ApicUser $user) {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $loginResponse = new UserManagerResponse();

    // At this point we are unauthenticated in Drupal so we DON'T want to
    // store the token in the private session store (FALSE)
    $token_retrieved = $this->mgmtServer->getAuth($user);
    if(!$token_retrieved) {
      $this->logger->error('unable to retrieve bearer token on login.');
      $loginResponse->setSuccess(FALSE);
      if (!isset($GLOBALS['__PHPUNIT_BOOTSTRAP']) && \Drupal::hasContainer()) {
        $loginResponse->setMessage(t('Unable to retrieve bearer token, please contact the system administrator.'));
      }
      if (function_exists('ibm_apim_exit_trace')) {
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
      }
      return $loginResponse;
    }

    $apic_me = $this->mgmtServer->getMe($token_retrieved);

    if ($apic_me->getCode() !== 200) {
      $this->logger->error('failed to authenticate with APIM server');
      // Not successfully authenticated with management server, do not log in.
      $loginResponse->setSuccess(FALSE);
      $loginResponse->setMessage(serialize($apic_me->getData()));
    }
    else {

      // Pull the existing account out of the drupal db or create it if it doesn't exist yet
      $this->createOrGetLocalAccount($user);

      // in all cases check whether anything has been updated in the account.
      // the response from the management server is what we need to store.
      $updatedUser = $apic_me->getUser();
      $updatedUser->setUsername($user->getUsername());

      $this->updateLocalAccount($updatedUser);

      // Read the full account that we now have everything in the db.
      $account = $this->externalAuth->load($user->getUsername(), $this->provider);

      $this->externalAuth->userLoginFinalize($account, $user->getUsername(), $this->provider);
      $this->logger->notice('@username [UID=@uid] logged in.', array(
        '@username' => $user->getUsername(),
        '@uid' => $account->get('uid')->value,
      ));

      // we'll need to keep this information around for some of the other user flows (e.g. change password)
      $account->set('apic_user_registry_url', $user->getApicUserRegistryUrl());
      $account->set('apic_url', $updatedUser->getUrl());
      $account->save();

      // TODO: refactor consumer org handling out to separate function?

      // Now we have called userLoginFinalize we are logged in to drupal and
      // have a new private tempstore for this user so we need to setAuth again
      $this->mgmtServer->setAuth($user);

      // If this is the first log in for this user, user_consumerorg_url will not be populated.
      // We rely on this field to be able to set the current dev org and populate the consumerorg selector.
      $apicMeConsumerorgs = $apic_me->getUser()->getConsumerorgs();
      $consumerorg_urls = $account->get('consumerorg_url')->getValue();

      foreach ($apicMeConsumerorgs as $nextApicConsumerorg) {

        $org_is_new = TRUE;
        foreach ($consumerorg_urls as $index => $valueArray) {
          $nextExistingConsumerorgUrl = $valueArray['value'];
          if ($nextExistingConsumerorgUrl == $nextApicConsumerorg->getUrl()) {
            // Already in the list, don't add it again.
            $org_is_new = FALSE;
          }
        }

        if ($org_is_new) {
          $consumerorg_urls[] = array("value" => $nextApicConsumerorg->getUrl());
        }
      }

      // Update field and save
      $account->set('consumerorg_url', $consumerorg_urls);
      $account->save();

      // We may not have a consumerorg in our database for this user. Check and create as required.
      foreach ($apicMeConsumerorgs as $nextApicConsumerorg) {
        $this->createOrUpdateLocalConsumerorg($nextApicConsumerorg, $updatedUser);
      }

      // if not logging in as admin then set current consumerorg to first in the list returned by apic
      if ($account->id() != 1) {
        $this->userUtils->setCurrentConsumerorg();
        $this->userUtils->setOrgSessionData();
      }

      $loginResponse->setSuccess(TRUE);
      $loginResponse->setUid($account->get('uid')->value);
    }
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    return $loginResponse;
  }

  /**
   * Activate user directly in db to avoid triggering email notification.
   *
   * @param $uid
   *   UID of user to be activated.
   */
  private function activateUserInDB($uid = NULL) {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $uid);
    }
    $this->logger->notice('Activating %uid directly in the database.', array("%uid" => $uid));
    $query = $this->database->update('users_field_data');
    // TODO more reliable way to work out we are not in unit test environment here.
    if ($query != NULL) {
      $query->fields(['status' => 1,]);
      $query->condition('uid', $uid);
      $query->execute();
    }
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
  }

  /**
   * Process the provided JWTToken and activate the user account associated with it
   *
   * @param \Drupal\auth_apic\JWTToken $jwt
   */
  public function activateFromActivationToken(JWTToken $jwt) {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    $headers = array(
      'Content-Type: application/json',
      'Accept: application/json',
      'Authorization: Bearer ' . $jwt->getDecodedJwt(),
      'X-IBM-Consumer-Context: ' . $this->siteConfig->getOrgId() . '.' . $this->siteConfig->getEnvId(),
      'X-IBM-Client-Id: ' . $this->siteConfig->getClientId(),
      'X-IBM-Client-Secret: ' . $this->siteConfig->getClientSecret()
    );

    $mgmt_result = ApicRest::json_http_request($jwt->getUrl(), 'POST', $headers, '');

    $contact_link = \Drupal::l(t('Contact the site administrator.'), \Drupal\Core\Url::fromRoute('contact.site_page'));

    if($mgmt_result->code === 401) {
      drupal_set_message(t('There was an error while processing your activation. Has this activation link already been used?'), 'error');
      \Drupal::logger('auth_apic')->error('Error while processing user activation. Received response code \'@code\' from backend. 
        Message from backend was \'@message\'.', array('@code' => $mgmt_result->code, '@message' => $mgmt_result->data['message'][0]));
    } else if($mgmt_result->code !== 204) {
      drupal_set_message(t('There was an error while processing your activation. @contact_link', array('@contact_link' => $contact_link)), 'error');
      \Drupal::logger('auth_apic')->error('Error while processing user activation. Received response code \'@code\' from backend. 
        Message from backend was \'@message\'.', array('@code' => $mgmt_result->code, '@message' => $mgmt_result->data['message'][0]));
    } else {
      // We can activate the account in our local database and allow the user to log in
      $user_mail = $jwt->getPayload()['email'];
      $account = $this->externalAuth->load($user_mail, $this->provider);

      if(!$account) {
        // username is not equal to email address - need to do a lookup
        $ids = \Drupal::entityQuery('user')->execute();
        $users = User::loadMultiple($ids);

        foreach ($users as $user) {
          if ($user->getEmail() == $user_mail) {
            $account = $this->externalAuth->load($user->getUsername(), $this->provider);
          }
        }
      }

      if(!$account){
        drupal_set_message(t('There was an error while processing your activation. @contact_link', array('@contact_link' => $contact_link)), 'error');
        \Drupal::logger('auth_apic')->error('Error while processing user activation. Could not find account in our database for @mail',
          array('@mail' => $user_mail));
      } else {
        $account->activate();
        $account->save();

        // all is well - direct the user to log in!
        drupal_set_message(t('Your account has been activated. You can now log in.'));
      }
    }
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
  }

  /**
   * @inheritDoc
   */
  public function registerApicUser($username = NULL, array $fields) {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $username);
    }
    $returnValue = NULL;
    try {

      // The code inside this if statement isn't valid in the unit test environment where we have no Drupal instance
      if (!isset($GLOBALS['__PHPUNIT_BOOTSTRAP']) && \Drupal::hasContainer()) {

        // Check if the account already exists before creating it
        // This supports the ibmsocial_login case where users are created in drupal before
        // we register them with the mgmt appliance (this is out of our control)
        $ids = \Drupal::entityQuery('user')->execute();
        $users = User::loadMultiple($ids);

        foreach ($users as $user) {
          if ($user->getUsername() == $username) {
            // Ensure that there is an authmap entry for the user. There isn't if the account was created by OIDC.
            $this->externalAuth->linkExistingAccount($username, $this->provider, $user);
            $returnValue = $user;
          }
        }
      }
      if ($returnValue == NULL) {
        $account = $this->externalAuth->register($username, $this->provider, $fields);
        $returnValue = $account;
      }

      // For all non-admin users, don't store their password in our database.
      if($account->id() !== 1) {
        $account->setPassword(NULL);
        $account->save();
      }

    } catch (ExternalAuthRegisterException $e) {
      throw $e;
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    return $returnValue;
  }

  /**
   * @inheritDoc
   */
  public function updateLocalAccount(ApicUser $user) {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $user);
    }

    $returnValue = TRUE;
    if ($user->getUsername() == "admin") {
      $account = User::load(1);
    }
    else {
      $account = $this->externalAuth->load($user->getUsername(), $this->provider);
    }

    if ($account === FALSE || $account === NULL) {
      // No matching account was found. Probably we don't have someone with this username. Log and ignore.
      $this->logger->notice("Attempted to update account data for user with username '@username' but we didn't find this user in our authmap.", array(
        '@username' => $user->getUsername()
      ));

      if (!isset($GLOBALS['__PHPUNIT_BOOTSTRAP']) && \Drupal::hasContainer()) {
        drupal_set_message(t("Failed to update your account data. Contact your site administrator."), "error");
      }

      $this->logger->error("Failed to update local account data for username '@username'.", array(
        '@username' => $user->getUsername()
      ));

      $returnValue = FALSE;

    } else {
      $account->set('first_name', $user->getFirstname());
      $account->set('last_name', $user->getLastname());
      $account->set('mail', $user->getMail());

      // For all non-admin users, don't store their password in our database.
      if($account->id() !== "1") {
        $account->setPassword(NULL);
      }

      $account->save();
    }
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    }
    return $returnValue;
  }

  /**
   * @inheritDoc
   */
  public function updateLocalAccountRoles(ApicUser $user, $roles) {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    if ($user->getUsername() == "admin") {
      $account = User::load(1);
    }
    else {
      $account = $this->externalAuth->load($user->getUsername(), $this->provider);
    }

    // Splat all of the old roles
    $existingRoles = $account->getRoles();
    foreach ($existingRoles as $role) {
      $account->removeRole($role);
    }

    // Add all of the new roles
    unset($roles['authenticated']);          // This isn't a 'proper' role so remove it
    foreach ($roles as $role) {
      if ($role != 'authenticated') {
        $account->addRole($role);
      }
    }

    $account->save();

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    return TRUE;
  }

  /**
   * @inheritDoc
   */
  public function updateApicAccount(ApicUser $user) {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $returnValue = TRUE;
    $apic_me = $this->mgmtServer->updateMe($user);

    if ($apic_me->getCode() !== 200) {

      // The management server rejected our update. Log the error.
      if (!isset($GLOBALS['__PHPUNIT_BOOTSTRAP']) && \Drupal::hasContainer()) {
        drupal_set_message(t("There was an error while saving your account data. Contact your site administrator."), "error");
      }

      $this->logger->error("Failed to update a user in the management server. Response code was @code and error message was @error", array(
        '@code' => $apic_me->getCode(),
        '@error' => $apic_me->getErrors()
      ));
      $returnValue = FALSE;
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    }
    return $returnValue;
  }

  /**
   * @inheritDoc
   */
  public function resetPassword(JWTToken $obj, $password) {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $mgmtResponse = $this->mgmtServer->resetPassword($obj, $password);
    $code = $mgmtResponse->getCode();

    if ($code !== 204) {
      $this->logger->notice('Error resetting password.');
      $this->logger->error('Reset password response: %result', array('%result' => serialize($mgmtResponse)));
      if (!isset($GLOBALS['__PHPUNIT_BOOTSTRAP']) && \Drupal::hasContainer()) {
        drupal_set_message(t('Error resetting password. Contact the system administrator.'), 'error');
        // If we have more information then provide it to the user as well.
        if ($mgmtResponse->getErrors() !== NULL) {
          drupal_set_message('Error detail:', 'error');
          // Show the errors that the server has returned.
          foreach ($mgmtResponse->getErrors() as $error) {
            drupal_set_message('  ' . $error, 'error');
          }
        }
      }
    }
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $code);
    }
    return $code;
  }


  /**
   * @inheritdoc
   */
  public function changePassword($user, $old_password, $new_password) {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $this->logger->notice('changePassword called for %username', array('%username' => $user->get('name')->value));
    $mgmtResponse = $this->mgmtServer->changePassword($old_password, $new_password);
    if ($mgmtResponse->getCode() == 204) {
      $this->logger->notice('Password changed successfully.');
      $apic_user = new ApicUser();
      $apic_user->setUsername($user->get('name')->value);
      $apic_user->setPassword($new_password);
      $apic_user->setApicUserRegistryUrl($user->get('apic_user_registry_url')->value);
      $this->mgmtServer->setAuth($apic_user);
      $returnValue = TRUE;
    }
    else {
      $this->logger->error('Password change failure.');
      $returnValue = FALSE;
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    return $returnValue;
  }

  /**
   * Checks to see if the user details provided match with an account in the drupal database.
   * If not, an account is created.
   */
  public function createOrGetLocalAccount(ApicUser $user) {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    // Load this username, this will show if drupal record exists for this user.
    $account = $this->externalAuth->load($user->getUsername(), $this->provider);

    // If user doesn't already exist in the drupal db, create them.
    if (!$account) {

      $this->logger->notice('(Login) Registering and activating user in drupal database (username=@username)', array('@username' => $user->getUsername()));

      $account = $this->registerApicUser($user->getUsername(), $this->userService->getUserAccountFields($user));

      $this->activateUserInDB($account->get('uid')->value);

    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
    return $account;
  }

  /**
   * Checks to see if a consumerorg as specified by the consumerorg array exists already. If it does, the consumerorg
   * is returned. If it doesn't, the contents of the array are used to create the org. If the org is to be created,
   * the provided user is the owner.
   *
   * The consumerorg array should contain id and name parameters at a minimum.

   * @param \Drupal\consumerorg\ApicType\ConsumerOrg $consumerorg
   * @param \Drupal\ibm_apim\ApicType\ApicUser $user
   *
   * @return \Drupal\node\Entity\Node
   */
  public function createOrUpdateLocalConsumerorg($consumerorg, ApicUser $user) {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $consumerorg);
    }

    $theOrg = $this->consumerOrgService->get($consumerorg->getUrl());

    if ($theOrg == NULL) {
      // This consumerorg exists in APIC but not in drupal so create it.
      $this->logger->notice('Consumerorg @consumerorgname (url=@consumerorgurl) was not found in drupal database during login. It will be created.', array(
        '@consumerorgurl' => $consumerorg->getUrl(),
        '@consumerorgname' => $consumerorg->getName()
      ));

      if ($consumerorg->getTitle() === NULL) {
        // Create call expects a 'title' value but we don't have one at this point. Use 'name'.
        $consumerorg->setTitle($consumerorg->getName());
      }
      if ($consumerorg->getOwnerUrl() === NULL) {
        $consumerorg->setOwnerUrl($user->getUrl());
      }
      if ($consumerorg->getMembers() === NULL) {
        $consumerorg->setMembers(array());
      }

      $this->consumerOrgService->createNode($consumerorg);

      $theOrg = $this->consumerOrgService->get($consumerorg->getUrl());
    }

    // regardless of whether we just created the org or not, we may need to update membership
    // if this user is not listed already as a member of the org, add them
    if($consumerorg->isMember($user->getUrl()) === FALSE) {

      // get existing members and roles so they can be preserved
      $consumerorg->addMembers($theOrg->getMembers());
      $consumerorg->addRoles($theOrg->getRoles());

      $this->consumerOrgService->createOrUpdateNode($consumerorg, 'login-update-members');
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $theOrg);
    }
    return $theOrg;
  }

  /**
   * @inheritDoc
   */
  public function deleteLocalAccount(ApicUser $user) {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $uid = user_load_by_name($user->getUsername())->get('uid')->value;

    user_cancel(array(), $uid, 'user_cancel_reassign');

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
  }

  /**
   * @inheritdoc
   */
  public function findUserInDatabase($username){
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $account = $this->externalAuth->load($username, $this->provider);

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $account);
    }

    return $account;
  }

  /**
   * @inheritdoc
   */
  public function findUserByUrl($url) {

    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $account = NULL;

    $users = User::loadMultiple();
    foreach($users as $user) {
      if($user->apic_url->value === $url){
        $account = $user;
        break;
      }
    }

    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $account);
    }

    return $account;
  }

}
