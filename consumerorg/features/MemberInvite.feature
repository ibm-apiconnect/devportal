Feature: Org member invitation
  As Andre I am able to invite a user to an organization I am in, with different user roles.

  @api
  Scenario: As an org owner, I can view the invite org member form
    Given I am not logged in
    Given users:
    | name              | mail              | pass                  | status |
    | @data(andre.name) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
    | title                     | name                     | id                     | owner             |
    | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
    And I am logged in as "@data(andre.name)"
    And I am at "/myorg/invite"
    Then I should see the text "Invite a user to join your consumer organization"
    And I should see the text "Email"
    And I should see the text "Assign Roles"
    And I should see the text "Cancel"
    And I should see the text "Submit"

  @api
  Scenario: As an org owner, I can send an org member invitation
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.name) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
    And I am logged in as "@data(andre.name)"
    And I am at "/myorg/invite"
    When I enter "inviteduser@example.com" for "Email"
    And I press the "Submit" button
    Then I should be on "/myorg"
    And there are messages
    And I should see the text "Invitation sent successfully."
    And there are no errors

  @api
  # Note that this a negative test for an admin user (uid=1) that manually sets their url to the /myorg/invite page
  Scenario: Sign in as the admin user (uid==1) and try to view the Invite page
    Given users:
      | name              | mail              | pass                  | status |
      | @data(admin.name) | @data(admin.mail) | @data(admin.password) | 1      |
    Given I am logged in as "@data(admin.name)"
    When I go to "/myorg/invite"
    Then I should get a "403" HTTP response
    And I should see the text "Access denied"
    And I should see the text "You are not authorized to access this page."
    And there are no errors

  @api
  Scenario: As an org member, role=administrator, I can send an org member invitation for role=Administrator
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
    And I am at "/myorg/invite"
    When I enter "inviteduser@example.com" for "Email"
    And I select the radio button "Administrator"
    And I press the "Submit" button
    Then I should be on "/myorg"
    And there are messages
    And I should see the text "Invitation sent successfully."
    And I should see the text "inviteduser@example.com"
    And I should see that "inviteduser@example.com" is an "administrator"
    And I should not see that "inviteduser@example.com" is a "developer"
    And I should not see that "inviteduser@example.com" is a "viewer"
    And I should see the text "Pending"
    And there are no errors

  @api
  Scenario: As an org member, role=administrator, I can send an org member invitation for role=Viewer
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
    And I am at "/myorg/invite"
    When I enter "inviteduser@example.com" for "Email"
    And I select the radio button "Viewer"
    And I press the "Submit" button
    Then I should be on "/myorg"
    And there are messages
    And I should see the text "Invitation sent successfully."
    And I should see the text "inviteduser@example.com"
    And I should see that "inviteduser@example.com" is a "viewer"
    And I should not see that "inviteduser@example.com" is a "developer"
    And I should not see that "inviteduser@example.com" is an "administrator"
    And I should see the text "Pending"
    And there are no errors


  @api
  Scenario: As an org owner, I can send an org member invitation for the role=Developer (default)
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.name) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
    And I am logged in as "@data(andre.name)"
    And I am at "/myorg/invite"
    When I enter "inviteduser@example.com" for "Email"
    #Role=Developer radio button is selected by default
    And I press the "Submit" button
    Then I should be on "/myorg"
    And there are messages
    And I should see the text "Invitation sent successfully."
    And I should see the text "inviteduser@example.com"
    And I should see that "inviteduser@example.com" is a "developer"
    And I should not see that "inviteduser@example.com" is an "administrator"
    And I should not see that "inviteduser@example.com" is a "viewer"
    And I should see the text "Pending"
    And there are no errors
