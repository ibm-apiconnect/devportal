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

use Drupal\auth_apic\Service\Interfaces\TokenParserInterface;
use Drupal\auth_apic\UserManagement\ApicPasswordInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Messenger\Messenger;

/**
 * Form controller for the user password forms.
 */
class ApicUserPasswordResetForm extends FormBase {

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * @var \Drupal\auth_apic\UserManagement\ApicPasswordInterface
   */
  protected ApicPasswordInterface $apicPassword;

  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * @var \Drupal\auth_apic\Service\Interfaces\TokenParserInterface
   */
  protected TokenParserInterface $tokenParser;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * ApicUserPasswordResetForm constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   * @param \Drupal\auth_apic\UserManagement\ApicPasswordInterface $apic_password
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   * @param \Drupal\auth_apic\Service\Interfaces\TokenParserInterface $token_parser
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   * @param \Drupal\Core\Messenger\Messenger $messenger
   */
  public function __construct(LoggerInterface $logger,
                              LanguageManagerInterface $language_manager,
                              ApicPasswordInterface $apic_password,
                              AccountProxyInterface $current_user,
                              TokenParserInterface $token_parser,
                              ModuleHandlerInterface $module_handler,
                              Messenger $messenger) {
    $this->logger = $logger;
    $this->languageManager = $language_manager;
    $this->apicPassword = $apic_password;
    $this->currentUser = $current_user;
    $this->tokenParser = $token_parser;
    $this->moduleHandler = $module_handler;
    $this->messenger = $messenger;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return \Drupal\auth_apic\Form\ApicUserPasswordResetForm
   */
  public static function create(ContainerInterface $container): ApicUserPasswordResetForm {
    /** @noinspection PhpParamsInspection */
    return new static($container->get('logger.channel.auth_apic'),
      $container->get('language_manager'),
      $container->get('auth_apic.password'),
      $container->get('current_user'),
      $container->get('auth_apic.jwtparser'),
      $container->get('module_handler'),
      $container->get('messenger'));
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
      $this->messenger->addStatus(t('You can not reset passwords while you are logged in. You must log out first.'));
      return $this->redirect('<front>');
    }

    $token = \Drupal::request()->query->get('token');

    if (!$token) {
      $this->messenger->addError(t('Missing token. Contact the system administrator for assistance.'));
      $this->logger->notice('Missing token.');

      return $this->redirect('<front>');
    }

    $resetPasswordObject = $this->tokenParser->parse($token);
    if ($resetPasswordObject === null || empty($resetPasswordObject)) {
      $this->messenger->addError(t('Invalid token. Contact the system administrator for assistance.'));
      $this->logger->notice('Invalid token: %token', ['%token' => $token]);
      return $this->redirect('<front>');
    }

    $form['token'] = [
      '#type' => 'hidden',
      '#value' => serialize($resetPasswordObject),
    ];

    $form['pass'] = [
      '#type' => 'password_confirm',
      '#required' => TRUE,
      '#description' => $this->t('Provide a password.'),
      '#attributes' => ['autocomplete' => 'off'],
    ];

    // If the password policy module is enabled, modify this form to show
    // the configured policy.
    $showPasswordPolicy = FALSE;

    if ($this->moduleHandler->moduleExists('password_policy')) {
      $showPasswordPolicy = _password_policy_show_policy();
    }
    if ($showPasswordPolicy) {
      $user = User::load($form_state->getFormObject()->getEntity()->id());
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
        '#rows' => \Drupal::service('password_policy.validator')
        ->buildPasswordPolicyConstraintsTableRows(
          $form_state->getValue('pass', ''),
          $user,
          _password_policy_get_edited_user_roles($form, $form_state)
        ),
      ];

      $form['ibm-apim-password-policy-status'] = ibm_apim_password_policy_check_constraints($form, $form_state);
      $form['#attached']['drupalSettings']['ibmApimPassword'] = ibm_apim_password_policy_client_settings($form, $form_state);
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save'),
    ];
    $form['#attached']['library'][] = 'ibm_apim/validate_password';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    if ($this->moduleHandler->moduleExists('password_policy')) {
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
      $this->messenger->addError(t('New password not set. Try again.'));
      $this->logger->notice('New password not set.');
      $form_state->setRedirect('user/forgot-password?token=' . $token);
      return;
    }

    if (empty($token)) {
      $this->messenger->addError(t('Missing token. Contact the system administrator.'));
      $this->logger->notice('Missing token.');
      $form_state->setRedirect('<front>');
      return;
    }

    if ($this->moduleHandler->moduleExists('password_policy')) {
      if (!isset($form)) {
        $form = [];
      }
      _password_policy_user_profile_form_submit($form, $form_state);
    }

    $resetPasswordObject = unserialize($token, ['allowed_classes' => TRUE]);
    $responseCode = $this->apicPassword->resetPassword($resetPasswordObject, $password);

    if ($responseCode >= 200 && $responseCode < 300) {
      $this->messenger->addStatus(t('Password successfully updated.'));
      // Success, user needs to login now that the password has been reset.
      $form_state->setRedirect('user.login');
    }
    else {
      $this->messenger->addError(t('Invalid token. Try copy and pasting the link from the email manually or resetting your password again.'));
      $form_state->setRedirect('user.pass');
    }

  }

  /**
   * @return \Drupal\Core\Session\AccountProxyInterface
   */
  public function getEntity(): AccountProxyInterface {
    return $this->currentUser;
  }
}
