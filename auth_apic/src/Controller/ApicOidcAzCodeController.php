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

namespace Drupal\auth_apic\Controller;

use Drupal\auth_apic\Service\Interfaces\UserManagerInterface;
use Drupal\auth_apic\Service\Interfaces\OidcStateServiceInterface;
use Drupal\ibm_apim\Service\SiteConfig;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\ibm_apim\Service\Utils;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\session_based_temp_store\SessionBasedTempStoreFactory;

class ApicOidcAzCodeController extends ControllerBase {

  protected $utils;

  protected $userManager;

  protected $userUtils;

  protected $siteConfig;

  protected $oidcStateService;

  protected $authApicSessionStore;

  public function __construct(Utils $utils,
                              UserManagerInterface $user_manager,
                              UserUtils $user_utils,
                              SiteConfig $site_config,
                              OidcStateServiceInterface $oidc_state_service,
                              SessionBasedTempStoreFactory $sessionStoreFactory) {
    $this->utils = $utils;
    $this->userManager = $user_manager;
    $this->userUtils = $user_utils;
    $this->siteConfig = $site_config;
    $this->oidcStateService = $oidc_state_service;
    $this->authApicSessionStore = $sessionStoreFactory->get('auth_apic_invitation_token');
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ibm_apim.utils'),
      $container->get('auth_apic.usermanager'),
      $container->get('ibm_apim.user_utils'),
      $container->get('ibm_apim.site_config'),
      $container->get('auth_apic.oidc_state'),
      $container->get('session_based_temp_store')
    );
  }

  public function processOidcRedirect(): ?\Symfony\Component\HttpFoundation\RedirectResponse {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $authCode = \Drupal::request()->query->get('code');
    if (empty($authCode)) {
      drupal_set_message(t('Error: Missing authorization code parameter. Contact your system administrator.'), 'error');
      return $this->redirect('<front>');
    }
    $state = \Drupal::request()->query->get('state');
    if (empty($state)) {
      drupal_set_message(t('Error: Missing state parameter. Contact your system administrator.'), 'error');
      return $this->redirect('<front>');
    }

    $stateReceived = unserialize($this->utils->base64_url_decode($state), ['allowed_classes' => FALSE]);
    // TODO: confirm that login is all that is needed on any flow... if so then this can all be simplified.
    if (is_string($stateReceived)) {
      // key to retrieve what we need from state.
      $stateObj = $this->oidcStateService->get($stateReceived);
      if (isset($stateObj)) {
        $this->oidcStateService->delete($stateReceived);
        // Clear the JWT from the session as we're done with it now
        $this->authApicSessionStore->delete('invitation_object');

        $redirect_location = $this->loginViaAzCode($authCode, $stateObj['registry_url']);
        return $this->redirect($redirect_location);
      }
      else {
        drupal_set_message(t('Unable to retrieve information required to proceed with invitation. Please contact your system administrator.'), 'error');
        return $this->redirect('<front>');
      }
    }
    else {
      $stateObj = $stateReceived;
      $redirectLocation = $this->loginViaAzCode($authCode, $stateObj['registry_url']);
      return $this->redirect($redirectLocation);
    }
  }

  public function loginViaAzCode($authCode, $registryUrl): string {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $redirectTo = '<front>';

    $loginUser = new ApicUser();
    $loginUser->setUsername('');
    $loginUser->setPassword('');
    $loginUser->setApicUserRegistryUrl($registryUrl);
    $loginUser->setAuthcode($authCode);

    $apimResponse = $this->userManager->login($loginUser);

    if ($apimResponse->success()) {

      // check if the user we just logged in is a member of at least one dev org
      $currentCOrg = $this->userUtils->getCurrentConsumerorg();
      if (!isset($currentCOrg)) {
        // if onboarding is enabled, we can redirect to the create org page
        if ($this->siteConfig->isSelfOnboardingEnabled()) {
          $redirectTo = 'consumerorg.create';
        }
        else {
          // we can't help the user, they need to talk to an administrator
          $redirectTo = 'ibm_apim.noperms';
        }
        $message = 'redirect to ' . $redirectTo . ' as no consumer org set';
      }
      else {
        $message = 'redirect to ' . $redirectTo . ' successful';
      }
    }
    else {
      drupal_set_message(t('Error while authenticating user. Please contact your system administrator.'), 'error');
      $message = 'redirect to front error from apim';
      $redirectTo = '<front>';
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $message);
    return $redirectTo;
  }

}
