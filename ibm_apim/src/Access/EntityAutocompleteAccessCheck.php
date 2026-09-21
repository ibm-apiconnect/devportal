<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2024
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;

/**
 * Access check for entity autocomplete to prevent username enumeration.
 *
 * This access checker implements a security fix for the entity autocomplete
 * vulnerability that allowed anonymous users to enumerate usernames.
 * 
 */
class EntityAutocompleteAccessCheck implements AccessInterface {
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account) {
    // Block all anonymous users
    if ($account->isAnonymous()) {
      return AccessResult::forbidden('Anonymous users cannot use entity autocomplete')
        ->addCacheContexts(['user.roles:anonymous']);
    }

    $target_type = $route_match->getParameter('target_type');
    
    if ($target_type === NULL) {
      $raw_params = $route_match->getRawParameters()->all();
      $target_type = $raw_params['target_type'] ?? NULL;
    }
    // User entity autocomplete requires admin permission
    if ($target_type === 'user') {
      $has_permission = $account->hasPermission('administer users');
      
      if ($has_permission) {
        return AccessResult::allowed()
          ->addCacheContexts(['user.permissions']);
      } else {
         return AccessResult::forbidden('message')
            ->addCacheContexts(['user.permissions']);
      }
    }

    // Allow authenticated users for other entity types
    return AccessResult::allowed()
      ->addCacheContexts(['user.roles:authenticated']);
  }
}
