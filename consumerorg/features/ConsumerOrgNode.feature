@api
Feature: Consumer Organization Nodes
  Check the node rendering for consumerorgs in different scenarios

  Scenario: Sign in as different users and view the corg node directly (not via /myorg)
    Given I am not logged in
    Given users:
      | name                 | mail                 | pass                     | status |
      | @data(admin.name)    | @data(admin.mail)    | @data(admin.password)    | 1      |
      | @data(andre[0].mail) | @data(andre[0].mail) | @data(andre[0].password) | 1      |
      | @data(andre[1].mail) | @data(andre[1].mail) | @data(andre[1].password) | 1      |
    Given consumerorgs:
      | title                             | name                             | id                             | owner                |
      | @data(andre[0].consumerorg.title) | @data(andre[0].consumerorg.name) | @data(andre[0].consumerorg.id) | @data(andre[0].mail) |
      | @data(andre[1].consumerorg.title) | @data(andre[1].consumerorg.name) | @data(andre[1].consumerorg.id) | @data(andre[1].mail) |
    Given members:
      | consumerorgid                  | username             | roles                   |
      | @data(andre[0].consumerorg.id) | @data(andre[1].mail) | administrator           |
    # test that the org owner can access the node and its in edit mode
    Given I am logged in as "@data(andre[0].mail)"
    And I am viewing the "consumerorg" node "@data(andre[0].consumerorg.title)"
    # First two text items are hidden in an options menu
    Then I should see the text "Edit organization"
    And I should see the text "Delete organization"
    And I should see the text "Members"
    And I should see the text "NAME"
    And I should see the text "STATE"
    And there are no errors
    # test that it is read only for users whose current cOrg is a different org
    Given I am not logged in
    Given I am logged in as "@data(andre[1].mail)"
    And I click "@data(andre[1].consumerorg.title)"
    Then My active consumerorg is "@data(andre[1].consumerorg.title)"
    And I am viewing the "consumerorg" node "@data(andre[0].consumerorg.title)"
    Then I should see the text "Members"
    And I should see the text "NAME"
    And I should see the text "STATE"
    And I should not see the text "Edit organization"
    And I should not see the text "Delete organization"
    And I should see the text "Consumer organization displayed as read only"
    And there are no errors
    # Test that it is read only for admins
    Given I am not logged in
    Given I am logged in as "@data(admin.name)"
    And I am viewing the "consumerorg" node "@data(andre[0].consumerorg.title)"
    Then I should see the text "Members"
    And I should see the text "NAME"
    And I should see the text "STATE"
    And I should not see the text "Edit organization"
    And I should not see the text "Delete organization"
    And I should see the text "Consumer organization displayed as read only"
    And there are no errors

  Scenario: Admin should not be able to edit the org title from admin edit page
    Given I am not logged in
    Given users:
      | name                 | mail                 | pass                     | status |
      | @data(admin.name)    | @data(admin.mail)    | @data(admin.password)    | 1      |
      | @data(andre[0].mail) | @data(andre[0].mail) | @data(andre[0].password) | 1      |
    Given consumerorgs:
      | title                             | name                             | id                             | owner                |
      | @data(andre[0].consumerorg.title) | @data(andre[0].consumerorg.name) | @data(andre[0].consumerorg.id) | @data(andre[0].mail) |
    Given I am not logged in
    Given I am logged in as "@data(admin.name)"
    And I am editing the "consumerorg" node "@data(andre[0].consumerorg.title)"
    Then The field "#edit-title-0-value" should be disabled
    And I should not see the text "APIC Catalog ID"
    And I should not see the text "APIC Hostname"
    And I should not see the text "APIC Provider ID"
    And I should not see the text "Organization ID"
    And I should not see the text "Invites"
    And I should not see the text "List of members"
    And I should not see the text "Members"
    And I should not see the text "Organization Name"
    And I should not see the text "Organization Owner"
    And I should not see the text "Organization Communities"
    And I should not see the text "Organization URL"