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
    Then I should see the text "Edit organization"
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
    When I click "Edit organization"
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


  @api
  # Note that this a negative test for an admin user (uid=1) that manually sets their url to the /myorg page
  Scenario: Sign in as the admin user (uid==1) and try to view the My Organization page
    Given users:
      | name              | mail              | pass                  | status |
      | @data(admin.name) | @data(admin.mail) | @data(admin.password) | 1      |
    Given I am logged in as "@data(admin.name)"
    When I go to "/myorg"
    Then I should get a "403" HTTP response
    And I should see the text "Access denied"
    And I should see the text "You are not authorized to access this page."
    And there are no errors

  @api
  Scenario: As the org owner, I can view multiple members with different roles correctly in the My Organization page
    Given users:
      | name                 | mail                 | pass                     | status |
      | @data(andre[0].mail) | @data(andre[0].mail) | @data(andre[0].password) | 1      |
      | @data(andre[1].mail) | @data(andre[1].mail) | @data(andre[1].password) | 1      |
      | @data(andre[2].mail) | @data(andre[2].mail) | @data(andre[2].password) | 1      |
      | @data(andre[3].mail) | @data(andre[3].mail) | @data(andre[3].password) | 1      |
      | @data(andre[4].mail) | @data(andre[4].mail) | @data(andre[4].password) | 1      |
    Given consumerorgs:
      | title                             | name                             | id                             | owner                |
      | @data(andre[0].consumerorg.title) | @data(andre[0].consumerorg.name) | @data(andre[0].consumerorg.id) | @data(andre[0].mail) |
    Given members:
      | consumerorgid                  | username             | roles                   |
      | @data(andre[0].consumerorg.id) | @data(andre[1].mail) | administrator           |
      | @data(andre[0].consumerorg.id) | @data(andre[2].mail) | administrator,developer |
      | @data(andre[0].consumerorg.id) | @data(andre[3].mail) | viewer                  |
      | @data(andre[0].consumerorg.id) | @data(andre[4].mail) | developer               |
    Given I am logged in as "@data(andre[0].mail)"
    When I go to "/myorg"
    Then I should see the text "@data(andre[1].mail)"
    Then I should see that "@data(andre[1].mail)" is an "administrator"
    Then I should not see that "@data(andre[1].mail)" is a "developer"
    Then I should not see that "@data(andre[1].mail)" is a "viewer"
    Then I should see the text "@data(andre[2].mail)"
    Then I should see that "@data(andre[2].mail)" is an "administrator"
    Then I should see that "@data(andre[2].mail)" is an "developer"
    Then I should not see that "@data(andre[2].mail)" is a "viewer"
    Then I should see the text "@data(andre[3].mail)"
    Then I should see that "@data(andre[3].mail)" is an "viewer"
    Then I should not see that "@data(andre[3].mail)" is an "administrator"
    Then I should not see that "@data(andre[3].mail)" is a "developer"
    Then I should see the text "@data(andre[4].mail)"
    Then I should see that "@data(andre[4].mail)" is an "developer"
    Then I should not see that "@data(andre[4].mail)" is an "administrator"
    Then I should not see that "@data(andre[4].mail)" is a "viewer"
    Then I should see the text "Change role"
    Then I should see the text "Delete member"
    And there are no errors
    And there are no messages

  @api
  Scenario: As the org owner, I can view one member correctly in the My Organization page
    Given users:
      | name                 | mail                 | pass                     | status |
      | @data(andre[0].mail) | @data(andre[0].mail) | @data(andre[0].password) | 1      |
      | @data(andre[1].mail) | @data(andre[1].mail) | @data(andre[1].password) | 1      |
    Given consumerorgs:
      | title                             | name                             | id                             | owner                |
      | @data(andre[0].consumerorg.title) | @data(andre[0].consumerorg.name) | @data(andre[0].consumerorg.id) | @data(andre[0].mail) |
    Given members:
      | consumerorgid                  | username             | roles                   |
      | @data(andre[0].consumerorg.id) | @data(andre[1].mail) | administrator           |
    Given I am logged in as "@data(andre[0].mail)"
    When I go to "/myorg"
    Then I should see the text "@data(andre[1].mail)"
    Then I should see that "@data(andre[1].mail)" is an "administrator"
    Then I should see the text "Change role"
    Then I should see the text "Delete member"
    And there are no errors
    And there are no messages

  @api
  Scenario: As an org member, role=administrator, I can view the My Org page, and see correct text options
    Given users:
      | name                 | mail                 | pass                     | status |
      | @data(andre[0].mail) | @data(andre[0].mail) | @data(andre[0].password) | 1      |
      | @data(andre[1].mail) | @data(andre[1].mail) | @data(andre[1].password) | 1      |
    Given consumerorgs:
      | title                             | name                             | id                             | owner                |
      | @data(andre[0].consumerorg.title) | @data(andre[0].consumerorg.name) | @data(andre[0].consumerorg.id) | @data(andre[0].mail) |
    Given members:
      | consumerorgid                  | username             | roles                   |
      | @data(andre[0].consumerorg.id) | @data(andre[1].mail) | administrator           |
    Given I am logged in as "@data(andre[1].mail)"
    When I go to "/myorg"
    Then I should see the text "@data(andre[0].consumerorg.title)"
    Then I should see the text "@data(andre[0].mail)"
    Then I should see the text "@data(andre[1].mail)"
    Then I should see that "@data(andre[1].mail)" is an "administrator"
    Then I should see the text "Change role"
    Then I should see the text "Delete member"
    And there are no errors
    And there are no messages

  @api
  Scenario: As an org member, role=developer, I can view the My Org page, and see correct text options
    Given users:
      | name                 | mail                 | pass                     | status |
      | @data(andre[0].mail) | @data(andre[0].mail) | @data(andre[0].password) | 1      |
      | @data(andre[4].mail) | @data(andre[4].mail) | @data(andre[4].password) | 1      |
    Given consumerorgs:
      | title                             | name                             | id                             | owner                |
      | @data(andre[0].consumerorg.title) | @data(andre[0].consumerorg.name) | @data(andre[0].consumerorg.id) | @data(andre[0].mail) |
    Given members:
      | consumerorgid                  | username             | roles                   |
      | @data(andre[0].consumerorg.id) | @data(andre[4].mail) | developer               |
    Given I am logged in as "@data(andre[4].mail)"
    When I go to "/myorg"
    Then I should see the text "@data(andre[0].consumerorg.title)"
    Then I should see the text "@data(andre[0].mail)"
    Then I should see the text "@data(andre[4].mail)"
    Then I should see that "@data(andre[4].mail)" is an "developer"
    Then I should not see the text "Change role"
    Then I should not see the text "Delete member"
    And there are no errors
    And there are no messages

  @api
  Scenario: As an org member, role=viewer, I can view the My Org page, and see correct text options
    Given users:
      | name                 | mail                 | pass                     | status |
      | @data(andre[0].mail) | @data(andre[0].mail) | @data(andre[0].password) | 1      |
      | @data(andre[3].mail) | @data(andre[3].mail) | @data(andre[3].password) | 1      |
    Given consumerorgs:
      | title                             | name                             | id                             | owner                |
      | @data(andre[0].consumerorg.title) | @data(andre[0].consumerorg.name) | @data(andre[0].consumerorg.id) | @data(andre[0].mail) |
    Given members:
      | consumerorgid                  | username             | roles                   |
      | @data(andre[0].consumerorg.id) | @data(andre[3].mail) | viewer                  |
    Given I am logged in as "@data(andre[3].mail)"
    When I go to "/myorg"
    Then I should see the text "@data(andre[0].consumerorg.title)"
    Then I should see the text "@data(andre[0].mail)"
    Then I should see the text "@data(andre[3].mail)"
    Then I should see that "@data(andre[3].mail)" is an "viewer"
    Then I should not see the text "Change role"
    Then I should not see the text "Delete member"
    And there are no errors
    And there are no messages


  @api
  Scenario: As an org owner, I see correct text options for a pending administrator invitation on the My Org page
    Given users:
      | name                 | mail                 | pass                     | status |
      | @data(andre[0].mail) | @data(andre[0].mail) | @data(andre[0].password) | 1      |
    Given consumerorgs:
      | title                             | name                             | id                             | owner                |
      | @data(andre[0].consumerorg.title) | @data(andre[0].consumerorg.name) | @data(andre[0].consumerorg.id) | @data(andre[0].mail) |
    Given invitations:
      | consumerorgid                  | mail                 | roles          |
      | @data(andre[0].consumerorg.id) | @data(andre[1].mail) | administrator  |
    Given I am logged in as "@data(andre[0].mail)"
    When I go to "/myorg"
    Then I should see the text "@data(andre[0].consumerorg.title)"
    Then I should see the text "@data(andre[0].mail)"
    Then I should see the text "@data(andre[1].mail)"
    Then I should see that "@data(andre[1].mail)" is an "administrator"
    Then I should see the text "Pending"
    Then I should see the text "Resend invitation"
    Then I should see the text "Delete invitation"
    Then I should not see the text "Change role"
    Then I should not see the text "Delete member"
