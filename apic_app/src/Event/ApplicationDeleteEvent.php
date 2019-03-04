<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2019
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\apic_app\Event;

use Drupal\core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Event that is fired when an application is deleted.
 *
 * @see Application::deleteNode()
 */
class ApplicationDeleteEvent extends GenericEvent {

  public const EVENT_NAME = 'application_delete';

  /**
   * The application.
   *
   * @var \Drupal\core\Entity\EntityInterface
   */
  public $application;

  /**
   * ApplicationDeleteEvent constructor.
   *
   * @param \Drupal\core\Entity\EntityInterface $application
   * @param array $arguments
   */
  public function __construct(EntityInterface $application, array $arguments = []) {
    GenericEvent::__construct($application, $arguments);
    $this->application = $application;
    $this->arguments = $arguments;
  }

  /**
   * @return \Drupal\core\Entity\EntityInterface|null
   */
  public function getApplication(): ?EntityInterface {
    return $this->application;
  }

  /**
   * @param \Drupal\core\Entity\EntityInterface $node
   */
  public function setApplication(EntityInterface $node): void {
    $this->application = $node;
  }

}
