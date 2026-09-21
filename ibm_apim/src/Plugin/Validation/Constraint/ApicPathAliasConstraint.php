<?php

namespace Drupal\ibm_apim\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that node path alias is valid.
 *
 * @Constraint(
 *   id = "ApicPathAlias",
 *   label = @Translation("Path Alias", context = "Validation"),
 * )
 */
class ApicPathAliasConstraint extends Constraint {

  public string $isInt = 'The path alias cannot be an integer as this would conflict with node IDs.';

  public string $invalidChars = 'The path alias can only contain the following characters: [A-Za-z0-9_-.]';

}
