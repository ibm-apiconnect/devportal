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

namespace Drupal\auth_apic\Form;


use Drupal\auth_apic\Service\Interfaces\OidcRegistryServiceInterface;
use Drupal\auth_apic\UserManagement\ApicInvitationInterface;
use Drupal\auth_apic\UserManagement\ApicLoginServiceInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\ApicType\UserRegistry;
use Drupal\ibm_apim\Service\ApimUtils;
use Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface;
use Drupal\ibm_apim\Service\SiteConfig;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\ibm_apim\UserManagement\ApicAccountInterface;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\user\Entity\User;
use Drupal\user\Form\UserLoginForm;
use Drupal\user\UserAuthInterface;
use Drupal\user\UserStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\auth_apic\Service\Interfaces\TokenParserInterface;

class ApicUserLoginForm extends UserLoginForm {

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * @var \Drupal\ibm_apim\UserManagement\ApicAccountInterface
   */
  protected ApicAccountInterface $accountService;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface
   */
  protected UserRegistryServiceInterface $userRegistryService;

  /**
   * @var \Drupal\ibm_apim\Service\ApimUtils
   */
  protected ApimUtils $apimUtils;

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
  protected UserUtils $userUtils;

  /**
   * @var \Drupal\ibm_apim\Service\SiteConfig
   */
  protected SiteConfig $siteConfig;

  /**
   * @var \Drupal\auth_apic\Service\Interfaces\OidcRegistryServiceInterface
   */
  protected OidcRegistryServiceInterface $oidcService;

  /**
   * @var \Drupal\Core\TempStore\PrivateTempStore
   */
  protected PrivateTempStore $authApicSessionStore;

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected Config $ibmSettingsConfig;

  /**
   * @var \Drupal\auth_apic\UserManagement\ApicLoginServiceInterface
   */
  protected ApicLoginServiceInterface $loginService;

  /**
   * @var \Drupal\auth_apic\UserManagement\ApicInvitationInterface
   */
  protected ApicInvitationInterface $invitationService;

  /**
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

  /**
   * @var \Drupal\auth_apic\Service\Interfaces\TokenParserInterface
   */
  protected TokenParserInterface $jwtParser;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  protected $chosen_registry;

