<?php

namespace Drupal\restrict_by_ip\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Setup shared data and determine IP ranges to use for current testing
 * environment.
 */
class RestrictByIPWebTestBase extends WebTestBase {

  public static $modules = ['restrict_by_ip'];

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $conf;

  /**
   * @var \Drupal\user\Entity\User
   */
  protected $regularUser;

  /**
   * The IP of the client that runs these tests.
   *
   * @var string
   */
  protected $currentIPCIDR;

  /**
   * An IP range that the client running these tests is not a part of.
   */
  protected $outOfRangeCIDR;

  public function setUp() {
    // Enable modules needed for these tests.
    parent::setUp();

    $this->conf = $this->config('restrict_by_ip.settings');

    // Create a user that we'll use to test logins.
    $this->regularUser = $this->drupalCreateUser();

    $outOfRangeCIDRs = [
      '10' => '10.0.0.0/8',
      '172' => '172.16.0.0/12',
      '192' => '192.168.0.0/16',
    ];

    $adminUser = $this->drupalCreateUser(['administer restrict by ip']);
    $this->drupalLogin($adminUser);
    $this->drupalGet('admin/config/people/restrict_by_ip/login');
    $pageContent = $this->getTextContent();
    preg_match('#is (.*?). If#', $pageContent, $matches);
    $this->drupalLogout();

    // The IP address when testing if client DOES matches restrictions.
    $this->currentIPCIDR = $matches[1] . '/32';

    // The IP address when testing if client DOESN'T match restrictions.
    unset($outOfRangeCIDRs[explode('.', $this->currentIPCIDR)[0]]);
    $this->outOfRangeCIDR = array_shift($outOfRangeCIDRs);
  }
}
