<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 *
 * (C) Copyright IBM Corporation 2018, 2020
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\apic_app\Plugin\Condition;

use Drupal\node\NodeInterface;
use Drupal\rules\Core\RulesConditionBase;

/**
 * Provides an 'Application is confidential client type' condition.
 *
 * @Condition(
 *   id = "rules_application_is_confidential",
 *   label = @Translation("Application is confidential client type"),
 *   category = @Translation("Application"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node",
 *       label = @Translation("Application")
 *     )
 *   }
 * )
 */
class ApplicationIsConfidential extends RulesConditionBase {

  /**
   * Check if the given Application is confidential.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check.
   *
   * @return bool
   *   TRUE if the Application is confidential.
   */
  protected function doEvaluate(NodeInterface $node): bool {
    return $node->application_client_type->value === 'confidential';
  }

}