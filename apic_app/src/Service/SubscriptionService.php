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

namespace Drupal\apic_app\Service;

use Drupal\apic_app\Entity\ApplicationSubscription;
use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Database;
use Drupal\Core\TempStore\TempStoreException;
use Drupal\ibm_apim\Service\ApimUtils;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\ibm_event_log\ApicType\ApicEvent;
use Drupal\user\Entity\User;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Throwable;

/**
 * Class to work with the Application content type, takes input from the JSON returned by
 * IBM API Connect and updates / creates subscriptions as needed
 */
class SubscriptionService {

  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
  protected UserUtils $userUtils;

  /**
   * @var \Drupal\ibm_apim\Service\ApimUtils
   */
  protected ApimUtils $apimUtils;

    /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * CredentialsService constructor.
   *
   * @param \Drupal\ibm_apim\Service\UserUtils $userUtils
   * @param \Drupal\ibm_apim\Service\ApimUtils $apimUtils
   */
  public function __construct(UserUtils $userUtils,
                              ApimUtils $apimUtils,
                              ModuleHandlerInterface $moduleHandler) {
    $this->userUtils = $userUtils;
    $this->apimUtils = $apimUtils;
    $this->moduleHandler = $moduleHandler;
  }

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
   * @param $subscription
   *
   * @return bool
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function create(string $appUrl, string $subId, string $product, string $plan, string $consumerOrgUrl, $state, $billingUrl, $subscription): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, [$appUrl, $subId]);
    $created = FALSE;
    $appUrl = Html::escape($this->apimUtils->removeFullyQualifiedUrl($appUrl));
    $product = Html::escape($product);
    $billingUrl = Html::escape($this->apimUtils->removeFullyQualifiedUrl($billingUrl));
    $plan = Html::escape($plan);
    $created_at = $subscription['created_at'] ?? NULL;
    $updated_at = $subscription['updated_at'] ?? NULL;
    $created_by = $subscription['created_by'] ?? NULL;
    $updated_by = $subscription['updated_by'] ?? NULL;
    $consumerOrgUrl = Html::escape($this->apimUtils->removeFullyQualifiedUrl($consumerOrgUrl));

    // Only allow state to be enabled, disabled or pending
    if (!in_array($state, ['enabled', 'disabled', 'pending'], TRUE)) {
      $state = 'enabled';
    }

    $result = $this->getSubscriptionRecord($subId);

    // Populate an array of field values.  We use this to see if we need to perform an update or to create a new record
    $fields = [
      'uuid' => $subId,
      'billing_url' => $billingUrl,
      'state' => $state,
      'plan' => $plan,
      'app_url' => $appUrl,
      'product_url' => $product,
      'consumerorg_url' => $consumerOrgUrl
    ];

    if (is_string($created_at)) {
      // store as epoch, incoming format will be like 2021-02-26T12:18:59.000Z
      $timestamp = strtotime($created_at);
      if ($timestamp < 2147483647 && $timestamp > 0) {
        $created_at = $timestamp;
      } else {
        $created_at = time();
      }
    } else {
      $created_at = time();
    }
    $fields['created_at'] = $created_at;

    if (is_string($updated_at)) {
      // store as epoch, incoming format will be like 2021-02-26T12:18:59.000Z
      $timestamp = strtotime($updated_at);
      if ($timestamp < 2147483647 && $timestamp > 0) {
        $updated_at = $timestamp;
      } else {
        $updated_at = time();
      }
    } else {
      $updated_at = time();
    }
    $fields['updated_at'] = $updated_at;

    // See if we need to update an existing record first
    [$subscriptionExists, $appEntityId, $updated] = $this->update($result, $fields);
    if ($subscriptionExists === FALSE) {
      // If we haven't updated then create a new record
      [$created, $appEntityId] = $this->doCreate($fields, $created_at, $updated_at);
    }

    if ($created === TRUE) {
      // Add the create event log entry
      $this->addEventLog('create', $created_at, $appEntityId, $fields, $created_by);
    } else if ($updated === TRUE) {
      // Add the update event log entry
      $this->addEventLog('update', $updated_at, $appEntityId, $fields, $updated_by);
    } else {
      \Drupal::logger('apic_app')->notice('Subscription @subid already existed and had not changed. No update was carried out', [
        '@subid' => $subId,
      ]);
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $created);
    return $created;
  }

  /**
   * Adds an event log for the application subscription interaction (create or update)
   * @param $event
   * @param $timestamp
   * @param $appEntityId
   * @param $fields
   * @param $byUserUrl
   */
  public function addEventLog($event, $timestamp, $appEntityId, $fields, $byUserUrl = NULL): void {
    $eventLogService = \Drupal::service('ibm_apim.event_log');
    if ($appEntityId === NULL) {
      $appEntityId = $this->getAppEntityIdByUrl($fields['app_url']);
    }

    $appTitle = $this->getApplicationTitle($appEntityId);

    \Drupal::logger('apic_app')->notice('Subscription @subid for application @app was @event', [
      '@subid' => $fields['uuid'],
      '@app' => $appTitle,
      '@event' => $event . 'd',
    ]);

    $eventEntity = new ApicEvent();
    $eventEntity->setArtifactType('subscription');
    $eventEntity->setEvent($event);

    if (\Drupal::currentUser()->isAuthenticated() && (int) \Drupal::currentUser()->id() !== 1) {
      $current_user = User::load(\Drupal::currentUser()->id());
      if ($current_user !== NULL) {
        // We only set the user if we're running as someone other than admin
        // if running as admin then we're likely doing things on behalf of the admin
        $eventEntity->setUserUrl($current_user->get('apic_url')->value);
      }
    }
    if (!isset($current_user)) {
      // Set the user url to the *_by value provided in the object payload
      $eventEntity->setUserUrl($byUserUrl);
    }

    // Get the product title - this is done now instead of when rendering the notifications since the product might get deleted before then
    $productTitle = $this->getProductTile($fields['product_url']);
    $eventEntity->setArtifactUrl($fields['app_url'] . '/subscriptions/' . $fields['uuid']);
    $eventEntity->setAppUrl($fields['app_url']);
    $eventEntity->setConsumerOrgUrl($fields['consumerorg_url']);
    $eventEntity->setData(['planName' => $fields['plan'], 'appName' => $appTitle, 'productUrl' => $productTitle]);

    if ($event === 'create' && isset($fields['updated_at']) && $fields['updated_at'] !== $timestamp) {
      $createEventEntity = clone $eventEntity;
      $createEventEntity->setTimestamp($fields['updated_at']);
      $createEventEntity->setEvent('update');
      $eventLogService->createIfNotExist($createEventEntity);
    }

    $eventLogService->createIfNotExist($eventEntity);
  }

  /**
   * Gets the title of the product from the given url
   * @param $productUrl
   * @return mixed
   */
  private function getProductTile($productUrl) {
    $productTitle = $productUrl;

    $query = Database::getConnection()->select('node__apic_url', 'u');
    $query->leftJoin('node_field_data', 'd', 'u.entity_id = d.nid');
    $result = $query
      ->fields('d', ['title'])
      ->condition('u.apic_url_value', $productUrl)
      ->condition('u.bundle', 'product')
      ->execute();

    if ($result && $record = $result->fetch()) {
      $productTitle = $record->title;
    }

    return $productTitle;
  }

  /**
   * Gets the subscription record for the given uuid (if one exists)
   * @param $uuid
   * @return \Drupal\Core\Database\StatementInterface|int|string|null
   */
  private function getSubscriptionRecord($uuid) {
    return Database::getConnection()
      ->query("SELECT * FROM {apic_app_application_subs} WHERE uuid = :uuid", [':uuid' => $uuid]);
  }

  /**
   * Gets the subscription entity id for the given uuid
   * @param $uuid
   * @return \Drupal\Core\Database\StatementInterface|int|string|null
   */
  private function getSubscriptionEntityId($uuid) {
    return Database::getConnection()
      ->query("SELECT id FROM {apic_app_application_subs} WHERE uuid = :uuid", [':uuid' => $uuid]);
  }

  /**
   * Ensures that a given subscription for a given application has a reference in both
   * the ref tables.  If a record is missing from either of the tables a new reference is added
   *
   * @param $appUrl
   * @param $subscriptionEntityId
   * @return mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function assertSubscriptionRef($appUrl, $subscriptionEntityId) {
    $tables = [];
    $appEntityId = NULL;
    $db = Database::getConnection();

    // Make sure we have a reference in both tables
    foreach (['node__application_subscription_refs', 'node_revision__application_subscription_refs'] as $table) {
      $result = $db->select($table, 'r')
        ->fields('r', ['entity_id'])
        ->condition('r.application_subscription_refs_target_id', $subscriptionEntityId)
        ->execute();
      if ($result && $record = $result->fetch()) {
        $appEntityId = $record->entity_id;
      } else {
        $tables[] = $table;
      }
    }

    if (empty($tables)) {
      $this->clearCaches($appEntityId, $subscriptionEntityId);
    } else {
      // Insert the reference in any table missing it (this also takes care of clearing caches)
      $appEntityId = $this->insertApplicationSubscriptionRef($appUrl, $subscriptionEntityId, $tables);
    }

    return $appEntityId;
  }

  /**
   * Inserts a new application subscription reference into the desired tables.
   * The tables array should contain one or both of the values node__application_subscription_refs and node_revision__application_subscription_refs.
   * For new subscription creations the array should contain both.  For subscription updates it is valid for the array to contain only one
   * value.
   *
   * @param $appUrl
   * @param $subscriptionEntityId
   * @param $tables
   * @return mixed
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function insertApplicationSubscriptionRef($appUrl, $subscriptionEntityId, $tables) {
    $appEntityId = null;

    // Get the data we need for the insert
    $db = Database::getConnection();
    $query = $db->select('node__apic_url', 'u');
    $query->leftJoin('node__application_subscription_refs', 'r', 'u.entity_id = r.entity_id');
    $result = $query
      ->fields('r', ['delta'])
      ->fields('u', ['entity_id', 'revision_id', 'langcode'])
      ->condition('u.apic_url_value', $appUrl)
      ->condition('u.bundle', 'application')
      ->orderBy('r.delta', 'DESC')
      ->range(0,1)
      ->execute();

    if (isset($result) && $record = $result->fetch()) {
      if (isset($record->delta)) {
        $delta = $record->delta +1;
      } else {
        $delta = 0;
      }

      $appEntityId = $record->entity_id;

      // Insert into the desired tables
      $transaction = $db->startTransaction();
      try {
        foreach ($tables as $table) {
          // This is a little hacky because drupal doesn't allow placeholders for table names so we have to concat the query. $table is only ever set internaly though so not subject to SQL injection
          $db->query("INSERT INTO {" . $table . "} VALUES ('application', 0, :eid, :rid, :lang, :delta, :subId)",
            [':eid' => $record->entity_id, ':rid' => $record->revision_id, ':lang' => $record->langcode, ':delta' => $delta, ':subId' => $subscriptionEntityId]);
        }
      } catch (Throwable $e) {
        $transaction->rollback();
        \Drupal::logger('apic_app')->error('Failed to insert the reference for subscription @s to application @a', ['@s' => $subscriptionEntityId, '@a' => $appUrl]);
      }
      unset($transaction);

      $this->clearCaches($appEntityId, $subscriptionEntityId);
    }

    return $appEntityId;
  }

  /**
   * Creates a new Subscription if one doesnt already exist.
   * Update if one does
   *
   * @param $subscription
   *
   * @return bool
   * @throws \Drupal\Core\Entity\EntityStorageException*@throws \Exception
   * @throws \Exception
   */
  public function createOrUpdate($subscription): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $returnValue = NULL;
    if (isset($subscription['id'], $subscription['app_url'], $subscription['product_url'])) {
      if (!isset($subscription['state'])) {
        $subscription['state'] = 'enabled';
      }
      $appId = $this->apimUtils->removeFullyQualifiedUrl($subscription['app_url']);
      $subId = $subscription['id'];

      $product = $this->apimUtils->removeFullyQualifiedUrl($subscription['product_url']);
      $plan = $subscription['plan'];
      $state = $subscription['state'];
      if (isset($subscription['consumer_org_url'])) {
        $consumer_org_url = $subscription['consumer_org_url'];
      } elseif (isset($subscription['org_url'])) {
        $consumer_org_url = $subscription['org_url'];
      } else {
        throw new \Exception('No consumer org url provided to SubscriptionService::createOrUpdate');
      }
      $billingUrl = $subscription['billing_url'] ?? NULL;
      $returnValue = $this->create($appId, $subId, $product, $plan, $consumer_org_url, $state, $billingUrl, $subscription);

    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $returnValue;
  }

  /**
   * Deletes a subscription record from all relevant tables based on the given subscription id.
   * This function makes use of the drupal database api to speed up interaction with the database.
   *
   * @param $subId
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function delete($subId): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, [$subId]);

    // Get the application node id for the given subscription id
    $query = Database::getConnection()->select('apic_app_application_subs', 's');
    $query->join('node__application_subscription_refs', 'n', 's.id = n.application_subscription_refs_target_id');
    $result = $query
      ->fields('n', ['entity_id'])
      ->fields('s', ['id'])
      ->condition('s.uuid', $subId)
      ->execute();
    if ($result !== NULL) {
      $record = $result->fetch();
      $this->moduleHandler->invokeAll('apic_app_subscription_pre_delete', ['subId' => $subId]);

      // Delete the subscription from all relevant tables
      Database::getConnection()
        ->query("DELETE s, n, r FROM {apic_app_application_subs} s INNER JOIN {node__application_subscription_refs} n ON n.application_subscription_refs_target_id = s.id INNER JOIN {node_revision__application_subscription_refs} r ON r.application_subscription_refs_target_id = s.id WHERE s.uuid = :sub_id", [':sub_id' => $subId]);

        $this->moduleHandler->invokeAll('apic_app_subscription_post_delete', ['subId' => $subId]);
      if ($record) {
        // Invalidate the tags and reset the cache of the application node
        $this->clearCaches($record->entity_id, $record->id);
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Deletes all subscriptions to the given product url.  This function makes use of a
   * database api query to remove all matching records from the subscription, node ref and revision
   * tables all at once.
   *
   * @param string $productUrl
   *
   */
  public function deleteAllSubsForProduct(string $productUrl): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, [$productUrl]);

    $db = Database::getConnection();
    $result = $db->query("SELECT id FROM {apic_app_application_subs} s WHERE s.product_url = :product_url", [':product_url' => $productUrl]);

    if ($result && $subIds = $result->fetchCol()) {
      foreach ($subIds as $subId) {
        $this->moduleHandler->invokeAll('apic_app_subscription_pre_delete', ['subId' => $subId]);
      }
      if (isset($productUrl)) {
        $db->query("DELETE s, n, r FROM {apic_app_application_subs} s INNER JOIN {node__application_subscription_refs} n ON n.application_subscription_refs_target_id = s.id INNER JOIN {node_revision__application_subscription_refs} r ON r.application_subscription_refs_target_id = s.id WHERE s.product_url = :product_url", [':product_url' => $productUrl]);
      }
      foreach ($subIds as $subId) {
        $this->moduleHandler->invokeAll('apic_app_subscription_post_delete', ['subId' => $subId]);
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Get the title of the application for a given app nid
   *
   * @param $appNid
   * @return string
   */
  private function getApplicationTitle($appNid): string {
    $title = '';
    $result = Database::getConnection()
      ->query("SELECT title FROM {node_field_data} WHERE nid = :appNid", [':appNid' => $appNid]);

    if ($result && $record = $result->fetch()) {
      $title = $record->title;
    }
    return $title;
  }

  /**
   * Gets an applications entity id from its url
   *
   * @param $appUrl
   * @return mixed
   */
  private function getAppEntityIdByUrl($appUrl) {
    $id = null;
    $result = Database::getConnection()
      ->query("SELECT entity_id FROM {node__apic_url} WHERE apic_url_value = :appUrl", [':appUrl' => $appUrl]);

    if ($result && $record = $result->fetch()) {
      $id = $record->entity_id;
    }

    return $id;
  }

  /**
   * Invalidates the tags and resets the necessary caches to ensure that subscription updates are
   * reflected in the ui
   *
   * @param $appEntityId
   * @param $subscriptionEntityId
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function clearCaches($appEntityId, $subscriptionEntityId): void {
    Cache::invalidateTags(['node:' . $appEntityId]);
    \Drupal::entityTypeManager()->getStorage('apic_app_application_subs')->resetCache([$subscriptionEntityId]);
    \Drupal::entityTypeManager()->getStorage('node')->resetCache([$appEntityId]);
  }

  /**
   * Performs an update on the given subscription record if one exists and the
   * values have changed.
   *
   * @param $result
   * @param $fields
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function update($result, $fields): array {
    $subscriptionExists = FALSE;
    $updateNeeded = FALSE;
    $appEntityId = null;

    if ($result && $record = $result->fetch()) {
      // Compare the field values and see if any are different
      foreach ($fields as $fieldName => $value) {
        if ($fieldName === "created_at" || $fieldName === "updated_at") {
          //$value = (string) $value;
          $record->$fieldName = (int) $record->$fieldName;
        }
        if ($record->$fieldName !== $value) {
          $updateNeeded = TRUE;
          break;
        }
      }

      // Only do the update if something has changed
      if ($updateNeeded) {
        $insertResult = Database::getConnection()
          ->update('apic_app_application_subs')
          ->fields($fields)
          ->condition('id', $record->id)
          ->execute();

        // Make sure the reference to the subscription exists
        $appEntityId = $this->assertSubscriptionRef($fields['app_url'], $record->id);
      }

      $subscriptionExists = TRUE;
    }

    return [$subscriptionExists, $appEntityId, $updateNeeded];
  }

  /**
   * Creates a new subscription record and references
   *
   * @param $fields
   * @param $createdAt
   * @param $updatedAt
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function doCreate($fields, $createdAt, $updatedAt): array {
    $appEntityId = null;
    $newSub = ApplicationSubscription::create($fields);
    if (isset($createdAt)) {
      $newSub->set('created_at', $createdAt);
    }
    if (isset($updatedAt)) {
      $newSub->set('updated_at', $updatedAt);
    }
    $newSub->enforceIsNew();
    $newSub->save();

    // Get the newly created subscription entity id
    $result = $this->getSubscriptionEntityId($fields['uuid']);

    if ($result && $record = $result->fetch()) {
      // Create the references
      $tables = ['node__application_subscription_refs', 'node_revision__application_subscription_refs'];
      $appEntityId = $this->insertApplicationSubscriptionRef($fields['app_url'], $record->id, $tables);

    } else {
      \Drupal::logger('apic_app')->warning('Did not find the entity id of newly created subscription record for uuid @uuid', ['@uuid' => $fields['uuid']]);
    }

    return [TRUE, $appEntityId];
  }

  /**
   * Get all the subscription entities for the current consumer org
   *
   * @return array
   */
  public function listSubscriptions(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $credEntityIds = [];
    try {
      $org = $this->userUtils->getCurrentConsumerOrg();
    } catch (TempStoreException | \JsonException $e) {
    }
    if (isset($org['url'])) {
      $query = \Drupal::entityQuery('apic_app_application_subs');
      $query->condition('consumerorg_url', $org['url']);
      $entityIds = $query->accessCheck()->execute();
      if (isset($entityIds) && !empty($entityIds)) {
        $credEntityIds = $entityIds;
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $credEntityIds;
  }

}
