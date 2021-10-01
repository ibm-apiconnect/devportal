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

namespace Drupal\ibm_apim\Service;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Database\Database;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\ibm_event_log\ApicType\ApicEvent;
use Drupal\ibm_event_log\Entity\EventLog;
use Drupal\node\Entity\Node;

/**
 * Class to work with the Event Log entity type
 */
class EventLogService {

  public const MIN_RETENTION = 1;

  public const MAX_RETENTION = 180;

  public const DEFAULT_RETENTION = 30;

  /**
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected DateFormatter $dateFormatter;

  /**
   * @var \Drupal\ibm_apim\Service\ApicUserStorage
   */
  protected ApicUserStorage $apicUserStorage;

  /**
   * EventLogService constructor.
   *
   * @param \Drupal\Core\Datetime\DateFormatter $dateFormatter
   * @param \Drupal\ibm_apim\Service\ApicUserStorage $apicUserStorage
   */
  public function __construct(DateFormatter $dateFormatter, ApicUserStorage $apicUserStorage) {
    $this->dateFormatter = $dateFormatter;
    $this->apicUserStorage = $apicUserStorage;
  }

  /**
   * Used by cron to delete older events from the DB based on the configured retention policy
   */
  public function prune(): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    $retentionDays = (int) \Drupal::config('ibm_event_log.settings')->get('retention_days');
    if ($retentionDays === NULL || $retentionDays <= 0) {
      $retentionDays = self::DEFAULT_RETENTION;
    }
    elseif ($retentionDays > self::MAX_RETENTION) {
      $retentionDays = self::MAX_RETENTION;
    }
    elseif ($retentionDays < self::MIN_RETENTION) {
      $retentionDays = self::MIN_RETENTION;
    }
    $adjusted_date = strtotime('-' . $retentionDays . ' days');
    // delete via DB API rather than loading entities as much faster and our entities are not
    // fieldable so only contained in a single table
    Database::getConnection()->delete('event_logs')
      ->condition('timestamp', $adjusted_date, '<=')
      ->execute();

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * @param \Drupal\ibm_event_log\ApicType\ApicEvent $apicEvent
   *
   * @return void
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createIfNotExist(ApicEvent $apicEvent): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    
    $queryString = "SELECT * FROM {event_logs} WHERE event = :event AND consumerorg_url = :corg AND artifact_url = :aurl AND artifact_type = :atype";
    $queryTerms = [
      ':event' => $apicEvent->getEvent(),
      ':corg' => $apicEvent->getConsumerOrgUrl(),
      ':aurl' => $apicEvent->getArtifactUrl(),
      ':atype' => $apicEvent->getArtifactType(),
    ];

    // If a timestamp has been set then query on that as well (we only provide the option not to
    // for cases such as deleting an object because we don't get a deleted_at timestamp to use)
    if ($apicEvent->getTimestamp() !== NULL && $apicEvent->getTimestamp() !== 0) {
      $queryString .= " AND timestamp= :timestamp";
      $queryTerms[':timestamp'] = $apicEvent->getTimestamp();
    }

    $result = Database::getConnection()->query($queryString, $queryTerms);

