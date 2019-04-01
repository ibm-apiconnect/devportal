Feature: My Organization
  In order to use the developer portal
  I need to be able to view my organization page

  @api
  Scenario: Sign in as an organization owner and view my organization page with no members
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
    Given I am logged in as "@data(andre.mail)"
    And I am at "/myorg"
    # First two text items are hidden in an options menu
    Then I should see the text "Edit organization name"
    And I should see the text "Delete organization"
    And I should see the text "Members"
    And I should see the text "Invite"
    And I should see the text "NAME"
    And I should see the text "STATE"
    And I should see the text "Members will be listed here"
    And there are no errors

  @api
  Scenario: Sign in as an org owner, go to My org page, click Edit org name, and be taken to myorg/edit form
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
    Given I am logged in as "@data(andre.mail)"
    And I am at "/myorg"
    When I click "Edit organization name"
    Then I should be on "/myorg/edit"
    And there are no errors
    And there are no messages

  @api
  Scenario: Sign in as an org owner, go to My org page, click Delete org, and be taken to myorg/delete form
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
    Given I am logged in as "@data(andre.mail)"
    And I am at "/myorg"
    When I click "Delete organization"
    Then I should be on "/myorg/delete"
    # There will be an error on this page if the user is not a member of any other organizations

  @api
  Scenario: Sign in as an org owner, go to My org page, click Invite, and be taken to myorg/invite form
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
    Given I am logged in as "@data(andre.mail)"
    And I am at "/myorg"
    When I click "Invite"
    Then I should be on "/myorg/invite"
    And there are no errors
    And there are no messages
