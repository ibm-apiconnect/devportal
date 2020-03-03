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

namespace Drupal\auth_apic\Form;

use Drupal\auth_apic\Service\Interfaces\OidcRegistryServiceInterface;
use Drupal\auth_apic\UserManagement\ApicInvitationInterface;
use Drupal\auth_apic\UserManagement\SignUpInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\ApicType\UserRegistry;
use Drupal\ibm_apim\Service\ApicUserService;
use Drupal\ibm_apim\Service\ApimUtils;
use Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface;
use Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface;
use Drupal\ibm_apim\UserManagement\ApicAccountInterface;
use Drupal\session_based_temp_store\SessionBasedTempStoreFactory;
use Drupal\user\RegisterForm;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Self sign up / create new user form.
 */
class ApicUserRegisterForm extends RegisterForm {

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @var \Drupal\ibm_apim\UserManagement\ApicAccountInterface
   */
  protected $accountService;

  protected $userRegistries;

  /**
   * @var \Drupal\ibm_apim\Service\ApicUserService
   */
  protected $userService;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface
   */
  protected $userRegistryService;

  /**
   * @var \Drupal\ibm_apim\Service\ApimUtils
   */
  protected $apimUtils;

  /**
   * @var \Drupal\auth_apic\Service\Interfaces\OidcRegistryServiceInterface
   */
  protected $oidcService;

  /**
   * @var \Drupal\session_based_temp_store\SessionBasedTempStoreFactory
   */
  protected $authApicSessionStore;

  /**
   * @var \Drupal\auth_apic\UserManagement\SignUpInterface
   */
  protected $userManagedSignUp;

  /**
   * @var \Drupal\auth_apic\UserManagement\SignUpInterface
   */
  protected $nonUserManagedSignUp;

