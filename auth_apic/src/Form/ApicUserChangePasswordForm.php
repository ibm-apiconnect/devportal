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

use Drupal\auth_apic\UserManagement\ApicPasswordInterface;
use Drupal\change_pwd_page\Form\ChangePasswordForm;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Password\PasswordInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\Messenger;

class ApicUserChangePasswordForm extends ChangePasswordForm {

  use StringTranslationTrait;

  /**
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\auth_apic\UserManagement\ApicPasswordInterface
   */
  protected $apicPassword;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Constructs a ChangePasswordForm object.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   * @param \Drupal\Core\Extension\ModuleHandler $module_handler
   *   Module handler.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   Translation interface, see https://www.drupal.org/docs/8/api/translation-api/overview
   * @param \Drupal\Core\Password\PasswordInterface $password_hasher
   *   The password hasher.
   * @param \Drupal\auth_apic\UserManagement\ApicPasswordInterface $apic_password
   *   Password service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(AccountInterface $account,
                              ModuleHandler $module_handler,
                              LoggerInterface $logger,
                              TranslationInterface $string_translation,
                              PasswordInterface $password_hasher,
                              ApicPasswordInterface $apic_password,
                              EntityTypeManagerInterface $entity_type_manager,
                              Messenger $messenger) {
    parent::__construct($password_hasher, $account);
    $this->account = $account;
    $this->moduleHandler = $module_handler;
    $this->logger = $logger;
    $this->stringTranslation = $string_translation;
    $this->password_hasher = $password_hasher;
    $this->apicPassword = $apic_password;
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('module_handler'),
      $container->get('logger.channel.auth_apic'),
      $container->get('string_translation'),
      $container->get('password'),
      $container->get('auth_apic.password'),
      $container->get('entity_type.manager'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'apic_change_pwd_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, UserInterface $user = NULL): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $this->userProfile = $account = $user;
    $user = $this->account;

    if ($this->getRequest()->get('pass-reset-token')) {
      // admin password reset or one-time login form.
      // this request has come via the change_pwd_page ChangePasswordResetForm page so fall back to their processing..
      $form = parent::buildForm($form, $form_state, $account);

      if (!isset($form['#form_id'])) {
        $form['#form_id'] = $this->getFormId();
      }
      if (!isset($form['account'])) {
        $form['account'] = [
          '#type' => 'container',
          '#weight' => -10,
        ];
      }
      if (!isset($form['account']['roles'])) {
        $form['account']['roles'] = [];
      }
      if (!isset($form['account']['roles']['#default_value'])) {
        $form['account']['roles']['#default_value'] = ['authenticated'];
      }

      // If the password policy module is enabled, modify this form to show
      // the configured policy.
      $showPasswordPolicy = FALSE;

      if ($this->moduleHandler->moduleExists('password_policy')) {
        $showPasswordPolicy = _password_policy_show_policy();
      }

      if ($showPasswordPolicy) {
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
            $form_state->getFormObject()->getEntity(),
            _password_policy_get_edited_user_roles($form, $form_state)
          ),
        ];

        $form['ibm-apim-password-policy-status'] = ibm_apim_password_policy_check_constraints($form, $form_state);
        $form['ibm-apim-password-policy-status']['#weight'] = 10;
        $form['#attached']['drupalSettings']['ibmApimPassword'] = ibm_apim_password_policy_client_settings($form, $form_state);
      }

    }
    elseif (!$user->isAnonymous()) {
      // Account information.
      $form['account'] = [
        '#type' => 'container',
        '#weight' => -10,
      ];

      $form['account']['current_pass'] = [
        '#type' => 'password',
        '#title' => $this->t('Current password'),
        '#size' => 25,
        //'#access' => !$form_state->get('user_pass_reset'),
        '#weight' => -5,
        // Do not let web browsers remember this password, since we are
        // trying to confirm that the person submitting the form actually
        // knows the current one.
        '#attributes' => ['autocomplete' => 'off'],
        '#required' => TRUE,
      ];
      $form_state->set('user', $account);

      $form['account']['pass'] = [
        '#type' => 'password_confirm',
        '#required' => TRUE,
        '#description' => $this->t('Provide a password.'),
        '#attributes' => ['autocomplete' => 'off'],
      ];

      $form['#form_id'] = $this->getFormId();
      $form['account']['roles'] = [];
      if (!isset($form['account']['roles'])) {
        $form['account']['roles'] = [];
      }
      if (!isset($form['account']['roles']['#default_value'])) {
        $form['account']['roles']['#default_value'] = ['authenticated'];
      }

      // If the password policy module is enabled, modify this form to show
      // the configured policy.
      $showPasswordPolicy = FALSE;

      if ($this->moduleHandler->moduleExists('password_policy')) {
        $showPasswordPolicy = _password_policy_show_policy();
      }

      if ($showPasswordPolicy) {
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
            $form_state->getFormObject()->getEntity(),
            _password_policy_get_edited_user_roles($form, $form_state)
          ),
        ];

