<?php

declare(strict_types=1);

namespace Drupal\navigation\EventSubscriber;

use Drupal\navigation\Event\NavigationLinkTreeEvent;
use Drupal\navigation\Event\NavigationLinkTreeEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for navigation menu link tree manipulation event.
 */
final class NavigationMenuLinkTreeSubscriber implements EventSubscriberInterface {

  /**
   * Removes unwanted menu links.
   *
   * @param \Drupal\navigation\Event\NavigationLinkTreeEvent $event
   *   The navigation link event.
   */
  public function onLinkTreeManipulate(NavigationLinkTreeEvent $event): void {
    $tree = $event->getMenuLinkTree();

    foreach ($tree as $key => $item) {
      // Skip elements where menu is not the 'admin' one.
      $menu_name = $item->link->getMenuName();
      if ($menu_name != 'admin') {
        continue;
      }

      // Remove unwanted Help and Content menus.
      $plugin_id = $item->link->getPluginId();
      if ($plugin_id == 'help.main' || $plugin_id == 'system.admin_content') {
        unset($tree[$key]);
      }

      // Remove child items of content menu, if any.
      $parent = $item->link->getParent();
      if ($parent == 'system.admin_content') {
        unset($tree[$key]);
      }
    }

    $event->setMenuLinkTree($tree);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      NavigationLinkTreeEvents::MANIPULATE => ['onLinkTreeManipulate'],
    ];
  }

}