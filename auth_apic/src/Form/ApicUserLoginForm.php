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

use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\auth_apic\Service\Interfaces\UserManagerInterface;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface;
use Drupal\user\Form\UserLoginForm;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\user\UserAuthInterface;
use Drupal\user\UserStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\ibm_apim\Service\ApimUtils;

class ApicUserLoginForm extends UserLoginForm {

  protected $logger;
  protected $userManager;
  protected $sessionStore;
  protected $userRegistryService;
  protected $apimUtils;

  /**
   * Constructs a new UserLoginForm.
   *
   * {@inheritdoc}
   *
   * @param \Drupal\auth_apic\Service\Interfaces\UserManagerInterface $user_manager
   *   User Manager.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   Session factory.
   */
  public function __construct(FloodInterface $flood,
                              UserStorageInterface $user_storage,
                              UserAuthInterface $user_auth,
                              RendererInterface $renderer,
                              LoggerInterface $logger,
                              UserManagerInterface $user_manager,
                              PrivateTempStoreFactory $temp_store_factory,
                              UserRegistryServiceInterface $user_registry_service,
                              ApimUtils $apim_utils) {
    parent::__construct($flood, $user_storage, $user_auth, $renderer);
    $this->logger = $logger;
    $this->userManager = $user_manager;
    $this->sessionStore = $temp_store_factory->get('ibm_apim');
    $this->userRegistryService = $user_registry_service;
    $this->apimUtils = $apim_utils;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('flood'),
      $container->get('entity.manager')->getStorage('user'),
      $container->get('user.auth'),
      $container->get('renderer'),
      $container->get('logger.channel.auth_apic'),
      $container->get('auth_apic.usermanager'),
      $container->get('tempstore.private'),
      $container->get('ibm_apim.user_registry'),
      $container->get('ibm_apim.apim_utils')
    );
  }

  /**
   * @inheritDoc
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $baseform = parent::buildForm($form, $form_state);

    // if we are on the invited user flow, there will be a JWT in the session so grab that
    $jwt = NULL;
    if(!empty($_SESSION['auth_apic'])) {
      $jwt = $_SESSION['auth_apic']['invitation_object'];
      if (!empty($jwt) && !(strpos($jwt->getUrl(), '/member-invitations/'))) {
        $form['#message']['message'] = t("To complete your invitation, sign in to an existing account or sign up to create a new account.");
        // and for this case we need a consumer org title as well
        $baseform['consumer_org'] = array(
          '#type' => 'textfield',
          '#title' => t('Consumer organization'),
          '#placeholder' => t('Enter the name of your consumer organization'),
          '#size' => 60,
          '#maxlength' => 128,
          '#required' => TRUE
        );
      }
    }


    // if the page was loaded due to invoking the subscription wizard, put up a more helpful piece of text on the form
    $subscription_wizard_cookie = \Drupal::request()->cookies->get('Drupal_visitor_startSubscriptionWizard');
    if(!empty($subscription_wizard_cookie)) {
      $form['#message']['message'] = t("Sign in to an existing account or create a new account to subscribe to this Product.");
    }

    // Make username and password not required as this prevents form submission if clicking one of the
    // buttons on the right hand side
    $baseform['name']['#required'] = FALSE;
    $baseform['pass']['#required'] = FALSE;

    $baseform['actions']['submit']['#value'] = t('Sign in') ;

    // Remove all validation as this also prevents form submission. We put bits back in the validate() function.
    $baseform['#validate'] = array();

    // work out what user registries are enabled on this catalog
    $registries = $this->userRegistryService->getAll();

    // if there are no registries on the catalog throw up the default login page
    if(empty($registries)) {
      return $baseform;
    }

    $chosen_registry = $this->userRegistryService->getDefaultRegistry();

    $chosen_registry_url = $test = \Drupal::request()->query->get('registry_url');
    if(!empty($chosen_registry_url) && ($this->apimUtils->sanitizeRegistryUrl($chosen_registry_url) === 1)) {
      if(in_array($chosen_registry_url, array_keys($registries))) {
        $chosen_registry = $registries[$chosen_registry_url];
      }
    }

    // store registry_url for validate/submit
    $form['registry_url'] = array(
      '#type' => 'hidden',
      '#value' => $chosen_registry->getUrl()
    );
    // store registry_url for template
    $form['#registry_url']['registry_url'] = $chosen_registry->getUrl();


    if(sizeof($registries) > 1) {
      $other_registries = array_diff_key($registries, array($chosen_registry->getUrl() => $chosen_registry));
    }

    // build the form
    // Build a container for the section headers to go in
    $form['headers_container'] = array(
      '#type' => "container",
      '#attributes' => array('class' => array('apic-user-form-container'))
    );

    // Explain this part of the form
    $form['headers_container']['signin_label'] = array(
      '#type' => "html_tag",
      '#tag' => "div",
      '#value' => t("Sign in with @registryName", array('@registryName' => $chosen_registry->getTitle())),
      '#attributes' => array('class' => array('apic-user-form-subheader')),
      '#weight' => -1000,
    );

    if(!empty($other_registries)) {
      // Explain the extra buttons
      $form['headers_container']['other_registries_label'] = array(
        '#type' => "html_tag",
        '#tag' => "div",
        '#value' => t("Continue with"),
        '#attributes' => array('class' => array('apic-user-form-subheader')),
        '#weight' => -1000,
      );
    }

    // Build the form by embedding the other forms
    // Wrap everything in a container so we can set flex display
    $form['main_container'] = array(
      '#type' => "container",
      '#attributes' => array('class' => array('apic-user-form-container'))
    );

    // Embed the default log in form
    // Wrap the whole form in a div that we can style.
    $baseform['#prefix'] = '<div class="apic-user-form-inner-wrapper">';
    $baseform['#suffix'] = '</div>';
    $form['main_container']['plainlogin'] = $baseform;

    if(!empty($other_registries)) {
      // Construct another container for the "or" part in the middle of the form
      $form['main_container']['or_container'] = array(
        '#type' => 'container',
        '#attributes' => array('class' => array('apic-user-form-or-container'))
      );

      $form['main_container']['or_container']['line1'] = array(
        '#type' => "html_tag",
        '#tag' => "div",
        '#attributes' => array('class' => array('apic-user-form-line'))
      );

      $form['main_container']['or_container']['or'] = array(
        '#type' => "html_tag",
        '#tag' => "div",
        '#value' => t("or"),
        '#attributes' => array('class' => array('apic-user-form-or'))
      );

      $form['main_container']['or_container']['line2'] = array(
        '#type' => "html_tag",
        '#tag' => "div",
        '#attributes' => array('class' => array('apic-user-form-line'))
      );

      // embed the openid login form
      // Wrap the whole form in a div that we can style.
      $otherRegistriesForm['#prefix'] = '<div class="apic-user-form-inner-wrapper apic-user-form-registries">';
      $otherRegistriesForm['#suffix'] = '</div>';

      $redirect_with_registry_url = Url::fromRoute('user.login')->toString() . '?registry_url=';


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

        $otherRegistriesForm[] = $button;
      }

      $form['main_container']['other'] = $otherRegistriesForm;
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    // call the validation functions depending on which type of submission was done
    $button_clicked = $form_state->getTriggeringElement()['#name'];

    // "op" is the id of the "Log in" button
    if ($button_clicked == "op") {

      $form_state->getFormObject()->validateName($form, $form_state);
      $form_state->getFormObject()->validateApicAuthentication($form, $form_state);
      $form_state->getFormObject()->validateFinal($form, $form_state);

      return TRUE;
    }

    // TODO : other login providers need explicitly listing here (or a better way to detect other registries being submitted)
    elseif ($button_clicked == "google") {
      return $this->oidc_login_form->validateForm($form, $form_state);
    }
    else {
      // If we land here, something went badly wrong.
      drupal_set_message(t('Unable to validate the login form. Triggering element was @element', array('@element' => $button_clicked)), "error");
    }
  }

  /**
   * @inheritDoc
   */
  public function validateApicAuthentication(array &$form, FormStateInterface $form_state) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $returnValue = NULL;
    if ($this->validateFloodProtection($form, $form_state)) {
      $name = $form_state->getValue('name');
      $password = $form_state->getValue('pass');
      $corg = $form_state->getValue('consumer_org');

      $admin = $this->userStorage->load(1);
      // special case the admin user and log in via standard drupal mechanism.
      if ($name === $admin->getUsername()) {
        parent::validateAuthentication($form, $form_state);
        $returnValue = FALSE;
      } else {

        $login_user = new ApicUser();
        $login_user->setUsername($name);
        $login_user->setPassword($password);
        $login_user->setApicUserRegistryURL($form_state->getValue('registry_url'));

        // maybe this was an invited user?
        $jwt = NULL;
        if(!empty($_SESSION['auth_apic'])) {
          $jwt = $_SESSION['auth_apic']['invitation_object'];
        }

        if (!empty($jwt)) {
          $_SESSION['auth_apic']['invitation_object'] = NULL;
          $response = $this->userManager->acceptInvite($jwt, $login_user, $corg);

          if($response->success() === TRUE) {
            $response = $this->userManager->login($login_user);
          }
        }
        else {
          $response = $this->userManager->login($login_user);
        }

        if ($response->success()) {
          $form_state->set('uid', $response->getUid());
          $returnValue = TRUE;
        }
        else {
          $returnValue = FALSE;
        }
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    return $returnValue;
  }

  /**
   * Taken from UserLoginForm::validateAuthentication().
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @return bool
   */
  protected function validateFloodProtection(array $form, FormStateInterface $form_state) {
    $password = trim($form_state->getValue('pass'));
    $flood_config = $this->config('user.flood');
    if (!$form_state->isValueEmpty('name') && strlen($password) > 0) {
      // Do not allow any login from the current user's IP if the limit has been
      // reached. Default is 50 failed attempts allowed in one hour. This is
      // independent of the per-user limit to catch attempts from one IP to log
      // in to many different user accounts.  We have a reasonably high limit
      // since there may be only one apparent IP for all users at an institution.
      if (!$this->flood->isAllowed('user.failed_login_ip', $flood_config->get('ip_limit'), $flood_config->get('ip_window'))) {
        $form_state->set('flood_control_triggered', 'ip');
        return FALSE;
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
          $identifier = $account->id() . '-' . $this->getRequest()->getClientIP();
        }
        $form_state->set('flood_control_user_identifier', $identifier);

        // Don't allow login if the limit for this user has been reached.
        // Default is to allow 5 failed attempts every 6 hours.
        if (!$this->flood->isAllowed('user.failed_login_user', $flood_config->get('user_limit'), $flood_config->get('user_window'), $identifier)) {
          $form_state->set('flood_control_triggered', 'user');
          return FALSE;
        }
      }
      return TRUE;
    }
  }


}
