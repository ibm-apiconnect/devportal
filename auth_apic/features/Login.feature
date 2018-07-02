Feature: Login
  In order to use the developer portal
  I need to be able to Sign in

@fullstack
  Scenario: Viewing the login page
    Given I am not logged in
    And I am at "/user/login"
    Then I should see the text "Sign in"
    And I should see the text "Create new account"
    And I should see the text "Reset your password"
    And I should see the text "Username"
    And I should see the text "Password"

@api
@fullstack
  Scenario: Sign in as a non-admin user
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
    Given I am logged in as "@data(andre.mail)"
    Then I should not see the text "Unrecognized username or password"
    And I should not see the text "sign in"
    Given the apim user "@data(andre.mail)" is logged in
    Given I am at "/user/logout"
    Then I should see the text "Sign in"

@api
@fullstack
  Scenario: Attempt Sign in with incorrect password
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
    When I am at "/user/login"
    Given I enter "@data(andre.mail)" for "Username"
    And I enter "invalidPassword" for "Password"
    When I press the "Sign in" button
    Then there are errors
    And I should see the text "Unrecognized username or password"
    And the apim user "@data(andre.mail)" is not logged in
