Feature: Self Sign-up
  In order to use the developer portal
  As an anonymous user
  And given that self-sign up is enabled
  I need to be able to sign-up for an account

  Scenario: Viewing the registration form
    Given I am not logged in
    And I am at "/user/register"
    Then I should see the text "Create new account"
    And I should see the text "First Name"
    And I should see the text "Last Name"
    And I should see the text "Consumer organization"
    And I should see the text "Email address"
    And I should see the text "Password"

  Scenario: Correctly completing the registration form
    Given I am not logged in
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
    Then there are no errors
    And there are no warnings
    And there are messages
    And I should not see the text "There was an error creating your account"
    And I should see the text "Your account was created successfully"

  Scenario: Trying to sign up with the same email address twice
    Given I am not logged in
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
    And I should see the text "A problem occurred while attempting to create your account. If you already have an account then please use that to login."
