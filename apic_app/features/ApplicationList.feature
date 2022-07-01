Feature: ApplicationList
  In order to use the developer portal
  I need to see a list of my applications

  @mocked
  @api
  Scenario: Empty application list
    Given I am not logged in
    Given users:
      | name  | pass     | mail              | status |
      | Andre | Qwert123IsBadPassword! | andre@example.com | 1      |
    Given consumerorgs:
      | title            | name             | id     | owner |
      | andreconsumerorg | andreconsumerorg | 123456 | Andre |
    Given I am logged in as "Andre"
    Given I do not have any applications
    Given I am at "/application"
    #Then I should see the text "No Applications found."
    And I should see a "#edit-title" element
    And I should see a "#edit-sort-bef-combine" element
    And I should see a "#edit-submit-applications" element
    And I should see a ".button.apicNewApp" element
