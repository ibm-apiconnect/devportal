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
 * Event that is fired when a set of application credentials are deleted.
 *
 * @see Application::deleteNode()
 */
class CredentialDeleteEvent extends Event {

  const EVENT_NAME = 'credential_delete';

  /**
   * The application.
   *
   * @var \Drupal\node\NodeInterface
   */
  public $application;

  /**
   * The credential ID.
   *
   * @var string
   */
  public $credId;

  /**
   * The data returned from the APIM consumer API
   *
   * @var array
   */
  public $data;

  /**
   * Constructs the object.
   *
   * @param \Drupal\node\NodeInterface $application
   *   The application whose credentials were deleted.
   * @param $data
   *   The data returned from the APIM consumer API
   * @param $credId
   *   The credential ID
   */
  public function __construct(NodeInterface $application, $data, $credId) {
    $this->application = $application;
    $this->credId = $credId;
    $this->data = $data;
  }

}
