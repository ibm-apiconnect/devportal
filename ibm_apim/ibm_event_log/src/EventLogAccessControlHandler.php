<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2022
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_event_log;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the ibm_event_log entity type.
 *
 * @see \Drupal\ibm_event_log\Entity\EventLog
 */
class EventLogAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation === 'view' || $operation === 'view label') {
      $allowed = FALSE;
      $org = \Drupal::service('ibm_apim.user_utils')->getCurrentConsumerorg();
      if ($account->isAuthenticated() && $entity->getConsumerorgUrl() === $org['url']) {
        $allowed = TRUE;
      }

      return AccessResult::allowedIf($allowed);
    }

    return parent::checkAccess($entity, $operation, $account);
  }

}