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

namespace Drupal\auth_apic\Controller;

use Drupal\auth_apic\JWTToken;
use Drupal\auth_apic\Service\Interfaces\UserManagerInterface;
use Drupal\auth_apic\Service\Interfaces\OidcStateServiceInterface;
use Drupal\ibm_apim\Service\SiteConfig;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\ibm_apim\Service\Utils;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\ibm_apim\ApicType\ApicUser;

class ApicOidcAzCodeController extends ControllerBase {

  protected $utils;
  protected $userManager;
  protected $userUtils;
  protected $siteConfig;
  protected $oidcStateService;

  public function __construct(Utils $utils,
                              UserManagerInterface $user_manager,
                              UserUtils $user_utils,
                              SiteConfig $site_config,
                              OidcStateServiceInterface $oidc_state_service) {
    $this->utils = $utils;
    $this->userManager = $user_manager;
    $this->userUtils = $user_utils;
    $this->siteConfig = $site_config;
    $this->oidcStateService = $oidc_state_service;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ibm_apim.utils'),
      $container->get('auth_apic.usermanager'),
      $container->get('ibm_apim.user_utils'),
      $container->get('ibm_apim.site_config'),
      $container->get('auth_apic.oidc_state')
    );
  }

  public function processOidcRedirect() {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $authcode = \Drupal::request()->query->get('code');
    if (empty($authcode)) {
      drupal_set_message(t('Error: Missing authorization code parameter. Contact your system administrator.'), 'error');
      return $this->redirect('<front>');
    }
    $state = \Drupal::request()->query->get('state');
    if (empty($state)) {
      drupal_set_message(t('Error: Missing state parameter. Contact your system administrator.'), 'error');
      return $this->redirect('<front>');
    }

    $state_received = unserialize($this->utils->base64_url_decode($state));
// TODO: confirm that login is all that is needed on any flow... if so then this can all be simplified.
    if (is_string($state_received)) {
      // key to retrieve what we need from state.
      $state_obj = $this->oidcStateService->get($state_received);
      if (isset($state_obj)) {
        $this->oidcStateService->delete($state_received);
        // Clear the JWT from the session as we're done with it now
        if(isset($_SESSION['auth_apic']) && isset($_SESSION['auth_apic']['invitation_object'])) {
          $_SESSION['auth_apic']['invitation_object'] = NULL;
        }
        $redirect_location = $this->loginViaAzCode($authcode, $state_obj['registry_url']);
        return $this->redirect($redirect_location);
      }
      else {
        drupal_set_message(t('Unable to retrieve information required to proceed with invitation. Please contact your system administrator.'), 'error');
        return $this->redirect('<front>');
      }
    }
    else {
      $state_obj = $state_received;
      $redirect_location = $this->loginViaAzCode($authcode, $state_obj['registry_url']);
      return $this->redirect($redirect_location);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, 'redirect to <front>');
    return $this->redirect('<front>');

  }

  public function loginViaAzCode($authcode, $registry_url) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $login_user = new ApicUser();
    $login_user->setUsername('');
    $login_user->setPassword('');
    $login_user->setApicUserRegistryURL($registry_url);
    $login_user->setAuthcode($authcode);

    $apim_response = $this->userManager->login($login_user);

    if ($apim_response->success()) {

      // check if the user we just logged in is a member of at least one dev org
      $current_corg = $this->userUtils->getCurrentConsumerorg();
      if (!isset($current_corg)) {
        // if onboarding is enabled, we can redirect to the create org page
        if ($this->siteConfig->isSelfOnboardingEnabled()) {
          ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, 'redirect to consumerorg.create');
          return 'consumerorg.create';
        }
        else {
          // we can't help the user, they need to talk to an administrator
          ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, 'redirect to ibm_apim.noperms');
          return 'ibm_apim.noperms';
        }
      }
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, 'redirect to <front> successful');
      return '<front>';
    }
    else {
      if ($apim_response->getMessage()) {
        drupal_set_message($apim_response->getMessage(), 'error');
      }
      else {
        drupal_set_message('Error while authenticating user. Please contact your system administrator.', 'error');
      }
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, 'redirect to <front> error from apim');
      return '<front>';
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, 'redirect to front');
    return '<front>';

  }

}
