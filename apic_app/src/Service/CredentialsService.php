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

namespace Drupal\apic_app\Service;

use Drupal\apic_app\Entity\ApplicationCredentials;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Database\Database;
use Drupal\Core\TempStore\TempStoreException;
use Drupal\ibm_apim\Service\UserUtils;
use Drupal\node\NodeInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;

class CredentialsService {


  /**
   * @var \Drupal\ibm_apim\Service\UserUtils
   */
  protected UserUtils $userUtils;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * CredentialsService constructor.
   *
   * @param \Drupal\ibm_apim\Service\UserUtils $userUtils
   */
  public function __construct(UserUtils $userUtils,
                              ModuleHandlerInterface $moduleHandler) {
    $this->moduleHandler = $moduleHandler;
    $this->userUtils = $userUtils;
  }

  /**
   * @param NodeInterface $node
   * @param array $cred
   *
   * @return \Drupal\node\NodeInterface
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addCredentials(NodeInterface $node, array $cred): NodeInterface {
    return $this->createOrUpdateSingleCredential($node, $cred);
  }

  /**
   * @param NodeInterface $node
   * @param $cred
   *
   * @return \Drupal\node\NodeInterface
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateCredentials(NodeInterface $node, $cred): NodeInterface {
    return $this->createOrUpdateSingleCredential($node, $cred);
  }

  /**
   * @param $uuid
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function deleteCredentials($uuid): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    if (isset($uuid)) {

      // Get the application node id for the given subscription id
      $query = Database::getConnection()->select('apic_app_application_creds', 'c');
      $query->join('node__application_credentials_refs', 'n', 'c.id = n.application_credentials_refs_target_id');
      $result = $query
        ->fields('n', ['entity_id'])
        ->condition('c.uuid', $uuid)
        ->execute();
      if ($result !== NULL) {
        $record = $result->fetch();
        $this->moduleHandler->invokeAll('apic_app_credential_pre_delete', ['credId' => $uuid]);

        // Delete the subscription from all relevant tables
        Database::getConnection()
          ->query("DELETE c, n, r FROM {apic_app_application_creds} c INNER JOIN {node__application_credentials_refs} n ON n.application_credentials_refs_target_id = c.id INNER JOIN {node_revision__application_credentials_refs} r ON r.application_credentials_refs_target_id = c.id WHERE c.uuid = :cred_id", [':cred_id' => $uuid]);

          $this->moduleHandler->invokeAll('apic_app_credential_post_delete', ['credId' => $uuid]);
        // Invalidate the tags and reset the cache of the application node
        Cache::invalidateTags(['application:' . $record->entity_id]);
        \Drupal::entityTypeManager()->getStorage('node')->resetCache([$record->entity_id]);
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * @param $cred
   *
   * @return mixed
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function createOrUpdateACredential($cred) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $newEntityId = NULL;
    if ($cred !== NULL) {
      if (isset($cred['consumer_org_url'])) {
        $cred['consumerorg_url'] = $cred['consumer_org_url'];
      }
      if (isset($cred['cred_url'])) {
        $cred['url'] = $cred['cred_url'];
      }
      if (isset($cred['uuid'])) {
        $cred['id'] = $cred['uuid'];
      }
      $query = \Drupal::entityQuery('apic_app_application_creds');
      $query->condition('uuid', $cred['id']);
      $entityIds = $query->accessCheck()->execute();
      if (isset($entityIds) && !empty($entityIds)) {
        $credEntities = ApplicationCredentials::loadMultiple($entityIds);
        $credEntity = array_shift($credEntities);
        $credEntity->set('name', $cred['name']);
        $credEntity->set('title', $cred['title']);
        $credEntity->set('consumerorg_url', $cred['consumerorg_url']);
        $credEntity->set('client_id', $cred['client_id']);
        $credEntity->set('app_url', $cred['app_url']);
        $credEntity->set('summary', $cred['summary']);
        $credEntity->set('cred_url', $cred['url']);
        if (array_key_exists('created_at', $cred) && is_string($cred['created_at'])) {
          // store as epoch, incoming format will be like 2021-02-26T12:18:59.000Z
          $timestamp = strtotime($cred['created_at']);
          if ($timestamp < 2147483647 && $timestamp > 0) {
            $credEntity->set('created_at', strval($timestamp));
          } else {
            $credEntity->set('created_at', strval(time()));
          }
        } else {
          $credEntity->set('created_at', strval(time()));
        }
        if (array_key_exists('updated_at', $cred) && is_string($cred['updated_at'])) {
          // store as epoch, incoming format will be like 2021-02-26T12:18:59.000Z
          $timestamp = strtotime($cred['updated_at']);
          if ($timestamp < 2147483647 && $timestamp > 0) {
            $credEntity->set('updated_at', strval($timestamp));
          } else {
            $credEntity->set('updated_at', strval(time()));
          }
        } else {
          $credEntity->set('updated_at', strval(time()));
        }
        $credEntity->save();
        $newEntityId = array_shift($entityIds);
        if (sizeof($credEntities) > 1) {
          // if there is more than one credential with this ID then something's gone wrong - delete any others
          foreach (array_slice($credEntities, 1) as $key => $credEntity) {
            $credEntity->delete();
          }
        }
      }
      else {
        $newCred = ApplicationCredentials::create([
          'uuid' => $cred['id'],
          'client_id' => $cred['client_id'],
          'name' => $cred['name'],
          'title' => $cred['title'],
          'app_url' => $cred['app_url'],
          'summary' => $cred['summary'],
          'consumerorg_url' => $cred['consumerorg_url'],
          'cred_url' => $cred['url'],
        ]);
        if (isset($cred['created_at'])) {
          $newCred->set('created_at', strtotime($cred['created_at']));
        }
        if (isset($cred['updated_at'])) {
          $newCred->set('updated_at', strtotime($cred['updated_at']));
        }
        $newCred->enforceIsNew();
        $newCred->save();
        $query = \Drupal::entityQuery('apic_app_application_creds');
        $query->condition('uuid', $cred['id']);
        $entityIds = $query->accessCheck()->execute();
        if (isset($entityIds) && !empty($entityIds)) {
          $newEntityId = array_shift($entityIds);
        }
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $newEntityId;
  }

  /**
   * @param NodeInterface $node
   * @param array $cred
   *
   * @return \Drupal\node\NodeInterface
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createOrUpdateSingleCredential(NodeInterface $node, array $cred): NodeInterface {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    if ($node !== NULL && $cred !== NULL) {

      $newId = $this->createOrUpdateACredential($cred);
      if ($newId !== NULL) {
        $newArray = $node->application_credentials_refs->getValue();
        if (!in_array(['target_id' => $newId], array_values($newArray), FALSE)) {
          $newArray[] = ['target_id' => $newId];
        }

        $node->set('application_credentials_refs', $newArray);
        $node->save();
      }
      else {
        \Drupal::logger('apic_app')
          ->warning('createOrUpdateSingleCredential: Error updating a credential @credId', ['@credId' => $cred['id']]);
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $node;
  }

  /**
   * @param NodeInterface $node
   * @param array $creds
   *    This should be an array of the credentials using ID as a key
   *
   * @return \Drupal\node\NodeInterface
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createOrUpdateCredentialsList(NodeInterface $node, array $creds, $save = TRUE): NodeInterface {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    if ($creds !== NULL && $node !== NULL) {
      $newCreds = [];
      foreach ($creds as $cred) {
        $newId = $this->createOrUpdateACredential($cred);
        if ($newId !== NULL) {
          $newCreds[] = ['target_id' => $newId];
        }
        else {
          \Drupal::logger('apic_app')
            ->warning('createOrUpdateCredentialsList: Error updating a credential @credId', ['@credId' => $cred['id']]);
        }
      }
      $oldCreds = $node->get('application_credentials_refs')->referencedEntities();
      foreach ($oldCreds as $cred) {
        if (!in_array(['target_id' => $cred->id()], $newCreds, FALSE)) {
          $credId = $cred->id();
          $this->moduleHandler->invokeAll('apic_app_credential_pre_delete', ['credId' => $credId]);
          \Drupal::entityTypeManager()->getStorage('apic_app_application_creds')->load($credId)->delete();
          $this->moduleHandler->invokeAll('apic_app_credential_post_delete', ['credId' => $credId]);
        }
      }
      // update the application
      $node->set('application_credentials_refs', $newCreds);

      if ($save) {
        $node->save();
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $node;
  }

  /**
   * @param string $credId
   * @param string $client_id
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateClientId(string $credId, string $client_id): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    if ($credId !== NULL && $client_id !== NULL) {
      $cred = ApplicationCredentials::load($credId);
      if ($cred !== NULL) {
        $cred->set('client_id', $client_id);
        $cred->save();
      }
      else {
        \Drupal::logger('apic_app')->warning('Credential @credId not found', ['@credId' => $credId]);
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Get all the credential entities for the current consumer org
   *
   * @return array
   */
  public function listCredentials(): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $credEntityIds = [];
    try {
      $org = $this->userUtils->getCurrentConsumerOrg();
    } catch (TempStoreException | \JsonException $e) {
    }
    if (isset($org['url'])) {
      $query = \Drupal::entityQuery('apic_app_application_creds');
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