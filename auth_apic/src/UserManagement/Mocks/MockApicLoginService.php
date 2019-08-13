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

namespace Drupal\auth_apic\UserManagement\Mocks;


use Drupal\auth_apic\UserManagement\ApicLoginServiceInterface;
use Drupal\auth_apic\UserManagerResponse;
use Drupal\ibm_apim\ApicType\ApicUser;
use Drupal\user\Entity\User;

class MockApicLoginService implements ApicLoginServiceInterface {

    /**
     * @inheritDoc
     */
    public function login(ApicUser $user): UserManagerResponse {
      // check for authcode first, is so then we are an oidc user - so mock out test responses.
      if ($authcode = $user->getAuthcode()) {
        return $this->oidcLogin($user);
      }

      \Drupal::logger('mock_auth_apic')->debug('MOCKED: MockApicLoginService->login()');
      //drupal_set_message('MOCKED: MockApicLoginService->login()');
      // otherwise we are a non-oidc user.
      $password = $user->getPassword();
      $username = $user->getUsername();

      $umResponse = new UserManagerResponse();

      // If password is 'invalidPassword', return 0 as the user id.
      if ($password === 'invalidPassword') {
        $umResponse->setSuccess(FALSE);
        $umResponse->setUid(0);
        return $umResponse;
      }

      // for not in database case, create dummy success response for the test
      if ($username === 'notindatabase') {
        $umResponse->setSuccess(TRUE);
        $umResponse->setUid(12);
        return $umResponse;
      }

      // Search the user database for the matching user.
      $ids = \Drupal::entityQuery('user')->execute();
      $users = User::loadMultiple($ids);

      // Return the id of the user if the user account is found.
      foreach ($users as $listUser) {
        if ($listUser->getUsername() === $username) {
          user_login_finalize($listUser);
          $umResponse->setSuccess(TRUE);
          $umResponse->setUid($listUser->id());

          if ((int) $listUser->id() !== 1) {
            \Drupal::service('ibm_apim.user_utils')->setCurrentConsumerorg();
            \Drupal::service('ibm_apim.user_utils')->setOrgSessionData();
          }

          return $umResponse;
        }
      }

      // Return an invalid user id if the user doesn't exist.
      $umResponse->setSuccess(FALSE);
      $umResponse->setUid(0);
      return $umResponse;
    }

    private function oidcLogin(ApicUser $user): UserManagerResponse {
      $response = new UserManagerResponse();

      switch ($user->getAuthCode()) {
        case 'validauthcode':
          $response->setSuccess(TRUE);
          break;
        case 'failwithmessage':
          $response->setSuccess(FALSE);
          $response->setMessage('Mocked error message from apim login() call');
          break;
        case 'fail':
          $response->setSuccess(FALSE);
          break;
      }

      return $response;
    }

  /**
   * @inheritDoc
   */
  public function loginViaAzCode($authCode, $registryUrl): string {

    if ($authCode === 'noorgenabledonboarding' || $authCode == 'noorgdisabledonboarding') {
      $user = new ApicUser();
      $user->setUsername('oidcandre');
      $user->setPassword('oidcoidc');
      $this->login($user);
    }

    if ($authCode === 'fail') {
      \drupal_set_message('Error while authenticating user. Please contact your system administrator.', 'error');
    }
    else if ($authCode === 'noorgenabledonboarding') {
      return 'consumerorg.create';
    }
    else if ($authCode === 'noorgdisabledonboarding') {
      return 'ibm_apim.noperms';
    }
    else if ($authCode === 'routetoerror') {
      return 'ERROR';
    }

    return '<front>';
  }
}
