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
 * Provides the product integration with APIC.
 */
namespace Drupal\product\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Access\AccessResultAllowed;
use Drupal\Core\Access\AccessResultForbidden;
use Drupal\Core\Access\AccessResultNeutral;
use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\product\Product;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

class ProductHooks {
  /**
   * Implements hook_node_access_records().
   *
   * For product nodes, create a list of grants for the node based on available
   * capabilities within the node configuration; where the capabilities are
   * specific to individual products, organisations or tags then use realm named
   * after product id, organisation or tag
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

    $config = \Drupal::config('ibm_apim.settings');
    $aclDebug = (bool) $config->get('acl_debug');

    $type = is_string($node) ? $node : $node->getType();
    $grants = [];

    // Only build permissions for product nodes
    if ($type === 'product') {
      // This is just too much debug; enable for special cases only
      //    if ($aclDebug === TRUE) {
      //      \Drupal::logger('ACLDEBUG')
      //        ->debug('Product Title: %title', ['%title' => $node->getTitle()]);
      //    }
      // Only issue grants if product_view is enabled
      if (isset($node->product_view_enabled->value) && (int) $node->product_view_enabled->value === 1) {
        // If the product is deprecated, only allow subscribers
        $state = $node->product_state->value;
        if ($state === NULL || empty($state) || mb_strtolower($state) === 'deprecated') {
          $pref = str_replace('/', '_', $node->apic_url->value);
          $grants[] = [
            'realm' => 'product_ref_' . $pref,
            'gid' => SUBSCRIBED_TO_PRODUCT_GRANT,
            'grant_view' => 1,
            'grant_update' => 0,
            'grant_delete' => 0,
            'priority' => 0,
          ];
        }
        else {
          // Create a grant for 'edit any product content'
          $grants[] = [
            'realm' => 'product',
            'gid' => EDIT_ANY_PRODUCT_CONTENT_GRANT,
            'grant_view' => 1,
            'grant_update' => 0,
            'grant_delete' => 0,
            'priority' => 0,
          ];
          // Create a grant for public access if public is set
          if ($node->product_visibility_public->value !== NULL && (int) $node->product_visibility_public->value === 1) {
            $grants[] = [
              'realm' => 'product',
              'gid' => PUBLIC_PRODUCT_GRANT,
              'grant_view' => 1,
              'grant_update' => 0,
              'grant_delete' => 0,
              'priority' => 0,
            ];
          }
          // Create a grant for authenticated access if authenticated is set
          if (isset($node->product_visibility_authenticated->value) && (int) $node->product_visibility_authenticated->value === 1) {
            $grants[] = [
              'realm' => 'product',
              'gid' => AUTHENTICATED_PRODUCT_GRANT,
              'grant_view' => 1,
              'grant_update' => 0,
              'grant_delete' => 0,
              'priority' => 0,
            ];
          }
          // Create a grant for subscription to this node based on product reference
          $pref = str_replace('/', '_', $node->apic_url->value);
          $grants[] = [
            'realm' => 'product_ref_' . $pref,
            'gid' => SUBSCRIBED_TO_PRODUCT_GRANT,
            'grant_view' => 1,
            'grant_update' => 0,
            'grant_delete' => 0,
            'priority' => 0,
          ];
          // Create a grant for all organisations (as separate realms) if org visibility is set
          if (isset($node->product_visibility_custom_orgs)) {
            foreach ($node->product_visibility_custom_orgs->getValue() as $customOrg) {
              if ($customOrg['value'] !== NULL) {
                $url = str_replace('/', '_', $customOrg['value']);
                $grants[] = [
                  'realm' => 'product_org_' . $url,
                  'gid' => ORG_PRODUCT_GRANT,
                  'grant_view' => 1,
                  'grant_update' => 0,
                  'grant_delete' => 0,
                  'priority' => 0,
                ];
              }
            }
          }
        }
        // Create a grant for all tags (as separate realms) if tag visibility is set
        if (isset($node->product_visibility_custom_tags)) {
          foreach ($node->product_visibility_custom_tags->getValue() as $customTag) {
            if ($customTag['value'] !== NULL) {
              $url = str_replace('/', '_', $customTag['value']);
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
      else {
        // Set the hidden product grant.  We need to do this because if we just return an empty grants array
        // ALL users will be able to view the product
        $grants[] = [
          'realm' => 'product',
          'gid' => HIDDEN_PRODUCT_GRANT,
          'grant_view' => 0,
          'grant_update' => 0,
          'grant_delete' => 0,
          'priority' => 0,
        ];
      }
      if ($aclDebug === TRUE) {
        foreach ($grants as $grant) {
          \Drupal::logger('ACLDEBUG')->debug('Realm: @realm granted: @grant', [
            '@realm' => $grant['realm'],
            '@grant' => product_permission_value($grant['gid']),
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
    $aclDebug = (bool) $config->get('acl_debug');

    $grants = [];
    $grants['product'] = [];

    // Grant everyone PUBLIC_PRODUCT_GRANT
    $grants['product'][] = PUBLIC_PRODUCT_GRANT;

    // If logged in, grant AUTHENTICATED_PRODUCT_GRANT
    if (\Drupal::currentUser()->isAuthenticated()) {
      $grants['product'][] = AUTHENTICATED_PRODUCT_GRANT;
    }

    $userUtils = \Drupal::service('ibm_apim.user_utils');
    // If 'edit any product content' is set, grant EDIT_ANY_PRODUCT_CONTENT_GRANT
    if ($userUtils->explicitUserAccess('edit any product content')) {
      $grants['product'][] = EDIT_ANY_PRODUCT_CONTENT_GRANT;
    }

    // Subscriptions and tags are only set for orgs, so only run that code
    // if the user is in a development organisation
    $myOrg = $userUtils->getCurrentConsumerOrg();
    if (isset($myOrg['url'])) {

      // Grant ORG_PRODUCT_GRANT for a realm representing the user's org
      $orgUrl = $myOrg['url'];
      $escapedOrgUrl = str_replace('/', '_', $orgUrl);
      $grants['product_org_' . $escapedOrgUrl] = [ORG_PRODUCT_GRANT];

      // Check for subscriptions, if they exist add a SUBSCRIBED_TO_PRODUCT_GRANT
      // for each subscription to a product-specific realm
      $options = ['target' => 'default'];
      $query = Database::getConnection($options['target'])
        ->query("SELECT * FROM apic_app_application_subs WHERE consumerorg_url = '" . $orgUrl . "'", [], $options);
      $subResults = $query->fetchAll();
      foreach ($subResults as $sub) {
        if ($sub !== NULL && $sub->product_url !== NULL) {
          $pref = str_replace('/', '_', $sub->product_url);
          $grants['product_ref_' . $pref] = [SUBSCRIBED_TO_PRODUCT_GRANT];
        }
      }

      // Check for custom tags, if they exist add a TAG_PRODUCT_GRANT for
      // each tag in a tag-specific realm
      $query = Database::getConnection($options['target'])
        ->query("SELECT tags.consumerorg_tags_value as consumerorg_tags_value
  FROM `node__consumerorg_url` id
  INNER JOIN `node__consumerorg_tags` tags ON id.entity_id = tags.entity_id
  WHERE (id.consumerorg_url_value = '" . $orgUrl . "')", [], $options);
      $doResults = $query->fetchAll();
      $tags = [];
      foreach ($doResults as $do) {
        $tags[] = $do->consumerorg_tags_value;
      }
      if ($tags !== NULL && is_array($tags) && count($tags) > 0) {
        foreach ($tags as $customTag) {
          if ($customTag !== NULL) {
            $url = str_replace('/', '_', $customTag);
            $grants['product_tag_' . $url] = [TAG_PRODUCT_GRANT];
          }
        }
      }
    }

    if ($aclDebug === TRUE) {
      foreach ($grants as $realm => $perms) {
        foreach ($perms as $grant) {
          \Drupal::logger('ACLDEBUG')->debug('Realm: @realm granted: @grant', [
            '@realm' => $realm,
            '@grant' => product_permission_value($grant),
          ]);
        }
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $grants);
    return $grants;
  }

  /**
   * Implements hook_node_access().
   * This is checking if the specified product is accessible to the current user, if not it blocks access.
   *
   * @param \Drupal\node\NodeInterface $node
   * @param $operation
   * @param \Drupal\Core\Session\AccountInterface $account
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden|\Drupal\Core\Access\AccessResultNeutral
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  #[Hook('node_access')]
  public function nodeAccess(NodeInterface $node, $operation, AccountInterface $account) {
    $type = $node->type;
    if ($type === 'product' && $operation === 'view') {
      if (Product::checkAccess($node)) {
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
   * Implements hook_views_data_alter().
   */
  #[Hook('views_data_alter')]
  public function viewsDataAlter(array &$data) {
    $data['node__apic_url']['subscription_status_filter'] = [
      'group' => 'APIC Filters',
      'title' => t('Subscription Status'),
      'filter' => [
        'title' => t('Subscription Status'),
        'field' => 'title',
        'id' => 'subscription_status_filter',
      ],
    ];
  }

