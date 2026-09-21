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
use Drupal\user\Entity\User;

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
      if (isset($form['modules']['IBM CMS Portal']['ibmsocial_login'])) {
        unset($form['modules']['IBM CMS Portal']['ibmsocial_login']);
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

  /**
   * Implements hook_token_info().
   *
   * Declares APIC-related user tokens.
   */
  #[Hook('token_info')]
  public function tokenInfo(): array {
    $definitions = [
      'first_name' => t('First name'),
      'last_name' => t('Last name'),
      'apic_realm' => t('APIC Realm'),
      'apic_state' => t('APIC State'),
      'apic_url' => t('APIC URL'),
      'consumerorg_url' => t('Consumer Organization URL'),
      'apic_user_registry_url' => t('APIC User Registry URL'),
      'apic_idp' => t('APIC Identity Provider'),
      'apic_catalog_id' => t('APIC Catalog ID'),
      'avatars_avatar_generator' => t('Avatar Generator'),
      'codesnippet' => t('Code Snippet Language'),
      'consumer_organization' => t('Consumer Organization'),
      'first_time_login' => t('First Time Login'),
      'language' => t('Preferred Language'),
      'field_last_password_reset' => t('Last Password Reset'),
      'avatars_user_picture' => t('User Picture Upload'),
      'field_password_expiration' => t('Password Expiration'),
      'field_pending_expire_sent' => t('Pending Expiration Mail Count'),
    ];

    $tokens = ['user' => []];

    foreach ($definitions as $key => $label) {
      $tokens['user'][$key] = [
        'name' => $label,
        'description' => $label,
      ];
    }

    return ['tokens' => $tokens];
  }

  /**
   * Helper method to get token value for a user.
   *
   * @param User $account The user account
   * @param string $name The token name (may include nested parts like 'created:long')
   */
  private function getTokenValue(User $account, string $name) {
    $value = NULL;

    switch ($name) {
      case 'apic_realm':
        $registry_url = $account->get('apic_user_registry_url')->value ?? NULL;
        if ($registry_url) {
          try {
            $registry = \Drupal::service('ibm_apim.user_registry')->get($registry_url);
            $value = $registry?->getRealm();
          }
          catch (\Throwable $e) {}
        }
        break;

      case 'apic_catalog_id':
        try {
          $value = \Drupal::service('ibm_apim.site_config')->getEnvId();
        }
        catch (\Throwable $e) {}
        break;

      case 'language':
        if ($account->hasField('preferred_langcode') && !$account->get('preferred_langcode')->isEmpty()) {
          $value = $account->get('preferred_langcode')->value;
        }
        elseif ($account->hasField('langcode') && !$account->get('langcode')->isEmpty()) {
          $value = $account->get('langcode')->value;
        }
        break;

      case 'consumerorg_url':
        if ($account->hasField('consumerorg_url') && !$account->get('consumerorg_url')->isEmpty()) {
          $values = $account->get('consumerorg_url')->getValue();
          if (!empty($values[0]['value'])) {
            $value = $values[0]['value'];
          }
        }
        break;

      case 'apic_idp':
        if ($account->hasField('apic_idp') && !$account->get('apic_idp')->isEmpty()) {
          $value = $account->get('apic_idp')->value;
        }
        break;

      case 'avatars_avatar_generator':
        if ($account->hasField('avatars_avatar_generator') && !$account->get('avatars_avatar_generator')->isEmpty()) {
          $value = $account->get('avatars_avatar_generator')->value;
        }
        break;

      case 'display-name':
        $value = $account->getDisplayName();
        break;

      case 'account-name':
      case 'name':
        $value = $account->getAccountName();
        break;

      case 'mail':
        $value = $account->getEmail();
        break;

      case 'url':
        $value = $account->toUrl('canonical', ['absolute' => TRUE])->toString();
        break;

      case 'edit-url':
        $value = $account->toUrl('edit-form', ['absolute' => TRUE])->toString();
        break;

      case 'last-login':
        $timestamp = $account->getLastLoginTime();
        if ($timestamp) {
          $value = \Drupal::service('date.formatter')->format($timestamp, 'medium');
        }
        break;

      case 'created':
        $timestamp = $account->getCreatedTime();
        if ($timestamp) {
          $value = \Drupal::service('date.formatter')->format($timestamp, 'medium');
        }
        break;

      default:

        if (strpos($name, ':') !== FALSE) {
          [$base_token, $format] = explode(':', $name, 2);

          $timestamp = NULL;
          if ($base_token === 'created') {
            $timestamp = $account->getCreatedTime();
          }
          elseif ($base_token === 'last-login') {
            $timestamp = $account->getLastLoginTime();
          }

          if ($timestamp) {
            $date_formatter = \Drupal::service('date.formatter');
            switch ($format) {
              case 'short':
              case 'medium':
              case 'long':
                $value = $date_formatter->format($timestamp, $format);
                break;
              case 'raw':
                $value = $timestamp;
                break;
              case 'since':
                $value = $date_formatter->formatTimeDiffSince($timestamp);
                break;
              case 'html_date':
              case 'html_datetime':
              case 'html_month':
              case 'html_time':
              case 'html_week':
              case 'html_year':
              case 'html_yearless_date':
                $value = $date_formatter->format($timestamp, 'custom', 'Y-m-d');
                break;
              default:
                if (strpos($format, 'custom:') === 0) {
                  $custom_format = substr($format, 7);
                  $value = $date_formatter->format($timestamp, 'custom', $custom_format);
                }
                else {
                  $value = $date_formatter->format($timestamp, $format);
                }
                break;
            }
          }
        }
        elseif ($account->hasField($name) && !$account->get($name)->isEmpty()) {
          $field = $account->get($name);
          $value = is_array($field->getValue())
            ? ($field->value ?? $field->getValue()[0]['value'] ?? NULL)
            : $field->value;
        }
        break;
    }

    return $value;
  }

  /**
   * Implements hook_tokens().
   *
   * Provides replacements for APIC user tokens.
   */
  #[Hook('tokens')]
  public function tokens(string $type, array $tokens, array $data = [], array $options = []): array {
    if (!in_array($type, ['user', 'current-user'], TRUE)) {
      return [];
    }

    $account = $data['user'] ?? User::load(\Drupal::currentUser()->id());
    if (!$account instanceof User) {
      return [];
    }

    $replacements = [];

    foreach ($tokens as $name => $original) {
      $value = $this->getTokenValue($account, $name);
      if ($value !== NULL) {
        $replacements[$original] = $value;
      }
    }

    $token_service = \Drupal::token();
    if ($token_service && method_exists($token_service, 'findWithPrefix')) {
      $original_tokens = $token_service->findWithPrefix($tokens, 'original');

      if ($original_tokens && !empty($original_tokens)) {
        $original_user = $data['user_original'] ?? $account;

        if ($original_user instanceof User) {

          foreach ($original_tokens as $name => $original) {
            $value = $this->getTokenValue($original_user, $name);
            if ($value !== NULL) {
              $replacements[$original] = $value;
            }
          }
        }
      }
    }

    return $replacements;
  }

}