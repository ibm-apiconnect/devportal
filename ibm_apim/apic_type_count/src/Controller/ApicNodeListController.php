<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2020, 2024
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * @file
 * Contains \Drupal\apic_type_count\Controller\ApicNodeListController.
 */

namespace Drupal\apic_type_count\Controller;

use Drupal\apic_api\Api;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\product\Product;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;


class ApicNodeListController extends ControllerBase {


  /**
   * Get the entity from the provided entity name:version or id
   *
   * @param string $entityRef - the reference to the entity e.g. name:version or id
   * @param string $type - the type of entity ( product or api )
   *
   * @return \Drupal\Core\Entity\EntityInterface
   * @throws \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException
   */
  public static function getEntityofType(string $entityRef, string $type): \Drupal\Core\Entity\EntityInterface {
    $node = NULL;

    if ($type !== 'api' && $type !== 'product') {
      \Drupal::logger('entity')->error('getEntityofType: provided type argument must be either api or product', []);
        throw new AccessDeniedHttpException();
    }

    // Check the format of the entity provided in the request. We accept either
    // name and version number (e.g. mathserverservice:1.0.0), or the actual
    // id (e.g. 53c1e135-fe44-413d-aa10-1f92cdc8a21b)
    if (strpos($entityRef, ':') !== FALSE) {

      // We have a entity name and version number
      $entityData = explode(':', $entityRef);
      [$entityName, $entityVer] = $entityData;
      // Check the format of the name ([A-Za-z0-9]+) and version ([A-Za-z0-9\.\-\_]+)
      // Do this before trying to load the node, to prevent attacks directed at drupal
      if (!preg_match('/^([A-Za-z0-9\.\-\_]+)$/', $entityName)) {
        \Drupal::logger('apic_type_count')->error('getEntityofType: invalid NAME in request', []);
        throw new AccessDeniedHttpException();
      }
      if (!preg_match('/^([A-Za-z0-9\.\-\_]+)$/', $entityVer)) {
        \Drupal::logger('apic_type_count')->error('getEntityofType: invalid VERSION in request', []);
        throw new AccessDeniedHttpException();
      }

       // Load the Node as an ACL check that we have access to this Entity
       $query = \Drupal::entityQuery('node');
       $query->condition('type', $type);
       $query->condition('apic_ref.value', $entityName . ':' . $entityVer);
       $nids = $query->accessCheck()->execute();

       if ($nids !== NULL && !empty($nids)) {
         $nid = array_shift($nids);
         $node = Node::load($nid);
       }
       else {
        \Drupal::logger('apic_type_count')->error('getEntityofType: Cannot find ' . $type . ' named ' . $entityRef, []);
         throw new AccessDeniedHttpException();
       }
    } elseif (preg_match('/^([A-Za-z0-9\-])+$/', $entityRef)) {
      // We have an ID, use it raw
      // Validate the format of the ID ([A-Za-z0-9\-]+)

      // Load the Node as an ACL check that we have access to this node
      $query = \Drupal::entityQuery('node');
      $query->condition('type', $type);
      $query->condition($type . '_id.value', $entityRef);
      $nids = $query->accessCheck()->execute();

      if ($nids !== NULL && !empty($nids)) {
        $nid = array_shift($nids);
        $node = Node::load($nid);
      }
      else {
        \Drupal::logger('apic_type_count')->error('getEntityofType: Caller denied access to load ' . $type, []);
        throw new AccessDeniedHttpException();
      }
    }
    else {
      \Drupal::logger('apic_type_count')->error('getEntityofType: invalid ID in request', []);
      throw new AccessDeniedHttpException();
    }

    return $node;
  }



