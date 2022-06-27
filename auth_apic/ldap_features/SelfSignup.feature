Feature: Self Sign-up LDAP
  In order to use the developer portal using an LDAP registry
  As an anonymous user
  And given that self-sign up is enabled
  I need to be able to sign-up for an account

  Scenario: Viewing the registration form
    Given I am not logged in
    And I am at "/user/register"
    Then I should see the text "Create new account"
    And I should not see the text "First Name"
    And I should not see the text "Last Name"
    And I should see the text "Consumer organization"
    And I should not see the text "Email address"
    And I should see the text "Username"
    And I should see the text "Password"

  Scenario: Correctly completing the registration form
    Given I am not logged in
    And I am at "/user/register"
    Then print current URL
    And I enter "ldapandre_org_@now" for "Consumer organization"
    And I enter "ldapandre_@now@example.com" for "Username"
    And I enter "Qwert123IsBadPassword!" for "Password"
    And if the field "captcha_response" is present, enter the value "@captcha"
    When I press the "Create new account" button
    Then there are no errors
    And there are no warnings
    And there are messages
    And I should not see the text "There was an error creating your account"
    And I should see the text "Your account was created successfully. Please login to continue."