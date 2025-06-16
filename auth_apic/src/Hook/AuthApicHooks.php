<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2025
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * @file
 * Provides user management integration with APIC.
 */
namespace Drupal\auth_apic\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\auth_apic\Form\ApicUserProfileForm;
use Drupal\auth_apic\Form\ApicUserRegisterForm;
use Drupal\Core\Form\FormStateInterface;

class AuthApicHooks {
  /**
   * Implements hook_form_alter().
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $form_id
   */
  #[Hook('form_alter')]
  public function formAlter(&$form, FormStateInterface $form_state, $form_id) {
    if ($form_id === 'system_modules') {
      /*
      * Block use of modules which would conflict with auth_apic
      */
      if (isset($form['modules']['HybridAuth'])) {
        unset($form['modules']['HybridAuth']);
      }
      if (isset($form['modules']['Lightweight Directory Access Protocol'])) {
        unset($form['modules']['Lightweight Directory Access Protocol']);
      }
      if (isset($form['modules']['IBM API Developer Portal']['ibmsocial_login'])) {
        unset($form['modules']['IBM API Developer Portal']['ibmsocial_login']);
      }
      if (isset($form['modules']['IBM API Connect']['ibmsocial_login'])) {
        unset($form['modules']['IBM API Connect']['ibmsocial_login']);
      }
      if (isset($form['modules']['Social']['social_auth_google'])) {
        unset($form['modules']['Social']['social_auth_google']);
      }
      if (isset($form['modules']['Social']['social_auth'])) {
        unset($form['modules']['Social']['social_auth']);
      }
      if (isset($form['modules']['Other']['openid_connect'])) {
        unset($form['modules']['Other']['openid_connect']);
      }
    }
    elseif ($form_id === 'user_form') {
      if (\Drupal::moduleHandler()->moduleExists('change_pwd_page')) {
        // this is here to ensure that the current password field is definitely not shown
        // mainly to avoid needing to patch the change_pwd_page module in test environments

        // Hide the new password fields.
        $form['pass']['#access'] = FALSE;
      }
    }
  }

  /**
   * Implements hook_entity_type_alter().
   *
   * @param array $entity_types
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types) {
    $entity_types['user']->setFormClass('default', ApicUserProfileForm::class);
    $entity_types['user']->setFormClass('register', ApicUserRegisterForm::class);
  }

  /**
   * Implementation of hook_form_FORM_ID_alter() to alter the account settings form
   *
   * @param $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $form_id
   */
  #[Hook('form_user_admin_settings_alter')]
  public function formUserAdminSettingsAlter(&$form, FormStateInterface $form_state, $form_id) {
    if (isset($form['registration_cancellation']['user_register'])) {
      unset($form['registration_cancellation']['user_register']);
    }
    if (isset($form['registration_cancellation']['user_email_verification'])) {
      unset($form['registration_cancellation']['user_email_verification']);
    }
    if (isset($form['email_admin_created'])) {
      unset($form['email_admin_created']);
    }
    if (isset($form['email_pending_approval'])) {
      unset($form['email_pending_approval']);
    }
    if (isset($form['email_pending_approval_admin'])) {
      unset($form['email_pending_approval_admin']);
    }
    if (isset($form['email_no_approval_required'])) {
      unset($form['email_no_approval_required']);
    }
    if (isset($form['email_activated'])) {
      unset($form['email_activated']);
    }
  }

  /**
   * Alter local tasks.
   * Hide the change password link on the user profile pages if using ldap and not admin, or admin viewing someone elses account.
   *
   * @param $data
   * @param $route_name
   */
  #[Hook('menu_local_tasks_alter')]
  public function menuLocalTasksAlter(&$data, $route_name) {
    if (isset($data['tabs'][0]['change_pwd_page.change_password_form'])) {
      $changepw_task = $data['tabs'][0]['change_pwd_page.change_password_form'];
    }

    if (isset($changepw_task)) {
      $url = $changepw_task['#link']['url'];

      $currentUserId = (int) \Drupal::currentUser()->id();
      $form_for_user = (int) $url->getRouteParameters()['user'];
      $ro = (boolean) \Drupal::state()->get('ibm_apim.readonly_idp');

      if (($currentUserId !== $form_for_user) || ($currentUserId !== 1 && $ro === TRUE)) {
        unset($data['tabs'][0]['change_pwd_page.change_password_form']);
      }
    }
  }
 }