  /**
   * @param $nodeType
   *
   * @return array
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public static function getNodesForType($nodeType): array {
    $returnResults = [];
    $query = \Drupal::entityQuery('node');
    $query->condition('type', $nodeType);
    $query->condition('status', 1);

    $results = $query->accessCheck()->execute();
    if ($results !== NULL && !empty($results)) {
      foreach (array_chunk($results, 50) as $chunk) {
        $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple($chunk);
        foreach ($nodes as $node) {
          $newResult = [];
          switch ($nodeType) {
            case 'product':
              $newResult['ref'] = $node->apic_ref->value;
              $newResult['id'] = $node->product_id->value;
              $newResult['name'] = $node->product_name->value;
              $newResult['version'] = $node->apic_version->value;
              $newResult['title'] = $node->getTitle();
              $newResult['url'] = $node->apic_url->value;
              $newResult['nid'] = $node->id();
              break;
            case 'api':
              $newResult['ref'] = $node->apic_ref->value;
              $newResult['id'] = $node->api_id->value;
              $newResult['name'] = $node->api_xibmname->value;
              $newResult['version'] = $node->apic_version->value;
              $newResult['title'] = $node->getTitle();
              $newResult['url'] = $node->apic_url->value;
              $newResult['protocol'] = $node->api_oaiversion->value;
              $newResult['nid'] = $node->id();
              break;
            case 'application':
              $newResult['name'] = $node->application_name->value;
              $newResult['id'] = $node->application_id->value;
              $newResult['title'] = $node->getTitle();
              $newResult['url'] = $node->apic_url->value;
              $newResult['nid'] = $node->id();
              break;
            case 'consumerorg':
              $newResult['name'] = $node->consumerorg_name->value;
              $newResult['id'] = $node->consumerorg_id->value;
              $newResult['title'] = $node->getTitle();
              $newResult['url'] = $node->consumerorg_url->value;
              $newResult['nid'] = $node->id();
              break;
            default:
              $newResult['title'] = $node->getTitle();
              $newResult['nid'] = $node->id();
              break;
          }
          $returnResults[] = $newResult;
        }
      }
    }
    return $returnResults;
  }

  /**
   * @param $input
   * @param string|null $outputType
   *
   * @return array|string|null
   */
  public static function getAPI($input, string $outputType = NULL) {
    $node = self::getEntityofType($input, 'api');
    $url = NULL;
    $json = NULL;

    if ($node !== NULL) {
      $url = $node->apic_url->value;
    }

    if ($url !== NULL) {
      if ($outputType === 'document') {
        $json = Api::getApiDocumentForDrush($url);
      } else {
        $json = Api::getApiForDrush($url);
      }
    }
    return $json;
  }

  /**
   * @param $input
   * @param string|null $outputType
   *
   * @return array|null
   */
  public static function getProduct($input, string $outputType = NULL): ?array {
    $node = self::getEntityofType($input, 'product');
    $json = NULL;

    if ($node !== NULL) {
      $url = $node->apic_url->value;
    }

    if ($url !== NULL) {
      if ($outputType === 'document') {
        $json = Product::getProductDocumentForDrush($url);
      } else {
        $json = Product::getProductForDrush($url);
      }
    }
    return $json;
  }

  /**
   * @param $input
   *
   * @return array|null
   */
  public static function getApplication($input): ?array {
    $url = NULL;
    $json = NULL;

    // We have an ID, use it raw
    // Validate the format of the ID ([A-Za-z0-9\-]+)
    if (preg_match('/^([A-Za-z0-9\-])+$/', $input)) {
      // Load the Node as an ACL check that we have access to this node
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'application');
      $query->condition('application_id.value', $input);
      $nids = $query->accessCheck()->execute();

      if ($nids !== NULL && !empty($nids)) {
        $nid = array_shift($nids);
        $node = Node::load($nid);
        if ($node !== NULL) {
          $url = $node->apic_url->value;
        }
      }
      else {
        \Drupal::logger('apic_type_count')->error('getApplication: Caller denied access to load application', []);
        throw new AccessDeniedHttpException();
      }
    }
    else {
      \Drupal::logger('apic_type_count')->error('getApplication: invalid ID in request', []);
      throw new AccessDeniedHttpException();
    }
    if ($url !== NULL) {
      $json = \Drupal::service('apic_app.application')->getApplicationForDrush($url);
    }
    return $json;
  }

  /**
   * @param $input
   *
   * @return array|null
   */
  public static function getConsumerorg($input): ?array {
    $url = NULL;
    $json = NULL;

    // We have an ID, use it raw
    // Validate the format of the ID ([A-Za-z0-9\-]+)
    if (preg_match('/^([A-Za-z0-9\-])+$/', $input)) {
      // Load the Node as an ACL check that we have access to this node
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'consumerorg');
      $query->condition('consumerorg_id.value', $input);
      $nids = $query->accessCheck()->execute();

      if ($nids !== NULL && !empty($nids)) {
        $nid = array_shift($nids);
        $node = Node::load($nid);
        if ($node !== NULL) {
          $url = $node->consumerorg_url->value;
        }
      }
      else {
        \Drupal::logger('apic_type_count')->error('getConsumerorg: Caller denied access to load consumer organization', []);
        throw new AccessDeniedHttpException();
      }
    }
    else {
      \Drupal::logger('apic_type_count')->error('getConsumerorg: invalid ID in request', []);
      throw new AccessDeniedHttpException();
    }
    if ($url !== NULL) {
      $json = \Drupal::service('ibm_apim.consumerorg')->getConsumerOrgForDrush($url);
    }
    return $json;
  }

}
