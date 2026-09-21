<?php

namespace Drupal\ibm_apim\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the ApicPathAlias constraint.
 */
class ApicPathAliasConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint): void {
    if (isset($value) && !empty($value)) {
      $first = $value->first();
      if (isset($first)) {
        $firstValue = $first->getValue()['value'];

        if (isset($firstValue)) {
          // Verify that it isn't an integer as would conflict with node ids in the param converter
          if (preg_match('/^[\d]+$/', $firstValue)) {
            $this->context->addViolation($constraint->isInt);
          }

          // Check for invalid characters
          if (!preg_match('/^[A-Za-z0-9._\-]+$/', $firstValue)) {
            $this->context->addViolation($constraint->invalidChars);
          }
        }
      }
    }
  }

}
