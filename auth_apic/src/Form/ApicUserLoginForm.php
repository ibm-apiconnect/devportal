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
use Drupal\auth_apic\UserManagement\ApicLoginServiceInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\ApicType\UserRegistry;
use Drupal\ibm_apim\Service\ApimUtils;
use Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface;
use Drupal\ibm_apim\Service\SiteConfig;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\ibm_apim\UserManagement\ApicAccountInterface;
use Drupal\session_based_temp_store\SessionBasedTempStoreFactory;
use Drupal\user\Entity\User;
use Drupal\user\Form\UserLoginForm;
use Drupal\user\UserAuthInterface;
use Drupal\user\UserStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ApicUserLoginForm extends UserLoginForm {

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @var \Drupal\ibm_apim\UserManagement\ApicAccountInterface
   */
  protected $accountService;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface
   */
  protected $userRegistryService;

  /**
   * @var \Drupal\ibm_apim\Service\ApimUtils
   */
  protected $apimUtils;

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
  protected $userUtils;

  /**
   * @var \Drupal\ibm_apim\Service\SiteConfig
   */
  protected $siteConfig;

  /**
   * @var \Drupal\auth_apic\Service\Interfaces\OidcRegistryServiceInterface
   */
  protected $oidcService;

  /**
   * @var
   */
  protected $authApicSessionStore;

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $ibmSettingsConfig;

  /**
   * @var \Drupal\auth_apic\UserManagement\ApicLoginServiceInterface
   */
  protected $loginService;

  /**
   * @var \Drupal\auth_apic\UserManagement\ApicInvitationInterface
   */
  protected $invitationService;
  
  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Constructs a new UserLoginForm.
   *
   * {@inheritdoc}
   */
  public function __construct(FloodInterface $flood,
                              UserStorageInterface $user_storage,
                              UserAuthInterface $user_auth,
                              RendererInterface $renderer,
                              LoggerInterface $logger,
                              ApicAccountInterface $account_service,
                              UserRegistryServiceInterface $user_registry_service,
                              ApimUtils $apim_utils,
                              UserUtils $user_utils,
                              SiteConfig $site_config,
                              OidcRegistryServiceInterface $oidc_service,
                              SessionBasedTempStoreFactory $session_store_factory,
                              Config $ibm_settings_config,
                              ApicLoginServiceInterface $login_service,
                              ApicInvitationInterface $invitation_service,
                              Messenger $messenger) {
    parent::__construct($flood, $user_storage, $user_auth, $renderer);
    $this->logger = $logger;
    $this->accountService = $account_service;
    $this->userRegistryService = $user_registry_service;
    $this->apimUtils = $apim_utils;
    $this->userUtils = $user_utils;
    $this->siteConfig = $site_config;
    $this->oidcService = $oidc_service;
    $this->authApicSessionStore = $session_store_factory->get('auth_apic_invitation_token');
    $this->ibmSettingsConfig = $ibm_settings_config;
    $this->loginService = $login_service;
    $this->invitationService = $invitation_service;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('flood'),
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('user.auth'),
      $container->get('renderer'),
      $container->get('logger.channel.auth_apic'),
      $container->get('ibm_apim.account'),
      $container->get('ibm_apim.user_registry'),
      $container->get('ibm_apim.apim_utils'),
      $container->get('ibm_apim.user_utils'),
      $container->get('ibm_apim.site_config'),
      $container->get('auth_apic.oidc'),
      $container->get('session_based_temp_store'),
      $container->get('config.factory')->get('ibm_apim.settings'),
      $container->get('auth_apic.login'),
      $container->get('auth_apic.invitation'),
      $container->get('messenger')
    );
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $baseForm = parent::buildForm($form, $form_state);

    // if we are on the invited user flow, there will be a JWT in the session so grab that
    $jwt = $this->authApicSessionStore->get('invitation_object');
    if ($jwt !== NULL) {
      $form['#message']['message'] = t('To complete your invitation, sign in to an existing account or sign up to create a new account.');

      if (!strpos($jwt->getUrl(), '/member-invitations/')) {
        // and for this case we need a consumer org title as well
        $baseForm['consumer_org'] = [
          '#type' => 'textfield',
          '#title' => t('Consumer organization'),
          '#description' => t('You are signing in with an existing account but have been invited to create a new consumer organization, please provide a name for that organization.'),
          '#size' => 60,
          '#maxlength' => 128,
          '#required' => TRUE,
        ];
      }
    }

    // if the page was loaded due to invoking the subscription wizard, put up a more helpful piece of text on the form
    $subscription_wizard_cookie = \Drupal::request()->cookies->get('Drupal_visitor_startSubscriptionWizard');
    if (!empty($subscription_wizard_cookie)) {
      $form['#message']['message'] = t('Sign in to an existing account or create a new account to subscribe to this Product.');
    }

    // work out what user registries are enabled on this catalog
    $registries = $this->userRegistryService->getAll();

    $chosen_registry = $this->userRegistryService->getDefaultRegistry();
    $chosen_registry_url = \Drupal::request()->query->get('registry_url');
    $hide_admin_registry = (bool) $this->ibmSettingsConfig->get('hide_admin_registry');

    // don't present admin login form on invitation flows.
    if (($jwt === NULL && !$hide_admin_registry) || $chosen_registry_url === $this->userRegistryService->getAdminRegistryUrl()) {
      // add dummy registry for admin login to ensure we always have it there
      $this->addAdminOnlyRegistry($registries);
    }

    // if there are no registries on the catalog throw up the default login page
    if (empty($registries)) {
      return $baseForm;
    }

    if (!empty($chosen_registry_url) && array_key_exists($chosen_registry_url, $registries) && ($this->apimUtils->sanitizeRegistryUrl($chosen_registry_url) === 1 || $chosen_registry_url === $this->userRegistryService->getAdminRegistryUrl())) {
      $chosen_registry = $registries[$chosen_registry_url];
    }

    // store registry_url for validate/submit
    $form['registry_url'] = [
      '#type' => 'hidden',
      '#value' => $chosen_registry->getUrl(),
    ];

    // store registry_url for template
    $form['#registry_url']['registry_url'] = $chosen_registry->getUrl();


    if (sizeof($registries) > 1) {
      $other_registries = array_diff_key($registries, [$chosen_registry->getUrl() => $chosen_registry]);
    }

    // build the form
    // Build a container for the section headers to go in
    $form['headers_container'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['apic-user-form-container']],
    ];

    // Explain this part of the form
    $form['headers_container']['signin_label'] = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => t('Sign in with @registryName', ['@registryName' => $chosen_registry->getTitle()]),
      '#attributes' => ['class' => ['apic-user-form-subheader']],
      '#weight' => -1000,
    ];

    if (!empty($other_registries)) {
      // Explain the extra buttons
      $form['headers_container']['other_registries_label'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => t('Continue with'),
        '#attributes' => ['class' => ['apic-user-form-subheader']],
        '#weight' => -1000,
      ];
    }

    // Build the form by embedding the other forms
    // Wrap everything in a container so we can set flex display
    $form['main_container'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['apic-user-form-container']],
    ];

    // Embed the default log in form
    // Wrap the whole form in a div that we can style.
    $baseForm['#prefix'] = '<div class="apic-user-form-inner-wrapper">';
    $baseForm['#suffix'] = '</div>';

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
        '#prefix' => '<a class="chosen-registry-button registry-button generic-button button" href="' . $oidc_info['az_url'] . '" title="' . $this->t('Sign in using @ur', ['@ur' => $chosen_registry->getTitle()]) . '">' .
          $oidc_info['image'] .
          '<span class="registry-name">' . $chosen_registry->getTitle() . '</span>
                      </a>',

      ];
      $baseForm['oidc_link'] = $button;
      $baseForm['name']['#access'] = FALSE;
      $baseForm['pass']['#access'] = FALSE;
      $baseForm['actions']['#access'] = FALSE;

    }
    else {
      // Make username and password not required as this prevents form submission if clicking one of the
      // buttons on the right hand side
      $baseForm['name']['#required'] = FALSE;
      $baseForm['name']['#attributes'] = ['autocomplete' => 'off'];
      $baseForm['pass']['#required'] = FALSE;
      $baseForm['pass']['#attributes'] = ['autocomplete' => 'off'];

      if ($chosen_registry->getUrl() === $this->userRegistryService->getAdminRegistryUrl()) {
        $baseForm['actions']['submit']['#value'] = t('Sign in');
        unset($baseForm['actions']['submit']['#icon']);
      }
      else {
        // !oidc login so we need the username/ password + submit
        $baseForm['actions']['submit']['#value'] = t('Sign in');

        // Remove all validation as this also prevents form submission. We put bits back in the validate() function.
        $baseForm['#validate'] = [];
      }
    }
    $form['main_container']['plainlogin'] = $baseForm;

    if (!empty($other_registries)) {
      // Construct another container for the "or" part in the middle of the form
      $form['main_container']['or_container'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['apic-user-form-or-container']],
      ];

      $form['main_container']['or_container']['line1'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => ['class' => ['apic-user-form-line']],
      ];

      $form['main_container']['or_container']['or'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => t('or'),
        '#attributes' => ['class' => ['apic-user-form-or']],
      ];

      $form['main_container']['or_container']['line2'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#attributes' => ['class' => ['apic-user-form-line']],
      ];

      // embed the openid login form
      // Wrap the whole form in a div that we can style.
      $otherRegistriesForm['#prefix'] = '<div class="apic-user-form-inner-wrapper apic-user-form-registries">';
      $otherRegistriesForm['#suffix'] = '</div>';

      $redirect_with_registry_url = Url::fromRoute('user.login')->toString() . '?registry_url=';

      foreach ($other_registries as $other_registry) {

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
            '#prefix' => '<a class="registry-button generic-button button" href="' . $oidc_info['az_url'] . '" title="' . $this->t('Sign in using @ur', ['@ur' => $other_registry->getTitle()]) . '">' .
              $oidc_info['image'] .
              '<span class="registry-name">' . $other_registry->getTitle() . '</span>
                          </a>',
          ];
        }
        else {
          $button['#prefix'] = '<a class="registry-button generic-button button" href="' . $redirect_with_registry_url . $other_registry->getUrl() . '" title="' . $this->t('Sign in using @ur', ['@ur' => $other_registry->getTitle()]) . '">
                                <svg width="18" height="18" viewBox="0 0 32 32" fill-rule="evenodd"><path d="M16 6.4c3.9 0 7 3.1 7 7s-3.1 7-7 7-7-3.1-7-7 3.1-7 7-7zm0-2c-5 0-9 4-9 9s4 9 9 9 9-4 9-9-4-9-9-9z"></path>
                                <path d="M16 0C7.2 0 0 7.2 0 16s7.2 16 16 16 16-7.2 16-16S24.8 0 16 0zm7.3 24.3H8.7c-1.2 0-2.2.5-2.8 1.3C3.5 23.1 2 19.7 2 16 2 8.3 8.3 2 16 2s14 6.3 14 14c0 3.7-1.5 7.1-3.9 9.6-.6-.8-1.7-1.3-2.8-1.3z"></path></svg>';
          $button['#suffix'] = '<span class="registry-name">' . $other_registry->getTitle() . '</span></a>';
        }


        $otherRegistriesForm[] = $button;
      }

      $form['main_container']['other'] = $otherRegistriesForm;
    }
    $form['#attached']['library'][] = 'ibm_apim/single_click';
    if (\Drupal::moduleHandler()->moduleExists('page_load_progress') && \Drupal::currentUser()->hasPermission('use page load progress')) {

      // Unconditionally attach assets to the page.
      $form['#attached']['library'][] = 'auth_apic/oidc_page_load_progress';

      $pjp_config = \Drupal::config('page_load_progress.settings');
      // Attach config settings.
      $form['#attached']['drupalSettings']['oidc_page_load_progress'] = [
        'esc_key' => $pjp_config->get('page_load_progress_esc_key'),
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

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $this->validateName($form, $form_state);

    $apicAuthenticated = $this->validateApicAuthentication($form, $form_state);
    if ($apicAuthenticated !== TRUE) {
      $user_input = $form_state->getUserInput();
      $query = isset($user_input['name']) ? ['name' => $user_input['name']] : [];
      $form_state->setErrorByName('usernameorpassword', $this->t('Unable to sign in. This may be because the credentials provided for authentication are invalid or the user has not been activated. Please check that the user is active, then repeat the request with valid credentials. Please note that repeated attempts with incorrect credentials can lock the user account.'));
      $form_state->setErrorByName('usernameorpassword2', $this->t('<a href=":password">Forgot your password? Click here to reset it.</a>', [':password' => Url::fromRoute('user.pass', [], ['query' => $query])->toString()]));
    }

    $this->validateFinal($form, $form_state);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * @inheritDoc
   */
  public function validateApicAuthentication(array &$form, FormStateInterface $form_state): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $returnValue = FALSE;
    if ($this->validateFloodProtection($form, $form_state)) {
      $name = $form_state->getValue('name');
      $password = $form_state->getValue('pass');
      $corg = $form_state->getValue('consumer_org');

      // maybe this was an invited user?
      $jwt = $this->authApicSessionStore->get('invitation_object');

      $admin = $this->userStorage->load(1);
      // special case the admin user and log in via standard drupal mechanism.
      if ($admin !== NULL && $name === $admin->getUsername()) {

        if ($jwt !== NULL) {
          $this->messenger->addError(t('admin user is not allowed when signing in an invited user.'));
          $returnValue = FALSE;
        }
        else {
          $this->logger->debug('admin login, using core validation for login');
          $this->validateAuthentication($form, $form_state);
          $returnValue = TRUE;
        }
      }
      else {

        $login_user = new ApicUser();
        $login_user->setUsername($name);
        $login_user->setPassword($password);
        if (!empty($corg)) {
          $login_user->setOrganization($corg);
        }
        $login_user->setApicUserRegistryURL($form_state->getValue('registry_url'));

        $registry = $this->userRegistryService->get($form_state->getValue('registry_url'));

        if ($registry !== NULL) {

          if ($jwt !== NULL) {
            $response = $this->invitationService->acceptInvite($jwt, $login_user);

            if ($response->success() === TRUE) {
              $response = $this->loginService->login($login_user);
            }
          }
          else {
            $response = $this->loginService->login($login_user);
          }

          if ($response->success()) {
            $this->authApicSessionStore->delete('invitation_object');
            $form_state->set('uid', $response->getUid());
            $returnValue = TRUE;
          }
          else {
            // unsuccessful login.
            $returnValue = FALSE;
          }
        }
        else {
          $this->logger->error('Failed to login. Unable to determine registry to use from login form.');
          $returnValue = FALSE;
        }
      }
    }

    if (!$returnValue) {
      $this->logger->error('Login attempt for %user which failed in validateApicAuthentication.', ['%user' => $form_state->getValue('name')]);
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    return $returnValue;
  }

  /**
   * Taken from UserLoginForm::validateAuthentication().
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return bool
   */
  protected function validateFloodProtection(array $form, FormStateInterface $form_state): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $returnValue = TRUE;
    $password = trim($form_state->getValue('pass'));
    $flood_config = $this->config('user.flood');
    if ($password !== '' && !$form_state->isValueEmpty('name')) {
      // Do not allow any login from the current user's IP if the limit has been
      // reached. Default is 50 failed attempts allowed in one hour. This is
      // independent of the per-user limit to catch attempts from one IP to log
      // in to many different user accounts.  We have a reasonably high limit
      // since there may be only one apparent IP for all users at an institution.
      if (!$this->flood->isAllowed('user.failed_login_ip', $flood_config->get('ip_limit'), $flood_config->get('ip_window'))) {
        $form_state->set('flood_control_triggered', 'ip');
        $returnValue = FALSE;
      }
      $accounts = $this->userStorage->loadByProperties(['name' => $form_state->getValue('name'), 'status' => 1]);
      $account = reset($accounts);
      if ($account) {
        if ($flood_config->get('uid_only')) {
          // Register flood events based on the uid only, so they apply for any
          // IP address. This is the most secure option.
          $identifier = $account->id();
        }
        else {
          // The default identifier is a combination of uid and IP address. This
          // is less secure but more resistant to denial-of-service attacks that
          // could lock out all users with public user names.
          $identifier = $account->id() . '-' . $this->getRequest()->getClientIp();
        }
        $form_state->set('flood_control_user_identifier', $identifier);

        // Don't allow login if the limit for this user has been reached.
        // Default is to allow 5 failed attempts every 6 hours.
        if (!$this->flood->isAllowed('user.failed_login_user', $flood_config->get('user_limit'), $flood_config->get('user_window'), $identifier)) {
          $form_state->set('flood_control_triggered', 'user');
          $returnValue = FALSE;
        }
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    return $returnValue;
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    // parent form will actually log the use in...
    parent::submitForm($form, $form_state);
    // now we need to check whether:
    // - this is a first time login?
    // - user needs to pick up in a subscription wizard?
    // - user isn't in a consumer org?

    $current_user = \Drupal::currentUser();
    $first_time_login = NULL;
    $subscription_wizard_cookie = NULL;

    if (isset($current_user)) {
      $current_user = User::load($current_user->id());
      $first_time_login = $current_user->first_time_login->value;
      $subscription_wizard_cookie = \Drupal::request()->cookies->get('Drupal_visitor_startSubscriptionWizard');
    }

    // If this is the first login, set langauge for user to browser language.
    if (isset($current_user) && (int) $first_time_login !== 0) {
      $this->accountService->setDefaultLanguage($current_user);
    }

    // check if the user we just logged in is a member of at least one dev org
    $current_corg = $this->userUtils->getCurrentConsumerorg();
    if (!isset($current_corg)) {
      // if onboarding is enabled, we can redirect to the create org page
      if ($this->siteConfig->isSelfOnboardingEnabled()) {
        $form_state->setRedirect('consumerorg.create');
      }
      else {
        // we can't help the user, they need to talk to an administrator
        $form_state->setRedirect('ibm_apim.noperms');
      }
      // if no consumer org then return early, everything else is secondary.
      return;
    }

    if (isset($current_user) && (int) $first_time_login !== 0 && empty($subscription_wizard_cookie)) {
      // set first_time_login to 0 for next time
      $current_user->set('first_time_login', 0);
      $current_user->save();

      $form_state->setRedirect('ibm_apim.get_started');
    }
    elseif (!empty($subscription_wizard_cookie)) {
      // If the startSubscriptionWizard cookie is set, grab the value from it, set up a redirect and delete it
      $form_state->setRedirect('ibm_apim.subscription_wizard.step', [
        'step' => 'chooseplan',
        'productId' => $subscription_wizard_cookie,
      ]);
      user_cookie_delete('startSubscriptionWizard');
    }
    else {
      // this is for the 404 redirect from the apic_app module
      $destination = \Drupal::request()->get('redirectto');
      if (isset($destination) && !empty($destination)) {
        $form_state->setRedirectUrl(Url::fromUserInput($destination));
      }
      else {
        $form_state->setRedirect('<front>');
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * @param $registries
   */
  private function addAdminOnlyRegistry(&$registries): void {
    $admin_reg = new UserRegistry();
    $admin_reg->setRegistryType('admin_only');
    $admin_reg->setUserManaged(TRUE);
    $admin_reg->setName('admin_only');
    $admin_reg->setTitle('admin');
    $admin_reg->setUrl($this->userRegistryService->getAdminRegistryUrl());
    if (!isset($registries)) {
      $registries = [];
    }
    $registries[$admin_reg->getUrl()] = $admin_reg;
  }


}
