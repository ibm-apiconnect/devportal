<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2024
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\Plugin\DataType;

use Drupal\Core\TypedData\Plugin\DataType\StringData;

/**
 * The registry_url_field data type.
 *
 * The plain value of a registry_url for an entity.
 *
 * @DataType(
 *   id = "registry_url_field",
 *   label = @Translation("Registry URL Field")
 * )
 */
class RegistryUrlField extends StringData {

}
