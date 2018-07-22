Feature: Forgot Password
  If I have fogotten my password or it has been compromised.
  I need to request a password reset

  Scenario: Viewing the forgotten password form
    Given I am not logged in
    And I am at "/user/password"
    Then I should see text matching "Sign in|Log in"
    And I should see the text "Create new account"
    And I should see the text "Reset your password"
    And I should see the text "Username or email address"
    And I should see the "Submit" button

  @api
  Scenario: Request new password for andre user (non-admin)
    Given I am not logged in
    Given users:
      | name           | mail                     | status |
      | Forgot PW Test | forgotpwtest@example.com | 1      |
    Given consumerorgs:
      | title          | name           | id    | owner          |
      | forgotpwconsumerorg | forgotpwconsumerorg | 12345 | Forgot PW Test |
    Given I am at "/user/password"
    And I enter "forgotpwtest@example.com" for "name"
    When I press the "Submit" button
    # This is odd but this is the only string that both travis and full site installs have in common!
    Then I should see the text "been sent"
    And I am at "/user/login"

   Scenario: Request new password for admin user
     Given I am not logged in
     And I am at "/user/password"
     And I enter "admin" for "name"
     When I press the "Submit" button
     # This is odd but this is the only string that both travis and full site installs have in common!
     Then I should see the text "been sent"
     And I am at "/user/login"

  @api
  Scenario: Request new password for non-existent user
  This scenario covers when a site is recreated by the user has forgotten their password.
  The management server knows the password but drupal doesn't have a record.
  For this reason, in any case other than admin, we always call the management node for a forgotten password.
    Given I am not logged in
    Given users:
      | name           | mail                     | status |
      | Forgot PW Test | forgotpwtest@example.com | 1      |
   Given consumerorgs:
      | title          | name           | id    | owner          |
      | forgotpwconsumerorg | forgotpwconsumerorg | 12345 | Forgot PW Test |
    Given I am at "/user/password"
    And I enter "this_is_not_a_user" for "name"
    When I press the "Submit" button
    # This is odd but this is the only string that both travis and full site installs have in common!
    Then I should see the text "been sent"
    And I am at "/user/login"

  @api
  Scenario: Forgot password form loads with multiple user registries
    Given the cache has been cleared
    Given I am not logged in
    Given userregistries:
      | type | title                             | url                               | user_managed | default |
      | lur  | @data(user_registries[0].title)   | @data(user_registries[0].url)     | yes          | yes     |
      | ldap | @data(user_registries[2].title)   | @data(user_registries[2].url)     | no           | no      |
    When I am at "/user/password"
    Then I should see the text "Reset Password"
    And I should see the text "@data(user_registries[0].title)"
    And I should see the text "If you have forgotten the password for your @data(user_registries[0].title) account or 'admin', you can reset it here."
    And I should see "or" in the ".apic-user-form-or" element
    And I should see the text "or"
    And I should see the text "Select a different registry"
    And I should see the link "@data(user_registries[2].title)"
    And I should see the link "Back to Sign in"

  @api
  Scenario: Forgot password for !user_managed registry
    Given the cache has been cleared
    Given I am not logged in
    Given userregistries:
      | type | title                             | url                               | user_managed | default |
      | ldap | @data(user_registries[2].title)   | @data(user_registries[2].url)     | no           | yes     |
    When I am at "/user/password"
    And I should see the text "@data(user_registries[2].title)"
    And I should see the text "If you have forgotten your 'admin' password, you can reset it here. Your @data(user_registries[2].title) account is managed externally and you must contact your user registry administrator."

  @api
  Scenario: Forgot password changes user registry via link
    Given the cache has been cleared
    Given I am not logged in
    Given userregistries:
      | type | title                             | url                               | user_managed | default |
      | lur  | @data(user_registries[0].title)   | @data(user_registries[0].url)     | yes          | yes     |
      | ldap | @data(user_registries[2].title)   | @data(user_registries[2].url)     | no           | no      |
    When I am at "/user/password"
    Then I should see the text "@data(user_registries[0].title)"
    And I should see the link "@data(user_registries[2].title)"
    When I click "@data(user_registries[2].title)"
    And I should see the text "@data(user_registries[2].title)"
    And I should see the text "If you have forgotten your 'admin' password, you can reset it here. Your @data(user_registries[2].title) account is managed externally and you must contact your user registry administrator."
    And I should see the link "@data(user_registries[0].title)"
