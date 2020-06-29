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

namespace Drupal\auth_apic\Controller;

use Drupal\auth_apic\Service\Interfaces\OidcStateServiceInterface;
use Drupal\auth_apic\UserManagement\ApicLoginServiceInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\ibm_apim\Service\Utils;
use Drupal\session_based_temp_store\SessionBasedTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ApicOidcAzCodeController extends ControllerBase {

  /**
   * @var \Drupal\ibm_apim\Service\Utils
   */
  protected $utils;

  /**
   * @var \Drupal\auth_apic\UserManagement\ApicLoginServiceInterface
   */
  protected $loginService;

  /**
   * @var \Drupal\auth_apic\Service\Interfaces\OidcStateServiceInterface
   */
  protected $oidcStateService;

  /**
   * @var
   */
  protected $authApicSessionStore;

  public function __construct(Utils $utils,
                              ApicLoginServiceInterface $login_service,
                              OidcStateServiceInterface $oidc_state_service,
                              SessionBasedTempStoreFactory $sessionStoreFactory) {
    $this->utils = $utils;
    $this->loginService = $login_service;
    $this->oidcStateService = $oidc_state_service;
    $this->authApicSessionStore = $sessionStoreFactory->get('auth_apic_invitation_token');
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('ibm_apim.utils'),
      $container->get('auth_apic.login'),
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

        $redirect_location = $this->loginService->loginViaAzCode($authCode, $stateObj['registry_url']);
        if ($redirect_location === 'ERROR') {
          drupal_set_message(t('Error while authenticating user. Please contact your system administrator.'), 'error');
          $redirect_location = '<front>';
        }
        return $this->redirect($redirect_location);
      }
      else {
        drupal_set_message(t('Unable to retrieve information required to proceed with invitation. Please contact your system administrator.'), 'error');
        return $this->redirect('<front>');
      }
    }
    else {
      $stateObj = $stateReceived;
      $redirectLocation = $this->loginService->loginViaAzCode($authCode, $stateObj['registry_url']);
      if ($redirectLocation === 'ERROR') {
        drupal_set_message(t('Error while authenticating user. Please contact your system administrator.'), 'error');
        $redirectLocation = '<front>';
      }
      return $this->redirect($redirectLocation);
    }
  }

}




