@api
Feature: Contextual Delete Link
  Verify that contextual delete links do not include destination parameter pointing to deleted content

  Scenario: Verify contextual delete link JavaScript library is loaded
    Given I am not logged in
    Given users:
      | name                 | mail                 | pass                     | status |
      | @data(admin.name)    | @data(admin.mail)    | @data(admin.password)    | 1      |
    Given I am logged in as "@data(admin.name)"
    And I am at "/node/add/page"
    And I fill in "Title" with "Test Page for Contextual Delete"
    And I press "Save"
    Then I should see the text "Test Page for Contextual Delete"
    And there are no errors
    And the contextual links fix library should be attached