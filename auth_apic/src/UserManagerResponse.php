<?php
/********************************************************* {COPYRIGHT-TOP} ***
 * Licensed Materials - Property of IBM
 * 5725-L30, 5725-Z22
 *
 * (C) Copyright IBM Corporation 2018, 2024
 *
 * All Rights Reserved.
 * US Government Users Restricted Rights - Use, duplication or disclosure
 * restricted by GSA ADP Schedule Contract with IBM Corp.
 ********************************************************** {COPYRIGHT-END} **/

namespace Drupal\auth_apic;

/**
 * Class UserManagerResponse
 *
 * Provides consistent structure for responses from the usermanager class so that displaying results can be done in the
 * forms which drive them.
 *
 * @package Drupal\auth_apic
 */
class UserManagerResponse {

  /**
   * @var boolean
   */
  private $success;

  /**
   * @var string
   */
  private $message;

  /**
   * @var string
   */
  private $redirect;

  /**
   * @var array
   */
  private $redirectParameters;

  /**
   * @var int
   */
  private $uid;

  public function __construct() {
  }

  /**
   * Success of the user manager function.
   *
   * @return boolean
   */
  public function success(): bool {
    if ($this->success === NULL) {
      $this->success = FALSE;
    }
    return $this->success;
  }

  /**
   * Set success of user manager function.
   *
   * @param boolean $success
   */
  public function setSuccess($success): void {
    $this->success = $success;
  }

  /**
   * message to display.
   *
   * @return string
   */
  public function getMessage(): ?string {
    return $this->message;
  }

  /**
   * Set message to display.
   *
   * @param string $message
   */
  public function setMessage($message): void {
    $this->message = $message;
  }

  /**
   * Redirect route name.
   *
   * @return string
   */
  public function getRedirect(): ?string {
    return $this->redirect;
  }

  /**
   * Redirect route name.
   *
   * @param string $redirect
   */
  public function setRedirect($redirect): void {
    $this->redirect = $redirect;
  }

  /**
   * Redirect parameters.
   *
   * @return array
   */
  public function getRedirectParameters(): ?array {
    return $this->redirectParameters;
  }

  /**
   * Redirect parameters array
   *
   * @param array $redirectParameters
   */
  public function setRedirectParameters($redirectParameters): void {
    $this->redirectParameters = $redirectParameters;
  }

  /**
   * User ID.
   *
   * @return integer
   */
  public function getUid(): ?int {
    return $this->uid;
  }

  /**
   * Set User ID.
   *
   * @param integer $uid
   */
  public function setUid($uid): void {
    $this->uid = $uid;
  }

}
