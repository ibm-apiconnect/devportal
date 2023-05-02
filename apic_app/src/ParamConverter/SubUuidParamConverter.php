<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2020, 2022
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\apic_app\ParamConverter;

use Drupal\apic_app\Entity\ApplicationSubscription;
use Drupal\Component\Utility\Html;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Symfony\Component\Routing\Route;

/**
 * Class SubUuidParamConverter
 *
 * @package Drupal\apic_app\ParamConverter
 */
class SubUuidParamConverter implements ParamConverterInterface {

  /**
   * @param mixed $value
   * @param mixed $definition
   * @param string $name
   * @param array $defaults
   *
   * @return \Drupal\Core\Entity\EntityInterface|null
   */
  public function convert($value, $definition, $name, array $defaults) {
    $returnValue = NULL;
    if (!empty($value)) {
      $query = \Drupal::entityQuery('apic_app_application_subs');
      $query->condition('uuid', Html::escape($value));
      $entityIds = $query->accessCheck()->execute();
      if (isset($entityIds) && !empty($entityIds)) {
        $cred = ApplicationSubscription::load(array_shift($entityIds));
        if ($cred !== NULL) {
          $returnValue = $cred;
        }
      }
    }
    return $returnValue;
  }

  /**
   * @param mixed $definition
   * @param string $name
   * @param \Symfony\Component\Routing\Route $route
   *
   * @return bool
   */
  public function applies($definition, $name, Route $route): bool {
    return (!empty($definition['type']) && $definition['type'] === 'apic_app.subid');
  }

}