<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2020
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/
namespace Drupal\apic_app\Event;

use Drupal\node\NodeInterface;
use Symfony\Component\EventDispatcher\Event;

/**
 * Event that is fired when an application's Client ID is reset.
 *
 * @see Application::update()
 */
class CredentialClientIDResetEvent extends Event {

  const EVENT_NAME = 'credential_clientid_reset';

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
   *   The application whose client ID was reset.
   */
  public function __construct(NodeInterface $application) {
    $this->application = $application;
  }

}
