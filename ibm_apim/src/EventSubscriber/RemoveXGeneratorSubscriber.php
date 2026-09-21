<?php

namespace Drupal\ibm_apim\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class RemoveXGeneratorSubscriber
 *
 * @package Drupal\ibm_apim\EventSubscriber
 */
class RemoveXGeneratorSubscriber implements EventSubscriberInterface {

  /**
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   */
  public function RemoveXGenerator(ResponseEvent $event): void {
    $response = $event->getResponse();
    $response->headers->remove('X-Generator');
  }

  /**
   * @return array
   */
  public static function getSubscribedEvents(): array {
    $events[KernelEvents::RESPONSE][] = ['RemoveXGenerator', -10];
    return $events;
  }

}