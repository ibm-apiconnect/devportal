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

    \Drupal::logger('mock_auth_apic')->debug('MOCKED: MockApicLoginService->login() with ' . \serialize($user));
    //\Drupal::messenger()->addStatus('MOCKED: MockApicLoginService->login()');
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

    $loginuser = NULL;
    $userStorage = \Drupal::service('entity_type.manager')->getStorage('user');

    $searchProperties = ['name' => $user->getUsername()];
    $apicUserRegistryUrl = $user->getApicUserRegistryUrl();
    if ($apicUserRegistryUrl !== NULL && $apicUserRegistryUrl !== '/mock/user/registry') {
      $searchProperties['registry_url'] = $apicUserRegistryUrl;
    }

    $users = $userStorage->loadByProperties($searchProperties);

    if (\sizeof($users) > 0) {
      // Return the id of the user if the user account is found.
      $loginuser = reset($users);
    }
    else if (\sizeof($users) === 0) {
      // some methods that users are created will not add the registry url yet we will still
      // search on it because of the login form functionality in these cases we will fall
      // back to older logic and do a more crude search on username.

      $ids = \Drupal::entityQuery('user')->execute();
      $users = User::loadMultiple($ids);

      foreach ($users as $listUser) {
        if ($listUser->getAccountName() === $username) {
          $loginuser = $listUser;
          break;
        }
      }

    }

    if ($loginuser !== NULL) {
      user_login_finalize($loginuser);
      $umResponse->setSuccess(TRUE);
      $umResponse->setUid($loginuser->id());

      if ((int) $loginuser->id() !== 1) {
        \Drupal::service('ibm_apim.user_utils')->setCurrentConsumerorg();
        \Drupal::service('ibm_apim.user_utils')->setOrgSessionData();
      }
      \Drupal::logger('mock_auth_apic')->debug('login from mock successful');
      return $umResponse;
    }
    else {

      $umResponse->setSuccess(FALSE);
      $umResponse->setUid(0);
      \Drupal::logger('mock_auth_apic')->debug('login from mock unsuccessful - no user found.');
      return $umResponse;
    }


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

    if ($authCode === 'noorgenabledonboarding' || $authCode === 'noorgdisabledonboarding' || $authCode === 'firsttimelogin') {
      $user = new ApicUser();
      $user->setUsername('oidcandre');
      $user->setPassword('oidcoidc');
      $this->login($user);
    }

    if ($authCode === 'fail') {
      \Drupal::messenger()->addError('Error while authenticating user. Please contact your system administrator.');
    }
    elseif ($authCode === 'noorgenabledonboarding') {
      return 'consumerorg.create';
    }
    elseif ($authCode === 'noorgdisabledonboarding') {
      return 'ibm_apim.noperms';
    }
    elseif ($authCode === 'firsttimelogin') {
      return 'ibm_apim.get_started';
    }
    elseif ($authCode === 'routetoerror') {
      return 'ERROR';
    }

    return '<front>';
  }

}
