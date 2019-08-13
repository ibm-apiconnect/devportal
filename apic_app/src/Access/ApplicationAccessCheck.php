<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2019
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
class ApplicationAccessCheck implements AccessInterface {

  public function access(NodeInterface $application = NULL) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $allowed = FALSE;
    $current_user = \Drupal::currentUser();
    // block anonymous and admin
    if (!$current_user->isAnonymous() && (int) $current_user->id() !== 1) {
      $corgService = \Drupal::service('ibm_apim.consumerorg');
      if (isset($application)) {
        $consumerorg_url = $application->application_consumer_org_url->value;
        $org = $corgService->get($consumerorg_url);
      }
      else {
        $consumerorg_url = \Drupal::service('ibm_apim.user_utils')->getCurrentConsumerOrg()['url'];
        $org = $corgService->get($consumerorg_url);
      }

      $user = User::load($current_user->id());

      if($org->isMember($user->get('apic_url')->value)){
        $allowed = TRUE;
      }

    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $allowed);
    return AccessResult::allowedIf($allowed)->addCacheableDependency($application);
  }
}
