<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2022
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

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
