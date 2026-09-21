<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2026
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * @file
 * Hook implementations for ibm_apim module.
 */

namespace Drupal\ibm_apim\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for ibm_apim.
 */
class IbmApimHooks {

  /**
   * Implements hook_page_attachments().
   *
   * Attaches libraries and displays messages for specific routes.
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$attachments): void {
    // Attach core library to all pages
    $attachments['#attached']['library'][] = 'ibm_apim/core';
    
    // Attach contextual links fix library to all pages
    $attachments['#attached']['library'][] = 'ibm_apim/contextual_links_fix';
    
    // Attach admin toolbar tooltip library for users with toolbar access
    $user = \Drupal::currentUser();
    if ($user->hasPermission('access toolbar')) {
      $attachments['#attached']['library'][] = 'ibm_apim/admin_toolbar_tooltip';
    }
    
    // Display message on update routes
    $route_name = \Drupal::routeMatch()->getRouteName();
    $update_routes = [
      'update.report_update',
      'update.module_update',
      'update.theme_update',
    ];
    
    if (in_array($route_name, $update_routes)) {
      \Drupal::messenger()->addMessage(t('Only enabled modules and themes are listed for updates. To update a module or theme, please ensure it is enabled.'), 'status');
    }
  }

}
