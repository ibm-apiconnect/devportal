Feature: Org member invitation
  As Andre I am able to invite a user to an organization I am in.

  @api
  Scenario: I can view the invite org member form
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
  Scenario: I can send an org member invitation
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
