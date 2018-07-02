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
