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

use Drupal\core\Entity\EntityInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when a set of application credentials are deleted.
 *
 * @see Application::deleteNode()
 */
class CredentialDeleteEvent extends Event {

  const EVENT_NAME = 'credential_delete';

  /**
   * The application.
   *
   * @var \Drupal\core\Entity\EntityInterface
   */
  public $application;

  /**
   * Constructs the object.
   *
   * @param \Drupal\core\Entity\EntityInterface $application
   *   The application whose credentials were deleted.
   */
  public function __construct(EntityInterface $application) {
    $this->application = $application;
  }

}
