<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2025
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * @file
 * Provides a Featured Content block.
 */
namespace Drupal\featuredcontent\Hook;

use Drupal\Core\Hook\Attribute\Hook;

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
   * @param \Drupal\Core\Database\Query\AlterableInterface $query
   */
  #[Hook('query_random_alter')]
  public function queryRandomAlter(Drupal\Core\Database\Query\AlterableInterface $query) {
    $query->orderRandom();
  }
 }
