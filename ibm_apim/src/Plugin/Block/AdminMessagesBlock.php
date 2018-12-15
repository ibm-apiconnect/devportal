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
   */
  public function build() {

    $build = array();
    $current_user = \Drupal::currentUser();
    if (isset($current_user)) {
      $user = User::load($current_user->id());
    }

    $status_messages = \Drupal::state()->get('ibm_apim.status_messages');
    $messages = array();

    if (is_array($status_messages)) {
      foreach($status_messages as $status_message) {
        if (!isset($status_message['role']) || (isset($status_message['role']) && isset($user) && $user->hasRole($status_message['role']))) {
          $messages[] = $status_message['text'];
        }
      }
    }
    $errors = [];
    $config_set = \Drupal::service('ibm_apim.site_config')->isSet();
    if (!isset($config_set) || $config_set === FALSE) {
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
    if (!isset($clientId) || empty($clientId) || !isset($clientSecret) || empty($clientSecret)) {
      $messages['config'][] = t('FATAL: Site credentials are missing. Contact your system administrator before continuing further. This will prevent almost all portal functionality from working including user login.');
      $errors[] = 'Site credentials are missing.';
    }

    if (isset($messages) && !empty($messages)) {
      $build['#header'] = t('There are issues with your site:');
      $build['#messages'] = $messages;
      $build['#theme'] = 'ibm_apim_status_messages_block';
    }

    if (!empty($errors)) {
      \Drupal::logger('ibm_apim')->error('AdminMessagesBlock FATAL ERRORS: %data.', [
        '%data' => implode(',', $errors)
      ]);
    }

    return $build;

  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
