<?php

namespace Drupal\restrict_by_ip\Tests;

use Drupal\user\Entity\Role;

/**
 * Tests roles are restricted to certain IP address range(s).
 *
 * @group restrict_by_ip
 */
class RoleTest extends RestrictByIPWebTestBase {

  /**
   * @var \Drupal\user\Entity\Role
   */
  private $role;

  public function setUp() {
    parent::setUp();

    // Create a role with administer permissions so we can load the user edit,
    // page and test if the user has this role when logged in.
    $random_name = $this->randomMachineName();
    $rid = $this->drupalCreateRole(['administer permissions'], null, $random_name);
    $this->role = Role::load($rid);

    // Add created role to user.
    $this->regularUser->addRole($this->role->id());
    $this->regularUser->save();
  }

  public function testRoleAppliedNoRestrictions() {
    $this->drupalLogin($this->regularUser);
    $this->drupalGet('user/' . $this->regularUser->id() . '/edit');
    $this->assertText($this->role->label());
  }

  public function testRoleAppliedMatchIP() {
    $this->conf->set('role.' . $this->role->id(), $this->currentIPCIDR)->save();
    $this->drupalLogin($this->regularUser);
    $this->drupalGet('user/' . $this->regularUser->id() . '/edit');
    $this->assertText($this->role->label());
  }

  public function testRoleDeniedDifferIP() {
    $this->conf->set('role.' . $this->role->id(), $this->outOfRangeCIDR)->save();
    $this->drupalLogin($this->regularUser);
    $this->drupalGet('user/' . $this->regularUser->id() . '/edit');
    $this->assertNoText($this->role->label());
  }

  // // Test ip restrictions
  public function testUIRoleRenamed() {
    $this->conf->set('role.' . $this->role->id(), $this->currentIPCIDR)->save();
    $this->drupalLogin($this->regularUser);
    $edit = [];
    $edit['label'] = 'a new role name';
    $this->drupalPostForm('admin/people/roles/manage/' . $this->role->id(), $edit, t('Save'));
    $this->assertText('Role a new role name has been updated.');
    $updatedConf = $this->config('restrict_by_ip.settings');
    $ip = $updatedConf->get('role.' . $this->role->id());
    $this->assertEqual($ip, $this->currentIPCIDR, 'IP restriction updated');
  }

  public function testUIRoleDeleted() {
    $this->conf->set('role.' . $this->role->id(), $this->currentIPCIDR)->save();
    $this->drupalLogin($this->regularUser);
    $edit = [];
    $this->drupalPostForm('admin/people/roles/manage/' . $this->role->id() . '/delete', $edit, t('Delete'));
    $this->assertText('The role ' . $this->role->label() . ' has been deleted.');
    // If we get the default, we know the variable is deleted.
    $updatedConf = $this->config('restrict_by_ip.settings');
    $ip = $updatedConf->get('role.' . $this->role->id());
    $this->assertNull($ip, 'IP restriction deleted');
  }
}
