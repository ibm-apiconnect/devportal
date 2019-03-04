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

namespace Drupal\apic_app;

use Drupal\apic_app\Event\SubscriptionCreateEvent;
use Drupal\apic_app\Event\SubscriptionDeleteEvent;
use Drupal\apic_app\Event\SubscriptionUpdateEvent;
use Drupal\Component\Utility\Html;
use Drupal\node\Entity\Node;

/**
 * Class to work with the Application content type, takes input from the JSON returned by
 * IBM API Connect and updates / creates subscriptions as needed
 */
class Subscription {

  /**
   * Create a new Subscription
   *
   * @param $appUrl
   * @param $subId
   * @param $product
   * @param $plan
   * @param string $state
   * @param $billingUrl
   *
   * @return bool
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function create($appUrl, $subId, $product, $plan, $state = 'enabled', $billingUrl): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, [$appUrl, $subId]);
    $createdOrUpdated = TRUE;
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'application');
    $query->condition('apic_url.value', Html::escape($appUrl));

    $nids = $query->execute();
    if (isset($nids) && !empty($nids)) {
      $nid = array_shift($nids);
      $node = Node::load($nid);
      if (isset($node)) {
        $appsub = [
          'id' => $subId,
          'product_url' => $product,
          'plan' => $plan,
          'state' => $state,
          'billing_url' => $billingUrl,
        ];
        $newSubs = [];
        foreach ($node->application_subscriptions->getValue() as $nextSub) {
          $unserialized = unserialize($nextSub['value'], ['allowed_classes' => FALSE]);
          if ((isset($unserialized['product_url']) && $unserialized['product_url'] === $product) || (isset($unserialized['id']) && (string) $unserialized['id'] === (string) $subId)) {
            // remove old subscriptions to this product or sub id
            $createdOrUpdated = FALSE;
          }
          else {
            $newSubs[] = serialize($unserialized);
          }
        }
        $newSubs[] = serialize($appsub);
        $node->set('application_subscriptions', $newSubs);
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
        }
        \Drupal::logger('apic_app')->notice('Subscription @subid for application @app created', [
          '@subid' => $subId,
          '@app' => $node->getTitle(),
        ]);
      }
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
      $billingUrl = $subscription['billing_url'] ?? NULL;

      $returnValue = self::create($appId, $subId, $product, $plan, $state, $billingUrl);

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
        $currentSubs = [];
        foreach ($node->application_subscriptions->getValue() as $nextSub) {
          $currentSubs[] = unserialize($nextSub['value'], ['allowed_classes' => FALSE]);
        }
        if (!empty($currentSubs)) {
          $newSubs = [];
          foreach ($currentSubs as $sub) {
            if (isset($sub['id']) && $sub['id'] === $subId) {
              // found the one we want to remove
              // found the one we want
              $product = $sub['product_url'];
              $planName = $sub['plan'];
              \Drupal::logger('apic_app')
                ->notice('Subscription @subid for application @app deleted', [
                  '@subid' => $subId,
                  '@app' => $node->getTitle(),
                ]);
            }
            else {
              $newSubs[] = serialize($sub);
            }
          }
          $node->set('application_subscriptions', $newSubs);
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
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}
