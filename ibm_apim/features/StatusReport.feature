Feature: System Status Report
  Check the status report page renders correctly

  @api
  Scenario: Sign in as admin and check the report renders correctly
    Given I am not logged in
    Given users:
      | name                 | mail                 | pass                     | status |
      | @data(admin.name)    | @data(admin.mail)    | @data(admin.password)    | 1      |
    Given I am logged in as "@data(admin.name)"
    And I am at "/admin/reports/status"
    Then there are no errors
    And I should see the text "IBM API Connect Version"
    And I should see the text "API Explorer"
    And I should see the text "Custom Modules"
    And I should see the text "Custom Themes"
    And I should see the text "Custom User Fields"
    And I should see the text "Custom Node Fields"