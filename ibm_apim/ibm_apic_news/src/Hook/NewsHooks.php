<?php

/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2025
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

/**
 * @file
 * Provides APIC integration.
 */
namespace Drupal\ibm_apic_news\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Markup;
use Drupal\page_manager\Entity\Page;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use \Drupal\Core\Entity\EntityInterface;
use Drupal\path_alias\Entity\PathAlias;
use Drupal\node\Entity\Node;

class NewsHooks {
  /**
   * Add twig templates
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
      'home_page_news_block' => [
        'variables' => ['newsContainer' => NULL],
      ]
    ];
  }

  #[Hook('entity_insert')]
  function entity_insert(EntityInterface $entity) : void {
    $route_name = \Drupal::routeMatch()->getRouteName();
    if ($route_name !== "node.add") {
      return;
    }
    if ($entity->getEntityTypeId() === 'node' && $entity->bundle() === 'article') {
      $path = '/node/' . $entity->id();
      $langcode = $entity->language()->getId();
      $title = $entity->label();
      $alias = \Drupal::service('path_alias.repository')->lookupBySystemPath($path, $langcode);
      
      //Set new alias if not present. Else check if provided alias contains /news if not add at the beggining
      if(!$alias) {
        $title =  strtolower($title);
        $title = preg_replace('/[^a-z0-9 ]/i', '', $title);
        $title = str_replace(' ', '_', $title);
        $alias = '/news/' . $title;
      }
      else {
        if (preg_match('#^/news#', $alias['alias'])) return;
        $alias = '/news' . $alias['alias'];
      }

      PathAlias::create([
        'path' => $path,
        'alias' => $alias,
        'langcode' => $langcode,
      ])->save();
    }
  }
}