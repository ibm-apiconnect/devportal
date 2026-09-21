<?php


namespace Drupal\apic_app\ParamConverter;

use Drupal\Component\Utility\Html;
use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\Routing\Route;

/**
 * Class AppIdParamConverter
 *
 * @package Drupal\apic_app\ParamConverter
 */
class AppIdParamConverter implements ParamConverterInterface {

  /**
   * @param mixed $value
   * @param mixed $definition
   * @param string $name
   * @param array $defaults
   *
   * @return \Drupal\Core\Entity\EntityBase|\Drupal\Core\Entity\EntityInterface|\Drupal\node\Entity\Node|null
   */
  public function convert($value, $definition, $name, array $defaults) {
    $returnValue = NULL;
    if (!empty($value)) {
      $query = \Drupal::entityQuery('node');
      $query->condition('type', 'application');
      $query->condition('application_id.value', Html::escape($value));

      $nids = $query->accessCheck()->execute();

      if (isset($nids) && !empty($nids)) {
        $nid = array_shift($nids);
        $node = Node::load($nid);
        if ($node !== NULL) {
          $returnValue = $node;
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
    return (!empty($definition['type']) && $definition['type'] === 'apic_app.appid');
  }

}