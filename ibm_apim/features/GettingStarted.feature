Feature: Getting started page
  Check the first time login page looks correct
  This is a duplicate test to the ones in auth_apic but put here for early alerting if it breaks
  If this test fails then the ones in auth_apic will too!

  @api
  Scenario: Sign in for the first time
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status | first_time_login |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      | 1                |
    Given consumerorgs:
      | title                          | name                          | id                          | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
    When I am at "/user/login"
    Given I enter "@data(andre.mail)" for "Username"
    And I enter "@data(andre.password)" for "Password"
    When I press the "Sign in" button
    Then I should be on "/start"
    And I should see the text "let's get started"
    And I should see the text "Explore API products"
    And I should see the text "Create a new app"
    And I should see the link "Take me to the homepage"
    Then there are no errors
    And there are no messages
    And there are no warnings

