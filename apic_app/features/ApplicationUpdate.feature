Feature: ApplicationUpdate
  In order to use the developer portal
  I need to be able to edit existing applications

  # needs to run in zombie driver cos goutte seems to cache the webpage meaning the updated node is not shown

  @api
  Scenario: Edit application name
    Given I am not logged in
    Given users:
      | name  | pass     | mail              | status |
      | Andre | Qwert123 | andre@example.com | 1      |
    Given consumerorgs:
      | title       | name        | id     | owner |
      | andreconsumerorg | andreconsumerorg | 123456 | Andre |
    Given I am logged in as "Andre"
    Given I do not have any applications
    Given I am at "/application/new"
    And I should see a "#edit-title-0-value" element
    And I should see a "#edit-apic-summary-0-value" element
    And I should see a "#edit-application-redirect-endpoints-0-value" element
    And I should see a "#edit-submit" element
    Given I enter "app23456" for "edit-title-0-value"
    And I enter "this is some text" for "edit-apic-summary-0-value"
    When I press the "Submit" button
    When I press the "Continue" button
    When I click on element ".applicationMenu .editApplication a"
    Then I should see a "#edit-title-0-value" element
    And I should see a "#edit-apic-summary-0-value" element
    And I should see a "#edit-application-redirect-endpoints-0-value" element
    And I should see a "#edit-submit" element
    Given I enter "newapp23456" for "edit-title-0-value"
    And I enter "this is some new text" for "edit-apic-summary-0-value"
    When I press the "Submit" button
    Given I am not logged in
    Given I am logged in as "Andre"
    Given I am at "/application"
    And I should see "newapp23456"
    And I should see "this is some new text"
    And I do not have any applications
