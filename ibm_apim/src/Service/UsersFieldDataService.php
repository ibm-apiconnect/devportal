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

namespace Drupal\ibm_apim\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface;
use Drupal\ibm_apim\Service\Interfaces\UsersFieldDataServiceInterface;
use Psr\Log\LoggerInterface;
use \Drupal\user\Entity\User;

class UsersFieldDataService implements UsersFieldDataServiceInterface {

  private $users_field_data_table = 'users_field_data';

  private $registry_url_column = 'registry_url';

  private $user_name_index = 'user__name';

  private $user_name_registry_index = 'user__name__registry';

  private $user_name_registry_index_fields = ['name', 'registry_url', 'langcode'];

  /**
   * @var \Drupal\Core\Database\Schema
   */
  protected $schema;

  /**
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\ibm_apim\Service\Interfaces\UserRegistryServiceInterface
   */
  protected $userRegistryService;

  public function __construct(Connection $database,
                              LoggerInterface $logger,
                              EntityTypeManagerInterface $entity_type_manager,
                              UserRegistryServiceInterface $user_registry_service) {
    $this->schema = $database->schema();
    $this->logger = $logger;
    $this->entityTypeManager = $entity_type_manager;
    $this->userRegistryService = $user_registry_service;
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
        $this->logger->notice('Dropping %index from %table.', ['%index' => $this->user_name_index, '%table' => $this->users_field_data_table]);
        $this->schema->dropUniqueKey($this->users_field_data_table, $this->user_name_index);
      }

      if (!$this->schema->indexExists($this->users_field_data_table, $this->user_name_registry_index)) {
        $this->logger->notice('Creating %index in %table.', ['%index' => $this->user_name_registry_index, '%table' => $this->users_field_data_table]);
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
   */
  public function updateRegistryUrlFieldIfEmpty(): void {
    if (function_exists('ibm_apim_entry_trace')) {
      ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }

    $storage = $this->entityTypeManager->getStorage('user');
    $entity_ids = $storage->getQuery()
      ->condition('uid', [0,1], 'NOT IN')
      ->condition('registry_url', NULL, 'IS NULL')
      ->execute();

    foreach ($entity_ids as $user_id) {
      $user = User::load($user_id);

      if ($user->hasField('apic_user_registry_url') && $user->get('apic_user_registry_url')->value !== NULL) {

        $this->logger->notice('updating user %uid registry_url with %apic_user_registry_url', [
          '%uid' =>$user->id(),
          '%apic_user_registry_url' => $user->get('apic_user_registry_url')->value
        ]);
        $user->set('registry_url', $user->get('apic_user_registry_url')->value);
        $user->save();
      }
    }
    if (function_exists('ibm_apim_exit_trace')) {
      ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    }
  }


}