        $form['ibm-apim-password-policy-status'] = ibm_apim_password_policy_check_constraints($form, $form_state);
        $form['ibm-apim-password-policy-status']['#weight'] = 10;
        $form['#attached']['drupalSettings']['ibmApimPassword'] = ibm_apim_password_policy_client_settings($form, $form_state);
      }

      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['submit'] = ['#type' => 'submit', '#value' => $this->t('Save')];
    }

    $form['#attached']['library'][] = 'ibm_apim/validate_password';
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    return $form;

  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $user = $this->currentUser();

    $moduleService = \Drupal::service('module_handler');
    if ($moduleService->moduleExists('password_policy')) {
      $show_password_policy_status = _password_policy_show_policy();

      // add validator if relevant.
      if ($show_password_policy_status) {
        if (!isset($form)) {
          $form = [];
        }
        if (!isset($form['account']['roles'])) {
          $form['account']['roles'] = ['authenticated'];
        }
        if (!isset($form['account']['roles']['#default_value'])) {
          $form['account']['roles']['#default_value'] = ['authenticated'];
        }
        _password_policy_user_profile_form_validate($form, $form_state);
      }
    }

    // special case original admin user who uses the drupal db.
    if ((int) $user->id() === 1) {
      $this->logger->notice('change password form validation for admin user');
      parent::validateForm($form, $form_state);
    }
    else {
      $this->logger->notice('change password form validation for non-admin user');
      // no-op for non-admin
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    // redirect to the home page regardless of outcome.
    $form_state->setRedirect('<front>');

    $moduleService = \Drupal::service('module_handler');
    if ($moduleService->moduleExists('password_policy')) {
      if (!isset($form)) {
        $form = [];
      }
      if (!isset($form['account']['roles'])) {
        $form['account']['roles'] = ['authenticated'];
      }
      if (!isset($form['account']['roles']['#default_value'])) {
        $form['account']['roles']['#default_value'] = ['authenticated'];
      }
      _password_policy_user_profile_form_submit($form, $form_state);
    }

    // special case original admin user who uses the drupal db.
    if ((int) $this->currentUser()->id() === 1) {
      $this->logger->notice('change password form submit for admin user');
      parent::submitForm($form, $form_state);
    }
    else {
      $success = FALSE;
      $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser()->id());
      if ($user !== NULL) {
        $this->logger->notice('change password form submit for non-admin user');
        $success = $this->apicPassword->changePassword($user, $form_state->getValue('current_pass'), $form_state->getValue('pass'));
      }
      if ($success) {
        $this->messenger->addStatus(t('Password changed successfully'));
        //$form_state->setRedirect('user.logout');
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

  }

  /**
   * @return \Drupal\Core\Entity\EntityInterface|null|static
   */
  public function getEntity() {
    $current_user = $this->currentUser();
    if (isset($current_user)) {
      $current_user = User::load($current_user->id());
    }
    return $current_user;
  }
}
