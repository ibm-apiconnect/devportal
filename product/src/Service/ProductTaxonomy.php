<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\product\Service;

use Drupal\ibm_apim\Service\ApicTaxonomy;

class ProductTaxonomy {

  protected $apicTaxonomy;

  public function __construct(ApicTaxonomy $apicTaxonomy) {
    $this->apicTaxonomy = $apicTaxonomy;
  }

  /**
   * Asserts the relevant taxonomy hierarchy is in
   * place from any provided product categories
   *
   * @param $product
   * @param $node
   */
  public function process_categories($product, $node) {
    ibm_apim_entry_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
    $categories = $product['catalog_product']['info']['categories'];
    $tids = $this->apicTaxonomy->get_taxonomies_from_categories($categories);

    if ($node->hasField('apic_tags')) {
      $currenttags = $node->apic_tags->getValue();
    }
    else {
      $currenttags = array();
    }

    if (is_array($tids) && !empty($tids)) {
      foreach ($tids as $tid) {
        if (isset($tid) && is_numeric($tid)) {
          $found = FALSE;
          foreach ($currenttags as $currentvalue) {
            if (isset($currentvalue['target_id']) && $currentvalue['target_id'] == $tid) {
              $found = TRUE;
            }
          }
          if ($found == FALSE) {
            $currenttags[] = array('target_id' => $tid);
          }
        }
      }

      $node->set('apic_tags', $currenttags);
      $node->save();
    }
    ibm_apim_exit_trace(__CLASS__ . '::' . __FUNCTION__, NULL);
  }
}
