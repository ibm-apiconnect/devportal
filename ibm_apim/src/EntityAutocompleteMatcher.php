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

namespace Drupal\ibm_apim;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Tags;
use Drupal\node\Entity\Node;


class EntityAutocompleteMatcher extends \Drupal\Core\Entity\EntityAutocompleteMatcher
{
  public function getMatches($target_type, $selection_handler, $selection_settings, $string = '')
  {
    $matches = array();
    $options = array(
      'target_type' => $target_type,
      'handler' => $selection_handler,
      'handler_settings' => $selection_settings,
    );
    $handler = $this->selectionManager
      ->getInstance($options);
    if (isset($string)) {

      // Get an array of matching entities.
      $match_operator = !empty($selection_settings['match_operator']) ? $selection_settings['match_operator'] : 'CONTAINS';

      $entity_labels = $handler->getReferenceableEntities($string, $match_operator, 10);

      // Loop through the entities and convert them into autocomplete output.
      foreach ($entity_labels as $entity => $values) {
        foreach ($values as $entity_id => $label) {

          if ($entity === 'product') {
            $version = $this->getProductVersion($entity_id, $target_type, $label);
          }
          if ($entity === 'api') {
            $version = $this->getApiVersion($entity_id, $target_type, $label);
          }

          if (isset($version)) {
            $label = "{$label} (v{$version})";
          }

          $key = "{$label} ({$entity_id})";
          $key = preg_replace('/\\s\\s+/', ' ', str_replace("\n", '', trim(Html::decodeEntities(strip_tags($key)))));
          $key = Tags::encode($key);
          $matches[] = array(
            'value' => $key,
            'label' => $label,
          );
        }
      }
    }
    return $matches;
  }

  protected function getProductVersion($entity_id, $target_type, $label) {
    $product = Node::load($entity_id);
    if (isset($product)) {
      return $product->apic_version->value;
    }
  }

  protected function getApiVersion($entity_id, $target_type, $label) {
    $api = Node::load($entity_id);
    if (isset($api)) {
      return $api->apic_version->value;
    }
  }
}