<?php

namespace Drupal\restrict_by_ip\Tests;

/**
 * Tests user is redirected when login denied.
 *
 * @group restrict_by_ip
 */
class RedirectTest extends RestrictByIPWebTestBase {

  /**
   * @var \Drupal\node\Entity\Node
   */
  private $loginDeniedNode;

  public static $modules = ['restrict_by_ip', 'node'];

  public function setUp() {
    parent::setUp();

    // Create a page users will get redirected to when denied login.
    $type = $this->drupalCreateContentType();
    $this->loginDeniedNode = $this->drupalCreateNode(['type' => $type->id()]);
    $this->conf->set('error_page', 'node/' . $this->loginDeniedNode->id())->save();
  }

  // User redirected when outside global range and no destination query
  // parameter is present.
  public function testIpDifferGlobalNoDestination() {
    // Add global IP range.
    $this->conf->set('login_range', $this->outOfRangeCIDR)->save();
    $this->assertRedirected();
  }

  // User redirected when outside user range and no destination query parameter
  // is present.
  public function testIpDifferUserNoDestination() {
    // Add out of range user IP.
    $this->conf->set('user.' . $this->regularUser->id(), $this->outOfRangeCIDR)->save();
    $this->assertRedirected();
  }

  // User redirected when outside global range and a destination query parameter
  // is present.
  public function testIpDifferGlobalWithDestination() {
    // Add global IP range.
    $this->conf->set('login_range', $this->outOfRangeCIDR)->save();
    $this->assertRedirected('node');
  }

  // User redirected when outside user range and a destination query parameter
  // is present.
  public function testIpDifferUserWithDestination() {
    // Add out of range user IP.
    $this->conf->set('user.' . $this->regularUser->id(), $this->outOfRangeCIDR)->save();
    $this->assertRedirected('node');
  }

  // Assert user gets redirected when login denied.
  private function assertRedirected($destination = NULL) {
    $edit = [
      'name' => $this->regularUser->label(),
      'pass' => $this->regularUser->pass_raw
    ];

    $options = ['external' => FALSE];
    if (isset($destination)) {
      $options['query'] = ['destination' => $destination];
    }

    $this->drupalPostForm('user/login', $edit, t('Log in'), $options);

    $this->assertFalse($this->drupalUserIsLoggedIn($this->regularUser), t('User %name unsuccessfully logged in.', ['%name' => $this->regularUser->label()]));

    $this->assertText($this->loginDeniedNode->label(), 'Title of login denied page found.');
  }
}
