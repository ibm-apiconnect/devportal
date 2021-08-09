Feature: Sign-up
  In order to use the developer portal
  As an anonymous user
  And given that self-sign up is enabled
  I need to be able to sign-up for an account

  Scenario: Viewing the registration form
    Given I am not logged in
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
    And I am at "/user/register"
    Then I should see the text "Create new account"
    And I should see the text "First Name"
    And I should see the text "Last Name"
    And I should see the text "Consumer organization"
    And I should see the text "Email address"
    And I should see the text "Password"
    And I should see the text "Confirm password"
    # - password policy! And there are no errors
    And there are no warnings
    And there are no messages

  Scenario: Unable to view registration form when self sign up is blocked
    Given I am not logged in
    And self service onboarding is disabled
    When I go to "/user/register"
    Then the response status code should be 403
    Then there are errors
    And I should see "Self-service onboarding is disabled for this site."
    And there are no messages
    And there are no warnings
    And self service onboarding is enabled

  Scenario: Correctly completing the registration form
    Given I am not logged in
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
    And I am at "/user/register"
    Given I enter "Andre" for "First Name"
    And I enter "Andreson_@now" for "Last Name"
    And I enter "andre_org_@now" for "Consumer organization"
    And I enter "andre_@now@example.com" for "Email address"
    And I enter "andre_@now@example.com" for "Username"
    And I enter "Qwert123" for "Password"
    And if the field "pass[pass2]" is present, enter the value "Qwert123"
    And if the field "captcha_response" is present, enter the value "@captcha"
    When I press the "Sign up" button
    Then there are no errors
    And there are no warnings
    And there are messages
    And I should not see the text "There was an error creating your account"
    And I should see the text "Your account was created successfully"

  Scenario: Unable to sign up as admin
    Given I am not logged in
    And I am at "/user/register"
    Given I enter "admin" for "Username"
    And I enter "admin" for "First Name"
    And I enter "mcadmin" for "Last Name"
    And I enter "admin org" for "Consumer organization"
    And I enter "admin@example.com" for "Email address"
    And I enter "Qwert123" for "Password"
    And if the field "pass[pass2]" is present, enter the value "Qwert123"
    And if the field "captcha_response" is present, enter the value "@captcha"
    When I press the "Sign up" button
    Then there are errors
    And I should see the text "A problem occurred while attempting to create your account. If you already have an account then please use that to Sign in."
    And there are no warnings
    And there are no messages

  Scenario: Unable to sign up as anonymous
    Given I am not logged in
    And I am at "/user/register"
    Given I enter "anonymous" for "Username"
    And I enter "anon" for "First Name"
    And I enter "mcanon" for "Last Name"
    And I enter "anon org" for "Consumer organization"
    And I enter "anon@example.com" for "Email address"
    And I enter "Qwert123" for "Password"
    And if the field "pass[pass2]" is present, enter the value "Qwert123"
    And if the field "captcha_response" is present, enter the value "@captcha"
    When I press the "Sign up" button
    Then there are errors
    And I should see the text "A problem occurred while attempting to create your account. If you already have an account then please use that to Sign in."
    And there are no warnings
    And there are no messages

  Scenario: Trying to sign up with the same email address twice
    Given I am not logged in
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
    And I am at "/user/register"
    Given I enter "Andre" for "First Name"
    And I enter "Andreson_@now" for "Last Name"
    And I enter "andre_org_@now" for "Consumer organization"
    And I enter "andre_@now@example.com" for "Email address"
    And I enter "andre_@now@example.com" for "Username"
    And I enter "Qwert123" for "Password"
    And if the field "pass[pass2]" is present, enter the value "Qwert123"
    And if the field "captcha_response" is present, enter the value "@captcha"
    When I press the "Sign up" button
    Then there are no errors
    And there are messages
    And I should not see the text "There was an error creating your account"
    And I should see the text "Your account was created successfully"
    Given I start a new session
    And I am not logged in
    And I am at "/user/register"
    Then print current URL
    Given I enter "Andre" for "First Name"
    And I enter "Andreson_@now" for "Last Name"
    And I enter "andre_org_@now" for "Consumer organization"
    And I enter "andre_@now@example.com" for "Email address"
    And I enter "andre_@now@example.com" for "Username"
    And I enter "Qwert123" for "Password"
    And if the field "pass[pass2]" is present, enter the value "Qwert123"
    And if the field "captcha_response" is present, enter the value "@captcha"
    When I press the "Sign up" button
    Then there are errors
    And there are no messages
    And I should not see the text "Your account was created successfully"
    And I should see the text "A problem occurred while attempting to create your account. If you already have an account then please use that to Sign in."

  Scenario: View the sign up form with multiple registries
    Given I am not logged in
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
      | ldap | @data(user_registries[2].title) | @data(user_registries[2].url) | no           | no      |
    When I am at "/user/register"
    Then I should see the text "Sign up with @data(user_registries[0].title)"
    And I should see "or" in the ".apic-user-form-or" element
    And I should see the text "or"
    And I should see the text "Select a different registry"
    And I should see the link "@data(user_registries[2].title)"
    And I should see the text "Already have an account?"
    And I should see the link "Sign in"

  Scenario: View the sign up form with an oidc registry
    Given I am not logged in
    # Enabled is the default but set it again to make sure
    And ibm_apim settings config boolean property "enable_oidc_register_form" value is "true"
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | oidc | @data(user_registries[3].title) | @data(user_registries[3].url) | no           | yes     |
    When I am at "/user/register"
    Then I should see the text "Sign up with @data(user_registries[3].title)"
    And I should see the link "@data(user_registries[3].title)"
    And I should see a link with href including "/consumer-api/oauth2/authorize"
    And I should not see the text "First Name"
    And I should not see the text "Last Name"
    And I should not see the text "Consumer organization"
    And I should not see the text "Email address"
    And I should not see the text "Confirm password"
    And I should not see the text "Select a different registry"
    And I should see the text "Already have an account?"
    And I should see the link "Sign in"
    And there are no errors
    And there are no warnings
    And there are no messages

  Scenario: View the sign up form with an oidc registry with oidc form disabled
    Given I am not logged in
    And ibm_apim settings config boolean property "enable_oidc_register_form" value is "false"
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | oidc | @data(user_registries[3].title) | @data(user_registries[3].url) | no           | yes     |
    When I am at "/user/register"
    Then I should see the text "Sign up with @data(user_registries[3].title)"
    And I should see the link "@data(user_registries[3].title)"
    And I should see a link with href including "/consumer-api/oauth2/authorize"
    And I should not see the text "First Name"
    And I should not see the text "Last Name"
    And I should not see the text "Consumer organization"
    And I should not see the text "Email address"
    And I should not see the text "Confirm password"
    And I should not see the text "Select a different registry"
    And I should see the text "Already have an account?"
    And I should see the link "Sign in"
    And there are no errors
    And there are no warnings
    And there are no messages

  @api
  Scenario: Self signup form changes user registry via link
    Given the cache has been cleared
    Given I am not logged in
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
      | ldap | @data(user_registries[2].title) | @data(user_registries[2].url) | no           | no      |
    When I am at "/user/register"
    Then I should see the text "Sign up with @data(user_registries[0].title)"
    And I should see the link "@data(user_registries[2].title)"
    When I click "@data(user_registries[2].title)"
    And I should see the text "Sign up with @data(user_registries[2].title)"
    And I should see the link "@data(user_registries[0].title)"

  @api
  Scenario: Register form loads non default registry directly
    Given I am not logged in
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
      | lur  | @data(user_registries[1].title) | @data(user_registries[1].url) | yes          | no      |
    When I am at "/user/register?registry_url=@data(user_registries[1].url)"
    Then I should see the text "Sign up with @data(user_registries[1].title)"

  @api
  Scenario: Register form handles invalid user registry url query parameter by using default registry
    Given I am not logged in
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
      | ldap | @data(user_registries[2].title) | @data(user_registries[2].url) | no           | no      |
    When I am at "/user/register?registry_url=thisisnotvalid"
    Then I should see the text "Sign up with @data(user_registries[0].title)"

  @api
  Scenario: Register form handles valid but incorrect user registry url query parameter by using default registry
    Given I am not logged in
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
      | ldap | @data(user_registries[2].title) | @data(user_registries[2].url) | no           | no      |
    When I am at "/user/register?registry_url=/consumer-api/user-registries/aaaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee"
    Then I should see the text "Sign up with @data(user_registries[0].title)"

  Scenario: Sign up form with oidc registry as default
    Given I am not logged in
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | oidc | @data(user_registries[3].title) | @data(user_registries[3].url) | no           | yes     |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | no      |
    And I am at "/user/register"
    Then I should not see the "Sign up" button
    And I should see a link with href including "/consumer-api/oauth2/authorize"
    And I should not see the text "What code is in the image?"
    And I should see the text "Select a different registry"
    And I should see the link "@data(user_registries[0].title)"
    When I click "@data(user_registries[0].title)"
    Then I should see the "Sign up" button
    And I should see the text "What code is in the image?"

  Scenario: Self signup form captcha failure
    Given I am not logged in
    And I am at "/user/register"
    Given I enter "Andre" for "First Name"
    And I enter "Andreson_@now" for "Last Name"
    And I enter "andre_org_@now" for "Consumer organization"
    And I enter "andre_@now@example.com" for "Email address"
    And I enter "andre_@now@example.com" for "Username"
    And I enter "Qwert123" for "Password"
    And if the field "pass[pass2]" is present, enter the value "Qwert123"
    And if the field "captcha_response" is present, enter the value "this(almostcertainly)isnotrightapologiesifitis"
    When I press the "Sign up" button
    Then there are no messages
    And there are errors
    And there are no warnings
    And I should see the text "1 error has been found:"
    And I should see the text "What code is in the image?"
    And I should not see the text "Your account was created successfully"


  Scenario: Self signup form password policy failure - length
    Given I am not logged in
    And I am at "/user/register"
    Given I enter "Andre" for "First Name"
    And I enter "Andreson_@now" for "Last Name"
    And I enter "andre_org_@now" for "Consumer organization"
    And I enter "andre_@now@example.com" for "Email address"
    And I enter "andre_@now@example.com" for "Username"
    And I enter "a" for "Password"
    And if the field "pass[pass2]" is present, enter the value "a"
    And if the field "captcha_response" is present, enter the value "@captcha"
    When I press the "Sign up" button
    Then there are no messages
    And there are errors
    And there are no warnings
    And I should see the text "The password does not satisfy the password policies."
    And I should not see the text "Your account was created successfully"

  Scenario: Self signup form password policy failure - consecutive characters
    Given I am not logged in
    And I am at "/user/register"
    Given I enter "Andre" for "First Name"
    And I enter "Andreson_@now" for "Last Name"
    And I enter "andre_org_@now" for "Consumer organization"
    And I enter "andre_@now@example.com" for "Email address"
    And I enter "andre_@now@example.com" for "Username"
    And I enter "Qwert111" for "Password"
    And if the field "pass[pass2]" is present, enter the value "Qwert111"
    And if the field "captcha_response" is present, enter the value "@captcha"
    When I press the "Sign up" button
    Then there are no messages
    And there are errors
    And there are no warnings
    And I should see the text "The password does not satisfy the password policies."
    And I should not see the text "Your account was created successfully"

  @api
  Scenario: register second user (unique username and email address - success)
    Given I am not logged in
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
      | lur  | @data(user_registries[1].title) | @data(user_registries[1].url) | yes          | no      |
    Given users:
      | name              | mail              | pass                  | status | registry_url                  |
      | @data(andre.name) | @data(andre.mail) | @data(andre.password) | 1      | @data(user_registries[0].url) |
    And I am at "/user/register?registry_url=@data(user_registries[1].url)"
    When I enter "andre_@now" for "Username"
    And I enter "Andre" for "First Name"
    And I enter "Andreson_@now" for "Last Name"
    And I enter "andre_org_@now" for "Consumer organization"
    And I enter "andre_@now@example.com" for "Email address"
    And I enter "Qwert123" for "Password"
    And if the field "pass[pass2]" is present, enter the value "Qwert123"
    And if the field "captcha_response" is present, enter the value "@captcha"
    And I press the "Sign up" button
    Then there are no errors
    And there are messages
    And I should see the text "Your account was created successfully. You will receive an email with activation instructions."
    And there are no warnings

  @api
  Scenario: sign up with username in the same registry - fail
    Given I am not logged in
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
      | lur  | @data(user_registries[1].title) | @data(user_registries[1].url) | yes          | no      |
    Given users:
      | name                   | mail                               | pass     | status | registry_url                  |
      | andre_existingusername | andre_existingusername@example.com | Qwert123 | 1      | @data(user_registries[0].url) |
    And I am at "/user/register"
    When I enter "andre_existingusername" for "Username"
    And I enter "Andre" for "First Name"
    And I enter "Andreson_@now" for "Last Name"
    And I enter "andre_org_@now" for "Consumer organization"
    And I enter "andre_somethingelse@example.com" for "Email address"
    And I enter "Qwert123" for "Password"
    And if the field "pass[pass2]" is present, enter the value "Qwert123"
    And if the field "captcha_response" is present, enter the value "@captcha"
    And I press the "Sign up" button
    Then there are errors
    And I should see the text "A problem occurred while attempting to create your account. If you already have an account then please use that to Sign in."
    And there are no messages
    And there are no warnings

  @api
  Scenario: sign up with existing email address in same registry - fail
    Given I am not logged in
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
      | lur  | @data(user_registries[1].title) | @data(user_registries[1].url) | yes          | no      |
    Given users:
      | name                | mail                            | pass     | status | registry_url                  |
      | andre_matchingemail | andre_matchingemail@example.com | Qwert123 | 1      | @data(user_registries[0].url) |
    And I am at "/user/register"
    When I enter "andre_@now" for "Username"
    And I enter "Andre" for "First Name"
    And I enter "Andreson_@now" for "Last Name"
    And I enter "andre_org_@now" for "Consumer organization"
    And I enter "andre_matchingemail@example.com" for "Email address"
    And I enter "Qwert123" for "Password"
    And if the field "pass[pass2]" is present, enter the value "Qwert123"
    And if the field "captcha_response" is present, enter the value "@captcha"
    And I press the "Sign up" button
    Then there are errors
    And I should see the text "A problem occurred while attempting to create your account. If you already have an account then please use that to Sign in."
    And there are no messages
    And there are no warnings

  @api
  Scenario: sign up with existing email address in different registry - fail
    Given I am not logged in
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
      | lur  | @data(user_registries[1].title) | @data(user_registries[1].url) | yes          | no      |
    Given users:
      | name                | mail                            | pass     | status | registry_url                  |
      | andre_matchingemail | andre_matchingemail@example.com | Qwert123 | 1      | @data(user_registries[0].url) |
    And I am at "/user/register?registry_url=@data(user_registries[1].url)"
    When I enter "AnotherAndre" for "Username"
    And I enter "Andre" for "First Name"
    And I enter "Andreson_@now" for "Last Name"
    And I enter "andre_org_@now" for "Consumer organization"
    And I enter "andre_matchingemail@example.com" for "Email address"
    And I enter "Qwert123" for "Password"
    And if the field "pass[pass2]" is present, enter the value "Qwert123"
    And if the field "captcha_response" is present, enter the value "@captcha"
    And I press the "Sign up" button
    Then there are errors
    And I should see the text "A problem occurred while attempting to create your account. If you already have an account then please use that to Sign in."
    And there are no messages
    And there are no warnings

  @api
  Scenario: sign up with same username in different registry - success.
    Given I am not logged in
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
      | lur  | @data(user_registries[1].title) | @data(user_registries[1].url) | yes          | no      |
    Given users:
      | name                | mail                       | pass     | status | registry_url                  |
      | andre_multiple_@now | andre_fromreg1@example.com | Qwert123 | 1      | @data(user_registries[0].url) |
    And I am at "/user/register?registry_url=@data(user_registries[1].url)"
    When I enter "andre_multiple_@now" for "Username"
    And I enter "Andre" for "First Name"
    And I enter "Andreson_@now" for "Last Name"
    And I enter "andre_org_@now" for "Consumer organization"
    And I enter "andre_fromreg2@example.com " for "Email address"
    And I enter "Qwert123" for "Password"
    And if the field "pass[pass2]" is present, enter the value "Qwert123"
    And if the field "captcha_response" is present, enter the value "@captcha"
    And I press the "Sign up" button
    Then there are no errors
    And there are messages
    And I should see the text "Your account was created successfully. You will receive an email with activation instructions."
    And there are no warnings


  Scenario: View the sign up form with a writable ldap registry
    Given I am not logged in
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | no      |
      | ldap | @data(user_registries[2].title) | @data(user_registries[2].url) | yes          | yes     |
    And I am at "/user/register"
    Then I should see the text "Sign up with @data(user_registries[2].title)"
    And I should see the text "Sign Up"
    And I should see the text "Username"
    And I should see the text "Email address"
    And I should see the text "First Name"
    And I should see the text "Last Name"
    And I should see the text "Consumer organization"
    And I should see the text "Password"
    And I should see the text "Confirm password"
    And I should see the text "What code is in the image?"
    And I should see the button "Sign up"
    #And there are no errors - can't use this step as password policy box is treated as an error!
    And there are no warnings
    And there are no messages


  Scenario: Correctly completing the registration form with writable ldap
    Given I am not logged in
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | ldap | @data(user_registries[2].title) | @data(user_registries[2].url) | yes          | yes     |
    And I am at "/user/register"
    Then I should see the text "Sign up with @data(user_registries[2].title)"
    Given I enter "Andre" for "First Name"
    And I enter "Andreson_@now" for "Last Name"
    And I enter "andre_org_@now" for "Consumer organization"
    And I enter "andre_@now@example.com" for "Email address"
    And I enter "andre_@now@example.com" for "Username"
    And I enter "Qwert123" for "Password"
    And if the field "pass[pass2]" is present, enter the value "Qwert123"
    And if the field "captcha_response" is present, enter the value "@captcha"
    When I press the "Sign up" button
    Then there are no errors
    And there are no warnings
    And there are messages
    And I should not see the text "There was an error creating your account"
    And I should see the text "Your account was created successfully"
