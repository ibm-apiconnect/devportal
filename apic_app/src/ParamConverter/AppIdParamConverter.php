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

namespace Drupal\apic_app\ParamConverter;

use Drupal\Component\Utility\Html;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\Routing\Route;

class AppIdParamConverter implements ParamConverterInterface {

  /**
   * @param mixed $value
   * @param mixed $definition
   * @param string $name
   * @param array $defaults
   *
   * @return \Drupal\Core\Entity\EntityInterface|\Drupal\node\Entity\Node|mixed|null
   */
  public function convert($value, $definition, $name, array $defaults) {
    $returnValue = NULL;
    if (!empty($value)) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'application');
      $query->condition('application_id.value', Html::escape($value));

      $nids = $query->execute();

      if (isset($nids) && !empty($nids)) {
        $nid = array_shift($nids);
        $node = Node::load($nid);
        if ($node !== NULL) {
          $returnValue = $node;
        }
      }
    }
    return $returnValue;
  }

  public function applies($definition, $name, Route $route): bool {
    return (!empty($definition['type']) && $definition['type'] === 'apic_app.appid');
  }
}