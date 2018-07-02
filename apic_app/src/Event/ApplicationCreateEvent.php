<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/
namespace Drupal\apic_app\Event;

use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when an application is created.
 *
 * @see Application::create()
 */
class ApplicationCreateEvent extends Event {

  const EVENT_NAME = 'application_create';

  /**
   * The application.
   *
   * @var \Drupal\node\NodeInterface
   */
  public $application;

  /**
   * Constructs the object.
   *
   * @param \Drupal\node\NodeInterface $application
   *   The application that was created.
   */
  public function __construct(NodeInterface $application) {
    $this->application = $application;
  }

}
