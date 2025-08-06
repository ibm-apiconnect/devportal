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
 * Provides a Social block.
 */
namespace Drupal\socialblock\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Config\Config;
use Drupal\Core\Entity\EntityInterface;

class SocialBlockHooks {
  /**
   * Add twig template
   * Implements hook_theme
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
      'socialblock_block' => [
        'variables' => [
          'posts' => NULL,
        ],
      ],
    ];
  }

    /**
   * Implements hook_entity_delete, used to remove saved info about block in local storage
   * @param $entity
   */
  #[Hook('entity_delete')]
  function socialblock_entity_delete(EntityInterface $entity): void {
    try {
      if ($entity->get('plugin') !== 'social_block') {
        return;
      }
      $settings = $entity->get('settings');
      if (!$settings || !isset($settings['uuid'])) {
        return;
      }
      $uuid = $settings['uuid'];
      $configInstances = \Drupal::state()->get('socialblock.config');
      if ($configInstances === NULL) {
        return;
      }
      if (array_key_exists($uuid, $configInstances) && $configInstances[$uuid]) {
        unset($configInstances[$uuid]);
      }
      \Drupal::state()->set('socialblock.config', $configInstances);
    }
    catch (\Exception $e) {
      return;
    }
  }
 }
