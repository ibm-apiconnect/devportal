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
    And I should see the text "Save"

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
    And I press the "Save" button
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


#This test is half done - cannot check display of org name until cache issue fixed: velox/devportal#4657
  @api
  Scenario: As Andre I can create a new consumer organization with ,&-! in the title
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.name) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
    And I am logged in as "@data(andre.name)"
    And I am at "/myorg/create"
    When I enter "New org1,&-!" for "title[0][value]"
    And I press the "Save" button
    Then I should be on the homepage
    And there are messages
    And I should see the text "Consumer organization created successfully."
#    Given the cache has been cleared
#    When I go to "/myorg"
#    Then I should see the text "New org1,&-!"
#    Then dump the current html
    And there are no errors

  # @api
  # Scenario: Create consumer organization with text custom field
  #   Given users:
  #     | name              | mail              | pass                  | status |
  #     | @data(andre.name) | @data(andre.mail) | @data(andre.password) | 1      |
  #   Given consumerorgs:
  #     | title                     | name                     | id                     | owner             |
  #     | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
  #   And consumerorg entities have text type custom fields
  #   And I am logged in as "@data(andre.name)"
  #   And I am at "/myorg/create"
  #   Then I should see a "#edit-field-singletext-0-value" element
  #   And I should see a "#edit-field-multitext-0-value" element
  #   And I should see a "#edit-field-multitext-1-value" element
  #   When I enter "New org1" for "title[0][value]"
  #   And I enter "singleVal" for "edit-field-singletext-0-value"
  #   And I enter "first multi val" for "edit-field-multitext-0-value"
  #   And I enter "theSecond" for "edit-field-multitext-1-value"
  #   And I press the "Save" button
  #   Given I am at "/myorg"
  #   Then I should see the text "singleVal"
  #   And I should see the text "first multi val"
  #   And I should see the text "theSecond"
  #   Then I delete the text type custom fields for consumerorg entities
