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

namespace Drupal\ibm_apim\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\user\Entity\User;

/**
 * Provides an Admin Status Messages Block.
 *
 * @Block(
 *   id = "ibm_apim_status_messages",
 *   admin_label = @Translation("Admin Status Messages"),
 *   category = @Translation("Admin Status Messages"),
 * )
 */
class AdminMessagesBlock extends BlockBase {

  /**
   * {@inheritdoc}
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function build(): array {

    // this is an evil hack to update the site URLs in content that needs it
    // this has to be done as part of a user browsing session since drush doesnt know what the site URL is
    $update_site_url = \Drupal::state()->get('ibm_apim.update_site_url');
    if (defined('DRUPAL_ROOT') && $update_site_url === TRUE) {
      require_once DRUPAL_ROOT . '/profiles/apim_profile/apim_profile.homepage.inc';
      if (function_exists('apim_profile_update_forum_block')) {
        apim_profile_update_forum_block();
      }
      \Drupal::state()->delete('ibm_apim.update_site_url');
    }

    // clear cookies when navigating away from user management pages
    $current_route = \Drupal::routeMatch()->getRouteName();
    if ($current_route !== 'user.login' && $current_route !== 'user.register' && $current_route !== 'auth_apic.azcode') {
      $sessionStore = \Drupal::service('tempstore.private')->get('auth_apic_storage');
      $sessionStore->delete('invitation_object');
    }

    $build = [];
    $current_user = \Drupal::currentUser();
    if (isset($current_user)) {
      $user = User::load($current_user->id());
    }

    $expired = \Drupal::request()->cookies->get('Drupal_visitor_ibm_apim_session_expired_on_token');
    if ($expired === 'TRUE') {
      \Drupal::messenger()->addError(t('Session expired. Please sign in again.'));
      \user_cookie_delete('ibm_apim_session_expired_on_token');
    }

    $status_messages = \Drupal::state()->get('ibm_apim.status_messages');
    $messages = [];

    if (is_array($status_messages)) {
      foreach ($status_messages as $status_message) {
        if (!isset($status_message['role']) || (isset($status_message['role'], $user) && $user->hasRole($status_message['role']))) {
          $messages[] = $status_message['text'];
        }
      }
    }

    $errors = [];
    $config_set = (boolean) \Drupal::service('ibm_apim.site_config')->isSet();
    if ($config_set === NULL || $config_set === FALSE) {
      $messages['config'][] = t('FATAL: Initial portal configuration has not been received from the API Manager. Contact your system administrator before continuing further. This will prevent almost all portal functionality from working including user login.');
      $errors[] = 'No portal config received.';
    }
    $urs = \Drupal::service('ibm_apim.user_registry')->getAll();
    if (!isset($urs) || empty($urs)) {
      $messages['config'][] = t('FATAL: No user registries defined. Contact your system administrator before continuing. This will prevent almost all portal functionality from working including user login.');
      $errors[] = 'No user registries defined.';
    }
    $orgId = \Drupal::service('ibm_apim.site_config')->getOrgId();
    if (!isset($orgId) || empty($orgId)) {
      $messages['config'][] = t('FATAL: Missing provider organization ID. Contact your system administrator before continuing further. This will prevent almost all portal functionality from working including user login.');
      $errors[] = 'Missing provider organization ID.';
    }
    $envId = \Drupal::service('ibm_apim.site_config')->getEnvId();
    if (!isset($envId) || empty($envId)) {
      $messages['config'][] = t('FATAL: Missing catalog ID. Contact your system administrator before continuing further. This will prevent almost all portal functionality from working including user login.');
      $errors[] = 'Missing catalog ID.';
    }
    $clientId = \Drupal::service('ibm_apim.site_config')->getClientId();
    $clientSecret = \Drupal::service('ibm_apim.site_config')->getClientSecret();
    if (!isset($clientId, $clientSecret) || empty($clientId) || empty($clientSecret)) {
      $messages['config'][] = t('FATAL: Site credentials are missing. Contact your system administrator before continuing further. This will prevent almost all portal functionality from working including user login.');
      $errors[] = 'Site credentials are missing.';
    }
    if (isset($errors) && !empty($errors)) {
      $messages['config'][] = t('FATAL: Essential required information has not been received from the API Manager. Contact your system administrator before continuing further.');
      $errors[] = 'Essential required information has not been received from the API Manager.';
    }

    if (isset($messages) && !empty($messages)) {
      $build['#header'] = t('There are issues with your site:');
      $build['#messages'] = $messages;
      $build['#theme'] = 'ibm_apim_status_messages_block';
    }

    if (!empty($errors)) {
      \Drupal::logger('ibm_apim')->error('AdminMessagesBlock FATAL ERRORS: %data.', [
        '%data' => implode(',', $errors),
      ]);
    }

    return $build;

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge(): int {
    return 0;
  }

}
