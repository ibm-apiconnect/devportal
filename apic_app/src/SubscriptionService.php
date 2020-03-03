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
    $createdOrUpdated = TRUE;
    $appUrl = Html::escape($appUrl);
    $product = Html::escape($product);
    $billingUrl = Html::escape($billingUrl);
    $plan = Html::escape($plan);
    $consumerOrgUrl = Html::escape($consumerOrgUrl);

    // only allow state to be enabled, disabled or pending
    if (!in_array($state, ['enabled', 'disabled', 'pending'], true)) {
      $state = 'enabled';
    }

    $query = \Drupal::entityQuery('apic_app_application_subs');
    $query->condition('id', $subId);

    $entityIds = $query->execute();
    if (isset($entityIds) && !empty($entityIds)) {
      $entityId = array_shift($entityIds);
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
      $entityIds[] = $subId;
    }
    if (!isset($entityIds)) {
      $entityIds = [];
    }
    if ($createdOrUpdated !== FALSE) {
      $newSub = ApplicationSubscription::create([
        'id' => $subId,
        'billing_url' => $billingUrl,
        'product_url' => $product,
        'plan' => $plan,
        'app_url' => $appUrl,
        'state' => $state,
        'consumerorg_url' => $consumerOrgUrl,
      ]);
      $newSub->enforceIsNew();
      $newSub->save();
      $entityIds[] = $newSub->get("id")->value;
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
        $entityId = array_shift($entityIds);
        $newArray = $node->application_subscription_refs->getValue();
        if (!in_array(['target_id' => $entityId], array_values($newArray), false)) {
          $newArray[] = ['target_id' => $entityId];
        }
        $node->set('application_subscription_refs', $newArray);
        $node->save();
      }
    }
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('rules')) {
      $productNode = NULL;
      if (isset($product)) {
        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'product');
        $query->condition('apic_url.value', $product);
        $results = $query->execute();
        if (isset($results) && !empty($results)) {
          $prodNid = array_shift($results);
          $productNode = Node::load($prodNid);
        }
      }
      if ($node !== NULL && $productNode !== NULL) {
        if ($createdOrUpdated === TRUE) {
          // Set the args twice on the event: as the main subject but also in the
          // list of arguments.
          $event = new SubscriptionCreateEvent($node, $productNode, $plan, $state, [
            'application' => $node,
            'product' => $productNode,
            'planName' => $plan,
            'state' => $state,
          ]);
          $eventDispatcher = \Drupal::service('event_dispatcher');
          $eventDispatcher->dispatch(SubscriptionCreateEvent::EVENT_NAME, $event);
        }
        else {
          // Set the args twice on the event: as the main subject but also in the
          // list of arguments.
          $event = new SubscriptionUpdateEvent($node, $productNode, $plan, $state, [
            'application' => $node,
            'product' => $productNode,
            'planName' => $plan,
            'state' => $state,
          ]);
          $eventDispatcher = \Drupal::service('event_dispatcher');
          $eventDispatcher->dispatch(SubscriptionUpdateEvent::EVENT_NAME, $event);
        }
      } else {
        \Drupal::logger('apic_app')->notice('Subscription @subid for application @app received but either the product or the application is missing.', [
          '@subid' => $subId,
          '@app' => $appTitle,
        ]);
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
    $query->condition('id', $subId);

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

        $moduleHandler = \Drupal::service('module_handler');
        if ($moduleHandler->moduleExists('rules')) {
          $productNode = NULL;
          if (isset($product)) {
            $query = \Drupal::entityQuery('node');
            $query->condition('type', 'product');
            $query->condition('apic_url.value', $product);
            $results = $query->execute();
            if (isset($results) && !empty($results)) {
              $prodNid = array_shift($results);
              $productNode = Node::load($prodNid);
            }
          }

          // we have to have a productNode to be able to generate an event
          // but if this sub delete is as a result of a product delete, we don't have a product any more!
          if (isset($productNode)) {
            // Set the args twice on the event: as the main subject but also in the
            // list of arguments.
            $event = new SubscriptionDeleteEvent($node, $productNode, $planName, [
              'application' => $node,
              'product' => $productNode,
              'planName' => $planName,
            ]);
            $eventDispatcher = \Drupal::service('event_dispatcher');
            $eventDispatcher->dispatch(SubscriptionDeleteEvent::EVENT_NAME, $event);
          }
        }
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}