    if (!$result->fetch()) {
      // If a timestamp has not been provided (or is greater than max int) set it to the current timestamp
      if ($apicEvent->getTimestamp() === NULL || $apicEvent->getTimestamp() <= 0 || $apicEvent->getTimestamp() >= 2147483647) {
        $apicEvent->setTimestamp(time());
      }

      // No event found, need to create one
      $event = EventLog::create([
        'timestamp' => $apicEvent->getTimestamp(),
        'event' => $apicEvent->getEvent(),
        'consumerorg_url' => $apicEvent->getConsumerOrgUrl(),
        'artifact_url' => $apicEvent->getArtifactUrl(),
        'app_url' => $apicEvent->getAppUrl(),
        'artifact_type' => $apicEvent->getArtifactType(),
        'data' => serialize($apicEvent->getData()),
        'user_url' => $apicEvent->getUserUrl(),
      ]);
      $event->enforceIsNew();
      $event->save();
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * @param string $consumerorg_url
   * @param null|int $range The max number of results to get
   *
   * @return array
   * @throws \Exception
   */
  public function getFeedForConsumerOrg(string $consumerorg_url, $range = NULL): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $events = [];
    if (isset($consumerorg_url)) {
      $query = \Drupal::entityQuery('event_log');
      $query->condition('consumerorg_url', $consumerorg_url);
      $query->sort('timestamp', 'DESC');
      if ($range !== NULL && is_int($range)) {
        $query->range(0, $range);
      }
      $entityIds = $query->execute();
      if (isset($entityIds) && !empty($entityIds)) {
        foreach (array_chunk($entityIds, 50) as $chunk) {
          $eventEntities = EventLog::loadMultiple($chunk);
          if (isset($eventEntities) && !empty($eventEntities)) {
            foreach ($eventEntities as $eventEntity) {
              $events[] = $this->createOutputMessage($eventEntity);
            }
          }
        }
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $events;
  }

  /**
   * @param string $app_url
   * @param null|int $range The max number of results to get
   *
   * @return array
   * @throws \Exception
   */
  public function getFeedForApplication(string $app_url, $range = NULL): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $events = [];
    if (isset($app_url)) {
      $query = \Drupal::entityQuery('event_log');
      $query->condition('app_url', $app_url);
      $query->sort('timestamp', 'DESC');
      if ($range !== NULL && is_int($range)) {
        $query->range(0, $range);
      }
      $entityIds = $query->execute();
      if (isset($entityIds) && !empty($entityIds)) {
        foreach (array_chunk($entityIds, 50) as $chunk) {
          $eventEntities = EventLog::loadMultiple($chunk);
          if (isset($eventEntities) && !empty($eventEntities)) {
            foreach ($eventEntities as $eventEntity) {
              $events[] = $this->createOutputMessage($eventEntity);
            }
          }
        }
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $events;
  }

  /**
   * This function converts the EventLog entity into the output translatable message
   *
   * @param \Drupal\ibm_event_log\Entity\EventLog $event
   *
   * @return array
   * @throws \Exception
   */
  public function createOutputMessage(EventLog $event): array {
    $output = [];
    $output['epoch'] = [
      'value' => $event->getTimestamp(),
      'formatted' => $event->getTimestamp(),
    ];
    $output['timestamp'] = [
      'value' => $event->getTimestamp(),
      'formatted' => $this->dateFormatter->format($event->getTimestamp(), 'medium'),
    ];
    $output['time'] = [
      'value' => $event->getTimestamp(),
      'formatted' => $this->dateFormatter->format($event->getTimestamp(), 'custom', 'g:i:s A'),
    ];
    // if the User URL isn't set then assume the action was done by the provider
    $userUrl = $event->getUserUrl();
    // TODO replace this with the placeholder provider URL
    $avatar = NULL;
    if ($userUrl !== NULL && !empty($userUrl)) {
      $username = 'unknown';
      $displayName = 'unknown';
      $firstName = NULL;
      $lastName = NULL;
      $userAccount = $this->apicUserStorage->loadUserByUrl($userUrl);
      if ($userAccount !== NULL) {
        $username = $userAccount->getAccountName();
        $displayName = $userAccount->getDisplayName();
        if (isset($userAccount->get('first_name')->getValue()[0]['value']) && $userAccount->get('first_name')
            ->getValue()[0]['value'] !== NULL) {
          $firstName = $userAccount->get('first_name')->getValue()[0]['value'];
        }
        if (isset($userAccount->get('last_name')->getValue()[0]['value']) && $userAccount->get('last_name')
            ->getValue()[0]['value'] !== NULL) {
          $lastName = $userAccount->get('last_name')->getValue()[0]['value'];
        }
        // if firstname and lastname set then use them as the display name
        if ($firstName !== NULL) {
          $displayName = $firstName;
          if ($lastName !== NULL) {
            $displayName .= ' ' . $lastName;
          }
        }
        if ($userAccount->user_picture->isEmpty() === FALSE) {
          $image = $userAccount->user_picture;
          $uri = $image->entity->getFileUri();
          $avatar = file_create_url($uri);
        }
      }
      $output['action_by'] = [
        'url' => $userUrl,
        'username' => $username,
        'displayName' => $displayName,
        'firstName' => $firstName,
        'lastName' => $lastName,
        'avatar' => $avatar,
      ];
    }
    else {
      $output['action_by'] = [
        'url' => 'provider',
        'username' => 'provider',
        'displayName' => t('API Provider'),
        'avatar' => $avatar,
      ];
    }
    $output['message'] = Xss::filter($this->getTranslatedMessageString($event)->render());

    return $output;
  }

  /**
   * @param \Drupal\ibm_event_log\Entity\EventLog $event
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   */
  protected function getTranslatedMessageString(EventLog $event): TranslatableMarkup {
    $message = t('An unknown event');
    switch ($event->getArtifactType()) {
      case 'application':
        $data = unserialize($event->getData(), ['allowed_classes' => FALSE]);
        if ($event->getEvent() === 'create') {
          $message = t('A new application named "@appName" was created.', ['@appName' => $data['name']]);
        }
        elseif ($event->getEvent() === 'update') {
          $message = t('The application named "@appName" was updated.', ['@appName' => $data['name']]);
        }
        elseif ($event->getEvent() === 'delete') {
          $message = t('The application named "@appName" was deleted.', ['@appName' => $data['name']]);
        }
        break;
      case 'credential':
        $data = unserialize($event->getData(), ['allowed_classes' => FALSE]);
        if ($event->getEvent() === 'create') {
          $message = t('A new credential "@credName" for application named "@appName" was created.', [
            '@appName' => $data['appName'],
            '@credName' => $data['name'],
          ]);
        }
        elseif ($event->getEvent() === 'update') {
          $message = t('The credential "@credName" for application named "@appName" was updated.', [
            '@appName' => $data['appName'],
            '@credName' => $data['name'],
          ]);
        }
        elseif ($event->getEvent() === 'delete') {
          $message = t('The credential "@credName" for application named "@appName" was deleted.', [
            '@appName' => $data['appName'],
            '@credName' => $data['name'],
          ]);
        }
        elseif ($event->getEvent() === 'reset') {
          $message = t('The credential "@credName" for application named "@appName" had its client ID and secret reset.', [
            '@appName' => $data['appName'],
            '@credName' => $data['name'],
          ]);
        }
        elseif ($event->getEvent() === 'resetSecret') {
          $message = t('The credential "@credName" for application named "@appName" had its client secret reset.', [
            '@appName' => $data['appName'],
            '@credName' => $data['name'],
          ]);
        }
        break;
      case 'subscription':
        $data = unserialize($event->getData(), ['allowed_classes' => FALSE]);
        // get the product title from the DB
        $productName = $data['productUrl'];
        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'product');
        $query->condition('apic_url.value', $data['productUrl']);
        $nids = $query->execute();
        if ($nids !== NULL && !empty($nids)) {
          $nid = array_shift($nids);
          $product = Node::load($nid);
          if ($product !== NULL) {
            $productName = $product->getTitle();
          }
        }
        if ($event->getEvent() === 'create') {
          $message = t('A new subscription for the "@planName" plan in "@productName" for application named "@appName" was created.', [
            '@appName' => $data['appName'],
            '@planName' => $data['planName'],
            '@productName' => $productName,
          ]);
        }
        elseif ($event->getEvent() === 'update') {
          $message = t('The subscription for the "@planName" plan in "@productName" for application named "@appName" was updated.', [
            '@appName' => $data['appName'],
            '@planName' => $data['planName'],
            '@productName' => $productName,
          ]);
        }
        elseif ($event->getEvent() === 'delete') {
          $message = t('The subscription for the "@planName" plan in "@productName" for application named "@appName" was deleted.', [
            '@appName' => $data['appName'],
            '@planName' => $data['planName'],
            '@productName' => $productName,
          ]);
        }
        break;
      case 'consumer_org':
        $data = unserialize($event->getData(), ['allowed_classes' => FALSE]);
        if ($event->getEvent() === 'create') {
          $message = t('A new consumer organization named "@orgName" was created.', ['@orgName' => $data['orgName']]);
        }
        elseif ($event->getEvent() === 'update') {
          $message = t('The consumer organization named "@orgName" was updated.', ['@orgName' => $data['orgName']]);
        }
        elseif ($event->getEvent() === 'delete') {
          $message = t('The consumer organization named "@orgName" was deleted.', ['@orgName' => $data['orgName']]);
        }
        elseif ($event->getEvent() === 'change_owner') {
          $message = t('The owner of consumer organization named "@orgName" was changed to @owner.', [
            '@orgName' => $data['orgName'],
            '@owner' => $data['owner'],
          ]);
        }
        break;
      case 'member':
        $data = unserialize($event->getData(), ['allowed_classes' => FALSE]);
        if ($event->getEvent() === 'create') {
          $message = t('"@member" was added to the consumer organization named "@orgName".', [
            '@member' => $data['member'],
            '@orgName' => $data['orgName'],
          ]);
        }
        elseif ($event->getEvent() === 'update') {
          $message = t('The role of "@member" in the consumer organization named "@orgName" was updated.', [
            '@member' => $data['member'],
            '@orgName' => $data['orgName'],
          ]);
        }
        elseif ($event->getEvent() === 'delete') {
          $message = t('"@member" was removed from the consumer organization named "@orgName".', [
            '@member' => $data['member'],
            '@orgName' => $data['orgName'],
          ]);
        }
        break;
      case 'invitation':
        $data = unserialize($event->getData(), ['allowed_classes' => FALSE]);
        if ($event->getEvent() === 'create') {
          $message = t('"@member" was invited to the consumer organization named "@orgName".', [
            '@member' => $data['member'],
            '@orgName' => $data['orgName'],
          ]);
        }
        elseif ($event->getEvent() === 'resend_invitation') {
          $message = t('The invitation for "@member" in the consumer organization named "@orgName" was resent.', [
            '@member' => $data['member'],
            '@orgName' => $data['orgName'],
          ]);
        }
        elseif ($event->getEvent() === 'delete') {
          $message = t('The invitation for "@member" for the consumer organization named "@orgName" was deleted.', [
            '@member' => $data['member'],
            '@orgName' => $data['orgName'],
          ]);
        }
        break;
      case 'payment_method':
        $data = unserialize($event->getData(), ['allowed_classes' => FALSE]);
        if ($event->getEvent() === 'create') {
          $message = t('A new payment method "@method" was added to the consumer organization named "@orgName".', [
            '@method' => $data['method'],
            '@orgName' => $data['orgName'],
          ]);
        }
        elseif ($event->getEvent() === 'resend_invitation') {
          $message = t('The payment method "@method" in the consumer organization named "@orgName" was updated.', [
            '@method' => $data['method'],
            '@orgName' => $data['orgName'],
          ]);
        }
        elseif ($event->getEvent() === 'delete') {
          $message = t('The payment method "@method" for the consumer organization named "@orgName" was deleted.', [
            '@method' => $data['method'],
            '@orgName' => $data['orgName'],
          ]);
        }
        break;
    }
    return $message;
  }

}
