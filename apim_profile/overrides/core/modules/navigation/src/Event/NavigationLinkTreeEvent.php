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

declare(strict_types=1);

namespace Drupal\navigation\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Wraps a navigation menu link tree manipulate event for event listeners.
 */
final class NavigationLinkTreeEvent extends Event {

  /**
   * Constructs a new NavigationLinkTreeEvent object.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   The navigation menu link tree.
   */
  public function __construct(
    private array $tree,
  ) {
  }

  /**
   * Gets the navigation link tree element array.
   *
   * @return \Drupal\Core\Menu\MenuLinkTreeElement[]
   *   Array containing the navigation link tree elements.
   */
  public function getMenuLinkTree(): array {
    return $this->tree;
  }

  /**
   * Sets the navigation link tree element array.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeElement[] $tree
   *   Array containing the navigation link tree elements.
   */
  public function setMenuLinkTree(array $tree): void {
    $this->tree = $tree;
  }

}