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

namespace Drupal\ibm_apim\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Schema;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface;
use Drupal\ibm_apim\Service\Interfaces\UsersFieldDataServiceInterface;
use Psr\Log\LoggerInterface;
use \Drupal\user\Entity\User;
use Drupal\ibm_apim\Service\SiteConfig;
use Drupal\Component\Datetime\Time;


/**
 * Class UsersFieldDataService
 *
 * @package Drupal\ibm_apim\Service
 */
class UsersFieldDataService implements UsersFieldDataServiceInterface {

  /**
   * @var string
   */
  private string $users_field_data_table = 'users_field_data';

  /**
   * @var string
   */
  private string $registry_url_column = 'registry_url';

  /**
   * @var string
   */
  private string $user_name_index = 'user__name';

  /**
   * @var string
   */
  private string $user_name_registry_index = 'user__name__registry';

  /**
   * @var array|string[]
   */
  private array $user_name_registry_index_fields = ['name', 'registry_url', 'langcode'];

  /**
   * @var \Drupal\Core\Database\Schema|null
   */
  protected ?Schema $schema;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected LoggerInterface $logger;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface
   */
  protected UserRegistryServiceInterface $userRegistryService;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

    /**
   * @var Drupal\ibm_apim\Service\SiteConfig
   */
  protected SiteConfig $siteConfig;

      /**
   * @var Drupal\Component\Datetime\Time
   */
  protected Time $time;

  public function __construct(Connection $database,
                              LoggerInterface $logger,
                              EntityTypeManagerInterface $entity_type_manager,
                              UserRegistryServiceInterface $user_registry_service,
                              SiteConfig $site_config,
                              Time $time) {
    $this->schema = $database->schema();
    $this->database = $database;
    $this->logger = $logger;
    $this->entityTypeManager = $entity_type_manager;
    $this->userRegistryService = $user_registry_service;
    $this->siteConfig = $site_config;
    $this->time = $time;
  }

  /**
   * @inheritdoc
   */
  public function addNameAndRegistryUniqueKey(): bool {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    if ($this->schema->fieldExists($this->users_field_data_table, $this->registry_url_column)) {

      if ($this->schema->indexExists($this->users_field_data_table, $this->user_name_index)) {
        $this->logger->notice('Dropping %index from %table.', [
          '%index' => $this->user_name_index,
          '%table' => $this->users_field_data_table,
        ]);
        $this->schema->dropUniqueKey($this->users_field_data_table, $this->user_name_index);
      }

      if (!$this->schema->indexExists($this->users_field_data_table, $this->user_name_registry_index)) {
        $this->logger->notice('Creating %index in %table.', [
          '%index' => $this->user_name_registry_index,
          '%table' => $this->users_field_data_table,
        ]);
        $this->schema->addUniqueKey($this->users_field_data_table, $this->user_name_registry_index, $this->user_name_registry_index_fields);
      }

    }
    else {
      $this->logger->error('%field is not available in %table. Unable to create unique key.', [
        '%field' => $this->registry_url_column,
        '%table' => $this->users_field_data_table,
      ]);
    }

    $result = $this->schema->indexExists($this->users_field_data_table, $this->user_name_registry_index);
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $result);
    }
    return $result;
  }

  /**
   * @inheritDoc
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function setAdminRegistryUrl(): void {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $url = $this->userRegistryService->getAdminRegistryUrl();
    // set a registry_url for the admin user.
    $storage = $this->entityTypeManager->getStorage('user');
    $user = $storage->load(1);
    if ($user !== NULL) {
      $user->set('registry_url', $url);
      $user->save();
    }
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $url);
    }
  }

  /**
   * @inheritDoc
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateRegistryUrlFieldIfEmpty(): void {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $storage = $this->entityTypeManager->getStorage('user');
    $entity_ids = $storage->getQuery()
      ->condition('uid', [0, 1], 'NOT IN')
      ->condition('registry_url', NULL, 'IS NULL')
      ->execute();

    foreach ($entity_ids as $user_id) {
      $user = User::load($user_id);

      if ($user !== NULL && $user->hasField('apic_user_registry_url') && $user->get('apic_user_registry_url')->value !== NULL) {

        $this->logger->notice('updating user %uid registry_url with %apic_user_registry_url', [
          '%uid' => $user->id(),
          '%apic_user_registry_url' => $user->get('apic_user_registry_url')->value,
        ]);
        $user->set('registry_url', $user->get('apic_user_registry_url')->value);
        $user->save();
      }
    }
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
  }

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function deleteUsersWithDuplicateEmails(): void {
    $this->logger->notice('Deleting users with duplicated emails');
    $options = ['target' => 'default'];
    $query = $this->database->query("SELECT uid, mail FROM users_field_data WHERE uid <> 0 AND uid <> 1 AND
      mail IN (SELECT mail FROM users_field_data GROUP BY mail HAVING COUNT(mail) > 1)", [], $options);
    $uids = $query->fetchAll();
    foreach ($uids as $uid) {
      $user = $this->entityTypeManager->getStorage('user')->load($uid->uid);
      //Only delete users who've never logged in
      if ($user !== NULL && (int) $user->first_time_login->value === 1) {
        $this->entityTypeManager->getStorage('user')->delete([$user]);
      }
    }
  }

  /**
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function deleteExpiredPendingApprovalUsers(): void {
    $now = $this->time->getCurrentTime();
    $approvalInvtitationTTLs =  2*$this->siteConfig->getInvitationTTL();
    $query = \Drupal::entityQuery('user');
    $query->condition('apic_state', 'pending_approval');
    $query->condition('created', $now - $approvalInvtitationTTLs, "<=" );
    $uids = $query->execute();
    if (!empty($uids)) {
      foreach (array_chunk($uids, 50) as $chunk) {
        $users = User::loadMultiple($chunk);
        foreach ($users as $user) {
          $user->delete();
        }
      }
    }
  }
}
