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

use Drupal\auth_apic\UserManagement\ApicPasswordInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Url;
use Drupal\ibm_apim\Rest\RestResponse;
use Drupal\ibm_apim\Service\ApimUtils;
use Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface;
use Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface;
use Drupal\user\Form\UserPasswordForm;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\auth_apic\Service\Interfaces\OidcRegistryServiceInterface;


/**
 * Provides a user password reset form.
 */
class ApicUserPasswordForm extends UserPasswordForm {

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface
   */
  protected $mgmtServer;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface
   */
  protected $registryService;

  /**
   * @var \Drupal\ibm_apim\Service\ApimUtils
   */
  protected $apimUtils;

  /**
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

  /**
   * @var \Drupal\auth_apic\UserManagement\ApicPasswordInterface
   */
  protected $password;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * ApicUserPasswordForm constructor.
   *
   * @param \Drupal\user\UserStorageInterface $user_storage
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   * @param \Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface $mgmtServer
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface $registry_service
   * @param \Drupal\ibm_apim\Service\ApimUtils $apim_utils
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   * @param \Drupal\Core\Flood\FloodInterface $flood
   * @param \Drupal\auth_apic\UserManagement\ApicPasswordInterface $password_service
   * @param \Drupal\Core\Messenger\Messenger $messenger
   */
  public function __construct(UserStorageInterface $user_storage,
                              LanguageManagerInterface $language_manager,
                              ManagementServerInterface $mgmtServer,
                              LoggerInterface $logger,
                              UserRegistryServiceInterface $registry_service,
                              ApimUtils $apim_utils,
                              ConfigFactory $config_factory,
                              FloodInterface $flood,
                              ApicPasswordInterface $password_service,
                              Messenger $messenger,
                              OidcRegistryServiceInterface $oidc_service) {
    parent::__construct($user_storage, $language_manager);
    $this->mgmtServer = $mgmtServer;
    $this->logger = $logger;
    $this->registryService = $registry_service;
    $this->apimUtils = $apim_utils;
    $this->configFactory = $config_factory;
    $this->flood = $flood;
    $this->password = $password_service;
    $this->messenger = $messenger;
    $this->oidcService = $oidc_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')->getStorage('user'),
      $container->get('language_manager'),
      $container->get('ibm_apim.mgmtserver'),
      $container->get('logger.channel.auth_apic'),
      $container->get('ibm_apim.user_registry'),
      $container->get('ibm_apim.apim_utils'),
      $container->get('config.factory'),
      $container->get('flood'),
      $container->get('auth_apic.password'),
      $container->get('messenger'),
      $container->get('auth_apic.oidc'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    if (\Drupal::service('module_handler')->moduleExists('social_media_links')) {
      $form['#attached']['library'][] = 'social_media_links/fontawesome.component';
    }

    $user_registries = $this->registryService->getAll();

    // We always need the base form so that 'admin' has a way to reset their password
    $baseForm = parent::buildForm($form, $form_state);

    // the simplest case - there are no registries so just use the drupal form
    if (empty($user_registries)) {
      return $baseForm;
    }

    // We present the form for an individual registry only, we need to work out what this is.
    // If nothing passed in then always fall back onto default registry.
    $chosen_registry = $this->registryService->getDefaultRegistry();
    $chosen_registry_url = \Drupal::request()->query->get('registry_url');
    if (!empty($chosen_registry_url) && array_key_exists($chosen_registry_url, $user_registries) && ($this->apimUtils->sanitizeRegistryUrl($chosen_registry_url) === 1)) {
      $chosen_registry = $user_registries[$chosen_registry_url];
    }

    if (sizeof($user_registries) > 1) {
      $other_registries = array_diff_key($user_registries, [$chosen_registry->getUrl() => $chosen_registry]);
    }

    // store chosen registry in form so we can use it in validation and submit
    $form['registry'] = [
      '#type' => 'value',
      '#value' => $chosen_registry,
    ];
    // store the name for the template
    $form['#registry_title']['registry_title'] = $chosen_registry->getTitle();

    if ($chosen_registry->isUserManaged()) {
      $form[$chosen_registry->getName()][$chosen_registry->getName() . '_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Username or email address'),
        '#maxlength' => UserInterface::USERNAME_MAX_LENGTH,
        '#attributes' => [
          'class' => ['username'],
          'autocorrect' => 'off',
          'autocapitalize' => 'off',
          'autocomplete' => 'off',
          'spellcheck' => 'false',
        ],
      ];
    }
    else {
      // readonly (!userManaged) registry.
      // We need the ability to reset the 'admin' password so add some extra fields.
      $form['admin']['admin_name'] = $baseForm['name'];
      $form['admin']['admin_name']['#required'] = TRUE;
      $form['admin_only'] = [
        '#type' => 'value',
        '#value' => TRUE,
      ];
    }

    if (!empty($other_registries)) {

      $otherRegistries = [];

      $redirect_with_registry_url = Url::fromRoute('user.pass')->toString() . '?registry_url=';

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
          $oidc_info = $this->oidcService->getOidcMetadata($other_registry);
          $button['#prefix'] = '<a class="registry-button x generic-button button" href="' . $redirect_with_registry_url . $other_registry->getUrl() . '" title="' . $this->t('Sign in using @ur', ['@ur' => $other_registry->getTitle()]) . '">' .
              $oidc_info['image']['html'];
        }
        else {
          $button['#prefix'] = '<a class="registry-button generic-button button" href="' . $redirect_with_registry_url . $other_registry->getUrl() . '" title="' . $this->t('Use @ur', ['@ur' => $other_registry->getTitle()]) . '">
                                <svg width="18" height="18" viewBox="0 0 32 32" fill-rule="evenodd"><path d="M16 6.4c3.9 0 7 3.1 7 7s-3.1 7-7 7-7-3.1-7-7 3.1-7 7-7zm0-2c-5 0-9 4-9 9s4 9 9 9 9-4 9-9-4-9-9-9z"></path>
                                <path d="M16 0C7.2 0 0 7.2 0 16s7.2 16 16 16 16-7.2 16-16S24.8 0 16 0zm7.3 24.3H8.7c-1.2 0-2.2.5-2.8 1.3C3.5 23.1 2 19.7 2 16 2 8.3 8.3 2 16 2s14 6.3 14 14c0 3.7-1.5 7.1-3.9 9.6-.6-.8-1.7-1.3-2.8-1.3z"></path></svg>';
        }

        $button['#suffix'] = '<span class="registry-name">' . $other_registry->getTitle() . '</span></a>';

        $otherRegistries[] = $button;
      }
      $form['#otherRegistries']['otherRegistries'] = $otherRegistries;
    }
    $form['#attached']['library'][] = 'ibm_apim/single_click';

    // Other bits we want to put back too
    $form['mail'] = $baseForm['mail'];
    $form['actions'] = $baseForm['actions'];

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
    $flood_config = $this->configFactory->get('user.flood');
    if (!$this->flood->isAllowed('user.password_request_ip', $flood_config->get('ip_limit'), $flood_config->get('ip_window'))) {
      $this->logger->notice('Flood Control: Blocking password reset attempt from IP: @ip_address', ['@ip_address' => $this->getRequest()->getClientIP()]);
      $form_state->setErrorByName('name', $this->t('Too many password recovery requests. You have been temporarily blocked. Try again later or contact the site administrator.'));
      return;
    }
    $this->flood->register('user.password_request_ip', $flood_config->get('ip_window'));

    $registry = $form_state->getValue('registry');
    $admin_only = $form_state->getValue('admin_only');

    $name_suffix = '_name';

    if ($admin_only) {
      $registry_to_use = 'admin';
    }
    else {
      $registry_to_use = $registry->getName();
    }

    // Look up the user account in our database
    $name = trim($form_state->getValue($registry_to_use . $name_suffix));
    if ($registry_to_use === 'admin') {
      $registry_url = $this->registryService->getAdminRegistryUrl();
    }
    else {
      $registry_url = $registry->getUrl();
    }
    $account = $this->password->lookupUpAccount($name, $registry_url);

    // If the account exists then pass it on to submit handling.
    // If it doesn't then we do not error as we need to continue to request
    // a new password from the management server for users not in the db.
    if ($account && $account->id()) {
      if ($flood_config->get('uid_only')) {
        // Register flood events based on the uid only, so they apply for any
        // IP address. This is the most secure option.
        $identifier = $account->id();
      }
      else {
        // The default identifier is a combination of uid and IP address. This
        // is less secure but more resistant to denial-of-service attacks that
        // could lock out all users with public user names.
        $identifier = $account->id() . '-' . $this->getRequest()->getClientIP();
      }
      if (!$this->flood->isAllowed('user.password_request_user', $flood_config->get('user_limit'), $flood_config->get('user_window'), $identifier)) {
        $this->logger->notice('Flood Control: Blocking password reset attempt for user ID: @account_id', ['@account_id' => $account->id()]);
        $form_state->setErrorByName('name', $this->t('Too many password recovery requests. You have been temporarily blocked. Try again later or contact the site administrator.'));
        return;
      }
      $this->flood->register('user.password_request_user', $flood_config->get('user_window'), $identifier);

      $form_state->setValueForElement(['#parents' => ['account']], $account);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $account = $form_state->getValue('account');
    $registry = $form_state->getValue('registry');
    $admin_only = $form_state->getValue('admin_only');

    $result = NULL;

    if (isset($account, $account->uid) && (int) $account->uid->value === 1) {
      $this->logger->notice('Forgot password request for admin user submitted.');
      parent::submitForm($form, $form_state);
    }
    elseif ($admin_only) {
      // admin only, but entry is neither 'admin' nor the correct email address.
      // here we don't have a registry to pass through to management server
      // but don't want to disclose any information so mock up a response.
      $this->logger->notice('Forgot password request for non-admin user in read only registry submitted.');
      $result = new RestResponse();
      $result->setCode(200);
    }
    elseif (isset($account) && $account !== FALSE) {
      $this->logger->notice('Forgot password request for known user submitted.');
      $result = $this->mgmtServer->forgotPassword($account->mail->value, $registry->getRealm());
    }
    elseif ($registry !== NULL) {
      // Account not set, likely it doesn't exist in portal DB.
      // This is possible when a site has been recreated and the user
      // has forgotten their password.
      $name = trim($form_state->getValue($registry->getName() . '_name'));
      $this->logger->notice('Forgot password request for unknown user submitted.');
      $result = $this->mgmtServer->forgotPassword($name, $registry->getRealm());
    }

    // avoid disclosing any information about whether the account exists or not.
    if ($result !== NULL && ($result instanceof RestResponse) && $result->getCode() !== 500) {
      $this->messenger->addMessage(t('If the account exists, an email has been sent with further instructions to reset the password.'));
      $form_state->setRedirect('user.page');
    }
    else {
      // If result is NULL, there will be errors/warnings set by the REST code so nothing for us to do except log it
      $this->logger->notice('Forgot password: response from management server @result', ['@result' => serialize($result)]);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

}
