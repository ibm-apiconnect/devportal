<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 *
 * (C) Copyright IBM Corporation 2018, 2019
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\apic_api\Plugin\Condition;

use Drupal\node\NodeInterface;
use Drupal\rules\Core\RulesConditionBase;

/**
 * Provides an 'API is online' condition.
 *
 * @Condition(
 *   id = "rules_api_is_online",
 *   label = @Translation("API is online"),
 *   category = @Translation("API"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node",
 *       label = @Translation("API")
 *     )
 *   }
 * )
 */
class ApiIsOnline extends RulesConditionBase {

  /**
   * Check if the given API is online.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check.
   *
   * @return bool
   *   TRUE if the API is online.
   */
  protected function doEvaluate(NodeInterface $node): bool {
    return $node->api_state->value === 'online';
  }

}