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
 *
 * IBM API Connect Integration
 *
 * Adds the API node content type to Drupal for representing APIs from IBM APIC
 */

namespace Drupal\apic_api\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\apic_api\Api;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

class ApicApiHooks {
  /**
   * Implements hook_node_access_records().
   *
   * For API nodes, create a list of grants for the node based on the
   * products to which the API belongs
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
    $uniqueGrants = [];

    // Only build permissions for API nodes
    if ($type === 'api') {

      // Create a grant for 'edit any api content'
      $grants[] = [
        'realm' => 'api',
        'gid' => EDIT_ANY_API_CONTENT_GRANT,
        'grant_view' => 1,
        'grant_update' => 0,
        'grant_delete' => 0,
        'priority' => 0,
      ];

      $apiRef = $node->apic_ref->value;
      if ($apiRef) {
        // find the list of products to which this api belongs
        // apiref will be a string like: 'maths:1.0.0'
        // product_apis_value will be the same string
        $options = ['target' => 'default'];
        $query = Database::getConnection($options['target'])
          ->query("SELECT node__apic_url.entity_id, node__apic_url.apic_url_value FROM node__product_apis INNER JOIN node__apic_url ON node__product_apis.entity_id = node__apic_url.entity_id WHERE product_apis_value LIKE  '%" . $apiRef . "%'", [], $options);
        $prods = $query->fetchAll();


        // Now go through and create grants based on each of the products, using the
        // same scheme as in product_node_access_records (the logic is that if a user
        // can access any of the products which contain this API, they can view the API)
        foreach ($prods as $prod) {
          // Pick up all of the data we need.  Left Joins are used for tables where
          // there may not be an entry (e.g. if tags or orgs are not defined)
          $query = Database::getConnection($options['target'])->query('SELECT pub.product_visibility_public_value AS product_visibility_public_value, auth.product_visibility_authenticated_value AS product_visibility_authenticated_value
  , org.product_visibility_custom_orgs_value AS product_visibility_custom_orgs_value, tags.product_visibility_custom_tags_value AS product_visibility_custom_tags_value, view.product_view_enabled_value AS product_view_enabled_value, prref.apic_ref_value AS apic_ref_value, state.product_state_value AS product_state_value, url.apic_url_value AS apic_url_value
  FROM
  node__product_visibility_public pub
  INNER JOIN node__product_visibility_authenticated auth ON pub.entity_id = auth.entity_id
  LEFT JOIN node__product_visibility_custom_orgs org ON pub.entity_id = org.entity_id
  LEFT JOIN node__product_visibility_custom_tags tags ON pub.entity_id = tags.entity_id
  LEFT JOIN node__apic_ref prref ON pub.entity_id = prref.entity_id
  INNER JOIN node__product_view_enabled view ON pub.entity_id = view.entity_id
  INNER JOIN node__product_state state ON prref.entity_id = state.entity_id
  INNER JOIN node__apic_url url ON prref.entity_id = state.entity_id
  WHERE  (pub.entity_id = ' . $prod->entity_id . ')', [], $options);

          $results = $query->fetchAll();

          // This is just too much debug; enable for special cases only
          //        if ($aclDebug === TRUE) {
          //          \Drupal::logger('apic_api')->debug('API %title searching for product %id: %results', [
          //            '%title' => var_export($node->getTitle(), TRUE),
          //            '%id' => $prod->entity_id,
          //            '%results' => var_export($results, TRUE),
          //          ]);
          //        }

          // The query will return multiple rows if there are multiple orgs or tags
          // or if there are multiple products with the same API
          // This causes duplication of the grants, which we will tidy up after
          foreach ($results as $row) {
            // Only create grants if the product is enabled
            if ((int) $row->product_view_enabled_value === 1) {
              // Create a grant for 'edit any product content'
              $grants[] = [
                'realm' => 'product',
                'gid' => EDIT_ANY_PRODUCT_CONTENT_GRANT,
                'grant_view' => 1,
                'grant_update' => 0,
                'grant_delete' => 0,
                'priority' => 0,
              ];

              if ((int) $row->product_visibility_public_value === 1) {

                // Create a grant for subscription base on the apic_url if the API's product is depcrecated
                if ($row->product_state_value === 'deprecated' && isset($row->apic_url_value)) {
                  $apic_url = str_replace('/', '_', $row->apic_url_value);
                  $grants[] = [
                    'realm' => 'product_ref_' . $apic_url,
                    'gid' => SUBSCRIBED_TO_PRODUCT_GRANT,
                    'grant_view' => 1,
                    'grant_update' => 0,
                    'grant_delete' => 0,
                    'priority' => 0,
                  ];
                }

                // Create a grant for public access if public is set and the API's product is not deprecated
                else {
                  $grants[] = [
                    'realm' => 'product',
                    'gid' => PUBLIC_PRODUCT_GRANT,
                    'grant_view' => 1,
                    'grant_update' => 0,
                    'grant_delete' => 0,
                    'priority' => 0,
                  ];
                }
              }
              // Create a grant for authenticated access if authenticated is set
              if ((int) $row->product_visibility_authenticated_value === 1) {
                $grants[] = [
                  'realm' => 'product',
                  'gid' => AUTHENTICATED_PRODUCT_GRANT,
                  'grant_view' => 1,
                  'grant_update' => 0,
                  'grant_delete' => 0,
                  'priority' => 0,
                ];
              }
              // Create a grant for subscription based on product reference
              if (isset($prod->apic_url_value)) {
                $url = str_replace('/', '_', $prod->apic_url_value);
                $grants[] = [
                  'realm' => 'product_ref_' . $url,
                  'gid' => SUBSCRIBED_TO_PRODUCT_GRANT,
                  'grant_view' => 1,
                  'grant_update' => 0,
                  'grant_delete' => 0,
                  'priority' => 0,
                ];
              }
              // Create a grant for organisations, as a separate realm based on the
              // org uuid.  If there are multiple orgs, then multiple grants each
              // with their own realm will be created
              if (isset($row->product_visibility_custom_orgs_value)) {
                $url = str_replace('/', '_', $row->product_visibility_custom_orgs_value);
                $grants[] = [
                  'realm' => 'product_org_' . $url,
                  'gid' => ORG_PRODUCT_GRANT,
                  'grant_view' => 1,
                  'grant_update' => 0,
                  'grant_delete' => 0,
                  'priority' => 0,
                ];
              }
              // Create a grant for tags, as a separate realm based on the
              // tag string.  If there are multiple tags, then multiple grants each
              // with their own realm will be created
              if (isset($row->product_visibility_custom_tags_value)) {
                $url = str_replace('/', '_', $row->product_visibility_custom_tags_value);
                $grants[] = [
                  'realm' => 'product_tag_' . $url,
                  'gid' => TAG_PRODUCT_GRANT,
                  'grant_view' => 1,
                  'grant_update' => 0,
                  'grant_delete' => 0,
                  'priority' => 0,
                ];
              }
            }
          }
        }
      }
      // Since multiple grants may have been created (due to the way the db_select
      // call returns multiple rows), we need to remove any that are duplicated
      // The code below does this by serialising each member of the grants array
      // and then using array_unique (which only works on strings) to ensure that
      // there are no duplicates
      $uniqueGrants = array_map('unserialize', array_unique(array_map('serialize', $grants)));
      if ($aclDebug === TRUE) {
        foreach ($uniqueGrants as $grant) {
          \Drupal::logger('ACLDEBUG')->debug('Realm: @realm granted: @grant', [
            '@realm' => $grant['realm'],
            '@grant' => api_permission_value($grant['gid']),
          ]);
        }
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $uniqueGrants);
    return $uniqueGrants;
  }

  /**
   * Implements hook_node_grants().
   *
   * Note that the vast majority of permissions for APIs are granted to the
   * user in the product.module, by design; access to APIs is based on access
   * to the products which use that API
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
    $userUtils = \Drupal::service('ibm_apim.user_utils');
    // If 'edit any api content' is set, grant EDIT_ANY_API_CONTENT_GRANT
    if ($userUtils->explicitUserAccess('edit any api content')) {
      $grants['api'] = [EDIT_ANY_API_CONTENT_GRANT];
      if ($aclDebug === TRUE) {
        foreach ($grants as $realm => $perms) {
          foreach ($perms as $grant) {
            \Drupal::logger('ACLDEBUG')->debug('Realm: @realm granted: @grant', [
              '@realm' => $realm,
              '@grant' => api_permission_value($grant),
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
   * This is checking if the specified api is returned from apim, if not it blocks access.
   *
   * @param \Drupal\node\NodeInterface $node
   * @param $operation
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden|\Drupal\Core\Access\AccessResultNeutral
   */
  #[Hook('node_access')]
  public function nodeAccess(NodeInterface $node, $operation, AccountInterface $account) {
    $type = $node->getType();
    if ($type === 'api' && $operation === 'view') {
      $found = Api::checkAccess($node);
      // found so we're allowed to access this API
      if ($found === TRUE) {
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
        if (isset($form['type']['#default_value'], $form['actions']['delete']) && $form['type']['#default_value'] === 'api') {
          unset($form['actions']['delete']);
        }
        break;
      case 'node_api_edit_form':
        // if anyone has made our internal fields visible, then lets make them readonly
        $internal_field_list = Api::getIBMFields();
        foreach ($internal_field_list as $fieldName) {
          if ($fieldName !== 'apic_pathalias' && $fieldName !== 'apic_tags' && $fieldName !== 'apic_rating' && $fieldName !== 'apic_image' && $fieldName !== 'apic_attachments' && isset($form[$fieldName])) {
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
    if (isset($links['entity.node_type.delete_form.api'])) {
      unset($links['entity.node_type.delete_form.api']);
    }
    // remove link to create content of our content type
    if (isset($links['node.add.api'])) {
      unset($links['node.add.api']);
    }
  }

  /**
   * Dynamically add to the api/explorer library since the name of the explorer main.js changes every build
   *
   * @param $libraries
   * @param $extension
   *
   * @throws \JsonException
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(&$libraries, $extension) {
    if (array_key_exists('explorer', $libraries) && file_exists(\Drupal::service('extension.list.module')
          ->getPath('apic_api') . '/explorer/app/asset-manifest.json')) {
      $string = file_get_contents(\Drupal::service('extension.list.module')->getPath('apic_api') . '/explorer/app/asset-manifest.json');
      $json = json_decode($string, TRUE, 512, JSON_THROW_ON_ERROR);
      if (isset($json['main.js']) && file_get_contents(\Drupal::service('extension.list.module')
            ->getPath('apic_api') . '/explorer/app' . $json['main.js'])) {
        $libraries['explorer']['js']['explorer/app' . $json['main.js']] = [
          'weight' => -1,
          'minified' => TRUE,
          'preprocess' => FALSE,
        ];
        foreach (array_keys($json) as $key => $value) {
          if (\Drupal::service('ibm_apim.utils')->endsWith($key, 'worker.js')) {
            $libraries['explorer']['js']['explorer/app' . $value] = [
              'weight' => -1,
              'minified' => TRUE,
              'preprocess' => FALSE,
            ];
          }
        }
      }
    }
    // modify the load order for the voting_widgets/fivestar library as it needs to be loaded before our explorer libraries
    if (array_key_exists('fivestar', $libraries)) {
      $libraries['fivestar']['js']['js/fivestars.js']['weight'] = -3;
    }
  }

  /**
   * Implements hook_token_info().
   */
  #[Hook('token_info')]
  public function tokenInfo(): array {

    $type = [
      'name' => t('API'),
      'description' => t('Tokens related to an individual API'),
      'needs-data' => 'api',
    ];
    $api['name'] = [
      'name' => t("Name"),
      'description' => t("The name of the API"),
    ];
    $api['version'] = [
      'name' => t("Version"),
      'description' => t("The version of the API"),
    ];
    $api['title'] = [
      'name' => t("Title"),
      'description' => t("The title of the API"),
    ];
    $api['id'] = [
      'name' => t("ID"),
      'description' => t("The ID of the API"),
    ];
    $api['state'] = [
      'name' => t("State"),
      'description' => t("The current state the API is in"),
    ];
    $api['protocol'] = [
      'name' => t("Protocol"),
      'description' => t("The protocol of the API"),
    ];
    $api['oai_version'] = [
      'name' => t("OAI Version"),
      'description' => t("The OAI version of the API"),
    ];
    $api['image_url'] = [
      'name' => t("Image URL"),
      'description' => t("The URL of the image for the api"),
    ];

    return [
      'types' => ['api' => $type],
      'tokens' => ['api' => $api],
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
    if ($type === 'api' && !empty($data['api'])) {
      $api = $data['api'];

      foreach ($tokens as $name => $original) {
        switch ($name) {
          case 'name':
            $replacements[$original] = $api->api_xibmname->value;
            break;
          case 'version':
            $replacements[$original] = $api->apic_version->value;
            break;
          case 'title':
            $replacements[$original] = $api->getTitle();
            break;
          case 'id':
            $replacements[$original] = $api->api_id->value;
            break;
          case 'state':
            $replacements[$original] = $api->api_state->value;
            break;
          case 'protocol':
            $replacements[$original] = $api->api_protocol->value;
            break;
          case 'oai_version':
            $replacements[$original] = $api->api_oaiversion->value;
            break;
          case 'image_url':
            $config = \Drupal::config('ibm_apim.settings');
            $ibmApimShowPlaceholderImages = (boolean) $config->get('show_placeholder_images');
            $customImage = $api->apic_image;
            $customImageString = NULL;

            if ($customImage !== NULL && !empty($customImage)) {
              $entity = $api->apic_image->entity;
              if ($entity !== NULL) {
                $customImageString = $entity->getFileUri();
              }
            }
            if ($customImageString !== NULL && !empty($customImageString)) {
              if (preg_match('/^http[s]?:\/\//', $customImageString) === 1) {
                $productImageUrl = $customImageString;
              }
              else {
                $productImageUrl = NULL;
              }
            }
            elseif ($ibmApimShowPlaceholderImages) {
              $productImageUrl = Api::getPlaceholderImage($api->getTitle());
            }
            else {
              $productImageUrl = NULL;
            }
            $replacements[$original] = $productImageUrl;

            break;
        }
      }
    }
    return $replacements;
  }
 }