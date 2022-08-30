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
 * Checks access to perform developer actions on an application.
 */
class DeveloperAccessCheck implements AccessInterface {

  /**
   * @param \Drupal\node\NodeInterface|null $application
   *
   * @return \Drupal\Core\Access\AccessResult|\Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultNeutral
   */
  public function access(NodeInterface $application = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $allowed = FALSE;
    $current_user = \Drupal::currentUser();
    // block anonymous and admin
    if (!$current_user->isAnonymous() && (int) $current_user->id() !== 1) {

      if (isset($application)) {
        $consumerorg_url = $application->application_consumer_org_url->value;
        $org = \Drupal::service("ibm_apim.consumerorg")->get($consumerorg_url);
      }
      else {
        $org = \Drupal::service('ibm_apim.user_utils')->getCurrentConsumerOrg();
        if (isset($org)) {
          $org = \Drupal::service("ibm_apim.consumerorg")->get($org['url']);
        }
      }

      $user = User::load($current_user->id());
      if (isset($org) && $user !== NULL) {
        $user_url = $user->get('apic_url')->value;
        if ($org->isOwner($user_url)) {
          $allowed = TRUE;
        }
        else if ($org->hasPermission($user_url, 'app:manage')) {
          $allowed = TRUE;
        }
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $allowed);
    return AccessResult::allowedIf($allowed)->addCacheableDependency($application);
  }

}
