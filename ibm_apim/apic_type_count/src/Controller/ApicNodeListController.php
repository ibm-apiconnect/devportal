<?php

/**
 * @file
 * Contains \Drupal\apic_type_count\Controller\ApicNodeListController.
 */

namespace Drupal\apic_type_count\Controller;

use Drupal\Core\Controller\ControllerBase;


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
}
