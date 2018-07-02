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
  public function validate($items, Constraint $constraint) {
    if (!isset($items) || empty($items)) {
      return NULL;
    }
    $first = $items->first();
    if (isset($first)) {
      $value = $first->getValue()['value'];

      if (isset($value)) {
        // Verify that it isn't an integer as would conflict with node ids in the param converter
        if (preg_match('/^[0-9]+$/', $value)) {
          $this->context->addViolation($constraint->isInt);
        }

        // Check for invalid characters
        if (!preg_match('/^[A-Za-z0-9._\-]+$/', $value)) {
          $this->context->addViolation($constraint->invalidChars);
        }
      }
    }
  }

}
