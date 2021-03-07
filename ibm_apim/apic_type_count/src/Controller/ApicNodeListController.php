<?php

/**
 * @file
 * Contains \Drupal\apic_type_count\Controller\ApicNodeListController.
 */

namespace Drupal\apic_type_count\Controller;

use Drupal\apic_api\Api;
use Drupal\apic_app\Application;
use Drupal\Core\Controller\ControllerBase;
use Drupal\node\Entity\Node;
use Drupal\product\Product;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;


class ApicNodeListController extends ControllerBase {

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

    $results = $query->execute();
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
              break;
            case 'api':
              $newResult['ref'] = $node->apic_ref->value;
              $newResult['id'] = $node->api_id->value;
              $newResult['name'] = $node->api_xibmname->value;
              $newResult['version'] = $node->apic_version->value;
              $newResult['title'] = $node->getTitle();
              $newResult['url'] = $node->apic_url->value;
              break;
            case 'application':
              $newResult['name'] = $node->application_name->value;
              $newResult['id'] = $node->application_id->value;
              $newResult['title'] = $node->getTitle();
              $newResult['url'] = $node->apic_url->value;
              break;
            case 'consumerorg':
              $newResult['name'] = $node->consumerorg_name->value;
              $newResult['id'] = $node->consumerorg_id->value;
              $newResult['title'] = $node->getTitle();
              $newResult['url'] = $node->consumerorg_url->value;
              break;
            default:
              $newResult['title'] = $node->getTitle();
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
   *
   * @return array|null
   */
  public static function getAPI($input): ?array {
    $url = NULL;
    $json = NULL;

    // Check the format of the API provided in the request.  We accept either
    // name and version number (e.g. mathserverservice:1.0.0), or the actual
    // id (e.g. 53c1e135-fe44-413d-aa10-1f92cdc8a21b)
    if (strpos($input, ':') !== FALSE) {
      // We have an API name and version number
      $apidata = explode(':', $input);
      [$apiname, $apiver] = $apidata;
      // Check the format of the name ([A-Za-z0-9]+) and version ([A-Za-z0-9\.\-\_]+)
      // Do this before trying to load the node, to prevent attacks directed at drupal
      if (!preg_match('/^([A-Za-z0-9\.\-\_]+)$/', $apiname)) {
        \Drupal::logger('apic_type_count')->error('getAPI: invalid NAME in request', []);
        throw new AccessDeniedHttpException();
      }
      if (!preg_match('/^([A-Za-z0-9\.\-\_]+)$/', $apiver)) {
        \Drupal::logger('apic_type_count')->error('getAPI: invalid VERSION in request', []);
        throw new AccessDeniedHttpException();
      }

      // Load the Node as an ACL check that we have access to this API
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'api');
      $query->condition('apic_ref.value', $apiname . ':' . $apiver);
      $nids = $query->execute();

      if ($nids !== NULL && !empty($nids)) {
        $nid = array_shift($nids);
        $node = Node::load($nid);
        if ($node !== NULL) {
          $url = $node->apic_url->value;
        }
        else {
          \Drupal::logger('apic_type_count')->error('getAPI: Caller denied access to load API', []);
          throw new AccessDeniedHttpException();
        }
      }
      else {
        throw new AccessDeniedHttpException();
      }
    }
    else {
      // We have an API ID, use it raw
      // Validate the format of the ID ([A-Za-z0-9\-]+)
      if (preg_match('/^([A-Za-z0-9\-])+$/', $input)) {
        // Load the Node as an ACL check that we have access to this API
        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'api');
        $query->condition('api_id.value', $input);
        $nids = $query->execute();

        if ($nids !== NULL && !empty($nids)) {
          $nid = array_shift($nids);
          $node = Node::load($nid);
          if ($node !== NULL) {
            $url = $node->apic_url->value;
          }
        }
        else {
          \Drupal::logger('apic_type_count')->error('getAPI: Caller denied access to load API', []);
          throw new AccessDeniedHttpException();
        }
      }
      else {
        \Drupal::logger('apic_type_count')->error('getAPI: invalid ID in request', []);
        throw new AccessDeniedHttpException();
      }
    }
    if ($url !== NULL) {
      $json = Api::getApiForDrush($url);
    }
    return $json;
  }

  /**
   * @param $input
   *
   * @return array|null
   */
  public static function getProduct($input): ?array {
    $url = NULL;
    $json = NULL;

    // Check the format of the product provided in the request.  We accept either
    // name and version number (e.g. mathserverservice:1.0.0), or the actual
    // id (e.g. 53c1e135-fe44-413d-aa10-1f92cdc8a21b)
    if (strpos($input, ':') !== FALSE) {
      // We have a product name and version number
      $productdata = explode(':', $input);
      [$productname, $productver] = $productdata;
      // Check the format of the name ([A-Za-z0-9]+) and version ([A-Za-z0-9\.\-\_]+)
      // Do this before trying to load the node, to prevent attacks directed at drupal
      if (!preg_match('/^([A-Za-z0-9\.\-\_]+)$/', $productname)) {
        \Drupal::logger('apic_type_count')->error('getProduct: invalid NAME in request', []);
        throw new AccessDeniedHttpException();
      }
      if (!preg_match('/^([A-Za-z0-9\.\-\_]+)$/', $productver)) {
        \Drupal::logger('apic_type_count')->error('getProduct: invalid VERSION in request', []);
        throw new AccessDeniedHttpException();
      }

      // Load the Node as an ACL check that we have access to this Product
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'product');
      $query->condition('apic_ref.value', $productname . ':' . $productver);
      $nids = $query->execute();

      if ($nids !== NULL && !empty($nids)) {
        $nid = array_shift($nids);
        $node = Node::load($nid);
        if ($node !== NULL) {
          $url = $node->apic_url->value;
        }
        else {
          \Drupal::logger('apic_type_count')->error('getProduct: Caller denied access to load Product', []);
          throw new AccessDeniedHttpException();
        }
      }
      else {
        throw new AccessDeniedHttpException();
      }
    }
    else {
      // We have an ID, use it raw
      // Validate the format of the ID ([A-Za-z0-9\-]+)
      if (preg_match('/^([A-Za-z0-9\-])+$/', $input)) {
        // Load the Node as an ACL check that we have access to this node
        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'product');
        $query->condition('product_id.value', $input);
        $nids = $query->execute();

        if ($nids !== NULL && !empty($nids)) {
          $nid = array_shift($nids);
          $node = Node::load($nid);
          if ($node !== NULL) {
            $url = $node->apic_url->value;
          }
        }
        else {
          \Drupal::logger('apic_type_count')->error('getProduct: Caller denied access to load product', []);
          throw new AccessDeniedHttpException();
        }
      }
      else {
        \Drupal::logger('apic_type_count')->error('getProduct: invalid ID in request', []);
        throw new AccessDeniedHttpException();
      }
    }
    if ($url !== NULL) {
      $json = Product::getProductForDrush($url);
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
        $nids = $query->execute();

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
      $json = Application::getApplicationForDrush($url);
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
      $nids = $query->execute();

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