  /**
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected ModuleHandler $moduleHandler;

  /**
   * ApicUserLoginForm constructor.
   *
   * @param \Drupal\Core\Flood\FloodInterface $flood
   * @param \Drupal\user\UserStorageInterface $user_storage
   * @param \Drupal\user\UserAuthInterface $user_auth
   * @param \Drupal\Core\Render\RendererInterface $renderer
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\ibm_apim\UserManagement\ApicAccountInterface $account_service
   * @param \Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface $user_registry_service
   * @param \Drupal\ibm_apim\Service\ApimUtils $apim_utils
   * @param \Drupal\ibm_apim\Service\UserUtils $user_utils
   * @param \Drupal\ibm_apim\Service\SiteConfig $site_config
   * @param \Drupal\auth_apic\Service\Interfaces\OidcRegistryServiceInterface $oidc_service
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $session_store_factory
   * @param \Drupal\Core\Config\Config $ibm_settings_config
   * @param \Drupal\auth_apic\UserManagement\ApicLoginServiceInterface $login_service
   * @param \Drupal\auth_apic\UserManagement\ApicInvitationInterface $invitation_service
   * @param \Drupal\Core\Messenger\Messenger $messenger
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
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
                              PrivateTempStoreFactory $session_store_factory,
                              Config $ibm_settings_config,
                              ApicLoginServiceInterface $login_service,
                              ApicInvitationInterface $invitation_service,
                              Messenger $messenger,
                              ModuleHandler $module_handler,
                              TokenParserInterface $token_parser) {
    parent::__construct($flood, $user_storage, $user_auth, $renderer);
    $this->flood = $flood;
    $this->logger = $logger;
    $this->accountService = $account_service;
    $this->userRegistryService = $user_registry_service;
    $this->apimUtils = $apim_utils;
    $this->userUtils = $user_utils;
    $this->siteConfig = $site_config;
    $this->oidcService = $oidc_service;
    $this->authApicSessionStore = $session_store_factory->get('auth_apic_storage');
    $this->ibmSettingsConfig = $ibm_settings_config;
    $this->loginService = $login_service;
    $this->invitationService = $invitation_service;
    $this->messenger = $messenger;
    $this->moduleHandler = $module_handler;
    $this->jwtParser = $token_parser;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return \Drupal\auth_apic\Form\ApicUserLoginForm|\Drupal\user\Form\UserLoginForm|static
   */
  public static function create(ContainerInterface $container) {
    /** @noinspection PhpParamsInspection */
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
      $container->get('tempstore.private'),
      $container->get('config.factory')->get('ibm_apim.settings'),
      $container->get('auth_apic.login'),
      $container->get('auth_apic.invitation'),
      $container->get('messenger'),
      $container->get('module_handler'),
      $container->get('auth_apic.jwtparser')
    );
  }

  /**
   * @inheritDoc
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $baseForm = parent::buildForm($form, $form_state);

    $this->authApicSessionStore->set('action', 'signin');

    $enabled_oidc_login_form = (boolean) \Drupal::config('ibm_apim.settings')->get('enable_oidc_login_form');
    $is_owner_invitation = FALSE;

    // if we are on the invited user flow, there will be a JWT in the session so grab that
    $jwt = $this->authApicSessionStore->get('invitation_object');
    if ($jwt === NULL) {
      $inviteToken = \Drupal::request()->query->get('token');
      if ($inviteToken !== NULL) {
        $jwt = $this->jwtParser->parse($inviteToken);
        $this->authApicSessionStore->set('invitation_object', $jwt);
      }
    }
    if ($jwt !== NULL) {
      $form['#message']['message'] = t('To complete your invitation, sign in to an existing account or sign up to create a new account.');

      if (!strpos($jwt->getUrl(), '/member-invitations/')) {
        $is_owner_invitation = TRUE;
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
    $this->authApicSessionStore->delete('redirect_to');
    if (\Drupal::request()->query->get('destination') === 'user/logout') {
      \Drupal::request()->query->remove('destination');
    }
    elseif (\Drupal::request()->query->get('redirectto') === 'user/logout') {
      \Drupal::request()->query->remove('redirectto');
    }
    if (\Drupal::request()->query->has('destination')) {
      $this->authApicSessionStore->set('redirect_to', \Drupal::request()->query->get('destination'));
    }
    elseif (\Drupal::request()->query->has('redirectto')) {
      $this->authApicSessionStore->set('redirect_to', \Drupal::request()->query->get('redirectto'));
    }

    // if the page was loaded due to invoking the subscription wizard, put up a more helpful piece of text on the form
    $subscription_wizard_cookie = \Drupal::request()->cookies->get('Drupal_visitor_startSubscriptionWizard');
    if (!empty($subscription_wizard_cookie)) {
      $form['#message']['message'] = t('Sign in to an existing account or create a new account to subscribe to this Product.');
    }

    // work out what user registries are enabled on this catalog
    $registries = $this->userRegistryService->getAll();

    $this->chosen_registry = $this->userRegistryService->getDefaultRegistry();
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

    if (!empty($chosen_registry_url) && array_key_exists($chosen_registry_url, $registries) && ($chosen_registry_url === $this->userRegistryService->getAdminRegistryUrl() || $this->apimUtils->sanitizeRegistryUrl($chosen_registry_url) === 1)) {
      $this->chosen_registry = $registries[$chosen_registry_url];
    }
    if ($this->chosen_registry !== NULL) {
      $chosenRegistryURL = $this->chosen_registry->getUrl();
      $chosenRegistryTitle = $this->chosen_registry->getTitle();
    } else {
      // if no UR then fallback on using the admin UR (only an issue if we didnt get a UR from APIM)
      $this->chosen_registry = $registries[$this->userRegistryService->getAdminRegistryUrl()];
      if ($this->chosen_registry !== NULL) {
        $chosenRegistryURL = $this->chosen_registry->getUrl();
        $chosenRegistryTitle = $this->chosen_registry->getTitle();
      } else {
        $chosenRegistryURL = 'error';
        $chosenRegistryTitle = 'ERROR';
      }
    }

    // store registry_url for validate/submit
    $form['registry_url'] = [
      '#type' => 'hidden',
      '#value' => $chosenRegistryURL,
    ];

    // store registry_url for template
    $form['#registry_url']['registry_url'] = $chosenRegistryURL;


    if (sizeof($registries) > 1) {
      $other_registries = array_diff_key($registries, [$chosenRegistryURL => $this->chosen_registry]);
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
      '#value' => t('Sign in with @registryName', ['@registryName' => $chosenRegistryTitle]),
      '#attributes' => ['class' => ['apic-user-form-subheader']],
      '#weight' => -1000,
    ];

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

    if ($this->chosen_registry !== NULL && $this->chosen_registry->getRegistryType() === 'oidc') {
      // for oidc we don't need to present a username/ password + submit form... just a button.
      $oidc_info = $this->oidcService->getOidcMetadata($this->chosen_registry, $jwt);
      $baseForm['actions']['submit']['#value'] = t('Sign in');

      if ($enabled_oidc_login_form || $is_owner_invitation) {
        $baseForm['oidc_url'] = [
          '#type' => 'hidden',
          '#value' => $oidc_info['az_url'] . '&action=signin',
        ];

        $baseForm['actions']['submit']['#attributes'] = [
          'class' => [
            'apic-user-registry-button',
            'apic-user-registry-' . $this->chosen_registry->getRegistryType(),
            'registry-button',
          ],
        ];
      }
      else {
        $button = [
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => [
            'class' => [
              'apic-user-registry-button',
              'apic-user-registry-' . $this->chosen_registry->getRegistryType(),
            ],
          ],
          '#name' => $this->chosen_registry->getName(),
          '#url' => $this->chosen_registry->getUrl(),
          '#limit_validation_errors' => [],
          '#prefix' => '<a class="chosen-registry-button registry-button generic-button button" href="' . $oidc_info['az_url'] . '&action=signin" title="' . $this->t('Sign in using @ur', ['@ur' => $this->chosen_registry->getTitle()]) . '">' .
            $oidc_info['image']['html'] .
            '<span class="registry-name">' . $this->chosen_registry->getTitle() . '</span>
                        </a>',

        ];
        $baseForm['oidc_link'] = $button;
        $baseForm['actions']['#access'] = FALSE;
      }
      $baseForm['name']['#access'] = FALSE;
      $baseForm['pass']['#access'] = FALSE;

    }
    else {
      // Make username and password not required as this prevents form submission if clicking one of the
      // buttons on the right hand side
      $baseForm['name']['#required'] = FALSE;
      $baseForm['name']['#attributes'] = ['autocomplete' => 'off'];
      $baseForm['pass']['#required'] = FALSE;
      $baseForm['pass']['#attributes'] = ['autocomplete' => 'off'];

      $baseForm['actions']['submit']['#value'] = t('Sign in');
      if ($chosenRegistryURL === $this->userRegistryService->getAdminRegistryUrl()) {
        unset($baseForm['actions']['submit']['#icon']);
      }
      else {
        // !oidc login so we need the username/ password + submit

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

      // explain the extra buttons
      $otherRegistriesForm['headers_container']['other_registries_label'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => t('Continue with'),
        '#attributes' => ['class' => ['apic-user-form-subheader']],
        '#weight' => -1000,
      ];

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
            '#prefix' => '<a class="registry-button generic-button button" href="' . $oidc_info['az_url'] . '&action=signin" title="' . $this->t('Sign in using @ur', ['@ur' => $other_registry->getTitle()]) . '">' .
              $oidc_info['image']['html'] .
              '<span class="registry-name">' . $other_registry->getTitle() . '</span>
                          </a>',
          ];
          if ($enabled_oidc_login_form || $is_owner_invitation) {
            $button['#prefix'] = '<a class="registry-button generic-button button" href="' . $redirect_with_registry_url . $other_registry->getUrl() . '" title="' . $this->t('Sign in using @ur', ['@ur' => $other_registry->getTitle()]) . '">'
              . $oidc_info['image']['html'] . '<span class="registry-name">' . $other_registry->getTitle() . '</span></a>';
          }
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
    if ($this->moduleHandler->moduleExists('page_load_progress') && \Drupal::currentUser()->hasPermission('use page load progress')) {

      // Unconditionally attach assets to the page.
      $form['#attached']['library'][] = 'auth_apic/oidc_page_load_progress';

      $pjp_config = \Drupal::config('page_load_progress.settings');
      // Attach config settings.
      $form['#attached']['drupalSettings']['oidc_page_load_progress'] = [
        'esc_key' => $pjp_config->get('page_load_progress_esc_key'),
      ];
    }
    if ($this->moduleHandler->moduleExists('social_media_links')) {
      $form['#attached']['library'][] = 'social_media_links/fontawesome.component';
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
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    //Don't need to validate if its oidc
    if ($this->chosen_registry !== NULL && $this->chosen_registry->getRegistryType() !== 'oidc') {
      $this->validateName($form, $form_state);

      if (empty($form_state->getErrors())) {
        $apicAuthenticated = $this->validateApicAuthentication($form, $form_state);
        if ($apicAuthenticated !== TRUE) {
          $user_input = $form_state->getUserInput();
          $query = isset($user_input['name']) ? ['name' => $user_input['name']] : [];
          $form_state->setErrorByName('usernameorpassword', $this->t('Unable to sign in. This may be because the credentials provided for authentication are invalid or the user has not been activated. Please check that the user is active, then repeat the request with valid credentials. Please note that repeated attempts with incorrect credentials can lock the user account.'));
          $form_state->setErrorByName('usernameorpassword2', $this->t('<a href=":password">Forgot your password? Click here to reset it.</a>', [
            ':password' => Url::fromRoute('user.pass', [], ['query' => $query])
              ->toString(),
          ]));
        }
      }

      $this->validateFinal($form, $form_state);
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return bool
   * @throws \Drupal\Core\TempStore\TempStoreException
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
      if ($admin !== NULL && $name === $admin->getAccountName()) {

        if ($jwt !== NULL) {
          $this->messenger->addError(t('admin user is not allowed when signing in an invited user.'));
          $returnValue = FALSE;
        }
        else {
          $this->logger->debug('admin login, using core validation for login');
          $this->validateAuthentication($form, $form_state);
          if (!$form_state->get('uid')) {
            $this->messenger->addError(t('Unauthorized'));
          }
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

            if (isset($response) && $response->success() === TRUE) {
              if ($response->getMessage()) {
                $this->messenger->addStatus($response->getMessage());
              }
              $response = $this->loginService->login($login_user);
            }
          }
          else {
            $response = $this->loginService->login($login_user);
          }

          if (isset($response) && $response->success()) {
            $this->authApicSessionStore->delete('invitation_object');
            if ($response->getMessage() === 'APPROVAL') {
              $form_state->set('approval', TRUE);
              $form_state->set('uid', -1);
            } else {
              $form_state->set('uid', $response->getUid());
            }
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

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TempStore\TempStoreException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    if ($this->chosen_registry->getRegistryType() === 'oidc') {
      $jwt = $this->authApicSessionStore->get('invitation_object');
      $oidc_url = $form_state->getValue('oidc_url');
      if (!empty($jwt) && !strpos($jwt->getUrl(), '/member-invitations/')) {
        $oidc_url .= "&invitation_scope=consumer-org&title=" . urlencode($form_state->getValue('consumer_org'));
      }
      $response = new TrustedRedirectResponse(Url::fromUri($oidc_url)->toString());
      $form_state->setResponse($response);
    }
    else {
      if ($form_state->get('approval') === TRUE) {
        $this->messenger->addStatus($this->t('Your account was created successfully and is pending approval. You will receive an email with further instructions.'));
        return;
      }
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
      if ($this->authApicSessionStore->get('redirect_to')) {
        $this->authApicSessionStore->delete('redirect_to');
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
          if ($destination[0] !== '/' && $destination[0] !== '?' && $destination[0] !== '#') {
            $destination = '/' . $destination;
          }
          $form_state->setRedirectUrl(Url::fromUserInput($destination));
        }
        else {
          $form_state->setRedirect('<front>');
        }
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
