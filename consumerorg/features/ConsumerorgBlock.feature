Feature: Consumer Organization Block
  In order to use the developer portal
  the Con Org drop-down menu must display the correct options for my role

  @api
  # Note that this a negative test for an admin user (uid=1) that should not see a con org menu
  Scenario: Sign in as the admin user (uid==1) and verify that the con org menu is not visible
    Given users:
      | name              | mail              | pass                  | status |
      | @data(admin.name) | @data(admin.mail) | @data(admin.password) | 1      |
    Given I am logged in as "@data(admin.name)"
    Given I am on the homepage
    Then I should see the text "admin"
#    I cannot check for not seeing text "Organization" because there is an admin menu option of "Consumer organization"
    And I should not see the text "My organization"
    And I should not see the text "Create organization"
    And I should not see the text "Select organization"
    And there are no errors

  @api
  Scenario: Sign in as a con org owner and verify that the con org menu options are correct
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
    Given I am logged in as "@data(andre.mail)"
    Given I am on the homepage
    Then I should see the text "@data(andre.consumerorg.title)"
    And I should see the text "My organization"
    And I should see the text "Create organization"
    And I should see the text "Select organization"
    And there are no errors

  @api
  Scenario: Sign in as a con org owner of multiple orgs, and verify that the con org menu options are correct
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre[0].mail) | @data(andre[0].mail) | @data(andre[0].password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre[0].consumerorg.title) | @data(andre[0].consumerorg.name) | @data(andre[0].consumerorg.id) | @data(andre[0].mail) |
      | @data(andre[1].consumerorg.title) | @data(andre[1].consumerorg.name) | @data(andre[1].consumerorg.id) | @data(andre[0].mail) |
    Given I am logged in as "@data(andre[0].mail)"
    Given I am on the homepage
    Then I should see the text "@data(andre[0].consumerorg.title)"
    And I should see the text "@data(andre[1].consumerorg.title)"
    And I should see the text "My organization"
    And I should see the text "Create organization"
    And I should see the text "Select organization"
    And there are no errors