  /**
   * @var \Drupal\auth_apic\UserManagement\ApicInvitationInterface
   */
  protected $invitationService;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface
   */
  protected $userStorage;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityManagerInterface $entity_manager,
                              LanguageManagerInterface $language_manager,
                              LoggerInterface $logger,
                              ApicAccountInterface $account_service,
                              UserRegistryServiceInterface $userRegistryService,
                              ApicUserService $userService,
                              EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL,
                              TimeInterface $time = NULL,
                              ApimUtils $apim_utils,
                              OidcRegistryServiceInterface $oidc_service,
                              SessionBasedTempStoreFactory $sessionStoreFactory,
                              SignUpInterface $user_managed_signup,
                              SignUpInterface $non_user_managed_signup,
                              ApicInvitationInterface $invitation_service,
                              ApicUserStorageInterface $user_storage,
                              CacheBackendInterface $cache_backend) {
    parent::__construct($entity_manager, $language_manager, $entity_type_bundle_info, $time);
    $this->logger = $logger;
    $this->accountService = $account_service;
    $this->userRegistryService = $userRegistryService;
    $this->userService = $userService;
    $this->apimUtils = $apim_utils;
    $this->oidcService = $oidc_service;
    $this->authApicSessionStore = $sessionStoreFactory->get('auth_apic_invitation_token');
    $this->userManagedSignUp = $user_managed_signup;
    $this->nonUserManagedSignUp = $non_user_managed_signup;
    $this->invitationService = $invitation_service;
    $this->userStorage = $user_storage;
    $this->cacheBackend = $cache_backend;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('logger.channel.auth_apic'),
      $container->get('ibm_apim.account'),
      $container->get('ibm_apim.user_registry'),
      $container->get('ibm_apim.apicuser'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('ibm_apim.apim_utils'),
      $container->get('auth_apic.oidc'),
      $container->get('session_based_temp_store'),
      $container->get('auth_apic.usermanaged_signup'),
      $container->get('auth_apic.nonusermanaged_signup'),
      $container->get('auth_apic.invitation'),
      $container->get('ibm_apim.user_storage'),
      $container->get('cache.default')
    );
  }

  /**
   * @inheritdoc
   */
  public function form(array $form, FormStateInterface $form_state) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $member_invitation = FALSE;

    $form = parent::form($form, $form_state);

    $form['#form_id'] = $this->getFormId();
    $form['account']['roles'] = [];
    $form['account']['roles']['#default_value'] = ['authenticated' => 'authenticated'];

    // which fields displayed is controlled by config, we need to read and honour this.
    // we need an entity to work from, but this is for register so anonymous is the best we can do.
    $entity_form = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load('user.user.register');

    // if there are no registries on the catalog then bail out.
    $all_registries = $this->userRegistryService->getAll();
    if (empty($all_registries)) {
      drupal_set_message(t('Self-service onboarding not possible: No user registries defined.'), 'error');
      throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
    }

    // Hide the multi-value submit for consumerorgs regardless of whether we are going to add it again.
    $form['consumer_organization']['#access'] = FALSE;
    $form['consumer_organization']['#required'] = FALSE;

    // if we are on the invited user flow, there will be a JWT in the session so grab that
    // we can use this to pre-populate the email field
    $jwt = $this->authApicSessionStore->get('invitation_object');
    if ($jwt !== NULL) {
      $form['#message']['message'] = t('To complete your invitation, fill out any required fields below.');

      // for andre inviting another andre, we won't need the consumer org field
      if (strpos($jwt->getPayload()['scopes']['url'], '/member-invitations/')) {
        $member_invitation = TRUE;
      }
    }

    // If self onboarding is disabled and this is not the invited user flow then bail out.
    if ((boolean) \Drupal::state()->get('ibm_apim.selfSignUpEnabled', TRUE) === FALSE && empty($jwt)) {
      drupal_set_message(t('Self-service onboarding is disabled for this site.'), 'error');
      throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
    }

    // decide which registry is going on the left side and which buttons to put on the right
    // we have two options :
    //  1) display the default registry on the left and the others on the right
    //  2) display the "chosen" registry on the left (second time through after clicking a registry button)
    $chosen_registry = $this->userRegistryService->getDefaultRegistry();
    $chosen_registry_url = \Drupal::request()->query->get('registry_url');
    if (!empty($chosen_registry_url) && array_key_exists($chosen_registry_url, $all_registries) && ($this->apimUtils->sanitizeRegistryUrl($chosen_registry_url) === 1)) {
      $chosen_registry = $all_registries[$chosen_registry_url];
    }

    // store chosen registry in form so we can use it in validation and submit
    $form['registry_url'] = [
      '#type' => 'hidden',
      '#value' => $chosen_registry->getUrl(),
    ];
    // store the name for the template
    $form['#registry_title']['registry_title'] = $chosen_registry->getTitle();

    if (sizeof($all_registries) > 1) {
      $other_registries = array_diff_key($all_registries, [$chosen_registry->getUrl() => $chosen_registry]);
    }

    // The rest of the fields depend on whether this is LUR, LDAP etc
    if ($chosen_registry->isUserManaged()) {

      if (!$member_invitation) {
        // override multi select consumer org panel with just a textfield
        $form['consumerorg'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Consumer organization'),
          '#required' => TRUE,
          '#weight' => $form['consumer_organization']['#weight'],
          '#description' => $this->t('Provide a name for your consumer organization such as "ACME Enterprises".'),
        ];
      }

      if (!empty($jwt)) {
        if (isset($form['account']['mail'])) {
          $form['account']['mail']['#value'] = $jwt->getPayload()['email'];
          $form['account']['mail']['#disabled'] = TRUE;
          unset($form['account']['mail']['#description']);
        }
        if (isset($form['mail'])) {
          $form['mail']['#value'] = $jwt->getPayload()['email'];
          $form['mail']['#disabled'] = TRUE;
          unset($form['mail']['#description']);
        }
      }

      // If the password policy module is enabled, modify this form to show
      // the configured policy.
      $moduleService = \Drupal::service('module_handler');
      $showPasswordPolicy = FALSE;

      if ($moduleService->moduleExists('password_policy')) {
        $showPasswordPolicy = _password_policy_show_policy();
      }

      if ($showPasswordPolicy) {
        $form['ibm-apim-password-policy-status'] = ibm_apim_password_policy_check_constraints($form, $form_state);
        $form['ibm-apim-password-policy-status']['#weight'] = 10;
        $form['#attached']['drupalSettings']['ibmApimPassword'] = ibm_apim_password_policy_client_settings($form, $form_state);
      }

      if (isset($form['account']['pass'])) {
        if (empty($form['account']['pass']['#attributes'])) {
          $form['account']['pass']['#attributes'] = [];
        }
        $form['account']['pass']['#attributes']['autocomplete'] = 'off';
      }
      if (isset($form['account']['name'])) {
        if (empty($form['account']['name']['#attributes'])) {
          $form['account']['name']['#attributes'] = [];
        }
        $form['account']['name']['#attributes']['autocomplete'] = 'off';
      }
      if (isset($form['account']['mail'])) {
        if (empty($form['account']['mail']['#attributes'])) {
          $form['account']['mail']['#attributes'] = [];
        }
        $form['account']['mail']['#attributes']['autocomplete'] = 'off';
      }

      // first_name and last_name aren't required in our config as they aren't stored in some registries, but they are needed for LUR.
      if ($chosen_registry->getRegistryType() === 'lur') {
        if (isset($form['first_name'])) {
          $form['first_name']['widget'][0]['value']['#required'] = TRUE;
        }
        if (isset($form['last_name'])) {
          $form['last_name']['widget'][0]['value']['#required'] = TRUE;
        }
      }

    }
    else {

      // user exists already in the registry
      unset($form['account']['pass'], $form['account']['mail']);

      if ($chosen_registry->getRegistryType() === 'oidc') {
        // for oidc we don't need to present a username/ password + submit form... just a button.

        $oidc_info = $this->oidcService->getOidcMetadata($chosen_registry, $jwt);
        $button = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => [
            'class' => [
              'apic-user-registry-button',
              'apic-user-registry-' . $chosen_registry->getRegistryType(),
            ],
          ],
          '#name' => $chosen_registry->getName(),
          '#url' => $chosen_registry->getUrl(),
          '#limit_validation_errors' => [],
          '#prefix' => '<a class="chosen-registry-button registry-button generic-button button" href="' . $oidc_info['az_url'] . '"  title="' . $this->t('Create account using @ur', ['@ur' => $chosen_registry->getTitle()]) . '">' .
            $oidc_info['image'] .
            '<span class="registry-name">' . $chosen_registry->getTitle() . '</span>
                        </a>',
        ];
        $form['oidc_link'] = $button;
        unset($form['account']['name']);

      }
      else {
        // ldap /authurl

        // change name of property to avoid password policy
        $form['pw_no_policy'] = [
          '#type' => 'password',
          '#title' => t('Password'),
          '#size' => 60,
          '#maxlength' => 128,
          '#required' => TRUE,
          '#attributes' => [
            'class' => ['username'],
            'autocorrect' => 'off',
            'autocapitalize' => 'off',
            'spellcheck' => 'false',
          ],
        ];
      }
      // here we are invited from apim, we need to specify a consumer org.
      if (!empty($jwt) && !$member_invitation && $chosen_registry->getRegistryType() !== 'oidc') {
        $form['consumerorg'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Consumer organization'),
          '#required' => TRUE,
          '#weight' => $form['consumer_organization']['#weight'],
          '#description' => $this->t('Provide a name for your consumer organization such as "ACME Enterprises".'),
        ];
      }

      // loop over everything which would be required from the config on a user managed form but isn't in this case.
      if ($entity_form !== NULL) {
        foreach ($entity_form->getComponents() as $name => $options) {
          if ($name !== 'consumer_organization' && $name !== 'account' && $name !== 'pw_no_policy') {
            unset($form[$name]);
          }
        }
      }

    }

    if (!empty($other_registries)) {

      $otherRegistries = [];

      $redirect_with_registry_url = Url::fromRoute('user.register')->toString() . '?registry_url=';

      foreach ($other_registries as $other_registry) {

        $button = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => ['class' => ['apic-user-registry-button', 'apic-user-registry-' . $other_registry->getRegistryType()]],
          '#name' => $other_registry->getName(),
          '#url' => $other_registry->getUrl(),
          '#limit_validation_errors' => [],
        ];
        if ($other_registry->getRegistryType() === 'oidc') {
          $oidc_info = $this->oidcService->getOidcMetadata($other_registry, $jwt);
          $button = [
            '#type' => 'html_tag',
            '#tag' => 'span',
            '#attributes' => [
              'class' => [
                'apic-user-registry-button',
                'apic-user-registry-' . $other_registry->getRegistryType(),
              ],
            ],
            '#name' => $other_registry->getName(),
            '#url' => $other_registry->getUrl(),
            '#limit_validation_errors' => [],
            '#prefix' => '<a class="registry-button generic-button button" href="' . $oidc_info['az_url'] . '">' .
              $oidc_info['image'] .
              '<span class="registry-name">' . $other_registry->getTitle() . '</span>
                          </a>',
          ];
        }
        else {
          $button['#prefix'] = '<a class="registry-button generic-button button" href="' . $redirect_with_registry_url . $other_registry->getUrl() . '" title="' . $this->t('Create account using @ur', ['@ur' => $other_registry->getTitle()]) . '">
                                <svg width="18" height="18" viewBox="0 0 32 32" fill-rule="evenodd"><path d="M16 6.4c3.9 0 7 3.1 7 7s-3.1 7-7 7-7-3.1-7-7 3.1-7 7-7zm0-2c-5 0-9 4-9 9s4 9 9 9 9-4 9-9-4-9-9-9z"></path>
                                <path d="M16 0C7.2 0 0 7.2 0 16s7.2 16 16 16 16-7.2 16-16S24.8 0 16 0zm7.3 24.3H8.7c-1.2 0-2.2.5-2.8 1.3C3.5 23.1 2 19.7 2 16 2 8.3 8.3 2 16 2s14 6.3 14 14c0 3.7-1.5 7.1-3.9 9.6-.6-.8-1.7-1.3-2.8-1.3z"></path></svg>';
          $button['#suffix'] = '<span class="registry-name">' . $other_registry->getTitle() . '</span></a>';
        }

        $otherRegistries[] = $button;
      }
      $form['#otherRegistries']['otherRegistries'] = $otherRegistries;
    }

    // attach custom javascript library to generate corg names from first/last name
    $form['#attached']['library'][] = 'auth_apic/generate-consumerorg-name';

    $form['#attached']['library'][] = 'ibm_apim/single_click';
    $form['#attached']['library'][] = 'ibm_apim/validate_password';

    if (\Drupal::moduleHandler()->moduleExists('page_load_progress') && \Drupal::currentUser()->hasPermission('use page load progress')) {

      // Unconditionally attach assets to the page.
      $form['#attached']['library'][] = 'auth_apic/oidc_page_load_progress';

      $pjp_config = \Drupal::config('page_load_progress.settings');
      // Attach config settings.
      $form['#attached']['drupalSettings']['oidc_page_load_progress'] = [
        'esc_key' => $pjp_config->get('page_load_progress_esc_key')
      ];
    }

    // need to add cache context for the query param
    if (!isset($form['#cache'])) {
      $form['#cache'] = [];
    }
    if (!isset($form['#cache']['contexts'])) {
      $form['#cache']['contexts'] = [];
    }
    $form['#cache']['contexts'][] = 'url.query_args:registry_url';

    // and clear the cache for captcha placement
    $this->cacheBackend->delete('captcha_placement_map_cache');

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $form;
  }

  /**
   * @inheritdoc
   */
  protected function actions(array $form, FormStateInterface $form_state): array {
    $element = parent::actions($form, $form_state);
    if (isset($form['oidc_link'])) {
      // oidc is currently handled via a link, suppress the submit button.
      unset($element['submit']);
    }
    else {
      $element['submit']['#value'] = $this->t('Sign up');
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $registry_url = $form_state->getValue('registry_url');
    $registry = $this->userRegistryService->get($registry_url);

    $this->validateUniqueUser($form_state, $registry);

    // Set the form redirect to the homepage.
    $language = $this->languageManager->getCurrentLanguage();
    $form_state->setRedirect('<front>', [], ['language' => $language]);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $registry_url = $form_state->getValue('registry_url');
    $registry = $this->userRegistryService->get($registry_url);

    $this->submitToApim($form_state, $registry);

    // Clear the JWT from the session as we're done with it now
    $this->authApicSessionStore->delete('invitation_object');

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  public function save(array $form, FormStateInterface $form_state) {
    // no-op on save as we save account via the user manager service.
  }

  private function validateUniqueUser(FormStateInterface $form_state, UserRegistry $registry) : bool{
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    if ($registry !== NULL && $registry->isUserManaged()) {
      // we need to check for existing usernames and email addresses.
      $emailAddress = $form_state->getValue('mail');
      $username = $form_state->getValue('name');

      $user_with_same_email = $this->userStorage->loadUserByEmailAddress($emailAddress);

      $testUser = new ApicUser();
      $testUser->setUsername($username);
      $testUser->setApicUserRegistryUrl($registry->getUrl());
      $username_in_same_registry = $this->userStorage->load($testUser);

      if ($this->isBannedName($username) || $user_with_same_email !== NULL || $username_in_same_registry !== NULL) {
        $signInLink = Url::fromRoute('user.login')->toString();
        $form_state->setErrorByName('',
          t('A problem occurred while attempting to create your account. If you already have an account then please use that to <a href="@link">Sign in</a>.',
            ['@link' => $signInLink])
        );
        ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, FALSE);
        return FALSE;
      }

    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, TRUE);
    return TRUE;
  }

  private function isBannedName(string $username) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $username);
    // explicitly ban some usernames.
    $banned_usernames = ['admin', 'anonymous'];

    $result = in_array(strtolower($username), $banned_usernames, true);

    if ($result) {
      $this->logger->warning('username is banned from registering: %name', ['%name' => $username]);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $result);
    return $result;
  }

  private function submitToApim(FormStateInterface $form_state, UserRegistry $registry) : void{

    $new_user = $this->userService->parseRegisterForm($form_state->getUserInput());
    if (empty($new_user->getMail())) {
      $new_user->setMail($form_state->getValue('mail'));
    }
    $new_user->setApicUserRegistryURL($registry->getUrl());

    $jwt = $this->authApicSessionStore->get('invitation_object');

    if ($jwt !== NULL) {
      // this is an invited user for which we are gathering more information.
      if ($registry->isUserManaged()) {
        $response = $this->invitationService->registerInvitedUser($jwt, $new_user);
      }
      else {
        $response = $this->invitationService->acceptInvite($jwt, $new_user);
      }
    }
    // this is self signup, which is processed differently depending on the user registry.
    elseif ($registry->isUserManaged()) {
      $response = $this->userManagedSignUp->signUp($new_user);
    }
    else {
      $response = $this->nonUserManagedSignUp->signUp($new_user, $registry);
    }

    if ($response === NULL) {
      $form_state->setRedirect('user.register');
    }
    elseif ($response->success()) {

      // we now have an account registered regardless of path taken, so can update with other information we need to store.
      $loaded_user = $this->userStorage->load($new_user);
      if ($loaded_user) {
        $this->accountService->setDefaultLanguage($loaded_user);
        $this->accountService->saveCustomFields($loaded_user, $form_state, 'register');
      }

      drupal_set_message($response->getMessage());
      $form_state->setRedirect($response->getRedirect());
    }
    elseif (strpos($response->getMessage(), 'Passwords must')) {
      // strip out the generic prefix for registration errors as we are going to place something useful next to the field.
      $pw_error = substr($response->getMessage(), strlen('Error during account registration: '));
      $form_state->setErrorByName('pass', $pw_error);
    }
    else {
      $form_state->setRedirect('user.register');
      drupal_set_message($response->getMessage(), 'error');
    }
  }

}
