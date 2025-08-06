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
 * IBM API Connect Integration
 *
 * Adds the Consumer organization node content type to Drupal for representing consumer organizations from IBM APIC
 */

namespace Drupal\consumerorg\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;


class ConsumerOrgHooks {
  /**
   * Implements hook_node_access_records().
   *
   * For consumerorg nodes, create a realm named after the orgid for that node,
   * and require a permission of CONSUMERORG_GRANT to view that node
   *
   * @param $node
   *
   * @return array
   *
   * Note: hook is called when rebuilding permissions
   */
  #[Hook('node_access_records')]
  public function nodeAccessRecords($node): array {

    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $config = \Drupal::config('ibm_apim.devel_settings');
    $acl_debug = (boolean) $config->get('acl_debug');

    $type = is_string($node) ? $node : $node->getType();
    $grants = [];

    // Only build permissions for consumerorg nodes
    if ($type === 'consumerorg') {
      $orgUrl = $node->consumerorg_url->value;
      $escapedOrgUrl = str_replace('/', '_', $orgUrl);
      $grants[] = [
        'realm' => 'consumerorg_' . $escapedOrgUrl,
        'gid' => CONSUMERORG_GRANT,
        'grant_view' => 1,
        'grant_update' => 0,
        'grant_delete' => 0,
        'priority' => 0,
      ];
      if ($acl_debug === TRUE) {
        foreach ($grants as $grant) {
          \Drupal::logger('ACLDEBUG')->debug('Realm: @realm granted: @grant', [
            '@realm' => $grant['realm'],
            '@grant' => 'CONSUMERORG_GRANT',
          ]);
        }
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $grants);
    return $grants;
  }

  /**
   * Implements hook_node_grants().
   *
   * For the view operation, allow CONSUMERORG_GRANT permission to the
   * consumerorg realm named after the orgid of the user
   *
   * @param $account
   * @param $op
   *
   * @return array
   *
   * Note: hook is not called at all when admin logged in
   * Note: hook is called on every login, logout and page load
   */
  #[Hook('node_grants')]
  public function nodeGrants($account, $op): array {

    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $config = \Drupal::config('ibm_apim.settings');
    $acl_debug = (boolean) $config->get('acl_debug');

    $grants = [];
    if ($op === 'view' && \Drupal::currentUser()->isAuthenticated()) {
      $userOrgs = [];

      $user = User::load(\Drupal::currentUser()->id());
      if ($user !== NULL) {
        $userOrgs = $user->consumerorg_url->getValue();
      }
      foreach ($userOrgs as $index => $values) {
        $userOrgUrl = $values['value'];
        $escapedOrgUrl = str_replace('/', '_', $userOrgUrl);
        $grants['consumerorg_' . $escapedOrgUrl] = [CONSUMERORG_GRANT];
      }

      if ($acl_debug !== NULL && $acl_debug === TRUE) {
        foreach ($grants as $realm => $perms) {
          foreach ($perms as $grant) {
            \Drupal::logger('ACLDEBUG')->debug('Realm: @realm granted: @grant', [
              '@realm' => $realm,
              '@grant' => 'CONSUMERORG_GRANT',
            ]);
          }
        }
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $grants);
    return $grants;
  }


  /**
   * Implements hook_node_access().
   * This is checking if the specified consumerorg is returned from apic, if not it blocks access.
   *
   * @param \Drupal\node\NodeInterface $node
   * @param $operation
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden|\Drupal\Core\Access\AccessResultNeutral
   */
  #[Hook('node_access')]
  public function nodeAccess(NodeInterface $node, $operation, AccountInterface $account) {
    $type = $node->type;
    if ($type === 'consumerorg' && $operation === 'view') {
      $userUtils = \Drupal::service('ibm_apim.user_utils');
      $org = $userUtils->getCurrentConsumerorg();
      if ($node->consumerorg_url->value === $org['url']) {
        $access = new AccessResultAllowed();
      }
      else {
        $access = new AccessResultForbidden();
      }
    }
    else {
      $access = new AccessResultNeutral();
    }
    return $access;
  }


  /**
   * Only allows access to the payment_methods for the current consumerorg
   *
   * Implements hook_entity_access().
   */
  #[Hook('entity_access')]
  public function consumerorg_entityAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($entity->bundle() === 'consumerorg_payment_method') {
      $userUtils = \Drupal::service('ibm_apim.user_utils');
      $org = $userUtils->getCurrentConsumerorg();
      if ($entity->consumerorg_url->value === $org['url']) {
        $access = new AccessResultAllowed();
      }
      else {
        $access = new AccessResultForbidden();
      }
    }
  }

  /**
   * Implements hook_form_alter().
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   * @param $form_id
   */
  #[Hook('form_alter')]
  public function formAlter(array &$form, FormStateInterface $form_state, $form_id) {
    switch ($form_id) {
      // remove link to delete our content type
      case 'node_type_edit_form' :
        if (isset($form['type']['#default_value'], $form['actions']['delete']) && $form['type']['#default_value'] === 'consumerorg') {
          unset($form['actions']['delete']);
        }
        break;
      case 'node_consumerorg_edit_form':
        // disable fields to stop admin editing applications
        $currentUser = \Drupal::currentUser();
        if ((int) $currentUser->id() === 1) {
          $form['title']['#disabled'] = TRUE;
        }
        // if anyone has made our internal fields visible, then lets make them readonly
        $internal_field_list = \Drupal::service('ibm_apim.consumerorg')->getIBMFields();
        foreach ($internal_field_list as $fieldName) {
          if (isset($form[$fieldName])) {
            $form[$fieldName]['#disabled'] = TRUE;
          }
        }
        break;
    }
  }

  /**
   *  Implements hook_menu_links_discovered_alter().
   *
   * @param $links
   */
  #[Hook('menu_links_discovered_alter')]
  public function menuLinksDiscoveredAlter(&$links) {
    // remove link to delete our content type
    if (isset($links['entity.node_type.delete_form.consumerorg'])) {
      unset($links['entity.node_type.delete_form.consumerorg']);
    }
    // remove link to create content of our content type
    if (isset($links['node.add.consumerorg'])) {
      unset($links['node.add.consumerorg']);
    }
  }


  /**
   * Add twig template for My Organization page
   *
   * @param $existing
   * @param $type
   * @param $theme
   * @param $path
   *
   * @return array
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path): array {
    return [
      'consumerorg_select_block' => [
        'variables' => [
          'orgs' => [],
          'selected_name' => NULL,
          'selected_id' => NULL,
          'create_allowed' => FALSE,
        ],
      ],
      'consumerorg_billing' => [
        'variables' => [
          'node' => [],
          'consumerorgId' => NULL,
          'consumerorgTitle' => NULL,
          'tabs' => [],
          'images_path' => \Drupal::service('extension.list.module')->getPath('ibm_apim'),
          'showPlaceholders' => TRUE,
        ],
      ],
    ];
  }

  /**
   * Implements hook_token_info().
   */
  #[Hook('token_info')]
  public function tokenInfo(): array {

    $type = [
      'name' => t('Consumer organization'),
      'description' => t('Tokens related to the individual consumer organization'),
      'needs-data' => 'consumer-org',
    ];

    $consumerOrg['name'] = [
      'name' => t("Name"),
      'description' => t("The name of the consumer organization"),
    ];
    $consumerOrg['title'] = [
      'name' => t("Title"),
      'description' => t("The title of the consumer organization"),
    ];
    $consumerOrg['id'] = [
      'name' => t("ID"),
      'description' => t("The ID of the consumer organization"),
    ];

    return [
      'types' => ['consumer-org' => $type],
      'tokens' => ['consumer-org' => $consumerOrg],
    ];
  }

  /**
   * Implementation hook_tokens().
   *
   * These token replacements are used by Rules.
   *
   * @param $type
   * @param $tokens
   * @param array $data
   * @param array $options
   *
   * @return array
   */
  #[Hook('tokens')]
  public function tokens($type, $tokens, array $data = [], array $options = []): array {

    $replacements = [];
    if ($type === 'consumer-org' && !empty($data['consumer-org'])) {
      $consumerOrg = $data['consumer-org'];

      foreach ($tokens as $name => $original) {
        switch ($name) {
          case 'name':
            $replacements[$original] = $consumerOrg->consumerorg_name->value;
            break;
          case 'title':
            $replacements[$original] = $consumerOrg->getTitle();
            break;
          case 'id':
            $replacements[$original] = $consumerOrg->consumerorg_id->value;
            break;
        }
      }
    }
    return $replacements;
  }
 }