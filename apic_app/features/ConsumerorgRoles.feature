Feature: ConsumerorgRoles
  In order to use the developer portal
  I need to be sure that consumer organization roles provide the correct level of access

  # needs to run in zombie driver cos goutte seems to cache the webpage meaning the updated node is not shown

  @api
  Scenario: Add application credentials
    Given I am not logged in
    Given users:
      | name  | pass     | mail              | status |
      | Andre | Qwert123 | andre@example.com | 1      |
      | Dave  | Qwert123 | dave@example.com  | 1      |
      | Vicky | Qwert123 | vicky@example.com | 1      |
    Given consumerorgs:
      | title       | name        | id     | owner |
      | andreconsumerorg | andreconsumerorg | 123456 | Andre |
    Given members:
      | consumerorgid | username | roles     |
      | 123456        | Dave     | developer |
      | 123456        | Vicky    | viewer    |
    Given I am logged in as "Andre"
    Then print the current consumerorg
    Given I do not have any applications
    Given I am at "/application/new"
    And I should see a "#edit-title-0-value" element
    And I should see a "#edit-apic-summary-0-value" element
    And I should see a "#edit-application-redirect-endpoints-0-value" element
    And I should see a "#edit-submit" element
    Given I enter "app34567" for "edit-title-0-value"
    And I enter "this is some text" for "edit-apic-summary-0-value"
    When I press the "Submit" button
    When I press the "Continue" button
    Given I am not logged in
    # check dave developer can access edit links
    Given I am logged in as "Dave"
    Then print the current consumerorg
    Given I am at "/application"
    And I should see "app34567"
    When I click "app34567"
    When I click on element ".applicationMenu .editApplication a"
    Then I should see a "#edit-title-0-value" element
    Given I am not logged in
    # check vicky viewer cannot access edit links
    Given I am logged in as "Vicky"
    Then print the current consumerorg
    Given I am at "/application"
    And I should see "app34567"
    # Intentional use of "Given I am on" as opposed to "Given I am at" as it skips the HTTP code check
    Given I am on "/application/34567/edit"
    Then I should see "Access denied"
    Given I am on "/application/new"
    Then I should see "Access denied"
    And I do not have any applications
