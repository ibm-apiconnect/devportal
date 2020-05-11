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

namespace Drupal\product\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Symfony\Component\Routing\Route;

class ProdIdParamConverter implements ParamConverterInterface {

  public function convert($value, $definition, $name, array $defaults): ?NodeInterface {
    $returnValue = NULL;
    if (!empty($value)) {
      $lang_code = \Drupal::languageManager()->getCurrentLanguage()->getId();
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'product');
      $query->condition('status', 1);
      $query->condition('product_id.value', $value);
      $nids = $query->execute();

      if (isset($nids) && !empty($nids)) {
        $nid = array_shift($nids);
        $returnValue = Node::load($nid);
        if ($returnValue !== null) {
          // ensure use the translated version of api nodes
          $hasTranslation = $returnValue->hasTranslation($lang_code);
          if ($hasTranslation === TRUE) {
            $returnValue = $returnValue->getTranslation($lang_code);
          }
        }
      }
    }
    return $returnValue;
  }

  public function applies($definition, $name, Route $route): bool {
    return (!empty($definition['type']) && $definition['type'] === 'product.id');
  }
}