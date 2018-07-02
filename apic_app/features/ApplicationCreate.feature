Feature: ApplicationCreate
  In order to use the developer portal
  I need to be able to register applications

  @api
  Scenario: Create application (green path)
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
    Given I enter "app12345" for "edit-title-0-value"
    And I enter "this is some text" for "edit-apic-summary-0-value"
    When I press the "Submit" button
    Then there are no errors
    And I should see the text "API Key and Secret"
    And I should see a ".appID" element
    And I should see a ".appSecret" element
    When I press the "Continue" button
    Then I should see the text "app12345"
