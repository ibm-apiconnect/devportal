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

namespace Drupal\apic_app\Service;

use Drupal\apic_app\Entity\ApplicationCredentials;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

class CredentialsService {


  public function __construct() {

  }

  /**
   * @param NodeInterface $node
   * @param array $cred
   *
   * @return \Drupal\node\NodeInterface
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function addCredentials($node, $cred): NodeInterface {
    $node = $this->createOrUpdateSingleCredential($node, $cred);
    return $node;
  }

  /**
   * @param NodeInterface $node
   * @param $cred
   *
   * @return \Drupal\node\NodeInterface
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateCredentials($node, $cred): NodeInterface {
    $node = $this->createOrUpdateSingleCredential($node, $cred);
    return $node;
  }

  /**
   * @param $node
   * @param $credId
   *
   * @return \Drupal\node\NodeInterface
   */
  public function deleteCredentials($node, $uuid): NodeInterface {
    if (isset($node, $uuid)) {
      // delete the credential entities
      $query = \Drupal::entityQuery('apic_app_application_creds');
      $query->condition('uuid', $uuid);
      $entityIds = $query->execute();
      if (isset($entityIds) && !empty($entityIds)) {
        $credEntities = ApplicationCredentials::loadMultiple($entityIds);
        foreach ($credEntities as $credEntity) {
          $credEntity->delete();
        }
      }

      $newCreds = [];

      // Now ensure the app doesnt reference the deleted credential
      $credentials = $node->application_credentials_refs->referencedEntities();
      foreach ($credentials as $credential) {
        if ($credential->uuid() !== $uuid) {
          $newCreds[] = ['target_id' => $credential->id()];
        }
      }
      $node->set('application_credentials_refs', $newCreds);
      $node->save();
    }
    return $node;
  }

  /**
   * @param $cred
   *
   * @return mixed
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function createOrUpdateACredential($cred) {
    $newEntityId = null;
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
      $entityIds = $query->execute();
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
        $newCred->enforceIsNew();
        $newCred->save();
        $query = \Drupal::entityQuery('apic_app_application_creds');
        $query->condition('uuid', $cred['id']);
        $entityIds = $query->execute();
        if (isset($entityIds) && !empty($entityIds)) {
          $newEntityId = array_shift($entityIds);
        }
      }
    }
    return $newEntityId;
  }

  /**
   * @param NodeInterface $node
   * @param array $cred
   *
   * @return \Drupal\node\NodeInterface
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createOrUpdateSingleCredential($node, $cred): NodeInterface {
    if ($node !== NULL && $cred !== NULL) {

      $newId = $this->createOrUpdateACredential($cred);
      if ($newId !== null) {
        $newArray = $node->application_credentials_refs->getValue();
        if (!in_array(['target_id' => $newId], array_values($newArray), false)) {
          $newArray[] = ['target_id' => $newId];
        }

        $node->set('application_credentials_refs', $newArray);
        $node->save();
      } else {
        \Drupal::logger('apic_app')->warning('createOrUpdateSingleCredential: Error updating a credential @credId', ['@credId' => $cred['id']]);
      }
    }
    return $node;
  }

  /**
   * @param NodeInterface $node
   * @param array $creds
   *    This should be an array of the credentials using ID as a key
   *
   * @return \Drupal\node\NodeInterface
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createOrUpdateCredentialsList($node, $creds): NodeInterface {
    if ($creds !== NULL && $node !== NULL) {
      $newCreds = [];
      foreach($creds as $cred) {
        $newId = $this->createOrUpdateACredential($cred);
        if ($newId !== null) {
          $newCreds[] = ['target_id' => $newId];
        } else {
          \Drupal::logger('apic_app')->warning('createOrUpdateCredentialsList: Error updating a credential @credId', ['@credId' => $cred['id']]);
        }
      }
      $oldCreds = $node->get('application_credentials_refs')->referencedEntities();
      foreach($oldCreds as $cred) {
        if (!in_array(['target_id' => $cred->id()], $newCreds)) {
          \Drupal::entityTypeManager()->getStorage('apic_app_application_creds')->load($cred->id())->delete();
        }
      }
      // update the application
      $node->set('application_credentials_refs', $newCreds);
      $node->save();
    }
    return $node;
  }

  /**
   * @param string $credId
   * @param string $client_id
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function updateClientId($credId, $client_id) : void {
    if ($credId !== NULL && $client_id !== NULL) {
      $cred = ApplicationCredentials::load($credId);
      if ($cred !== null) {
        $cred->set('client_id', $client_id);
        $cred->save();
      } else {
        \Drupal::logger('apic_app')->warning('Credential @credId not found', ['@credId' => $credId]);
      }
    }
  }

}