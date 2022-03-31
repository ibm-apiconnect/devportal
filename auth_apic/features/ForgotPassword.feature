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
  Scenario: Request new password for andre user (non-admin) - via username
    Given I am not logged in
    Given users:
      | name         | mail                     | pass     | status |
      | forgotpwtest | forgotpwtest@example.com | Qwert123 | 1      |
    Given consumerorgs:
      | title               | name                | id    | owner        |
      | forgotpwconsumerorg | forgotpwconsumerorg | 12345 | forgotpwtest |
    Given I am at "/user/password"
    And I enter "forgotpwtest" for "name"
    And if the field "captcha_response" is present, enter the value "@captcha"
    When I press the "Submit" button
    Then there are no errors
    And there are no warnings
    And there are messages
    And I should see the text "If the account exists, an email has been sent with further instructions to reset the password."
    And I am at "/user/login"

  @api
  Scenario: Request new password for andre user (non-admin) - via email address
    Given I am not logged in
    Given users:
      | name         | mail                     | pass     | status |
      | forgotpwtest | forgotpwtest@example.com | Qwert123 | 1      |
    Given consumerorgs:
      | title               | name                | id    | owner        |
      | forgotpwconsumerorg | forgotpwconsumerorg | 12345 | forgotpwtest |
    Given I am at "/user/password"
    And I enter "forgotpwtest@example.com" for "name"
    And if the field "captcha_response" is present, enter the value "@captcha"
    When I press the "Submit" button
    Then there are no errors
    And there are no warnings
    And there are messages
    And I should see the text "If the account exists, an email has been sent with further instructions to reset the password."
    And I am at "/user/login"

  @api
  Scenario: Request new password for admin user - username
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(admin.name) | @data(admin.mail) | @data(admin.password) | 1      |
    And I am at "/user/password"
    And I enter "admin" for "name"
    And if the field "captcha_response" is present, enter the value "@captcha"
    When I press the "Submit" button
    Then there are no errors
    And there are no warnings
    And there are messages
    And I should see the text "If the account exists, an email has been sent with further instructions to reset the password."
    And I am at "/user/login"

  @api
  Scenario: Request new password for admin user - email address
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(admin.name) | @data(admin.mail) | @data(admin.password) | 1      |
    And I am at "/user/password"
    And I enter "@data(admin.mail)" for "name"
    And if the field "captcha_response" is present, enter the value "@captcha"
    When I press the "Submit" button
    Then there are no errors
    And there are no warnings
    And there are messages
    And I should see the text "If the account exists, an email has been sent with further instructions to reset the password."
    And I am at "/user/login"

  @api
  Scenario: Request new password for non-existent user - via username
  This scenario covers when a site is recreated but the user has forgotten their password.
  The management server knows the password but drupal doesn't have a record.
  For this reason, in any case other than admin, we always call the management node for a forgotten password.
    Given I am not logged in
    Given I am at "/user/password"
    And I enter "this_is_not_a_user" for "name"
    And if the field "captcha_response" is present, enter the value "@captcha"
    When I press the "Submit" button
    Then there are no errors
    And there are no warnings
    And there are messages
    And I should see the text "If the account exists, an email has been sent with further instructions to reset the password."
    And I am at "/user/login"

  @api
  Scenario: Request new password for non-existent user - email address
    Given I am not logged in
    Given I am at "/user/password"
    And I enter "this_is_not_a_user@example.com" for "name"
    And if the field "captcha_response" is present, enter the value "@captcha"
    When I press the "Submit" button
    Then there are no errors
    And there are no warnings
    And there are messages
    And I should see the text "If the account exists, an email has been sent with further instructions to reset the password."
    And I am at "/user/login"

  @api
  Scenario: Forgot password form loads with multiple user registries
    Given the cache has been cleared
    Given I am not logged in
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
      | ldap | @data(user_registries[2].title) | @data(user_registries[2].url) | no           | no      |
    When I am at "/user/password"
    Then I should see the text "Reset Password"
    And I should see the text "@data(user_registries[0].title)"
    And I should see the text "If you have forgotten the password for your @data(user_registries[0].title) account or a local administrator, you can reset it here."
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
      | type | title                           | url                           | user_managed | default |
      | ldap | @data(user_registries[2].title) | @data(user_registries[2].url) | no           | yes     |
    When I am at "/user/password"
    And I should see the text "@data(user_registries[2].title)"
    And I should see the text "If you have forgotten your local administrator password, you can reset it here. Your @data(user_registries[2].title) account is managed externally and you must contact your authentication provider."

  @api
  Scenario: Forgot password changes user registry via link
    Given the cache has been cleared
    Given I am not logged in
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
      | ldap | @data(user_registries[2].title) | @data(user_registries[2].url) | no           | no      |
    When I am at "/user/password"
    Then I should see the text "@data(user_registries[0].title)"
    And I should see the link "@data(user_registries[2].title)"
    When I click "@data(user_registries[2].title)"
    And I should see the text "@data(user_registries[2].title)"
    And I should see the text "If you have forgotten your local administrator password, you can reset it here. Your @data(user_registries[2].title) account is managed externally and you must contact your authentication provider."
    And I should see the link "@data(user_registries[0].title)"

  @api
  Scenario: Forgot password form for multiple users with the same name
    Given I am not logged in
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
      | ldap | @data(user_registries[2].title) | @data(user_registries[2].url) | no           | no      |
    Given users:
      | name     | mail                   | pass     | status | registry_url                  |
      | forgotpw | andre_lur@example.com  | Qwert123 | 1      | @data(user_registries[0].url) |
      | forgotpw | andre_ldap@example.com | Qwert123 | 1      | @data(user_registries[2].url) |
    When I am at "/user/password"
    And I enter "forgotpw" for "name"
    And if the field "captcha_response" is present, enter the value "@captcha"
    And I press the "Submit" button
    Then there are no errors
    And there are no warnings
    And there are messages
    And I should see the text "If the account exists, an email has been sent with further instructions to reset the password."
    And I am at "/user/password?registry_url=@data(user_registries[2].url)"
    And I enter "forgotpw" for "name"
    And if the field "captcha_response" is present, enter the value "@captcha"
    And I press the "Submit" button
    Then there are no errors
    And there are no warnings
    And there are messages
    And I should see the text "If the account exists, an email has been sent with further instructions to reset the password."


  @api
  Scenario: Forgot password form loads for writable ldap
    Given the cache has been cleared
    Given I am not logged in
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | no      |
      | ldap | @data(user_registries[2].title) | @data(user_registries[2].url) | yes          | yes     |
    When I am at "/user/password"
    Then I should see the text "Reset Password"
    And I should see the text "@data(user_registries[2].title)"
    And I should see the text "If you have forgotten the password for your @data(user_registries[2].title) account or a local administrator, you can reset it here."
    And I should see the text "Username or email address"
    And I should see the text "Password reset instructions will be sent to your registered email address."
    And I should see the button "Submit"
    And I should see "or" in the ".apic-user-form-or" element
    And I should see the text "or"
    And I should see the text "Select a different registry"
    And I should see the link "@data(user_registries[0].title)"
    And I should see the link "Back to Sign in"


  @api
  Scenario: Request new password for a writable ldap andre user
    Given the cache has been cleared
    Given I am not logged in
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | no      |
      | ldap | @data(user_registries[2].title) | @data(user_registries[2].url) | yes          | yes     |
    Given users:
      | name         | mail                     | pass     | status |
      | forgotpwtest | forgotpwtest@example.com | Qwert123 | 1      |
    Given consumerorgs:
      | title               | name                | id    | owner        |
      | forgotpwconsumerorg | forgotpwconsumerorg | 12345 | forgotpwtest |
    Given I am at "/user/password"
    And I enter "forgotpwtest" for "name"
    And if the field "captcha_response" is present, enter the value "@captcha"
    When I press the "Submit" button
    Then there are no errors
    And there are no warnings
    And there are messages
    And I should see the text "If the account exists, an email has been sent with further instructions to reset the password."
    And I am at "/user/login"
    And I should see the text "Sign in with @data(user_registries[2].title)"

