Feature: Create consumer organization
  Depending on my role, I am able to create an organization.

  @api
  Scenario: As Andre I can view the create new consumer organization form
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.name) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
    And I am logged in as "@data(andre.name)"
    And I am at "/myorg/create"
    #Should I also be testing the action to click on "Create organization"?
    Then I should see the text "Create new consumer organization"
    And I should see the text "Organization title"
    And I should see the text "Cancel"
    And I should see the text "Submit"

  @api
  Scenario: As Andre I can create a new consumer organization
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.name) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
    And I am logged in as "@data(andre.name)"
    And I am at "/myorg/create"
    When I enter "New org1" for "title[0][value]"
    And I press the "Submit" button
    Then I should be on the homepage
    And there are messages
    And I should see the text "Consumer organization created successfully."
    And there are no errors

  @api
  # Note that this a negative test for an admin user (uid=1) that manually sets their url to the /myorg/create page
  Scenario: Sign in as the admin user (uid==1) and try to view the My Organization page
    Given users:
      | name              | mail              | pass                  | status |
      | @data(admin.name) | @data(admin.mail) | @data(admin.password) | 1      |
    Given I am logged in as "@data(admin.name)"
    When I go to "/myorg/create"
    Then I should get a "403" HTTP response
    And I should see the text "Access denied"
    And I should see the text "You are not authorized to access this page."
    And there are no errors
