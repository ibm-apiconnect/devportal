Feature: Delete user
  As a user of the developer portal i want to be able to delete my account

  @api
  Scenario: View the delete user form as andre
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given I am logged in as "@data(andre.mail)"
    And I am at "/user/delete"
    Then I should see the text "Are you sure you want to delete your account?"
    And I should see the text "Are you sure you want to delete your account? This action cannot be undone."
    And I should see the link "Cancel"
    And I should see the "Delete" button
    And there are no messages
    And there are no warnings
    And there are no errors

  @api
  Scenario: Unable to open the form for admin
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(admin.name) | @data(admin.mail) | @data(admin.password) | 1      |
    Given I am logged in as "@data(admin.name)"
    Given I am on "/user/delete"
    Then the response status code should be 403
    Then I should not see the text "Are you sure you want to delete your account?"
    And I should not see the text "Are you sure you want to delete your account? This action cannot be undone."
    And I should not see the link "Cancel"
    And I should not see the "Delete" button
    And I should see the text "Access denied"
    And I should see the text "You are not authorized to access this page."
    And there are no messages
    And there are no warnings
    And there are no errors

  @api
  Scenario: Andre - click cancel
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given I am logged in as "@data(andre.mail)"
    And I am at "/user/delete"
    And I should see the link "Cancel"
    When I click "Cancel"
    Then I am at "/"
    And there are no messages
    And there are no warnings
    And there are no errors

  @api
  Scenario: Andre - click delete
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given I am logged in as "@data(andre.mail)"
    And I am at "/user/delete"
    And I should see the "Delete" button
    When I press the "Delete" button
    Then I am at "/"
    And there are messages
    And I should see the text "The update has been performed."
    # the following doesn't appear while using mocks.
      # And I should see the text "andre has been deleted."
    And there are no warnings
    And there are no errors

  @api
  Scenario: Andre org owner - click delete
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                          | name                          | id                          | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
    Given I am logged in as "@data(andre.mail)"
    And I am at "/user/delete"
    And I should see the "Delete" button
    When I press the "Delete" button
    Then I am at "/"
    And there are messages
    And I should see the text "Organization successfully deleted."
    And I should see the text "The update has been performed."
    # the following doesn't appear while using mocks.
      # And I should see the text "andre has been deleted."
    And there are no warnings
    And there are no errors

  @api
  Scenario: Andre multiple org owner - not allowed
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                          | name                          | id                          | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
      | Anotherorg                     | Anotherorg                    | 2468                        | @data(andre.name) |
    Given I am logged in as "@data(andre.mail)"
    And I am at "/user/delete"
    And I should see the link "Cancel"
    And I should not see the "Delete" button
    And there are errors
    And I should see the text "You cannot delete your account because you own more than 1 organization."
    And I should see the text "Delete account"
    And I should see the text "You are the owner of multiple consumer organizations. You can delete your account only when you are the owner of a single organization. Please transfer the ownership of, or delete, the other organizations before you delete your account."
    And there are no messages
    And there are no warnings

  @api
  Scenario: Andre org owner with writable LDAP - click delete
    Given I am not logged in
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | ldap | @data(user_registries[2].title) | @data(user_registries[2].url) | yes          | yes     |
    Given users:
      | name              | mail              | pass                  | status | registry_url                  |
      | @data(andre.name) | @data(andre.mail) | @data(andre.password) | 1      | @data(user_registries[2].url) |
    Given consumerorgs:
      | title                          | name                          | id                          | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
    Given I am logged in as "@data(andre.mail)"
    When I am at "/user/@uid/edit"
    And I should see the link "Delete account"
    When I click "Delete account"
    Then I am at "/user/delete"
    And I should see the text "Are you sure you want to delete your account?"
    And I should see the text "This action cannot be undone."
    And I should see the link "Cancel"
    And I should see the "Delete" button
    When I press the "Delete" button
    Then I am at "/"
    And there are messages
    And I should see the text "Organization successfully deleted."
    And I should see the text "The update has been performed."
    # the following doesn't appear while using mocks.
      # And I should see the text "andre has been deleted."
    And there are no warnings
    And there are no errors