# I'm not checking for the success message, as this is part of the invitation flow & tested separately
    And there are no errors

  @api
  Scenario: As an org owner, I see correct text options for a pending developer invitation on the My Org page
    Given users:
      | name                 | mail                 | pass                     | status |
      | @data(andre[0].mail) | @data(andre[0].mail) | @data(andre[0].password) | 1      |
    Given consumerorgs:
      | title                             | name                             | id                             | owner                |
      | @data(andre[0].consumerorg.title) | @data(andre[0].consumerorg.name) | @data(andre[0].consumerorg.id) | @data(andre[0].mail) |
    Given invitations:
      | consumerorgid                  | mail                 | roles          |
      | @data(andre[0].consumerorg.id) | @data(andre[2].mail) | developer      |
    Given I am logged in as "@data(andre[0].mail)"
    When I go to "/myorg"
    Then I should see the text "@data(andre[0].consumerorg.title)"
    Then I should see the text "@data(andre[0].mail)"
    Then I should see the text "@data(andre[2].mail)"
    Then I should see that "@data(andre[2].mail)" is a "developer"
    Then I should see the text "Pending"
    Then I should see the text "Resend invitation"
    Then I should see the text "Delete invitation"
    Then I should not see the text "Change role"
    Then I should not see the text "Delete member"
    And there are no errors

  @api
  Scenario: As an org owner, I see correct text options for a pending viewer invitation on the My Org page
    Given users:
      | name                 | mail                 | pass                     | status |
      | @data(andre[0].mail) | @data(andre[0].mail) | @data(andre[0].password) | 1      |
    Given consumerorgs:
      | title                             | name                             | id                             | owner                |
      | @data(andre[0].consumerorg.title) | @data(andre[0].consumerorg.name) | @data(andre[0].consumerorg.id) | @data(andre[0].mail) |
    Given invitations:
      | consumerorgid                  | mail                 | roles          |
      | @data(andre[0].consumerorg.id) | @data(andre[3].mail) | viewer         |
    Given I am logged in as "@data(andre[0].mail)"
    When I go to "/myorg"
    Then I should see the text "@data(andre[0].consumerorg.title)"
    Then I should see the text "@data(andre[0].mail)"
    Then I should see the text "@data(andre[3].mail)"
    Then I should see that "@data(andre[3].mail)" is a "viewer"
    Then I should see the text "Pending"
    Then I should see the text "Resend invitation"
    Then I should see the text "Delete invitation"
    Then I should not see the text "Change role"
    Then I should not see the text "Delete member"
    And there are no errors


  @api
  Scenario: As an org member, role=administrator, I can click Invite and be taken to the myorg/invite form
    Given users:
      | name                 | mail                 | pass                     | status |
      | @data(andre[0].mail) | @data(andre[0].mail) | @data(andre[0].password) | 1      |
      | @data(andre[1].mail) | @data(andre[1].mail) | @data(andre[1].password) | 1      |
    Given consumerorgs:
      | title                             | name                             | id                             | owner                |
      | @data(andre[0].consumerorg.title) | @data(andre[0].consumerorg.name) | @data(andre[0].consumerorg.id) | @data(andre[0].mail) |
    Given members:
      | consumerorgid                  | username             | roles                   |
      | @data(andre[0].consumerorg.id) | @data(andre[1].mail) | administrator           |
    Given I am logged in as "@data(andre[1].mail)"
    And I am at "/myorg"
    Then I should see the text "Invite"
    When I click "Invite"
    Then I should be on "/myorg/invite"
    And there are no errors
    And there are no messages

  @api
  Scenario: As an org member, role=developer, I cannot invite new members
    Given users:
      | name                 | mail                 | pass                     | status |
      | @data(andre[0].mail) | @data(andre[0].mail) | @data(andre[0].password) | 1      |
      | @data(andre[4].mail) | @data(andre[4].mail) | @data(andre[4].password) | 1      |
    Given consumerorgs:
      | title                             | name                             | id                             | owner                |
      | @data(andre[0].consumerorg.title) | @data(andre[0].consumerorg.name) | @data(andre[0].consumerorg.id) | @data(andre[0].mail) |
    Given members:
      | consumerorgid                  | username             | roles                   |
      | @data(andre[0].consumerorg.id) | @data(andre[4].mail) | developer               |
    Given I am logged in as "@data(andre[4].mail)"
    And I am at "/myorg"
    Then I should not see the text "Invite"
    When I go to "/myorg/invite"
    Then I should see the text "Access denied"
    And I should see the text "You are not authorized to access this page."

  @api
  Scenario: As an org member, role=viewer, I cannot invite new members
    Given users:
      | name                 | mail                 | pass                     | status |
      | @data(andre[0].mail) | @data(andre[0].mail) | @data(andre[0].password) | 1      |
      | @data(andre[3].mail) | @data(andre[3].mail) | @data(andre[3].password) | 1      |
    Given consumerorgs:
      | title                             | name                             | id                             | owner                |
      | @data(andre[0].consumerorg.title) | @data(andre[0].consumerorg.name) | @data(andre[0].consumerorg.id) | @data(andre[0].mail) |
    Given members:
      | consumerorgid                  | username             | roles                   |
      | @data(andre[0].consumerorg.id) | @data(andre[3].mail) | viewer                  |
    Given I am logged in as "@data(andre[3].mail)"
    And I am at "/myorg"
    Then I should not see the text "Invite"
    When I go to "/myorg/invite"
    Then I should see the text "Access denied"
    And I should see the text "You are not authorized to access this page."

  @api
  Scenario: As the org owner, with multiple members with different roles, I can click Invite and be taken to the myorg/invite form
    Given users:
      | name                 | mail                 | pass                     | status |
      | @data(andre[0].mail) | @data(andre[0].mail) | @data(andre[0].password) | 1      |
      | @data(andre[1].mail) | @data(andre[1].mail) | @data(andre[1].password) | 1      |
      | @data(andre[2].mail) | @data(andre[2].mail) | @data(andre[2].password) | 1      |
      | @data(andre[3].mail) | @data(andre[3].mail) | @data(andre[3].password) | 1      |
      | @data(andre[4].mail) | @data(andre[4].mail) | @data(andre[4].password) | 1      |
    Given consumerorgs:
      | title                             | name                             | id                             | owner                |
      | @data(andre[0].consumerorg.title) | @data(andre[0].consumerorg.name) | @data(andre[0].consumerorg.id) | @data(andre[0].mail) |
    Given members:
      | consumerorgid                  | username             | roles                   |
      | @data(andre[0].consumerorg.id) | @data(andre[1].mail) | administrator           |
      | @data(andre[0].consumerorg.id) | @data(andre[2].mail) | administrator,developer |
      | @data(andre[0].consumerorg.id) | @data(andre[3].mail) | viewer                  |
      | @data(andre[0].consumerorg.id) | @data(andre[4].mail) | developer               |
    Given I am logged in as "@data(andre[0].mail)"
    And I am at "/myorg"
    Then I should see the text "Invite"
    When I click "Invite"
    Then I should be on "/myorg/invite"
    And there are no errors
    And there are no messages

  @api
  Scenario: As an admin member of an org with multiple members with different roles, I can click Invite and be taken to the myorg/invite form
    Given users:
      | name                 | mail                 | pass                     | status |
      | @data(andre[0].mail) | @data(andre[0].mail) | @data(andre[0].password) | 1      |
      | @data(andre[1].mail) | @data(andre[1].mail) | @data(andre[1].password) | 1      |
      | @data(andre[2].mail) | @data(andre[2].mail) | @data(andre[2].password) | 1      |
      | @data(andre[3].mail) | @data(andre[3].mail) | @data(andre[3].password) | 1      |
      | @data(andre[4].mail) | @data(andre[4].mail) | @data(andre[4].password) | 1      |
    Given consumerorgs:
      | title                             | name                             | id                             | owner                |
      | @data(andre[0].consumerorg.title) | @data(andre[0].consumerorg.name) | @data(andre[0].consumerorg.id) | @data(andre[0].mail) |
    Given members:
      | consumerorgid                  | username             | roles                   |
      | @data(andre[0].consumerorg.id) | @data(andre[1].mail) | administrator           |
      | @data(andre[0].consumerorg.id) | @data(andre[2].mail) | administrator,developer |
      | @data(andre[0].consumerorg.id) | @data(andre[3].mail) | viewer                  |
      | @data(andre[0].consumerorg.id) | @data(andre[4].mail) | developer               |
    Given I am logged in as "@data(andre[2].mail)"
    And I am at "/myorg"
    Then I should see the text "Invite"
    When I click "Invite"
    Then I should be on "/myorg/invite"
    And there are no errors
    And there are no messages

  @api
  Scenario: As a developer member of an org with multiple members with different roles, I cannot invite new members
    Given users:
      | name                 | mail                 | pass                     | status |
      | @data(andre[0].mail) | @data(andre[0].mail) | @data(andre[0].password) | 1      |
      | @data(andre[1].mail) | @data(andre[1].mail) | @data(andre[1].password) | 1      |
      | @data(andre[2].mail) | @data(andre[2].mail) | @data(andre[2].password) | 1      |
      | @data(andre[3].mail) | @data(andre[3].mail) | @data(andre[3].password) | 1      |
      | @data(andre[4].mail) | @data(andre[4].mail) | @data(andre[4].password) | 1      |
    Given consumerorgs:
      | title                             | name                             | id                             | owner                |
      | @data(andre[0].consumerorg.title) | @data(andre[0].consumerorg.name) | @data(andre[0].consumerorg.id) | @data(andre[0].mail) |
    Given members:
      | consumerorgid                  | username             | roles                   |
      | @data(andre[0].consumerorg.id) | @data(andre[1].mail) | administrator           |
      | @data(andre[0].consumerorg.id) | @data(andre[2].mail) | administrator,developer |
      | @data(andre[0].consumerorg.id) | @data(andre[3].mail) | viewer                  |
      | @data(andre[0].consumerorg.id) | @data(andre[4].mail) | developer               |
    Given I am logged in as "@data(andre[4].mail)"
    And I am at "/myorg"
    Then I should not see the text "Invite"
    When I go to "/myorg/invite"
    Then I should see the text "Access denied"
    And I should see the text "You are not authorized to access this page."

  @api
  Scenario: As a viewer member of an org with multiple members with different roles, I cannot invite new members
    Given users:
      | name                 | mail                 | pass                     | status |
      | @data(andre[0].mail) | @data(andre[0].mail) | @data(andre[0].password) | 1      |
      | @data(andre[1].mail) | @data(andre[1].mail) | @data(andre[1].password) | 1      |
      | @data(andre[2].mail) | @data(andre[2].mail) | @data(andre[2].password) | 1      |
      | @data(andre[3].mail) | @data(andre[3].mail) | @data(andre[3].password) | 1      |
      | @data(andre[4].mail) | @data(andre[4].mail) | @data(andre[4].password) | 1      |
    Given consumerorgs:
      | title                             | name                             | id                             | owner                |
      | @data(andre[0].consumerorg.title) | @data(andre[0].consumerorg.name) | @data(andre[0].consumerorg.id) | @data(andre[0].mail) |
    Given members:
      | consumerorgid                  | username             | roles                   |
      | @data(andre[0].consumerorg.id) | @data(andre[1].mail) | administrator           |
      | @data(andre[0].consumerorg.id) | @data(andre[2].mail) | administrator,developer |
      | @data(andre[0].consumerorg.id) | @data(andre[3].mail) | viewer                  |
      | @data(andre[0].consumerorg.id) | @data(andre[4].mail) | developer               |
    Given I am logged in as "@data(andre[3].mail)"
    And I am at "/myorg"
    Then I should not see the text "Invite"
    When I go to "/myorg/invite"
    Then I should see the text "Access denied"
    And I should see the text "You are not authorized to access this page."
