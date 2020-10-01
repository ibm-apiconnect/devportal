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
                              Messenger $messenger) {
    parent::__construct($user_storage, $language_manager);
    $this->mgmtServer = $mgmtServer;
    $this->logger = $logger;
    $this->registryService = $registry_service;
    $this->apimUtils = $apim_utils;
    $this->configFactory = $config_factory;
    $this->flood = $flood;
    $this->password = $password_service;
    $this->messenger = $messenger;
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
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

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
        if ($other_registry->getRegistryType() === 'google') {
          $button['#prefix'] = '<a class="registry-button google-button button" href="' . $redirect_with_registry_url . $other_registry->getUrl() . '" title="' . $this->t('Use @ur', ['@ur' => $other_registry->getTitle()]) . '">
                                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" width="18px" height="18px" viewBox="0 0 48 48" class="abcRioButtonSvg">
                                    <g><path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"></path>
                                    <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"></path>
                                    <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"></path>
                                    <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"></path>
                                    <path fill="none" d="M0 0h48v48H0z"></path></g></svg>';
        }
        elseif ($other_registry->getRegistryType() === 'github') {
          $button['#prefix'] = '<a class="registry-button github-button button" href="' . $redirect_with_registry_url . $other_registry->getUrl() . '" title="' . $this->t('Use @ur', ['@ur' => $other_registry->getTitle()]) . '">
                                <?xml version="1.0" encoding="UTF-8" standalone="no"?>
                                <svg width="18px" height="18px" viewBox="0 0 256 250" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" preserveAspectRatio="xMidYMid">
                                <g><path d="M128.00106,0 C57.3172926,0 0,57.3066942 0,128.00106 C0,184.555281 36.6761997,232.535542 87.534937,249.460899 C93.9320223,250.645779 96.280588,246.684165 96.280588,243.303333 C96.280588,240.251045 96.1618878,230.167899 96.106777,219.472176 C60.4967585,227.215235 52.9826207,204.369712 52.9826207,204.369712 C47.1599584,189.574598 38.770408,185.640538 38.770408,185.640538 C27.1568785,177.696113 39.6458206,177.859325 39.6458206,177.859325 C52.4993419,178.762293 59.267365,191.04987 59.267365,191.04987 C70.6837675,210.618423 89.2115753,204.961093 96.5158685,201.690482 C97.6647155,193.417512 100.981959,187.77078 104.642583,184.574357 C76.211799,181.33766 46.324819,170.362144 46.324819,121.315702 C46.324819,107.340889 51.3250588,95.9223682 59.5132437,86.9583937 C58.1842268,83.7344152 53.8029229,70.715562 60.7532354,53.0843636 C60.7532354,53.0843636 71.5019501,49.6441813 95.9626412,66.2049595 C106.172967,63.368876 117.123047,61.9465949 128.00106,61.8978432 C138.879073,61.9465949 149.837632,63.368876 160.067033,66.2049595 C184.49805,49.6441813 195.231926,53.0843636 195.231926,53.0843636 C202.199197,70.715562 197.815773,83.7344152 196.486756,86.9583937 C204.694018,95.9223682 209.660343,107.340889 209.660343,121.315702 C209.660343,170.478725 179.716133,181.303747 151.213281,184.472614 C155.80443,188.444828 159.895342,196.234518 159.895342,208.176593 C159.895342,225.303317 159.746968,239.087361 159.746968,243.303333 C159.746968,246.709601 162.05102,250.70089 168.53925,249.443941 C219.370432,232.499507 256,184.536204 256,128.00106 C256,57.3066942 198.691187,0 128.00106,0 Z M47.9405593,182.340212 C47.6586465,182.976105 46.6581745,183.166873 45.7467277,182.730227 C44.8183235,182.312656 44.2968914,181.445722 44.5978808,180.80771 C44.8734344,180.152739 45.876026,179.97045 46.8023103,180.409216 C47.7328342,180.826786 48.2627451,181.702199 47.9405593,182.340212 Z M54.2367892,187.958254 C53.6263318,188.524199 52.4329723,188.261363 51.6232682,187.366874 C50.7860088,186.474504 50.6291553,185.281144 51.2480912,184.70672 C51.8776254,184.140775 53.0349512,184.405731 53.8743302,185.298101 C54.7115892,186.201069 54.8748019,187.38595 54.2367892,187.958254 Z M58.5562413,195.146347 C57.7719732,195.691096 56.4895886,195.180261 55.6968417,194.042013 C54.9125733,192.903764 54.9125733,191.538713 55.713799,190.991845 C56.5086651,190.444977 57.7719732,190.936735 58.5753181,192.066505 C59.3574669,193.22383 59.3574669,194.58888 58.5562413,195.146347 Z M65.8613592,203.471174 C65.1597571,204.244846 63.6654083,204.03712 62.5716717,202.981538 C61.4524999,201.94927 61.1409122,200.484596 61.8446341,199.710926 C62.5547146,198.935137 64.0575422,199.15346 65.1597571,200.200564 C66.2704506,201.230712 66.6095936,202.705984 65.8613592,203.471174 Z M75.3025151,206.281542 C74.9930474,207.284134 73.553809,207.739857 72.1039724,207.313809 C70.6562556,206.875043 69.7087748,205.700761 70.0012857,204.687571 C70.302275,203.678621 71.7478721,203.20382 73.2083069,203.659543 C74.6539041,204.09619 75.6035048,205.261994 75.3025151,206.281542 Z M86.046947,207.473627 C86.0829806,208.529209 84.8535871,209.404622 83.3316829,209.4237 C81.8013,209.457614 80.563428,208.603398 80.5464708,207.564772 C80.5464708,206.498591 81.7483088,205.631657 83.2786917,205.606221 C84.8005962,205.576546 86.046947,206.424403 86.046947,207.473627 Z M96.6021471,207.069023 C96.7844366,208.099171 95.7267341,209.156872 94.215428,209.438785 C92.7295577,209.710099 91.3539086,209.074206 91.1652603,208.052538 C90.9808515,206.996955 92.0576306,205.939253 93.5413813,205.66582 C95.054807,205.402984 96.4092596,206.021919 96.6021471,207.069023 Z" fill="#161614"></path>
                                </g></svg>';
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
