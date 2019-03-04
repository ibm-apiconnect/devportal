<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2019
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\apic_api\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\Routing\Route;

class ApiNidOrPathParamConverter implements ParamConverterInterface {

  public function convert($value, $definition, $name, array $defaults) {
    $returnValue = NULL;
    if (!empty($value)) {
      if ((int) $value > 0) {
        $node = Node::load($value);
        $returnValue = $node;
      }
      else {
        $query = \Drupal::entityQuery('node');
        $query->condition('type', 'api');
        $query->condition('status', 1);
        $query->condition('apic_pathalias.value', $value);
        $nids = $query->execute();

        if ($nids !== NULL && !empty($nids)) {
          $nid = array_shift($nids);
          $node = Node::load($nid);
          $returnValue = $node;
        }
      }
    }
    return $returnValue;
  }

  public function applies($definition, $name, Route $route): bool {
    return (!empty($definition['type']) && $definition['type'] === 'apic_api.nidorpath');
  }
}
