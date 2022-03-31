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

namespace Drupal\consumerorg\Service;

use Drupal\consumerorg\ApicType\PaymentMethodObj;
use Drupal\consumerorg\Entity\PaymentMethod;
use Drupal\Core\Url;
use Drupal\ibm_event_log\ApicType\ApicEvent;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;

/**
 * Class to work with the Consumerorg content type, takes input from the JSON returned by
 * IBM API Connect and updates / creates payment methods as needed
 */
class PaymentMethodService {

  /**
   * @param \Drupal\consumerorg\ApicType\PaymentMethodObj $paymentMethodObj
   * @param bool $isDefault
   *
   * @return bool
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function create(PaymentMethodObj $paymentMethodObj, bool $isDefault = TRUE): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $paymentMethodObj->getId());
    $createdOrUpdated = TRUE;
    $paymentMethodEntityId = NULL;

    // Add Activity Feed Event Log
    $eventEntity = new ApicEvent();
    $eventEntity->setArtifactType('payment_method');

    $configuration = $paymentMethodObj->getConfiguration();

    $query = \Drupal::entityQuery('consumerorg_payment_method');
    $query->condition('uuid', $paymentMethodObj->getId());
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('encrypt')) {
      $ibmApimConfig = \Drupal::config('ibm_apim.settings');
      $encryptionProfileName = $ibmApimConfig->get('payment_method_encryption_profile');
      if (isset($encryptionProfileName)) {
        $encryptionProfile = \Drupal\encrypt\Entity\EncryptionProfile::load($encryptionProfileName);
        if ($encryptionProfile !== NULL) {
          $encryptionService = \Drupal::service('encryption');
          $configuration = $encryptionService->encrypt(serialize($configuration), $encryptionProfile);
        }
      } else {
        \Drupal::logger('consumerorg')->warning('create: No payment method encryption profile set', []);
      }
    }

    $entityIds = $query->execute();
    if (isset($entityIds) && !empty($entityIds)) {
      $entityId = array_shift($entityIds);
      $paymentMethodEntity = PaymentMethod::load($entityId);
      if ($paymentMethodEntity !== NULL) {
        $paymentMethodEntity->set('billing_url', $paymentMethodObj->getBillingUrl());
        $paymentMethodEntity->set('title', $paymentMethodObj->getTitle());
        $paymentMethodEntity->set('payment_method_type_url', $paymentMethodObj->getPaymentMethodTypeUrl());
        $paymentMethodEntity->set('configuration', $configuration);
        $paymentMethodEntity->set('consumerorg_url', $paymentMethodObj->getOrgUrl());
        $paymentMethodEntity->set('created_at', $paymentMethodObj->getCreatedAt());
        $paymentMethodEntity->set('updated_at', $paymentMethodObj->getUpdatedAt());
        $paymentMethodEntity->save();
        $paymentMethodEntityId = $entityId;
        $createdOrUpdated = FALSE;
      }
    }
    if ($createdOrUpdated !== FALSE) {
      $newPayment = PaymentMethod::create([
        'uuid' => $paymentMethodObj->getId(),
        'title' => $paymentMethodObj->getTitle(),
        'billing_url' => $paymentMethodObj->getBillingUrl(),
        'payment_method_type_url' => $paymentMethodObj->getPaymentMethodTypeUrl(),
        'configuration' => $configuration,
        'consumerorg_url' => $paymentMethodObj->getOrgUrl(),
        'created_at' => $paymentMethodObj->getCreatedAt(),
        'updated_at' => $paymentMethodObj->getUpdatedAt(),
      ]);
      $newPayment->enforceIsNew();
      $newPayment->save();
      $query = \Drupal::entityQuery('consumerorg_payment_method');
      $query->condition('uuid', $paymentMethodObj->getId());
      $entityIds = $query->execute();
      if (isset($entityIds) && !empty($entityIds)) {
        $paymentMethodEntityId = array_shift($entityIds);
      }
    }
    $eventEntity->setEvent('create');
    $timestamp = $paymentMethodObj->getCreatedAt();

    // load the consumerorg
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'consumerorg');
    $query->condition('consumerorg_url.value', $paymentMethodObj->getOrgUrl());

    $nids = $query->execute();
    if (isset($nids) && !empty($nids)) {
      $nid = array_shift($nids);
      $node = Node::load($nid);
      if (isset($node)) {
        $newArray = $node->consumerorg_payment_method_refs->getValue();
        $found = FALSE;
        foreach ($newArray as $key => $value) {
          if ($value['target_id'] === $paymentMethodEntityId) {
            $found = TRUE;
          }
        }
        if ($found !== TRUE) {
          $newArray[] = ['target_id' => $paymentMethodEntityId];
        }
        $node->set('consumerorg_payment_method_refs', $newArray);
        if (count($newArray) === 1 || $isDefault) {
          $node->set('consumerorg_def_payment_ref', ['target_id' => $paymentMethodEntityId]);
        }
        $node->save();

        if (\Drupal::currentUser()->isAuthenticated() && (int) \Drupal::currentUser()->id() !== 1) {
          $current_user = User::load(\Drupal::currentUser()->id());
          if ($current_user !== NULL) {
            // we only set the user if we're running as someone other than admin
            // if running as admin then we're likely doing things on behalf of the admin
            // TODO we might want to check if there is a passed in user_url and use that too
            $eventEntity->setUserUrl($current_user->get('apic_url')->value);
          }
        }

        // if timestamp still not set default to current time
        if ($timestamp === NULL) {
          $timestamp = time();
        }
        // ensure there is a create event, and then see if we need to do an update one too
        $eventEntity->setTimestamp((int) $timestamp);
        $eventEntity->setArtifactUrl($paymentMethodObj->getOrgUrl() . '/payment-methods/' . $paymentMethodObj->getId());
        $eventEntity->setConsumerOrgUrl($paymentMethodObj->getOrgUrl());
        $eventEntity->setData(['method' => $paymentMethodObj->getTitle(), 'orgName' => $node->getTitle()]);
        $eventLogService = \Drupal::service('ibm_apim.event_log');
        $eventLogService->createIfNotExist($eventEntity);
        if ($paymentMethodObj->getCreatedAt() !== NULL && $paymentMethodObj->getUpdatedAt() !== NULL && $paymentMethodObj->getUpdatedAt() !== $paymentMethodObj->getCreatedAt()) {
          $updateEventEntity = clone $eventEntity;
          $updateEventEntity->setEvent('update');
          $updateEventEntity->setTimestamp((int) $paymentMethodObj->getUpdatedAt());
          $eventLogService->createIfNotExist($updateEventEntity);
        }
      }
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $createdOrUpdated;
  }

  /**
   * Create a new Payment Method if one doesnt already exist
   * Update one if it does
   *
   * @param $paymentMethod
   *
   * @return bool
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \JsonException
   */
  public static function createOrUpdate($paymentMethod): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $returnValue = NULL;
    if (isset($paymentMethod['id'], $paymentMethod['billing_url'], $paymentMethod['payment_method_type_url'], $paymentMethod['configuration'])) {
      $paymentMethodObject = new PaymentMethodObj();
      $paymentMethodObject->createFromArray($paymentMethod);

      $returnValue = self::create($paymentMethodObject);
      $consumerOrgUrl = $paymentMethod['org_url'];
      \Drupal::service('cache_tags.invalidator')->invalidateTags(['myorg:url:' . $consumerOrgUrl]);
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $returnValue;
  }

