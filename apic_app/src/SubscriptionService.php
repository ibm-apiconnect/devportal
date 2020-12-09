<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2020
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\apic_app;

use Drupal\apic_app\Entity\ApplicationSubscription;
use Drupal\apic_app\Event\SubscriptionCreateEvent;
use Drupal\apic_app\Event\SubscriptionDeleteEvent;
use Drupal\apic_app\Event\SubscriptionUpdateEvent;
use Drupal\Component\Utility\Html;
use Drupal\node\Entity\Node;

/**
 * Class to work with the Application content type, takes input from the JSON returned by
 * IBM API Connect and updates / creates subscriptions as needed
 */
class SubscriptionService {

  /**
   * Create a new Subscription
   *
   * @param string $appUrl
   * @param string $subId
   * @param string $product
   * @param string $plan
   * @param string $consumerOrgUrl
   * @param string $state
   * @param string|NULL $billingUrl
   *
   * @return bool
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function create($appUrl, $subId, $product, $plan, $consumerOrgUrl, $state = 'enabled', $billingUrl = NULL): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, [$appUrl, $subId]);
    $apimUtils = \Drupal::service('ibm_apim.apim_utils');
    $createdOrUpdated = TRUE;
    $appUrl = Html::escape($apimUtils->removeFullyQualifiedUrl($appUrl));
    $product = Html::escape($product);
    $billingUrl = Html::escape($apimUtils->removeFullyQualifiedUrl($billingUrl));
    $plan = Html::escape($plan);

    $consumerOrgUrl = Html::escape($apimUtils->removeFullyQualifiedUrl($consumerOrgUrl));

    // only allow state to be enabled, disabled or pending
    if (!in_array($state, ['enabled', 'disabled', 'pending'], true)) {
      $state = 'enabled';
    }

    $query = \Drupal::entityQuery('apic_app_application_subs');
    $query->condition('uuid', $subId);

    $entityIds = $query->execute();
    if (isset($entityIds) && !empty($entityIds)) {
      $entityId = array_shift($entityIds);
      $targetEntityId = $entityId;
      $subEntity = ApplicationSubscription::load($entityId);
      if ($subEntity !== NULL) {
        $subEntity->set('billing_url', $billingUrl);
        $subEntity->set('state', $state);
        $subEntity->set('plan', $plan);
        $subEntity->set('state', $state);
        $subEntity->set('app_url', $appUrl);
        $subEntity->set('product_url', $product);
        $subEntity->set('consumerorg_url', $consumerOrgUrl);
        $subEntity->save();
        $createdOrUpdated = FALSE;
      }
      // put it back on the array since we'll need it down below to add to the app
      $entityIds[] = $entityId;
    }
    if (!isset($entityIds)) {
      $entityIds = [];
    }
    if ($createdOrUpdated !== FALSE) {
      $newSub = ApplicationSubscription::create([
        'uuid' => $subId,
        'billing_url' => $billingUrl,
        'product_url' => $product,
        'plan' => $plan,
        'app_url' => $appUrl,
        'state' => $state,
        'consumerorg_url' => $consumerOrgUrl,
      ]);
      $newSub->enforceIsNew();
      $newSub->save();
      $query = \Drupal::entityQuery('apic_app_application_subs');
      $query->condition('uuid', $subId);
      $entityIds = $query->execute();
      if (isset($entityIds) && !empty($entityIds)) {
        $targetEntityId = array_shift($entityIds);
        $entityIds[] = $targetEntityId;
      }
    }

    // load the application
    $appTitle = 'undefined';
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'application');
    $query->condition('apic_url.value', $appUrl);

    $nids = $query->execute();
    if (isset($nids) && !empty($nids)) {
      $nid = array_shift($nids);
      $node = Node::load($nid);
      if (isset($node)) {
        $appTitle = $node->getTitle();
        $newArray = $node->application_subscription_refs->getValue();
        if (!in_array(['target_id' => $targetEntityId], array_values($newArray), false)) {
          $newArray[] = ['target_id' => $targetEntityId];
        }
        $node->set('application_subscription_refs', $newArray);
        $node->save();
      }
    }
    if ($createdOrUpdated === TRUE) {
      \Drupal::logger('apic_app')->notice('Subscription @subid for application @app created', [
        '@subid' => $subId,
        '@app' => $appTitle,
      ]);
    }
    else {
      \Drupal::logger('apic_app')->notice('Subscription @subid for application @app updated', [
        '@subid' => $subId,
        '@app' => $appTitle,
      ]);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $createdOrUpdated);
    return $createdOrUpdated;
  }

  /**
   * Create a new Subscription if one doesnt already exist
   * Update one if it does
   *
   * @param $subscription
   *
   * @return bool
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function createOrUpdate($subscription): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $returnValue = NULL;
    if (isset($subscription['id'], $subscription['app_url'], $subscription['product_url'])) {
      if (!isset($subscription['state'])) {
        $subscription['state'] = 'enabled';
      }
      $appId = \Drupal::service('ibm_apim.apim_utils')->removeFullyQualifiedUrl($subscription['app_url']);
      $subId = $subscription['id'];

      $product = \Drupal::service('ibm_apim.apim_utils')->removeFullyQualifiedUrl($subscription['product_url']);
      $plan = $subscription['plan'];
      $state = $subscription['state'];
      $consumer_org_url = $subscription['consumer_org_url'] ?? NULL;
      $billingUrl = $subscription['billing_url'] ?? NULL;
      $returnValue = self::create($appId, $subId, $product, $plan, $consumer_org_url, $state, $billingUrl);

    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $returnValue;
  }

  /**
   * Delete a subscription
   *
   * @param $appUrl
   * @param $subId
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function delete($appUrl, $subId): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, [$appUrl, $subId]);

    $query = \Drupal::entityQuery('apic_app_application_subs');
    $query->condition('uuid', $subId);

    $entityIds = $query->execute();
    if (isset($entityIds) && !empty($entityIds)) {
      $subEntities = ApplicationSubscription::loadMultiple($entityIds);
      foreach ($subEntities as $subEntity) {
        $subEntity->delete();
      }
    }

    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'application');
    $query->condition('apic_url.value', Html::escape($appUrl));
    $nids = $query->execute();

    $product = NULL;
    $planName = NULL;

    if (isset($nids) && !empty($nids)) {
      $nid = array_shift($nids);
      $node = Node::load($nid);
      if (isset($node)) {
        $currentSubs = $node->application_subscription_refs->getValue();
        foreach ($entityIds as $entityId) {
          $index = array_search(['target_id' => $entityId], $currentSubs, FALSE);
          if ($index !== FALSE) {
            unset($currentSubs[$index]);
            \Drupal::logger('apic_app')
              ->notice('Subscription @subid for application @app deleted', [
                '@subid' => $subId,
                '@app' => $node->getTitle(),
              ]);
          }
        }
        $node->set('application_subscription_refs', $currentSubs);
        $node->save();
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}

