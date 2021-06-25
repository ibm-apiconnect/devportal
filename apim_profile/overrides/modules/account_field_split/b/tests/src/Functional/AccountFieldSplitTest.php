<?php

namespace Drupal\Tests\account_field_split\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\UserInterface;

/**
 * Provides test for split fields.
 *
 * @group account_field_split
 */
class AccountFieldSplitTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'user',
    'account_field_split',
  ];

  /**
   * The theme to install as the default for testing.
   *
   * Defaults to the install profile's default theme, if it specifies any.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Provides testing for splitting fields.
   */
  public function testRegistration() {
    $config = $this->config('user.settings');
    // Don't require email verification and allow registration by site visitors
    // without administrator approval.
    $config
      ->set('verify_mail', FALSE)
      ->set('register', UserInterface::REGISTER_VISITORS)
      ->save();

    $edit = [];
    $edit['name'] = $name = $this->randomMachineName();
    $edit['mail'] = $mail = $edit['name'] . '@example.com';

    // Try entering a mismatching password.
    $edit['pass[pass1]'] = '99999.0';
    $edit['pass[pass2]'] = '99999';
    $this->drupalPostForm('user/register', $edit, t('Create new account'));
    $this->assertText(t('The specified passwords do not match.'), 'Typing mismatched passwords displays an error message.');

    // Enter a correct password.
    $edit['pass[pass1]'] = $new_pass = $this->randomMachineName();
    $edit['pass[pass2]'] = $new_pass;
    $this->drupalPostForm('user/register', $edit, t('Create new account'));
    $this->container->get('entity_type.manager')->getStorage('user')->resetCache();
    $accounts = $this->container->get('entity_type.manager')->getStorage('user')
      ->loadByProperties(['name' => $name, 'mail' => $mail]);
    $new_user = reset($accounts);
    $this->assertNotNull($new_user, 'New account successfully created with matching passwords.');
    $this->assertText(t('Registration successful. You are now logged in.'), 'Users are logged in after registering.');
    $this->drupalLogout();

    // Allow registration by site visitors, but require administrator approval.
    $config->set('register', UserInterface::REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL)->save();
    $edit = [];
    $edit['name'] = $name = $this->randomMachineName();
    $edit['mail'] = $mail = $edit['name'] . '@example.com';
    $edit['pass[pass1]'] = $pass = $this->randomMachineName();
    $edit['pass[pass2]'] = $pass;
    $this->drupalPostForm('user/register', $edit, t('Create new account'));
    $this->assertText(t('Thank you for applying for an account. Your account is currently pending approval by the site administrator.'), 'Users are notified of pending approval');

    // Try to log in before administrator approval.
    $auth = [
      'name' => $name,
      'pass' => $pass,
    ];
    $this->drupalPostForm('user/login', $auth, t('Log in'));
    $this->assertText(t('The username @name has not been activated or is blocked.', ['@name' => $name]), 'User cannot log in yet.');

    // Activate the new account.
    $accounts = $this->container->get('entity_type.manager')->getStorage('user')
      ->loadByProperties(['name' => $name, 'mail' => $mail]);
    $new_user = reset($accounts);
    $admin_user = $this->drupalCreateUser(['administer users']);
    $this->drupalLogin($admin_user);
    $edit = [
      'status' => 1,
    ];
    $this->drupalPostForm('user/' . $new_user->id() . '/edit', $edit, t('Save'));
    $this->drupalLogout();

    // Log in after administrator approval.
    $this->drupalPostForm('user/login', $auth, t('Log in'));
    $this->assertText(t('Member for'), 'User can log in after administrator approval.');
  }

}
