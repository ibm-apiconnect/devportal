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

namespace Drupal\auth_apic\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\auth_apic\Service\Interfaces\TokenParserInterface;
use Drupal\auth_apic\Service\Interfaces\UserManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form controller for the user password forms.
 */
class ApicUserPasswordResetForm extends FormBase {

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * @var \Drupal\auth_apic\Service\Interfaces\UserManagerInterface
   */
  protected $userManager;

  /**
   * @var \Drupal\Core\Session\AccountProxy|\Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * @var \Drupal\auth_apic\Service\Interfaces\TokenParserInterface
   */
  protected $tokenParser;

  /**
   * Constructs an ApicPasswordResetForm object.
   *
   * {@inheritdoc}
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language Manager.
   * @param \Drupal\auth_apic\Service\Interfaces\UserManagerInterface $user_manager
   *   User Manager.
   * @param \Drupal\Core\Session\AccountProxy $current_user
   *   Current User.
   * @param \Drupal\auth_apic\Service\Interfaces\TokenParserInterface $tokenParser
   *   Activation/ Reset password token parser.
   */
  public function __construct(LoggerInterface $logger, LanguageManagerInterface $language_manager, UserManagerInterface $user_manager, AccountProxy $current_user, TokenParserInterface $token_parser) {
    $this->logger = $logger;
    $this->languageManager = $language_manager;
    $this->userManager = $user_manager;
    $this->currentUser = $current_user;
    $this->tokenParser = $token_parser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('logger.channel.auth_apic'),
      $container->get('language_manager'),
      $container->get('auth_apic.usermanager'),
      $container->get('current_user'),
      $container->get('auth_apic.jwtparser'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'apic_resetpw';
  }

  /**
   * {@inheritdoc}
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Check that nobody is logged in - if they are, send them away!
    if (\Drupal::currentUser()->isAuthenticated()) {
      drupal_set_message(t('You can not reset passwords while you are logged in. You must log out first.'));
      return $this->redirect('<front>');
    }

    $token = \Drupal::request()->query->get('token');

    if (!$token) {
      drupal_set_message(t('Missing token. Contact the system administrator for assistance.'), 'error');
      $this->logger->notice('Missing token.');

      return $this->redirect('<front>');
    }
    else {
      $resetPasswordObject = $this->tokenParser->parse($token);
      if (empty($resetPasswordObject)) {
        drupal_set_message(t('Invalid token. Contact the system administrator for assistance.'), 'error');
        $this->logger->notice('Invalid token: %token', ['%token' => $token]);
        return $this->redirect('<front>');
      }
    }

    $form['token'] = [
      '#type' => 'hidden',
      '#value' => serialize($resetPasswordObject),
    ];

    $form['pass'] = [
      '#type' => 'password_confirm',
      '#required' => TRUE,
      '#description' => $this->t('Provide a password.'),
    ];

    // If the password policy module is enabled, modify this form to show
    // the configured policy.
    $moduleService = \Drupal::service('module_handler');
    $showPasswordPolicy = FALSE;

    if ($moduleService->moduleExists('password_policy')) {
      $showPasswordPolicy = _password_policy_show_policy();
    }

    if ($showPasswordPolicy) {

      // required for password_policy
      $form['#form_id'] = $this->getFormId();
      $form['account']['roles'] = [];
      $form['account']['roles']['#default_value'] = ['authenticated' => 'authenticated'];

      $form['account']['password_policy_status'] = [
        '#title' => $this->t('Password policies'),
        '#type' => 'table',
        '#header' => [t('Policy'), t('Status'), t('Constraint')],
        '#empty' => t('There are no constraints for the selected user roles'),
        '#weight' => '400',
        '#prefix' => '<div id="password-policy-status" class="hidden">',
        '#suffix' => '</div>',
        '#rows' => _password_policy_constraints_table($form, $form_state),
      ];

      $form['auth-apic-password-policy-status'] = ibm_apim_password_policy_check_constraints($form, $form_state);
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $moduleService = \Drupal::service('module_handler');
    if ($moduleService->moduleExists('password_policy')) {
      $show_password_policy_status = _password_policy_show_policy();

      // add validator if relevant.
      if ($show_password_policy_status) {
        if (!isset($form)) {
          $form = [];
        }
        _password_policy_user_profile_form_validate($form, $form_state);
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

    $password = $form_state->getValue('pass');
    $token = $form_state->getValue('token');

    if (empty($password)) {
      drupal_set_message(t('New password not set. Try again.'), 'error');
      $this->logger->notice('New password not set.');
      $form_state->setRedirect('user/forgot-password?token=' . $token);
      return;
    }

    if (empty($token)) {
      drupal_set_message(t('Missing token. Contact the system administrator.'), 'error');
      $this->logger->notice('Missing token.');
      $form_state->setRedirect('<front>');
      return;
    }

    $moduleService = \Drupal::service('module_handler');
    if ($moduleService->moduleExists('password_policy')) {
      if (!isset($form)) {
        $form = [];
      }
      _password_policy_user_profile_form_submit($form, $form_state);
    }

    $resetPasswordObject = unserialize($token, ['allowed_classes' => TRUE]);

    $responseCode = $this->userManager->resetPassword($resetPasswordObject, $password);

    if ($responseCode >= 200 && $responseCode < 300) {
      drupal_set_message(t('Password successfully updated.'));
      // Success, user needs to login now that the password has been reset.
      $form_state->setRedirect('user.login');
      return;
    }
    else {
      $form_state->setRedirect('user.pass');
      return;
    }

  }

  /**
   * @return \Drupal\Core\Session\AccountProxy
   */
  public function getEntity(): AccountProxy {
    return $this->currentUser;
  }
}
