<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2022
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Wizard;

use Drupal\Core\Form\FormBase;

/**
 * Class IbmWizardStepBase
 *
 * @package Drupal\ibm_apim\Wizard
 */
abstract class IbmWizardStepBase extends FormBase {

  /**
   * Checks that the current user has developer permissions i.e. is able
   * to create subscriptions for apps. If not, redirects to the home page.
   */
  protected function validateAccess(): bool {

    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $return_value = TRUE;

    $user_utils = \Drupal::service('ibm_apim.user_utils');

    if (\Drupal::currentUser()->isAnonymous()) {
      // The user needs to log in / sign up first but we need to make note that they want to start the subscription wizard
      user_cookie_save(['startSubscriptionWizard' => \Drupal::request()->get('productId')]);
      \Drupal::messenger()->addError(t('Sign in or create an account to subscribe to products and start using our APIs'));
      $this->redirect('user.page')->send();
      $return_value = FALSE;
    }

    if (!$user_utils->checkHasPermission('subscription:manage')) {
      \Drupal::messenger()->addError(t('Only users with the required permissions can access the subscription wizard'));
      $this->redirect('<front>')->send();
      $return_value = FALSE;
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $return_value);

    return $return_value;

  }

}
