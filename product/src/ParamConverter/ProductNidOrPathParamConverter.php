<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2024
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\product\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Symfony\Component\Routing\Route;

class ProductNidOrPathParamConverter implements ParamConverterInterface {

  public function convert($value, $definition, $name, array $defaults): ?NodeInterface {
    $returnValue = NULL;
    if ($value !== NULL && !empty($value)) {
      $lang_code = \Drupal::languageManager()->getCurrentLanguage()->getId();
      if ((int) $value > 0) {
        $node = Node::load($value);
        if ($node !== NULL) {
          // ensure use the translated version of api nodes
          $hasTranslation = $node->hasTranslation($lang_code);
          if ($hasTranslation === TRUE) {
            $node = $node->getTranslation($lang_code);
          }
        }
        $returnValue = $node;
      }
      else {
        // try x-pathalias value
        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'product');
        $query->condition('status', 1);
        $query->condition('apic_pathalias.value', $value);
        $nids = $query->accessCheck()->execute();

        if ($nids !== NULL && !empty($nids)) {
          $nid = array_shift($nids);
          $node = Node::load($nid);
          if ($node !== NULL) {
            // ensure use the translated version of api nodes
            $hasTranslation = $node->hasTranslation($lang_code);
            if ($hasTranslation === TRUE) {
              $node = $node->getTranslation($lang_code);
            }
          }
          $returnValue = $node;
        }
        else {
          // try name:version
          $query = \Drupal::entityQuery('node');
          $query->condition('type', 'product');
          $query->condition('status', 1);
          $query->condition('apic_ref.value', $value);
          $nids = $query->accessCheck()->execute();

          if ($nids !== NULL && !empty($nids)) {
            $nid = array_shift($nids);
            $node = Node::load($nid);
            if ($node !== NULL) {
              // ensure use the translated version of api nodes
              $hasTranslation = $node->hasTranslation($lang_code);
              if ($hasTranslation === TRUE) {
                $node = $node->getTranslation($lang_code);
              }
            }
            $returnValue = $node;
          }
        }
      }
    }
    return $returnValue;
  }

  public function applies($definition, $name, Route $route): bool {
    return (!empty($definition['type']) && $definition['type'] === 'product.nidorpath');
  }

}