  /**
   * Implements hook_views_query_alter().
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  #[Hook('views_query_alter')]
  public function viewsQueryAlter(ViewExecutable $view, QueryPluginBase $query) {

    if ($view->id() === 'product_content') {
      foreach ($query->where as &$condition_group) {
        foreach ($condition_group['conditions'] as &$condition) {
          if ($condition['field'] === 'node_field_data.nid = :node_field_data_nid') {
            $condition['field'] = 'node__product_api_nids.entity_id = :node_field_data_nid';
          }
        }
        unset($condition);
      }
      unset($condition_group);
      $configuration = [
        'type' => 'LEFT',
        'table' => 'node__product_api_nids',
        'field' => 'product_api_nids_value',
        'left_table' => 'node_field_data',
        'left_field' => 'nid',
        'operator' => '=',
      ];

      $join = Views::pluginManager('join')->createInstance('standard', $configuration);
      $rel = $query->addRelationship('node__product_api_nids', $join, 'node_field_data');
      $query->addTable('node__product_api_nids', $rel, $join, 'node__product_api_nids');
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
      case 'node_type_edit_form':
        if (isset($form['type']['#default_value'], $form['actions']['delete']) && $form['type']['#default_value'] === 'product') {
          unset($form['actions']['delete']);
        }
        break;
      case 'node_product_edit_form':
        // if anyone has made our internal fields visible, then lets make them readonly
        $internal_field_list = Product::getIBMFields();
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
    if (isset($links['entity.node_type.delete_form.product'])) {
      unset($links['entity.node_type.delete_form.product']);
    }
    // remove link to create content of our content type
    if (isset($links['node.add.product'])) {
      unset($links['node.add.product']);
    }
  }

  /**
   * Add twig templates
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
      'productrecommendations_block' => [
        'variables' => [
          'productRecommendations' => NULL,
        ],
      ],
      'product_select' => [
        'variables' => [
          'apiNid' => NULL,
          'products' => NULL,
        ],
      ],
      'product_wrapper' => [
        'variables' => [
          'api' => NULL,
          'product' => NULL,
          'showPlaceholders' => TRUE,
          'showVersions' => TRUE,
        ],
      ],
    ];
  }

  /**
   * Implements hook_token_info().
   */
  #[Hook('token_info')]
  public function tokenInfo(): array {

    $types['product'] = [
      'name' => t('Product'),
      'description' => t('Tokens related to an individual product'),
      'needs-data' => 'product',
    ];
    $types['product-plan'] = [
      'name' => t('Plan'),
      'description' => t('Tokens related to an individual plan'),
      'needs-data' => 'product-plan',
    ];

    $product['name'] = [
      'name' => t("Name"),
      'description' => t("The name of the product"),
    ];
    $product['title'] = [
      'name' => t("Title"),
      'description' => t("The title of the product"),
    ];
    $product['id'] = [
      'name' => t("ID"),
      'description' => t("The ID of the product"),
    ];
    $product['state'] = [
      'name' => t("State"),
      'description' => t("The state of the product"),
    ];
    $product['apis'] = [
      'name' => t("APIs"),
      'description' => t("A comma separated list of APIs (name:version) within the product."),
    ];
    $product['plans'] = [
      'name' => t("Plans"),
      'description' => t("A comma separated list of Plans names within the product."),
    ];
    $product['image_url'] = [
      'name' => t("Image URL"),
      'description' => t("The URL of the image for the product"),
    ];

    $plan['name'] = [
      'name' => t("Name"),
      'description' => t("The name of a given plan within the product"),
    ];
    $plan['title'] = [
      'name' => t("Title"),
      'description' => t("The title of the plan"),
    ];
    $plan['description'] = [
      'name' => t("Description"),
      'description' => t("The description of the plan"),
    ];
    $plan['approval'] = [
      'name' => t("Approval required"),
      'description' => t("Whether the plan requires approval or not"),
    ];
    $plan['rate-limits'] = [
      'name' => t("Rate limits"),
      'description' => t("A comma separated list of rate limit name value pairs the plan contains"),
    ];

    return [
      'types' => $types,
      'tokens' => ['product' => $product, 'product-plan' => $plan],
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
    if ($type === 'product' && !empty($data['product'])) {
      $product = $data['product'];

      foreach ($tokens as $name => $original) {
        switch ($name) {
          case 'name':
            $replacements[$original] = $product->product_name->value;
            break;
          case 'title':
            $replacements[$original] = $product->getTitle();
            break;
          case 'id':
            $replacements[$original] = $product->product_id->value;
            break;
          case 'state':
            $replacements[$original] = $product->product_state->value;
            break;
          case 'apis':
            $apisList = [];
            foreach ($product->product_apis->getValue() as $arrayValue) {
              $apis = unserialize($arrayValue['value'], ['allowed_classes' => FALSE]);
              foreach ($apis as $prodRef) {
                $apisList[] = $prodRef;
              }
            }
            $replacements[$original] = implode(", ", $apisList);
            break;
          case 'plans':
            $plansList = [];
            foreach ($product->product_plans->getValue() as $arrayValue) {
              $plan = unserialize($arrayValue['value'], ['allowed_classes' => FALSE]);

              $plansList[] = $plan['name'];
            }

            $replacements[$original] = implode(", ", $plansList);
            break;
          case 'image_url':
            $config = \Drupal::config('ibm_apim.settings');
            $ibmApimShowPlaceholderImages = (bool) $config->get('show_placeholder_images');
            $customImage = $product->apic_image;
            $customImageString = NULL;

            if ($customImage !== NULL && !empty($customImage)) {
              $entity = $product->apic_image->entity;
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
              $productImageUrl = Product::getPlaceholderImage($product->getTitle());
            }
            else {
              $productImageUrl = NULL;
            }
            $replacements[$original] = $productImageUrl;

            break;
        }
      }
    }
    if ($type === 'product-plan' && !empty($data['product-plan'])) {
      $plan = $data['product-plan'];

      foreach ($tokens as $name => $original) {
        switch ($name) {
          case 'name':
            $replacements[$original] = $plan['name'];
            break;
          case 'title':
            $replacements[$original] = $plan['title'];
            break;
          case 'description':
            $replacements[$original] = $plan['description'];
            break;
          case 'approval':
            $replacements[$original] = $plan['approval'] ? 'true' : 'false';
            break;
          case 'rate-limits':
            $rateLimits = [];
            foreach ($plan['rate-limits'] as $planName => $limit) {
              $rateLimits[] = $planName . ': ' . $limit['value'];
            }
            if (isset($rateLimits) && !empty($rateLimits)) {
              $replacements[$original] = implode(", ", $rateLimits);;
            }
            break;
        }
      }
    }

    return $replacements;
  }
 }