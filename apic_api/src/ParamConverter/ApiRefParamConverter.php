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

namespace Drupal\apic_api\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\node\Entity\Node;
use Drupal\Component\Utility\Html;
use Symfony\Component\Routing\Route;

class ApiRefParamConverter implements ParamConverterInterface {
  public function convert($value, $definition, $name, array $defaults) {
    $ref = Html::escape(\Drupal::service('ibm_apim.utils')->base64_url_decode($value));
    $query = \Drupal::entityQuery('node');
    $query->condition('type', 'api');
    $query->condition('status', 1);
    $query->condition('apic_ref.value', $ref);

    $nids = $query->execute();

    if (isset($nids) && !empty($nids)) {
      $nid = array_shift($nids);
      $node = Node::load($nid);
      return $node;
    }
    else {
      return NULL;
    }
  }

  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] == 'api.ref');
  }
}