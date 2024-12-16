<?php

declare(strict_types=1);

namespace Drupal\navigation\Event;

/**
 * Defines events for the navigation link tree.
 *
 * @see \Drupal\navigation\Event\NavigationLinkTreeEvents
 */
final class NavigationLinkTreeEvents {

  /**
   * Name of the event fired when manipulating the navigation menu link tree.
   *
   * This event allows modules to perform an action whenever the navigation link
   * tree is being built.
   *
   * @Event
   *
   * @see \Drupal\navigation\Event\NavigationLinkTreeEvents
   *
   * @var string
   */
  const MANIPULATE = 'navigation_link_tree.manipulate';

}