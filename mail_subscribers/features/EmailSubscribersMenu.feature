@api
Feature: Email Subscribers Menu
  In order to access the Email Subscribers functionality
  As an administrator
  I need to see the Email Subscribers menu item only under the People menu

  Scenario: Email Subscribers menu appears only under People menu
    Given I am logged in as a user with the "Administrator" role
    When I am at "/admin/people"
    Then I should see the link "Email subscribers"

  Scenario: Email Subscribers page is accessible from People menu
    Given I am logged in as a user with the "Administrator" role
    When I am at "/admin/people"
    And I click "Email subscribers"
    Then the response status code should be 200
    And I should see the text "Email subscribers"