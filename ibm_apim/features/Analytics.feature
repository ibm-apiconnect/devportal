@api
Feature: My Organization Analytics
  In order to use the developer portal
  I need to be able to view my organization analytics page

  Scenario: Sign in as an organization owner and view my organization analytics page with no errors
    Given I have an analytics service
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
    Given I am logged in as "@data(andre.mail)"
    Then I am at "/myorg/analytics"
    And there are no errors