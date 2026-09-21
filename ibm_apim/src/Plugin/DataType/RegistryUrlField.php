<?php


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
