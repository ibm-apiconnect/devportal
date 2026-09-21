<?php


/**
 * @file
 * Provides a Featured Content block.
 */
namespace Drupal\featuredcontent\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Database\Query\SelectInterface;

class FeaturedContentHooks {
  /**
   * Add twig template
   *
   * @param $existing
   * @param $type
   * @param $theme
   * @param $path
   *
   * @return array
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path): array {
    return [
      'featuredcontent_block' => [
        'variables' => [
          'nodes' => NULL,
          'nodeType' => NULL,
          'algorithm' => NULL,
          'showPlaceholders' => TRUE,
          'showVersions' => TRUE,
        ],
      ],
    ];
  }

  /**
   * Implements hook_query_TAG_alter().
   *
   * Adds a 'orderRandom()' to entity queries
   *
   *  From https://www.drupal.org/node/1174806
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   */
  #[Hook('query_random_alter')]
  public function queryRandomAlter(SelectInterface $query) {
    $query->orderRandom();
  }
 }
