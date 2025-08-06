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
 * Provides a Github Markdown input filter.
 */
namespace Drupal\ghmarkdown\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;

class GhmarkdownHooks {
  /**
   * Implements hook_help().
   *
   * @param $route_name
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup|string
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    if ($route_name === 'help.page.ghmarkdown') {
      return t('<p>The Markdown filter allows you to enter content using <a href="https://guides.github.com/features/mastering-markdown/">Github Markdown</a>, a simple plain-text syntax that is transformed into valid HTML.</p>');
    }
  }
 }