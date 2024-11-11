<?php

declare(strict_types=1);

namespace Drupal\navigation;

use Drupal\navigation\Event\NavigationLinkTreeEvent;
use Drupal\navigation\Event\NavigationLinkTreeEvents;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a menu link tree manipulators.
 *
 *  This class provides menu link tree manipulators to:
 *  - trigger generic navigation link tree manipulate event,
 */
final class NavigationMenuLinkTreeManipulators {

  /**
   * Constructs a NavigationMenuLinkTreeManipulators object.
   */
  public function __construct(
    private readonly EventDispatcherInterface $eventDispatcher,
  ) {}

  /**
   * @todo Add method description.
   */
  public function manipulate(array $tree): array {
    $event = $this->eventDispatcher->dispatch(new NavigationLinkTreeEvent($tree), NavigationLinkTreeEvents::MANIPULATE);

    return $event->getMenuLinkTree();
  }

}