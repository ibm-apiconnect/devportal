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

use Drupal\auth_apic\Service\Interfaces\UserManagerInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\ApicUserService;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface;
use Drupal\user\RegisterForm;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ibm_apim\Service\ApimUtils;

/**
 * Self sign up / create new user form.
 */
class ApicUserRegisterForm extends RegisterForm {

  protected $logger;
  protected $userManager;
  protected $userRegistries;
  protected $userService;
  protected $userRegistryService;
  protected $apimUtils;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityManagerInterface $entity_manager,
                              LanguageManagerInterface $language_manager,
                              LoggerInterface $logger,
                              UserManagerInterface $user_manager,
                              UserRegistryServiceInterface $userRegistryService,
                              ApicUserService $userService,
                              EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL,
                              TimeInterface $time = NULL,
                              ApimUtils $apim_utils) {
    parent::__construct($entity_manager, $language_manager, $entity_type_bundle_info, $time);
    $this->logger = $logger;
    $this->userManager = $user_manager;
    $this->userRegistryService = $userRegistryService;
    $this->userService = $userService;
    $this->apimUtils = $apim_utils;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('logger.channel.auth_apic'),
      $container->get('auth_apic.usermanager'),
      $container->get('ibm_apim.user_registry'),
      $container->get('ibm_apim.apicuser'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('ibm_apim.apim_utils')
    );
  }

  /**
   * @inheritdoc
   */
  public function form(array $form, FormStateInterface $form_state) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $invited_andre = FALSE;
    $registries = array();

    $form = parent::form($form, $form_state);
    $form['#form_id'] = $this->getFormId();
    $form['account']['roles'] = array();
    $form['account']['roles']['#default_value'] = array('authenticated' => 'authenticated');

    // which fields displayed is controlled by config, we need to read and honour this.
    // we need an entity to work from, but this is for register so anonymous is the best we can do.
    $entity_form = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load('user.user.register');

    // if there are no registries on the catalog then bail out.
    $all_registries = $this->userRegistryService->getAll();
    if(empty($all_registries)) {
      drupal_set_message(t('Self-service onboarding not possible: No user registries defined.'), 'error');
      throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
    }

    // Hide the multi-value submit for consumerorgs regardless of whether we are going to add it again.
    $form['consumer_organization']['#access'] = FALSE;
    $form['consumer_organization']['#required'] = FALSE;

    // if we are on the invited user flow, there will be a JWT in the session so grab that
    // we can use this to pre-populate the email field
    $jwt = NULL;
    if(!empty($_SESSION['auth_apic'])) {
      $jwt = $_SESSION['auth_apic']['invitation_object'];
      if (!empty($jwt)) {
        $form['#message']['message'] = t("To complete your invitation, fill out any required fields below.");
      }
    }

    // for andre inviting another andre, we won't need the consumer org field
    if(!empty($jwt) && strpos($jwt->getPayload()['scopes']['url'], '/member-invitations/') !== FALSE) {
      $invited_andre = TRUE;
      $form['invited_andre'] = array(
        '#type' => 'value',
        '#value' => TRUE,
      );
    }

    // If self onboarding is disabled and this is not the invited user flow then bail out.
    if(\Drupal::state()->get('ibm_apim.selfSignUpEnabled', 1) === 0 && empty($jwt)){
      drupal_set_message(t('Self-service onboarding is disabled for this site.'), 'error');
      throw new \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException();
    }

    // decide which registry is going on the left side and which buttons to put on the right
    // we have two options :
    //  1) display the default registry on the left and the others on the right
    //  2) display the "chosen" registry on the left (second time through after clicking a registry button)
    $chosen_registry = $this->userRegistryService->getDefaultRegistry();
    $chosen_registry_url = $test = \Drupal::request()->query->get('registry_url');
    if(!empty($chosen_registry_url) && ($this->apimUtils->sanitizeRegistryUrl($chosen_registry_url) === 1)) {
      if(in_array($chosen_registry_url, array_keys($all_registries))) {
        $chosen_registry = $all_registries[$chosen_registry_url];
      }
    }

    // store chosen registry in form so we can use it in validation and submit
    $form['registry_url'] = array(
      '#type' => 'hidden',
      '#value' => $chosen_registry->getUrl()
    );
    // store the name for the template
    $form['#registry_title']['registry_title'] = $chosen_registry->getTitle();

    if(sizeof($all_registries) > 1) {
      $other_registries = array_diff_key($all_registries, array($chosen_registry->getUrl() => $chosen_registry));
    }

    // The rest of the fields depend on whether this is LUR, LDAP etc
    if ($chosen_registry->isUserManaged()) {

      if (!$invited_andre ) {
        // override multi select consumer org panel with just a textfield
        $form['consumerorg'] = array(
          '#type' => 'textfield',
          '#title' => $this->t('Consumer organization'),
          '#required' => TRUE,
          '#weight' => $form['consumer_organization']['#weight'],
          '#placeholder' => $this->t('Enter the name of your consumer organization'),
          '#description' => $this->t('Provide a name for your consumer organization such as "ACME Enterprises".')
        );
      }

      if(!empty($jwt)) {
        if (isset($form['account']['mail'])){
          $form['account']['mail']['#value'] = $jwt->getPayload()['email'];
          $form['account']['mail']['#disabled'] = TRUE;
        }
        if (isset($form['mail'])){
          $form['mail']['#value'] = $jwt->getPayload()['email'];
          $form['mail']['#disabled'] = TRUE;
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
        $form['auth-apic-password-policy-status'] = ibm_apim_password_policy_check_constraints($form, $form_state);
      }

    }
    else {

      // user exists already in the registry (ldap/ authurl).

      unset($form['account']['pass']);
      unset($form['account']['mail']);

      // change name of property to avoid password policy
      $form['pw_no_policy'] = array(
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
      );

      // here we are invited from apim, we need to specify a consumer org.
      if (!empty($jwt)) {
        $form['consumerorg'] = array(
          '#type' => 'textfield',
          '#title' => $this->t('Consumer organization'),
          '#required' => TRUE,
          '#weight' => $form['consumer_organization']['#weight'],
          '#placeholder' => $this->t('Enter the name of your consumer organization'),
          '#description' => $this->t('Provide a name for your consumer organization such as "ACME Enterprises".')
        );
      }

      // loop over everything which would be required from the config on a user managed form but isn't in this case.
      foreach ($entity_form->getComponents() as $name => $options) {
        if ($name !== 'consumer_organization' && $name !== 'account' && $name !== 'pw_no_policy') {
          unset($form[$name]);
        }
      }

    }

    if(!empty($other_registries)) {

      $otherRegistries = array();

      $redirect_with_registry_url = Url::fromRoute('user.register')->toString() . '?registry_url=';

      foreach($other_registries as $other_registry) {

        $button = array(
          '#type' => 'html_tag',
          '#tag' => 'span',
          '#attributes' => array('class' => array('apic-user-registry-button', 'apic-user-registry-' . $other_registry->getRegistryType())),
          '#name' => $other_registry->getName(),
          '#url' => $other_registry->getUrl(),
          '#limit_validation_errors' => array(),
        );
        if($other_registry->getRegistryType() === 'google') {
          $button['#prefix'] = '<a class="registry-button google-button button" href="' . $redirect_with_registry_url . $other_registry->getUrl() . '">
                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="18px" height="18px" viewBox="0 0 48 48" class="abcRioButtonSvg">
                                    <g><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"></path>
                                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"></path>
                                    <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"></path>
                                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"></path>
                                    <path fill="none" d="M0 0h48v48H0z"></path></g></svg>';
        }
        else if($other_registry->getRegistryType() === 'github') {
          $button['#prefix'] = '<a class="registry-button github-button button" href="' . $redirect_with_registry_url . $other_registry->getUrl() . '">
                                <?xml version="1.0" encoding="UTF-8" standalone="no"?>
                                <svg width="18px" height="18px" viewBox="0 0 256 250" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" preserveAspectRatio="xMidYMid">
                                <g><path fill="white" d="M128.00106,0 C57.3172926,0 0,57.3066942 0,128.00106 C0,184.555281 36.6761997,232.535542 87.534937,249.460899 C93.9320223,250.645779 96.280588,246.684165 96.280588,243.303333 C96.280588,240.251045 96.1618878,230.167899 96.106777,219.472176 C60.4967585,227.215235 52.9826207,204.369712 52.9826207,204.369712 C47.1599584,189.574598 38.770408,185.640538 38.770408,185.640538 C27.1568785,177.696113 39.6458206,177.859325 39.6458206,177.859325 C52.4993419,178.762293 59.267365,191.04987 59.267365,191.04987 C70.6837675,210.618423 89.2115753,204.961093 96.5158685,201.690482 C97.6647155,193.417512 100.981959,187.77078 104.642583,184.574357 C76.211799,181.33766 46.324819,170.362144 46.324819,121.315702 C46.324819,107.340889 51.3250588,95.9223682 59.5132437,86.9583937 C58.1842268,83.7344152 53.8029229,70.715562 60.7532354,53.0843636 C60.7532354,53.0843636 71.5019501,49.6441813 95.9626412,66.2049595 C106.172967,63.368876 117.123047,61.9465949 128.00106,61.8978432 C138.879073,61.9465949 149.837632,63.368876 160.067033,66.2049595 C184.49805,49.6441813 195.231926,53.0843636 195.231926,53.0843636 C202.199197,70.715562 197.815773,83.7344152 196.486756,86.9583937 C204.694018,95.9223682 209.660343,107.340889 209.660343,121.315702 C209.660343,170.478725 179.716133,181.303747 151.213281,184.472614 C155.80443,188.444828 159.895342,196.234518 159.895342,208.176593 C159.895342,225.303317 159.746968,239.087361 159.746968,243.303333 C159.746968,246.709601 162.05102,250.70089 168.53925,249.443941 C219.370432,232.499507 256,184.536204 256,128.00106 C256,57.3066942 198.691187,0 128.00106,0 Z M47.9405593,182.340212 C47.6586465,182.976105 46.6581745,183.166873 45.7467277,182.730227 C44.8183235,182.312656 44.2968914,181.445722 44.5978808,180.80771 C44.8734344,180.152739 45.876026,179.97045 46.8023103,180.409216 C47.7328342,180.826786 48.2627451,181.702199 47.9405593,182.340212 Z M54.2367892,187.958254 C53.6263318,188.524199 52.4329723,188.261363 51.6232682,187.366874 C50.7860088,186.474504 50.6291553,185.281144 51.2480912,184.70672 C51.8776254,184.140775 53.0349512,184.405731 53.8743302,185.298101 C54.7115892,186.201069 54.8748019,187.38595 54.2367892,187.958254 Z M58.5562413,195.146347 C57.7719732,195.691096 56.4895886,195.180261 55.6968417,194.042013 C54.9125733,192.903764 54.9125733,191.538713 55.713799,190.991845 C56.5086651,190.444977 57.7719732,190.936735 58.5753181,192.066505 C59.3574669,193.22383 59.3574669,194.58888 58.5562413,195.146347 Z M65.8613592,203.471174 C65.1597571,204.244846 63.6654083,204.03712 62.5716717,202.981538 C61.4524999,201.94927 61.1409122,200.484596 61.8446341,199.710926 C62.5547146,198.935137 64.0575422,199.15346 65.1597571,200.200564 C66.2704506,201.230712 66.6095936,202.705984 65.8613592,203.471174 Z M75.3025151,206.281542 C74.9930474,207.284134 73.553809,207.739857 72.1039724,207.313809 C70.6562556,206.875043 69.7087748,205.700761 70.0012857,204.687571 C70.302275,203.678621 71.7478721,203.20382 73.2083069,203.659543 C74.6539041,204.09619 75.6035048,205.261994 75.3025151,206.281542 Z M86.046947,207.473627 C86.0829806,208.529209 84.8535871,209.404622 83.3316829,209.4237 C81.8013,209.457614 80.563428,208.603398 80.5464708,207.564772 C80.5464708,206.498591 81.7483088,205.631657 83.2786917,205.606221 C84.8005962,205.576546 86.046947,206.424403 86.046947,207.473627 Z M96.6021471,207.069023 C96.7844366,208.099171 95.7267341,209.156872 94.215428,209.438785 C92.7295577,209.710099 91.3539086,209.074206 91.1652603,208.052538 C90.9808515,206.996955 92.0576306,205.939253 93.5413813,205.66582 C95.054807,205.402984 96.4092596,206.021919 96.6021471,207.069023 Z" fill="#161614"></path>
                                </g></svg>';
        }
        else {
          $button['#prefix'] = '<a class="registry-button generic-button button" href="' . $redirect_with_registry_url . $other_registry->getUrl() . '">
                                <svg width="18" height="18" viewBox="0 0 32 32" fill-rule="evenodd"><path d="M16 6.4c3.9 0 7 3.1 7 7s-3.1 7-7 7-7-3.1-7-7 3.1-7 7-7zm0-2c-5 0-9 4-9 9s4 9 9 9 9-4 9-9-4-9-9-9z"></path>
                                <path d="M16 0C7.2 0 0 7.2 0 16s7.2 16 16 16 16-7.2 16-16S24.8 0 16 0zm7.3 24.3H8.7c-1.2 0-2.2.5-2.8 1.3C3.5 23.1 2 19.7 2 16 2 8.3 8.3 2 16 2s14 6.3 14 14c0 3.7-1.5 7.1-3.9 9.6-.6-.8-1.7-1.3-2.8-1.3z"></path></svg>';
        }

        $button['#suffix'] = '<span class="registry-name">' . $other_registry->getTitle() . '</span></a>';

        $otherRegistries[] = $button;
      }
      $form['#otherRegistries']['otherRegistries'] = $otherRegistries;
    }

    // attach custom javascript library to generate corg names from first/last name
    $form['#attached']['library'][] = 'auth_apic/generate-consumerorg-name';

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $registry_url = $form_state->getValue('registry_url');
    $registry = $this->userRegistryService->get($registry_url);
    if($registry->isUserManaged()) {
      // we need to check for existing usernames and email addresses.
      $emailaddress = $form_state->getValue('mail');
      $username = $form_state->getValue('name');

      if (user_load_by_name($username) || user_load_by_mail($emailaddress)) {
        $form_state->setErrorByName('', t('A problem occurred while attempting to create your account. If you already have an account then please use that to login.'));
        return FALSE;
      }

    }

    // Set the form redirect to the homepage.
    $language = \Drupal::languageManager()->getCurrentLanguage();
    $form_state->setRedirect('<front>', array(), array('language' => $language));

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $registry_url = $form_state->getValue('registry_url');
    $registry = $this->userRegistryService->get($registry_url);
    $new_user = $this->userService->parseRegisterForm($form_state->getUserInput());
    if(empty($new_user->getMail())){
      $new_user->setMail($form_state->getValue('mail'));
    }
    $new_user->setApicUserRegistryURL($registry->getUrl());

    $jwt = NULL;
    if(!empty($_SESSION['auth_apic'])) {
      $jwt = $_SESSION['auth_apic']['invitation_object'];
    }

    if(!empty($jwt)) {
      // this is an invited user for which we are gathering more information.
      $new_user->setApicUserRegistryURL($registry->getUrl());
      if ($registry->isUserManaged()) {
        $response = $this->userManager->registerInvitedUser($jwt, $new_user, $registry);
      }
      else {
        $response = $this->userManager->acceptInvite($jwt, $new_user);
      }
    } else {
      // this is self signup, which is processed differently depending on the user registry.
      if ($registry->isUserManaged()) {
        $response = $this->userManager->userManagedSignUp($new_user);
      }
      else {
        $response = $this->userManager->nonUserManagedSignUp($new_user, $registry);
      }
    }

    if ($response == NULL) {
      $form_state->setRedirect('user.register');
    }
    else if ($response->success()) {
      drupal_set_message($response->getMessage());
      $form_state->setRedirect($response->getRedirect());
    }
    else {
      drupal_set_message($response->getMessage(), 'error');
    }

    // Clear the JWT from the session as we're done with it now
    if($jwt){
      $_SESSION['auth_apic']['invitation_object'] = NULL;
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  public function save(array $form, FormStateInterface $form_state) {
    // no-op on save as we save account via the user manager service.
  }

}
