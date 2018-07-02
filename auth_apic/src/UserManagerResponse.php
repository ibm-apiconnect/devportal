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

  private $success;
  private $message;
  private $redirect;
  private $redirectParameters;
  private $uid;

  function __construct() {
  }

  /**
   * Success of the user manager function.
   *
   * @return boolean
   */
  public function success() {
    return $this->success;
  }

  /**
   * Set success of user manager function.
   *
   * @param boolean $success
   */
  public function setSuccess($success) {
    $this->success = $success;
  }

  /**
   * message to display.
   *
   * @return string
   */
  public function getMessage() {
    return $this->message;
  }

  /**
   * Set message to display.
   *
   * @param string $message
   */
  public function setMessage($message) {
    $this->message = $message;
  }

  /**
   * Redirect route name.
   *
   * @return string
   */
  public function getRedirect() {
    return $this->redirect;
  }

  /**
   * Redirect route name.
   *
   * @param string $redirect
   */
  public function setRedirect($redirect) {
    $this->redirect = $redirect;
  }

  /**
   * Redirect parameters.
   *
   * @return array
   */
  public function getRedirectParameters() {
    return $this->redirectParameters;
  }

  /**
   * Redirect parameters array
   *
   * @param array $redirectParameters
   */
  public function setRedirectParameters($redirectParameters) {
    $this->redirectParameters = $redirectParameters;
  }

  /**
   * User ID.
   *
   * @return integer
   */
  public function getUid() {
    return $this->uid;
  }

  /**
   * Set User ID.
   *
   * @param integer $uid
   */
  public function setUid($uid) {
    $this->uid = $uid;
  }

}
