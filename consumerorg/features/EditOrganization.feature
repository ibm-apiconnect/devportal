@api
Feature: Edit Consumer Organization
  In order to use the developer portal
  I need to be able to edit my organization title

  Scenario: As an organization owner, I can go to the edit organization page
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
    Given I am logged in as "@data(andre.mail)"
    And I am at "/myorg"
    # Edit organization is hidden in an options menu
    Then I should see the text "Edit organization"
    When I click "Edit organization"
    Then I should be on "/myorg/edit"
    And I should see the text "Update the consumer organization"
    And I should see the text "Organization Title"
    And there are no errors


  Scenario: As an organization owner, I can edit the organization title
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
    Given I am logged in as "@data(andre.mail)"
    And I am at "/myorg/edit"
    Then I should see the text "Update the consumer organization"
    And I should see the text "Organization Title"
    When I enter "New org1" for "Organization Title"
    And I press the "Save" button
    Then I should be on "/myorg"
    And I should see the text "New org1"
    And there are messages
    And I should see the text "Organization updated."
    And there are no errors


  Scenario: As an org member, role=administrator, I can go to the edit organization page
    Given I am not logged in
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
    # Edit organization is hidden in an options menu
    Then I should see the text "Edit organization"
    When I click "Edit organization"
    Then I should be on "/myorg/edit"
    And I should see the text "Update the consumer organization"
    And I should see the text "Organization Title"
    And there are no errors


  Scenario: As an org member, role=administrator, I can edit the organization title
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
    When I go to "/myorg/edit"
    Then I should see the text "Update the consumer organization"
    And I should see the text "Organization Title"
    When I enter "New org1" for "Organization Title"
    And I press the "Save" button
    Then I should be on "/myorg"
    And I should see the text "New org1"
    And there are messages
    And I should see the text "Organization updated."
    And there are no errors


  Scenario: As an org member, role=developer, I cannot see the edit organization option
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
    Then I should not see the text "Edit organization"
    And there are no errors
    And there are no messages


  Scenario: As an org member, role=developer, I cannot edit the organization title
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
    When I go to "/myorg/edit"
    Then I should see the text "Access denied"
    And I should see the text "You are not authorized to access the requested page."
    And there are no errors


  Scenario: As an org member, role=viewer, I cannot see the edit organization option
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
    Then I should not see the text "Edit organization"
    And there are no errors
    And there are no messages


  Scenario: As an org member, role=viewer, I cannot edit the organization title
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
    When I go to "/myorg/edit"
    Then I should see the text "Access denied"
    And I should see the text "You are not authorized to access the requested page."
    And there are no errors

  Scenario: As an org owner, I can edit the custom fields
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
    Given consumerorg entities have text type custom fields
    Given I am logged in as "@data(andre.mail)"
    When I go to "/myorg/edit"
    Then I should see the text "Update the consumer organization"
    And I should see a "#edit-field-singletext-0-value" element
    And I should see a "#edit-field-multitext-0-value" element
    And I should see a "#edit-field-multitext-1-value" element
    When I enter "singleVal" for "edit-field-singletext-0-value"
    And I enter "first multi val" for "edit-field-multitext-0-value"
    And I enter "theSecond" for "edit-field-multitext-1-value"
    And I press the "Save" button
    Then I should be on "/myorg"
    And I should see the text "singleVal"
    And I should see the text "first multi val"
    And I should see the text "theSecond"
    And there are messages
    And I should see the text "Organization updated."
    And there are no errors
    Then I delete the text type custom fields for consumerorg entities