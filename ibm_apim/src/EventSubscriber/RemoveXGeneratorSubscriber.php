<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2021
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\ibm_apim\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class RemoveXGeneratorSubscriber implements EventSubscriberInterface {

  /**
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   */
  public function RemoveXGenerator(ResponseEvent $event): void {
    $response = $event->getResponse();
    $response->headers->remove('X-Generator');
  }

  /**
   * @return array|mixed
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = ['RemoveXGenerator', -10];
    return $events;
  }
}