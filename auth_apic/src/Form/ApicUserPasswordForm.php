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

namespace Drupal\auth_apic\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\State\State;
use Drupal\ibm_apim\Service\ApimUtils;
use Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface;
use Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface;
use Drupal\user\Form\UserPasswordForm;
use Drupal\user\UserStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Provides a user password reset form.
 */
class ApicUserPasswordForm extends UserPasswordForm {

  protected $mgmtServer;
  protected $logger;
  protected $registryService;
  protected $apimUtils;

  /**
   * Constructs an ApicUserPasswordForm object.
   *
   * {@inheritdoc}
   *
   * @param \Drupal\ibm_apim\Service\Interfaces\ManagementServerInterface $mgmtServer
   *   Management server.
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger.
   */
  public function __construct(UserStorageInterface $user_storage,
                              LanguageManagerInterface $language_manager,
                              ManagementServerInterface $mgmtServer,
                              LoggerInterface $logger,
                              State $state,
                              UserRegistryServiceInterface $registry_service,
                              ApimUtils $apim_utils) {
    parent::__construct($user_storage, $language_manager);
    $this->mgmtServer = $mgmtServer;
    $this->logger = $logger;
    $this->state = $state;
    $this->registryService = $registry_service;
    $this->apimUtils = $apim_utils;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')->getStorage('user'),
      $container->get('language_manager'),
      $container->get('ibm_apim.mgmtserver'),
      $container->get('logger.channel.auth_apic'),
      $container->get('state'),
      $container->get('ibm_apim.user_registry'),
      $container->get('ibm_apim.apim_utils')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $user_registries = $this->registryService->getAll();

    // We always need the base form so that 'admin' has a way to reset their password
    $baseform = parent::buildForm($form, $form_state);

    // the simplest case - there are no registries so just use the drupal form
    if(empty($user_registries)) {
      return $baseform;
    }

    // We present the form for an individual registry only, we need to work out what this is.
    // If nothing passed in then always fall back onto default registry.
    $chosen_registry = $this->registryService->getDefaultRegistry();
    $chosen_registry_url = \Drupal::request()->query->get('registry_url');
    if(!empty($chosen_registry_url) && ($this->apimUtils->sanitizeRegistryUrl($chosen_registry_url) === 1)) {
      if(in_array($chosen_registry_url, array_keys($user_registries))) {
        $chosen_registry = $user_registries[$chosen_registry_url];
      }
    }

    // store chosen registry in form so we can use it in validation and submit
    $form['registry'] = array(
      '#type' => 'value',
      '#value' => $chosen_registry
    );
    // and for the title.
    $form['#selected_registry']['selected_registry'] = $chosen_registry->getTitle();

    if ($chosen_registry->isUserManaged()) {
      $form[$chosen_registry->getName()][$chosen_registry->getName() . '_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Username or email address'),
        '#maxlength' => USERNAME_MAX_LENGTH,
        '#attributes' => [
          'class' => ['username'],
          'autocorrect' => 'off',
          'autocapitalize' => 'off',
          'spellcheck' => 'false',
        ]
      ];
    }
    else {
      // readonly (!userManaged) registry.
      // We need the ability to reset the 'admin' password so add some extra fields.
      $form['admin']['admin_name'] = $baseform['name'];
      $form['admin']['admin_name']['#required'] = TRUE;
      $form['admin_only'] = array(
        '#type' => 'value',
        '#value' => TRUE
      );
    }

    // Other bits we want to put back too
    $form['mail'] = $baseform['mail'];
    $form['actions'] = $baseform['actions'];

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
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
    // Try to load by email.
    $users = $this->userStorage->loadByProperties(['mail' => $name]);
    if (empty($users)) {
      // No success, try to load by name.
      $users = $this->userStorage->loadByProperties(['name' => $name]);
    }
    $account = reset($users);

    // If the account exists then pass it on to submit handling.
    // If it doesn't then we do not error as we need to continue to request
    // a new password from the management server for users not in the db.
    if ($account && $account->id()) {
      $form_state->setValueForElement(['#parents' => ['account']], $account);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $account = $form_state->getValue('account');
    $registry = $form_state->getValue('registry');
    $admin_only = $form_state->getValue('admin_only');

    $result = NULL;

    if (isset($account) && isset($account->uid) && $account->uid->value == 1) {
      $this->logger->notice('Forgot password request for admin user submitted.');
      parent::submitForm($form, $form_state);
    }
    else if($admin_only) {
      // admin only, but entry is neither 'admin' nor the correct email address.
      // here we don't have a registry to pass through to management server
      // but don't want to disclose any information so mock up a response.
      $this->logger->notice('Forgot password request for non-admin user in read only registry submitted.');
      $result = new \stdClass();
      $result->code = 200;
    }
    else {
      if (isset($account) && $account != FALSE) {
        $this->logger->notice('Forgot password request for known user submitted.');
        $result = $this->mgmtServer->forgotPassword($account->mail->value, $registry->getRealm());
      }
      else if ($registry !== NULL) {
        // Account not set, likely it doesn't exist in portal DB.
        // This is possible when a site has been recreated and the user
        // has forgotten their password.
        $name = trim($form_state->getValue($registry->getName() . '_name'));
        $this->logger->notice('Forgot password request for unknown user submitted.');
        $result = $this->mgmtServer->forgotPassword($name, $registry->getRealm());
      }
    }

    if ($result !== NULL && $result->code !== 500) {
      drupal_set_message(t('If the account exists, an email has been sent with further instructions to reset the password.'));
      $form_state->setRedirect('user.page');
    }
    // If result is NULL, there will be errors/warnings set by the REST code so nothing for us to do

  }

}
