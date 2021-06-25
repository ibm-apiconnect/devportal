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

namespace Drupal\product\Service;

use Drupal\ibm_apim\Service\ApicTaxonomy;
use Drupal\node\NodeInterface;

class ProductTaxonomy {

  protected ApicTaxonomy $apicTaxonomy;

  public function __construct(ApicTaxonomy $apicTaxonomy) {
    $this->apicTaxonomy = $apicTaxonomy;
  }

  /**
   * Asserts the relevant taxonomy hierarchy is in
   * place from any provided product categories
   *
   * @param array $product
   * @param \Drupal\node\NodeInterface $node
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function process_categories(array $product, NodeInterface $node): void {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $categories = $product['catalog_product']['info']['categories'];
    $tids = $this->apicTaxonomy->get_taxonomies_from_categories($categories);

    if ($node !== NULL && $node->hasField('apic_tags')) {
      $currentTags = $node->apic_tags->getValue();
    }
    else {
      $currentTags = [];
    }

    if (is_array($tids) && !empty($tids)) {
      foreach ($tids as $tid) {
        if (isset($tid) && is_numeric($tid)) {
          $found = FALSE;
          foreach ($currentTags as $currentValue) {
            if (isset($currentValue['target_id']) && (string) $currentValue['target_id'] === (string) $tid) {
              $found = TRUE;
            }
          }
          if ($found === FALSE) {
            $currentTags[] = ['target_id' => $tid];
          }
        }
      }

      $node->set('apic_tags', $currentTags);
      $node->save();
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }

  //Filtering the manually added product tags from normal categories to stop tags duplication when a new version of a product is created.

  /**
   * @param array $oldTags all the old tags on the old node
   * @param array $existingCategories the categories on the old node
   *
   * @return array just the old taxonomy tags
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function separate_categories(array $oldTags, array $existingCategories): array {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);

    if (empty($existingCategories) || empty($oldTags)) {
      $result = $oldTags;
    }
    else {
      $categoriesTargetIds = $this->apicTaxonomy->get_taxonomies_from_categories($existingCategories);
      $tids = [];
      $filteredTags = [];
      foreach ($oldTags as $tid) {
        $tids[] = $tid['target_id'];
      }

      foreach (array_diff($tids, $categoriesTargetIds) as $tid) {
        $filteredTags[] = ['target_id' => $tid];
      }

      $result = $filteredTags;
    }

    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, $result);
    return $result;
  }

}
