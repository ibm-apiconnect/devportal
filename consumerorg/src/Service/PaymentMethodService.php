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

namespace Drupal\consumerorg\Service;

use Drupal\consumerorg\Entity\PaymentMethod;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

/**
 * Class to work with the Consumerorg content type, takes input from the JSON returned by
 * IBM API Connect and updates / creates payment methods as needed
 */
class PaymentMethodService {

  /**
   * @param $paymentMethodId
   * @param $title
   * @param $billingUrl
   * @param $paymentMethodTypeUrl
   * @param $consumerOrgUrl
   * @param $configuration
   *
   * @return bool
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function create($paymentMethodId, $title, $billingUrl, $paymentMethodTypeUrl, $consumerOrgUrl, $configuration, $isDefault = true): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $paymentMethodId);
    $createdOrUpdated = TRUE;
    $paymentMethodEntityId = NULL;

    $query = \Drupal::entityQuery('consumerorg_payment_method');
    $query->condition('uuid', $paymentMethodId);
    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('encrypt')) {
      $ibmApimConfig = \Drupal::config('ibm_apim.settings');
      $encryptionProfileName = $ibmApimConfig->get('payment_method_encryption_profile');
      $encryptionProfile = \Drupal\encrypt\Entity\EncryptionProfile::load($encryptionProfileName);
      $encryptionService = \Drupal::service('encryption');
      $configuration = $encryptionService->encrypt(serialize($configuration), $encryptionProfile);
    }

    $entityIds = $query->execute();
    if (isset($entityIds) && !empty($entityIds)) {
      $entityId = array_shift($entityIds);
      $paymentMethodEntity = PaymentMethod::load($entityId);
      if ($paymentMethodEntity !== NULL) {
        $paymentMethodEntity->set('billing_url', $billingUrl);
        $paymentMethodEntity->set('title', $title);
        $paymentMethodEntity->set('payment_method_type_url', $paymentMethodTypeUrl);
        $paymentMethodEntity->set('configuration', $configuration);
        $paymentMethodEntity->set('consumerorg_url', $consumerOrgUrl);
        $paymentMethodEntity->save();
        $paymentMethodEntityId = $entityId;
        $createdOrUpdated = FALSE;
      }
    }
    if ($createdOrUpdated !== FALSE) {
      $newPayment = PaymentMethod::create([
        'uuid' => $paymentMethodId,
        'title' => $title,
        'billing_url' => $billingUrl,
        'payment_method_type_url' => $paymentMethodTypeUrl,
        'configuration' => $configuration,
        'consumerorg_url' => $consumerOrgUrl,
      ]);
      $newPayment->enforceIsNew();
      $newPayment->save();
      $query = \Drupal::entityQuery('consumerorg_payment_method');
      $query->condition('uuid', $paymentMethodId);
      $entityIds = $query->execute();
      if (isset($entityIds) && !empty($entityIds)) {
        $paymentMethodEntityId = array_shift($entityIds);
      }
    }

    // load the consumerorg
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'consumerorg');
    $query->condition('consumerorg_url.value', $consumerOrgUrl);

    $nids = $query->execute();
    if (isset($nids) && !empty($nids)) {
      $nid = array_shift($nids);
      $node = Node::load($nid);
      if (isset($node)) {
        $newArray = $node->consumerorg_payment_method_refs->getValue();
        $found = false;
        foreach ($newArray as $key=>$value) {
          if ($value['target_id'] === $paymentMethodEntityId) {
            $found = true;
          }
        }
        if ($found !== TRUE) {
          $newArray[] = ['target_id' => $paymentMethodEntityId];
        }
        $node->set('consumerorg_payment_method_refs', $newArray);
        if (count($newArray) == 1 || $isDefault) {
          $node->set('consumerorg_def_payment_ref', ['target_id' => $paymentMethodEntityId]);
        }
        $node->save();
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
   * @throws \JsonException
   */
  public static function createOrUpdate($paymentMethod): bool {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $returnValue = NULL;
    if (isset($paymentMethod['id'], $paymentMethod['billing_url'], $paymentMethod['payment_method_type_url'], $paymentMethod['configuration'])) {

      $billingUrl = \Drupal::service('ibm_apim.apim_utils')->removeFullyQualifiedUrl($paymentMethod['billing_url']);
      $paymentMethodTypeUrl = \Drupal::service('ibm_apim.apim_utils')->removeFullyQualifiedUrl($paymentMethod['payment_method_type_url']);
      $paymentMethodId = $paymentMethod['id'];
      $configuration = $paymentMethod['configuration'];
      $consumerOrgUrl = $paymentMethod['consumer_org_url'] ?? NULL;
      $title = $paymentMethod['title'] ?? $paymentMethodId;

      $returnValue = self::create($paymentMethodId, $title, $billingUrl, $paymentMethodTypeUrl, $consumerOrgUrl, $configuration);
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
   * @throws \JsonException
   */
  public function createOrUpdatePaymentMethodList($node, $paymentMethods): NodeInterface {
    if ($paymentMethods !== NULL && $node !== NULL) {
      $newPaymentMethods = [];

      foreach ($paymentMethods as $paymentMethod) {
        $paymentMethod['consumer_org_url'] = $node->consumerorg_url->value;
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
        } else {
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
  public static function delete($paymentMethodId, $consumerOrgUrl) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, $paymentMethodId);

    $query = \Drupal::entityQuery('consumerorg_payment_method');
    $query->condition('uuid', $paymentMethodId);

    $entityIds = $query->execute();
    if (isset($entityIds) && !empty($entityIds)) {
      $paymentEntities = PaymentMethod::loadMultiple($entityIds);
      foreach ($paymentEntities as $paymentEntity) {
        $paymentEntity->delete();
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
      $encryptionProfile = \Drupal\encrypt\Entity\EncryptionProfile::load($encryptionProfileName);
      $encryptionService = \Drupal::service('encryption');
      $configuration = unserialize($encryptionService->decrypt($configuration, $encryptionProfile), ['allowed_classes' => FALSE]);
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
    $returnValue = Url::fromUri('internal:/' . drupal_get_path('module', 'consumerorg') . '/images/' . self::getRandomImageName($name))
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
    $returnValue = base_path() . drupal_get_path('module', 'consumerorg') . '/images/' . $rawImage;
    \Drupal::moduleHandler()->alter('consumerorg_get_paymentmethod_placeholderimageurl', $returnValue);
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $returnValue);
    return $returnValue;
  }

}