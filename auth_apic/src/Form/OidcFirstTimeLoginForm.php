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

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\ApicUserService;
use Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface;
use Drupal\ibm_apim\Service\SiteConfig;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\ibm_apim\UserManagement\ApicAccountInterface;
use Drupal\user\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ibm_apim\Service\Utils;
use Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface;

class OidcFirstTimeLoginForm extends FormBase {

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * @var \Drupal\ibm_apim\UserManagement\ApicAccountInterface
   */
  protected ApicAccountInterface $accountService;

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
  protected UserUtils $userUtils;

  /**
   * @var \Drupal\ibm_apim\Service\SiteConfig
   */
  protected SiteConfig $siteConfig;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * @var \Drupal\ibm_apim\Service\ApicUserService
   */
  protected ApicUserService $userService;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface
   */
  protected UserRegistryServiceInterface $registryService;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  protected $entity;

  protected $fields;

  /**
   * @var \Drupal\ibm_apim\Service\Utils
   */
  protected Utils $utils;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface
   */
  protected ApicUserStorageInterface $userStorage;

  /**
   * OidcFirstTimeLoginForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   * @param \Drupal\ibm_apim\UserManagement\ApicAccountInterface $account_service
   * @param \Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface $registry_service
   * @param \Drupal\ibm_apim\Service\UserUtils $user_utils
   * @param \Drupal\ibm_apim\Service\ApicUserService $user_service
   * @param \Drupal\Core\Messenger\Messenger $messenger
   * @param \Drupal\ibm_apim\Service\SiteConfig $site_config
   * @param \Drupal\ibm_apim\Service\Utils $utils
   * @param \Drupal\ibm_apim\Service\Interfaces\ApicUserStorageInterface $user_storage
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    ApicAccountInterface $account_service,
    UserRegistryServiceInterface $registry_service,
    UserUtils $user_utils,
    ApicUserService $user_service,
    Messenger $messenger,
    SiteConfig $site_config,
    Utils $utils,
    ApicUserStorageInterface $user_storage
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->accountService = $account_service;
    $this->registryService = $registry_service;
    $this->userUtils = $user_utils;
    $this->userService = $user_service;
    $this->messenger = $messenger;
    $this->siteConfig = $site_config;
    $this->utils = $utils;
    $this->userStorage = $user_storage;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *
   * @return \Drupal\auth_apic\Form\OidcFirstTimeLoginForm
   */
  public static function create(ContainerInterface $container): OidcFirstTimeLoginForm {
    /** @noinspection PhpParamsInspection */
    return new static(
      $container->get('entity_type.manager'),
      $container->get('ibm_apim.account'),
      $container->get('ibm_apim.user_registry'),
      $container->get('ibm_apim.user_utils'),
      $container->get('ibm_apim.apicuser'),
      $container->get('messenger'),
      $container->get('ibm_apim.site_config'),
      $container->get('ibm_apim.utils'),
      $container->get('ibm_apim.user_storage')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'oidc_first_time_login';
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $account = \Drupal::currentUser();
    $this->entity = User::load($account->id());
    if (!$this->entity->isAuthenticated() || !($this->entity->get('first_time_login') !== NULL && $this->entity->get('first_time_login')
          ->getString() === '1')) {

      $this->messenger->addError(t('Permission denied.'));

      $form = [];
      $this->fields = [];
      $form['description'] = ['#markup' => '<p>' . t('You don\'t have permission to access this page.') . '</p>'];

      $form['cancel'] = [
        '#type' => 'link',
        '#title' => t('Cancel'),
        '#url' => Url::fromRoute('<front>'),
        '#attributes' => ['class' => ['button']],
      ];
    }
    else {
      $form['#parents'] = [];
      $max_weight = 500;

      $formObject = $this->entityTypeManager
        ->getFormObject('user', 'register')
        ->setEntity($this->entity);
      $form['#parents'] = [];
      $registerForm = \Drupal::formBuilder()->getForm($formObject, $form_state);
      $entity_form = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load('user.user.register');

      $definitions = \Drupal::service('entity_field.manager')->getFieldDefinitions('user', 'user');
      if ($this->entity !== NULL && $entity_form !== NULL) {
        foreach ($entity_form->getComponents() as $name => $options) {
          if ($name !== 'consumer_organization') {
            $widget = NULL;
            if (($name === 'mail' || $configuration = $entity_form->getComponent($name)) && isset($configuration['type']) && ($definition = $definitions[$name])) {
              $widget = \Drupal::service('plugin.manager.field.widget')->getInstance([
                'field_definition' => $definition,
                'form_mode' => 'default',
                // No need to prepare, defaults have been merged in setComponent().
                'prepare' => FALSE,
                'configuration' => $configuration,
              ]);
            }

            if ((($name === 'first_name' || $name === 'last_name') && $this->entity->{$name}->isEmpty()) ||
              ($name === 'mail' && $this->utils->endsWith($this->entity->get($name)->value, 'noemailinregistry@example.com')) ||
              (isset($widget) && $this->entity->hasField($name) &&
                $this->entity->{$name}->isEmpty() && $this->entity->get($name)->getFieldDefinition()->isRequired())) {
              $items = $this->entity->get($name);
              $items->filterEmptyItems();
              $form[$name] = $widget->form($items, $form, $form_state);
              if ($name === 'mail' && isset($form[$name]['widget'][0]['value']['#default_value'])) {
                $form[$name]['widget'][0]['value']['#default_value'] = '';
              }
              $form[$name]['#access'] = $items->access('edit');

              // Assign the correct weight.
              $form[$name]['#weight'] = $options['weight'];
              if ($options['weight'] > $max_weight) {
                $max_weight = $options['weight'];
              }
              $this->fields[] = $name;
            }
          }
        }
      }

      if (isset($registerForm['terms_of_use'])) {
        $form['terms_of_use'] = $registerForm['terms_of_use'];
        $this->fields[] = 'terms_of_use_checkbox';
      }
      $form['actions']['#type'] = 'actions';
      $form['actions']['#weight'] = $max_weight + 1;
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
        '#button_type' => 'primary',
      ];
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $userInputs = $form_state->getUserInput();
    foreach ($this->fields as $name) {
      if (isset($userInputs[$name])) {
        $val = $userInputs[$name];
        while (is_array($val) && !empty($val)) {
          $val = array_shift($val);
        }
        if (empty($val)) {
          $form_state->setErrorByName('', t('Fill all the required fields before proceeding.'));
          break;
        }
      }
    }
    if (isset($userInputs['mail'][0]['value']) && $this->userStorage->loadUserByEmailAddress($userInputs['mail'][0]['value']) !== NULL) {
      $form_state->setErrorByName('', t('A user with that email already exists.'));
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TempStore\TempStoreException|\JsonException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $editUser = $this->userService->parseRegisterForm($form_state);
    $userRegistry = $this->registryService->get($this->entity->get('registry_url')->value);
    if ($userRegistry !== NULL) {
      $editUser->setApicUserRegistryURL($userRegistry->getUrl());
    }
    $editUser->setUsername($this->entity->get('name')->value);
    if (empty($editUser->getMail())) {
      $editUser->setMail($this->entity->get('mail')->value);
    }
    $customFields = $this->userService->getCustomUserFields();
    $customFieldValues = $this->userUtils->handleFormCustomFields($customFields, $form_state);
    foreach ($customFieldValues as $customField => $value) {
      $editUser->addCustomField($customField, $value);
    }

    $apicUser = $this->accountService->updateApicAccount($editUser);
    if (isset($apicUser)) {
      $currentCOrg = $this->userUtils->getCurrentConsumerorg();
      if (!isset($currentCOrg)) {
        // if onboarding is enabled, we can redirect to the create org page
        if ($this->siteConfig->isSelfOnboardingEnabled()) {
          $form_state->setRedirect('consumerorg.create');
        }
        else {
          // we can't help the user, they need to talk to an administrator
          $form_state->setRedirect('ibm_apim.noperms');
        }
      }
      else {
        $form_state->setRedirect('ibm_apim.get_started');
      }
      $this->entity->set('first_time_login', 0);
      $this->entity->save();
      $this->accountService->updateLocalAccount($apicUser);
    }
    else {
      $form_state->setRedirect('auth_apic.oidc_first_time_login');
    }

  }

}
