Feature: Node Controller
  Check the add node page renders correctly

  # @api
  # Scenario: Sign in as admin and check the node/add page renders correctly
  #   Given I am not logged in
  #   Given users:
  #     | name                 | mail                 | pass                     | status |
  #     | @data(admin.name)    | @data(admin.mail)    | @data(admin.password)    | 1      |
  #   Given I am logged in as "@data(admin.name)"
  #   And I am at "/node/add"
  #   Then there are no errors
  #   And I should see the text "Article"
  #   And I should see the text "Basic page"
  #   When I am at "/admin/workbench/create"
  #   Then there are no errors
  #   And I should see the text "Article"
  #   And I should see the text "Basic page"
