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

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\State\State;
use Drupal\Core\Url;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\auth_apic\Service\Interfaces\UserManagerInterface;
use Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface;
use Drupal\user\ProfileForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ApicUserProfileForm extends ProfileForm {

  /**
   * @var \Drupal\auth_apic\Service\Interfaces\UserManagerInterface
   */
  protected $userManager;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface
   */
  protected $registryService;

  /**
   * @var \Drupal\Core\State\State
   */
  protected $state;

  public function __construct(EntityManagerInterface $entity_manager,
                              LanguageManagerInterface $language_manager,
                              UserManagerInterface $user_manager,
                              UserRegistryServiceInterface $registry_service,
                              State $state,
                              EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL,
                              TimeInterface $time = NULL) {
    $this->userManager = $user_manager;
    $this->state = $state;
    $this->registryService = $registry_service;
    parent::__construct($entity_manager, $language_manager, $entity_type_bundle_info, $time);
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('auth_apic.usermanager'),
      $container->get('ibm_apim.user_registry'),
      $container->get('state'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $form = parent::form($form, $form_state);
    $user = $this->entityManager->getStorage('user')->load($this->currentUser()->id());
    // This is an internal field that shouldn't be displayed for anyone
    $form['consumer_organization']['#access'] = FALSE;

    $formForUser = (int) $this->entity->get('uid')->value;
    $registryUrl = $this->entity->get('apic_user_registry_url')->value;
    $registry = $this->registryService->get($registryUrl);

    /* If the user is admin and they are editing another non-admin user, we need to prevent changes being made
     * as those changes can't be pushed back to APIC
     */
    if ($formForUser === (int) $this->currentUser()->id()) {

      // This is the user editing their own profile case.
      // For the admin user there are some special cases we need to consider.
      if ((int) $this->currentUser()->id() === 1) {
        // firstName and lastName need a default value for the admin user as this is a drupal user not an APIC one
        // and these fields have no value otherwise.
        // Defaulting them to 'admin' here causes them to be saved when the one-time login link is used.

        if ($user !== NULL && $user->get('first_name')->value === NULL) {
          $form['first_name']['widget'][0]['value']['#default_value'] = 'admin';
        }
        if ($user !== NULL && $user->get('last_name')->value === '') {
          $form['last_name']['widget'][0]['value']['#default_value'] = 'admin';
        }

        $form['account']['name']['#access'] = FALSE;
        $form['account']['name']['#required'] = FALSE;
      }
      // For non-admin (andre), there are some special cases to handle as well.
      else {

        $form['account']['#access'] = FALSE;
        $form['account']['account']['#required'] = FALSE;

        // also have to set every field individually to disabled for account_field_split to work properly
        $form['account']['name']['#access'] = FALSE;
        $form['account']['name']['#required'] = FALSE;
        $form['account']['mail']['#access'] = FALSE;
        $form['account']['mail']['#required'] = FALSE;
        $form['account']['pass']['#access'] = FALSE;
        $form['account']['pass']['#required'] = FALSE;
        $form['account']['current_pass']['#access'] = FALSE;
        $form['account']['current_pass']['#required'] = FALSE;
        $form['account']['password_policy_status']['#access'] = FALSE;

        // Dev orgs are not editable.
        $form['consumer_organization']['#disabled'] = TRUE;
        $form['consumer_organization']['widget']['#required'] = FALSE;

        if ($registry !== NULL) {
          $form['registry_type'] = [
            '#type' => 'hidden',
            '#value' => $registry->getRegistryType(),
          ];
        }

      } // end - andre editing own form
    } // end - editing own form
    else {

      // This is admin (or someone with Administrator role) editing someone else.
      $form['first_name']['#disabled'] = TRUE;
      $form['first_name']['#required'] = FALSE;

      $form['last_name']['#disabled'] = TRUE;
      $form['last_name']['#required'] = FALSE;

      // suppress account but reinstate email field as readonly.
      $form['account']['#access'] = FALSE;
      $form['account']['#required'] = FALSE;

      // also have to set every field individually to disabled for account_field_split to work properly
      $form['account']['name']['#access'] = FALSE;
      $form['account']['name']['#required'] = FALSE;
      $form['account']['pass']['#access'] = FALSE;
      $form['account']['pass']['#required'] = FALSE;
      $form['account']['mail']['#access'] = FALSE;
      $form['account']['mail']['#required'] = FALSE;
      $form['account']['current_pass']['#access'] = FALSE;
      $form['account']['current_pass']['#required'] = FALSE;
      $form['account']['password_policy_status']['#access'] = FALSE;
    } // end - editing someone else's form

    $formForUser = (int) $this->entity->get('uid')->value;

    // we need to add an email address field as account is supressed for all users except for
    // admin, unless it is another Administrator viewing admin.
    if ($formForUser !== 1 || (int) $this->currentUser()->id() !== 1) {

      // we store a special email address for users where there is nothing
      // stored in the user registry, blank this out for display purposes.
      if ($this->knownEmptyEmailAddress($this->entity->getEmail())) {
        $emailAddressToShow = '';
      }
      else {
        $emailAddressToShow = $this->entity->getEmail();
      }

      // Add readonly text field to show the email address of the user. This is opposed to including the
      // account entry which requires password verification on form submit.
      $form['emailaddress'] = [
        '#type' => 'textfield',
        '#title' => t('Email Address'),
        '#default_value' => $emailAddressToShow,
        '#disabled' => TRUE,
        '#weight' => -42,
      ];
    }

    // non admin users when using readonly registry (e.g. LDAP)
    if ($formForUser !== 1 && $registry && !$registry->isUserManaged()) {

      $form['first_name']['#disabled'] = TRUE;
      $form['first_name']['#required'] = FALSE;

      $form['last_name']['#disabled'] = TRUE;
      $form['last_name']['#required'] = FALSE;

      $form['consumer_organization']['#access'] = FALSE;
      $form['consumer_organization']['widget']['#required'] = FALSE;

    }

    // block all access to password expiry fields
    if (isset($form['field_last_password_reset'])) {
      $form['field_last_password_reset']['#access'] = FALSE;
    }
    if (isset($form['field_password_expiration'])) {
      $form['field_password_expiration']['#access'] = FALSE;
    }

    // do not allow editing status or roles on edit profile page so convert to plaintext instead of form item
    if (isset($form['account']['status'])) {
      $defaultValue = $form['account']['status']['#default_value'];
      if ((int) $form['account']['status']['#default_value'] === 1) {
        $markup = '<span class="children">' . t('Active') . '</span>';
      }
      else {
        $markup = '<span class="children">' . t('Blocked') . '</span>';
      }
      $weight = $form['account']['status']['#weight'] ?? NULL;

      $form['statusText'] = [
        '#type' => 'fieldset',
        '#title' => t('Status'),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
      ];
      $form['statusText']['text'] = [
        '#markup' => $markup,
      ];
      if (isset($weight)) {
        $form['statusText']['#weight'] = $weight;
      }
      $form['account']['status'] = ['#type' => 'hidden', '#default_value' => $defaultValue, '#value' => $defaultValue];
    }

    if (isset($form['account']['roles'])) {
      $roles = [];
      $defaultValue = $form['account']['roles']['#default_value'];
      if (isset($form['account']['roles']['#default_value'])) {
        foreach (array_values($form['account']['roles']['#default_value']) as $roleName) {
          if (isset($form['account']['roles']['#options'][$roleName])) {
            $roles[] = $form['account']['roles']['#options'][$roleName];
          }
          else {
            $roles[] = $roleName;
          }

        }
      }
      $weight = NULL;
      $markup = '';
      if (isset($form['account']['roles']['#weight'])) {
        $weight = $form['account']['roles']['#weight'];
      }
      foreach (array_unique($roles) as $role) {
        $markup .= '<div class="children">' . $role . '</div>';
      }
      $form['rolesText'] = [
        '#type' => 'fieldset',
        '#title' => t('Roles'),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
      ];
      $form['rolesText']['text'] = [
        '#markup' => $markup,
      ];
      if (isset($weight)) {
        $form['rolesText']['#weight'] = $weight;
      }
      $form['account']['roles'] = ['#type' => 'hidden', '#default_value' => $defaultValue, '#value' => $defaultValue];
    }

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('registry_type') !== NULL && $form_state->getValue('registry_type') === 'lur') {
      $firstName = $form_state->getValue(['first_name', '0', 'value']);
      if (empty($firstName)) {
        $form_state->setErrorByName('first_name', $this->t('First Name is required.'));
      }
      $lastName = $form_state->getValue(['last_name', '0', 'value']);
      if (empty($lastName)) {
        $form_state->setErrorByName('last_name', $this->t('Last Name is required.'));
      }
    }
    return parent::validateForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $password = NULL;
    $firstNameValue = $form_state->getValue(['first_name', '0', 'value']);
    $lastNameValue = $form_state->getValue(['last_name', '0', 'value']);
    $mailValue = $form_state->getValue('mail');
    $username = $form_state->getValue('name');

    if ($form_state->getValue('current_pass') !== NULL) {
      $password = $form_state->getValue('current_pass');
    }

    $editUser = new ApicUser();
    $editUser->setFirstname($firstNameValue);
    $editUser->setLastname($lastNameValue);
    $editUser->setMail($mailValue);
    $editUser->setUsername($username);
    if ($password !== NULL) {
      $editUser->setPassword($password);
    }

    // We need to call different functions on the user manager
    // depending on who we are and who we edited.
    if (((int) $this->currentUser()->id() === 1) || ($this->currentUser()->getEmail() !== $editUser->getMail())) {
      // local admin user should only be updated in drupal db
      // if an admin is editing someone else, those changes should be made to drupal only as well
      $this->userManager->updateLocalAccount($editUser);
    }
    else {
      // everyone else needs updating in the mgmt appliance too
      $updatedMgmtAppliance = $this->userManager->updateApicAccount($editUser);

      if ($updatedMgmtAppliance) {
        $this->userManager->updateLocalAccount($editUser);
      }
    }

    // If the user editing the form has admin permissions, there may be role updates to make
    if (in_array('administrator', $this->currentUser()->getRoles(), FALSE)) {
      $this->userManager->updateLocalAccountRoles($editUser, $form_state->getValue('roles'));
    }

    parent::submitForm($form, $form_state);

  }

  private function knownEmptyEmailAddress($accountEmail, $knownValue = 'noemailinregistry@example.com'): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $length = strlen($knownValue);
    if ($length === 0) {
      $returnValue = TRUE;
    }
    elseif (substr($accountEmail, -$length) === $knownValue) {
      $returnValue = TRUE;
    }
    else {
      $returnValue = FALSE;
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    return $returnValue;
  }

  /**
   * @inheritdoc
   */
  protected function actions(array $form, FormStateInterface $form_state): array {
    $element = parent::actions($form, $form_state);
    // Remove the Cancel account button.
    if (isset($element['delete'])) {
      unset($element['delete']);
    }

    // Add a cancel editing button
    $element['cancel'] = [
      '#type' => 'link',
      '#title' => t('Cancel'),
      '#weight' => 30,
      '#url' => Url::fromRoute('entity.user.canonical',
        ['user' => $this->entity->id()]),
      '#attributes' => [
        'class' => [
          'button',
          'apicSecondary',
        ],
      ],
    ];
    $themeHandler = \Drupal::service('theme_handler');
    if ($themeHandler->themeExists('bootstrap')) {
      $element['cancel']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('remove');
    }


    $config = \Drupal::config('ibm_apim.settings');
    $ibmApimAllowUserDeletion = (boolean) $config->get('allow_user_delete');
    if ($ibmApimAllowUserDeletion === TRUE && (int) $this->currentUser()->id() !== 1 && (int) $this->entity->id() === (int) $this->currentUser()
        ->id()) {
      $element['delete'] = [
        '#type' => 'link',
        '#title' => t('Delete account'),
        '#weight' => 500,
        '#url' => Url::fromRoute('auth_apic.deleteuser'),
        '#attributes' => [
          'class' => [
            'button',
            'apicSecondary',
          ],
        ],
      ];
      if ($themeHandler->themeExists('bootstrap')) {
        $element['delete']['#icon'] = \Drupal\bootstrap\Bootstrap::glyphicon('trash');
      }
    }

    return $element;
  }

  /**
   * Provides a submit handler for the 'Cancel' button.
   * Note, this is cancel the edit form not cancel account which was on the drupal core form.
   */
  public function editCancelSubmit($form, FormStateInterface $form_state): void {
    $form_state->setRedirect(
      'entity.user.canonical',
      ['user' => $this->entity->id()]
    );
  }

}
