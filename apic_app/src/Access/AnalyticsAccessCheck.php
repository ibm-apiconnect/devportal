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

namespace Drupal\apic_app\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;

/**
 * Checks access to view analytics on an application.
 */
class AnalyticsAccessCheck implements AccessInterface {

  /**
   * @param \Drupal\node\NodeInterface|null $node
   *
   * @return \Drupal\Core\Access\AccessResult|\Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultNeutral
   */
  public function access(NodeInterface $node = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $allowed = FALSE;
    $current_user = \Drupal::currentUser();
    $config = \Drupal::config('ibm_apim.settings');

    // block anonymous and admin
    if (!$current_user->isAnonymous() && (int) $current_user->id() !== 1) {

      if (isset($node)) {
        $consumerorg_url = $node->application_consumer_org_url->value;
        $org = \Drupal::service("ibm_apim.consumerorg")->get($consumerorg_url);
      }

      $user = User::load($current_user->id());
      $show_analytics = (boolean) $config->get('show_analytics');
      if ($user !== NULL) {
        $user_url = $user->get('apic_url')->value;
        $portal_analytics_service = \Drupal::service('ibm_apim.analytics')->getDefaultService();
        if (isset($portal_analytics_service)) {
          $analyticsClientUrl = $portal_analytics_service->getClientEndpoint();
        }

        if (isset($node, $org, $analyticsClientUrl) && $node->application_consumer_org_url->value === $org->getUrl()) {
          $allowed = ($org->hasPermission($user_url, 'app-analytics:view') || $org->hasPermission($user_url, 'subscription:manage')) && $show_analytics;
        }
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $allowed);
    return AccessResult::allowedIf($allowed)->addCacheableDependency($node);
  }

}