  /**
   * @param $node
   * @param $paymentMethods
   *
   * @return \Drupal\node\NodeInterface
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createOrUpdatePaymentMethodList($node, $paymentMethods): NodeInterface {
    if ($paymentMethods !== NULL && $node !== NULL) {
      $newPaymentMethods = [];

      foreach ($paymentMethods as $paymentMethod) {
        $paymentMethod['org_url'] = $node->consumerorg_url->value;
        $createdOrUpdated = self::createOrUpdate($paymentMethod);
        $query = \Drupal::entityQuery('consumerorg_payment_method');
        $query->condition('uuid', $paymentMethod['id']);
        $entityIds = $query->execute();
        if (isset($entityIds) && !empty($entityIds)) {
          $entityId = array_shift($entityIds);
          if ($createdOrUpdated !== NULL) {
            $newPaymentMethods[] = ['target_id' => $entityId];
          }
          else {
            \Drupal::logger('consumerorg')
              ->warning('createOrUpdatePaymentMethodList: Error updating a payment method @entityId', ['@entityId' => $paymentMethod['id']]);
          }
        }
        else {
          \Drupal::logger('consumerorg')
            ->warning('createOrUpdatePaymentMethodList: Couldn\'t find payment method @entityId', ['@entityId' => $paymentMethod['id']]);
        }

      }

      // update the consumerorg
      $node->set('consumerorg_payment_method_refs', $newPaymentMethods);
      $node->save();
    }
    return $node;
  }

  /**
   * @param $paymentMethodId
   * @param $consumerOrgUrl
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function delete($paymentMethodId, $consumerOrgUrl): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $paymentMethodId);

    $query = \Drupal::entityQuery('consumerorg_payment_method');
    $query->condition('uuid', $paymentMethodId);

    $entityIds = $query->execute();
    if (isset($entityIds) && !empty($entityIds)) {
      foreach (array_chunk($entityIds, 50) as $chunk) {
        $paymentEntities = PaymentMethod::loadMultiple($chunk);
        foreach ($paymentEntities as $paymentEntity) {
          $paymentEntity->delete();
        }
      }
    }

    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'consumerorg');
    $query->condition('consumerorg_url.value', $consumerOrgUrl);
    $nids = $query->execute();

    $product = NULL;
    $planName = NULL;

    if (isset($nids) && !empty($nids)) {
      $nid = array_shift($nids);
      $node = Node::load($nid);
      if (isset($node)) {
        $currentPaymentMethods = $node->consumerorg_payment_method_refs->getValue();
        foreach ($entityIds as $entityId) {
          $index = array_search(['target_id' => $entityId], $currentPaymentMethods, FALSE);
          if ($index !== FALSE) {
            unset($currentPaymentMethods[$index]);
            \Drupal::logger('consumerorg')
              ->notice('Payment method @entityid for consumer organization @org deleted', [
                '@entityid' => $paymentMethodId,
                '@org' => $node->getTitle(),
              ]);
          }
        }
        $node->set('consumerorg_payment_method_refs', $currentPaymentMethods);
        $node->save();
      }
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  /**
   * Decrypt the payment method configuration
   *
   * @param $configuration
   *
   * @return mixed
   */
  public static function decryptConfiguration($configuration) {
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('encrypt')) {
      $ibmApimConfig = \Drupal::config('ibm_apim.settings');
      $encryptionProfileName = $ibmApimConfig->get('payment_method_encryption_profile');
      if (isset($encryptionProfileName)) {
        $encryptionProfile = \Drupal\encrypt\Entity\EncryptionProfile::load($encryptionProfileName);
        $encryptionService = \Drupal::service('encryption');
        if ($encryptionProfile !== NULL) {
          $configuration = unserialize($encryptionService->decrypt($configuration, $encryptionProfile), ['allowed_classes' => FALSE]);
        }
      } else {
        \Drupal::logger('consumerorg')->warning('decryptConfiguration: No payment method encryption profile set', []);
      }
    }
    return $configuration;
  }

  /**
   * @param $name
   *
   * @return string - payment method icon for a given name
   *
   * @return string
   */
  public static function getRandomImageName($name): string {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $name);
    $asInt = 0;
    $strLength = mb_strlen($name);
    for ($i = 0; $i < $strLength; $i++) {
      $asInt += \ord($name[$i]);
    }
    $digit = $asInt % 19;
    if ($digit === 0) {
      $digit = 1;
    }
    $num = str_pad($digit, 2, 0, STR_PAD_LEFT);

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $num);
    return 'paymentmethod_' . $num . '.png';
  }

  /**
   * @param $name
   *
   * @return string - path to placeholder image for a given name
   *
   * @return string
   */
  public static function getPlaceholderImage($name): string {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $name);
    $returnValue = Url::fromUri('internal:/' . \Drupal::service('extension.list.module')->getPath('consumerorg') . '/images/' . self::getRandomImageName($name))
      ->toString();
    \Drupal::moduleHandler()->alter('consumerorg_get_paymentmethod_placeholderimage', $returnValue);
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    return $returnValue;
  }

  /**
   * @param $name
   *
   * @return string - path to placeholder image for a given name
   *
   * @return string
   */
  public static function getPlaceholderImageURL($name): string {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $name);
    $rawImage = self::getRandomImageName($name);
    $returnValue = base_path() . \Drupal::service('extension.list.module')->getPath('consumerorg') . '/images/' . $rawImage;
    \Drupal::moduleHandler()->alter('consumerorg_get_paymentmethod_placeholderimageurl', $returnValue);
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    return $returnValue;
  }

}