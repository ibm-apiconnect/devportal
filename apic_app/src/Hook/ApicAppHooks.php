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
 * Provides the application integration with APIC.
 */
namespace Drupal\apic_app\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\views\ViewExecutable;

class ApicAppHooks {
  /**
   * Implements hook_node_access_records().
   *
   * For application nodes, create a realm named after the url for that node,
   * and require a permission of APPLICATION_GRANT to view that node
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
    $aclDebug = (boolean) $config->get('acl_debug');

    $type = is_string($node) ? $node : $node->getType();
    $grants = [];

    // Only build permissions for application nodes
    if ($type === 'application') {
      $org = $node->application_consumer_org_url->value;
      $org = str_replace('/', '_', $org);
      $grants[] = [
        'realm' => 'application_' . $org,
        'gid' => APPLICATION_GRANT,
        'grant_view' => 1,
        'grant_update' => 0,
        'grant_delete' => 0,
        'priority' => 0,
      ];
      if ($aclDebug === TRUE) {
        foreach ($grants as $grant) {
          \Drupal::logger('ACLDEBUG')->debug('Realm: @realm granted: @grant', [
            '@realm' => $grant['realm'],
            '@grant' => 'APPLICATION_GRANT',
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
   * For the view operation, allow APPLICATION_GRANT permission to the
   * application realm named after the url of the consumer org
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

    $config = \Drupal::config('ibm_apim.devel_settings');
    $aclDebug = (boolean) $config->get('acl_debug');

    $grants = [];
    if ($op === 'view' && \Drupal::currentUser()->isAuthenticated()) {
      $userUtils = \Drupal::service('ibm_apim.user_utils');
      $org = $userUtils->getCurrentConsumerOrg();
      if (isset($org['url'])) {
        $url = str_replace('/', '_', $org['url']);
        $grants['application_' . $url] = [APPLICATION_GRANT];
        if ($aclDebug === TRUE) {
          foreach ($grants as $realm => $perms) {
            foreach ($perms as $grant) {
              \Drupal::logger('ACLDEBUG')->debug('Realm: @realm granted: @grant', [
                '@realm' => $realm,
                '@grant' => 'APPLICATION_GRANT',
              ]);
            }
          }
        }
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $grants);
    return $grants;
  }

  /**
   * Implements hook_node_access().
   * This is checking if the specified application is returned from apic, if not it blocks access.
   *
   * @param \Drupal\node\NodeInterface $node
   * @param $operation
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden|\Drupal\Core\Access\AccessResultNeutral
   */
  #[Hook('node_access')]
  public function nodeAccess(NodeInterface $node, $operation, AccountInterface $account) {
    if ($node->type === 'application' && $operation === 'view') {
      $userUtils = \Drupal::service('ibm_apim.user_utils');
      $org = $userUtils->getCurrentConsumerOrg();
      if ($node->application_consumer_org_url->value === $org['url']) {
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
        if (isset($form['type']['#default_value'], $form['actions']['delete']) && $form['type']['#default_value'] === 'application') {
          unset($form['actions']['delete']);
        }
        break;
      case 'node_application_edit_form':
        // disable fields to stop admin editing applications
        $currentUser = \Drupal::currentUser();
        if ((int) $currentUser->id() === 1) {
          $form['title']['#disabled'] = TRUE;
          $form['apic_summary']['#disabled'] = TRUE;
          $form['application_image']['#disabled'] = TRUE;
          $form['application_redirect_endpoints']['#disabled'] = TRUE;
        }
        // if anyone has made our internal fields visible, then lets make them readonly
        $internal_field_list = \Drupal::service('apic_app.application')->getIBMFields();
        foreach ($internal_field_list as $fieldName) {
          if ($fieldName !== 'application_image' && $fieldName !== 'apic_summary' && $fieldName !== 'application_redirect_endpoints' && $fieldName !== 'application_client_type' && isset($form[$fieldName])) {
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
    if (isset($links['entity.node_type.delete_form.application'])) {
      unset($links['entity.node_type.delete_form.application']);
    }
    // remove link to create content of our content type
    if (isset($links['node.add.application'])) {
      unset($links['node.add.application']);
    }
  }

  /**
   * Add twig template for New Application link
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
      'new_application' => [
        'variables' => [
          'access' => FALSE
        ],
      ],
      'application_subscriptions' => [
        'variables' => [
          'catalogId' => NULL,
          'catalogName' => NULL,
          'porgId' => NULL,
          'userHasAppManage' => FALSE,
          'userHasSubManage' => FALSE,
          'userHasSubView' => FALSE,
          'applifecycleEnabled' => FALSE,
          'appImageUploadEnabled' => FALSE,
          'notifications_access' => FALSE,
          'analytics_access' => FALSE,
          'node' => NULL,
          'subscriptions' => NULL,
          'credentials' => NULL,
        ],
      ],
      'application_analytics' => [
        'variables' => [
          'catalogId' => NULL,
          'catalogName' => NULL,
          'porgId' => NULL,
          'userHasAppManage' => FALSE,
          'userHasSubManage' => FALSE,
          'userHasSubView' => FALSE,
          'applifecycleEnabled' => FALSE,
          'notifications_access' => FALSE,
          'appImageUploadEnabled' => FALSE,
          'node' => NULL,
        ],
      ],
      'application_activity' => [
        'variables' => [
          'catalogId' => NULL,
          'catalogName' => NULL,
          'porgId' => NULL,
          'userHasAppManage' => FALSE,
          'userHasSubManage' => FALSE,
          'userHasSubView' => FALSE,
          'applifecycleEnabled' => FALSE,
          'appImageUploadEnabled' => FALSE,
          'analytics_access' => FALSE,
          'notifications_access' => FALSE,
          'node' => NULL,
          'events' => [],
        ],
      ],
      'app_credentials' => [
        'variables' => [
          'node' => NULL,
          'clipboard' => NULL,
          'userHasAppManage' => FALSE,
          'allowNewCredentials' => FALSE,
          'allowClientidReset' => FALSE,
          'allowClientsecretReset' => FALSE,
        ],
      ],
      'app_subscriptions' => [
        'variables' => [
          'node' => NULL,
          'userHasAppManage' => FALSE,
          'userHasSubView' => FALSE,
          'userHasSubManage' => FALSE,
          'showVersions' => FALSE,
          'billing_enabled' => FALSE,
        ],
      ]
    ];
  }

  /**
   * Implements hook_ENTITY_TYPE_presave().
   *
   * @param \Drupal\node\Entity\Node $node
   */
  #[Hook('node_presave')]
  public function nodePresave(Node $node) {
    if ($node->getType() === 'application') {
      // Allows for clearing of caches for a given app id
      $tags = ['application:' . $node->id()];
      Cache::invalidateTags($tags);
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_delete().
   *
   * @param \Drupal\node\Entity\Node $node
   */
  #[Hook('node_delete')]
  public function nodeDelete(Node $node) {
    if ($node->getType() === 'application') {
      // Allows for clearing of caches for a given app id
      $tags = ['application:' . $node->id()];
      Cache::invalidateTags($tags);
    }
  }

  /**
   * Ensure the app library is loaded on the apps view
   *
   * @param \Drupal\views\ViewExecutable $view
   */
  #[Hook('views_pre_render')]
  public function viewsPreRender(ViewExecutable $view) {
    if (isset($view) && ($view->storage->id() === 'applications')) {
      $view->element['#attached']['library'][] = 'apic_app/basic';
    }
  }

  /**
   * Implements hook_token_info().
   */
  #[Hook('token_info')]
  public function tokenInfo(): array {

    $type = [
      'name' => t('Application'),
      'description' => t('Tokens related to an individual application'),
      'needs-data' => 'application',
    ];

    $app['name'] = [
      'name' => t("Name"),
      'description' => t("The name of the application"),
    ];
    $app['title'] = [
      'name' => t("Title"),
      'description' => t("The title of the application"),
    ];
    $app['id'] = [
      'name' => t("ID"),
      'description' => t("The ID of the application"),
    ];
    $app['enabled'] = [
      'name' => t("Enabled"),
      'description' => t("The state the application is currently in"),
    ];
    $app['description'] = [
      'name' => t("Description"),
      'description' => t("The description given to the application"),
    ];
    $app['lifecycle_state'] = [
      'name' => t("Lifecycle State"),
      'description' => t("The current lifecycle state of the application"),
    ];
    $app['image_url'] = [
      'name' => t("Image URL"),
      'description' => t("The URL of the image for the application"),
    ];

    return [
      'types' => ['application' => $type],
      'tokens' => ['application' => $app],
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
    if ($type === 'application' && !empty($data['application'])) {
      $application = $data['application'];

      foreach ($tokens as $name => $original) {
        switch ($name) {
          case 'name':
            $replacements[$original] = $application->application_name->value;
            break;
          case 'title':
            $replacements[$original] = $application->getTitle();
            break;
          case 'id':
            $replacements[$original] = $application->application_id->value;
            break;
          case 'enabled':
            $replacements[$original] = $application->application_enabled->value;
            break;
          case 'lifecycle_state':
            $replacements[$original] = $application->application_lifecycle_state->value;
            break;
          case 'description':
            $replacements[$original] = $application->apic_summary->value;
            break;
          case 'image_url':
            $config = \Drupal::config('ibm_apim.settings');
            $ibmApimShowPlaceholderImages = (boolean) $config->get('show_placeholder_images');
            $customImage = $application->application_image;
            $customImageString = NULL;

            if ($customImage !== NULL && !empty($customImage)) {
              $entity = $application->application_image->entity;
              if ($entity !== NULL) {
                $customImageString = $entity->getFileUri();
              }
            }
            if ($customImageString !== NULL && !empty($customImageString)) {
              if (preg_match('/^http[s]?:\/\//', $customImageString) === 1) {
                $apiImageUrl = $customImageString;
              }
              else {
                $apiImageUrl = NULL;
              }
            }
            elseif ($ibmApimShowPlaceholderImages) {
              $apiImageUrl = \Drupal::service('apic_app.application')->getPlaceholderImage($application->getTitle());
            }
            else {
              $apiImageUrl = NULL;
            }
            $replacements[$original] = $apiImageUrl;
            break;
        }
      }
    }
    return $replacements;
  }
 }