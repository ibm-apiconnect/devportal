<?php

namespace Drupal\product\Plugin\Condition;

use Drupal\node\NodeInterface;
use Drupal\rules\Core\RulesConditionBase;

/**
 * Provides a 'Product is of state' condition.
 *
 * @Condition(
 *   id = "rules_product_is_of_state",
 *   label = @Translation("Product is of state"),
 *   category = @Translation("Product"),
 *   context = {
 *     "node" = @ContextDefinition("entity:node",
 *       label = @Translation("Product")
 *     ),
 *     "states" = @ContextDefinition("string",
 *       label = @Translation("States"),
 *       description = @Translation("Check for the the allowed lifecycle states."),
 *       multiple = TRUE
 *     )
 *   }
 * )
 */
class ProductIsOfState extends RulesConditionBase {

  /**
   * Check if a product is in a specific set of states.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The product to check for a type.
   * @param string[] $states
   *   An array of state names as strings.
   *
   * @return bool
   *   TRUE if the product state is in the array of states.
   */
  protected function doEvaluate(NodeInterface $node, array $states): bool {
    return in_array($node->product_state->value, $states, FALSE);
  }

}
