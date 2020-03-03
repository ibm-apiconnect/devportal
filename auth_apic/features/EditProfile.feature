Feature: Edit Profile
  As a user of the developer portal
  I want to be able to edit my account details

@api
  Scenario: Viewing the edit profile form as a non admin user
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.name) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
    When I am logged in as "@data(andre.name)"
    And I am at "/user/@uid/edit"
    Then I should see the text "First Name"
    And the element "first_name[0][value]" is enabled
    And I should see the text "Last Name"
    And the element "last_name[0][value]" is enabled
    And I should see the text "Email Address"
    And the element "emailaddress" is disabled
    And I should see the text "Picture"
    And I should see the text "Code Snippet language"
    And I should see the text "Time zone"
    # Site language doesn't appear in travis profile.
    # And I should see the text "Site language"
    And I should not see the text "Current Password"
    And I should not see an "#edit-name" element
    And I should not see an "#edit-consumer-organization-0-value" element
    And I should not see the text "Cancel account"
    And I should see the text "Cancel"
    And I should see the text "Save"
    And I should see the text "Delete account"

@api
  Scenario: Editing user details as a non admin user
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.name) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
    When I am logged in as "@data(andre.name)"
    And I am at "/user/@uid/edit"
    And I enter "Changed Name" for "First Name"
    And I enter "Changed Last Name" for "Last Name"
    When I press the "Save" button
    And there are messages
    And I should see the text "Your account has been updated."
    And the "First Name" field should contain "Changed Name"
    And the "Last Name" field should contain "Changed Last Name"
    When I enter "@data(andre.firstName)" for "First Name"
    And I enter "@data(andre.lastName)" for "Last Name"
    When I press the "Save" button
    And there are messages
    And I should see the text "Your account has been updated."
    And the "First Name" field should contain "@data(andre.firstName)"
    And the "Last Name" field should contain "@data(andre.lastName)"


@api
  # This test is testing for admin users where uid!=1 i.e. apim users
  # who have been given the admin role by another admin
  Scenario: Viewing the edit profile form as a user with the Administrator role
    Given I am logged in as a user with the "Administrator" role
    When I am at "/user/@uid/edit"
    Then I should see the text "First Name"
    And the element "first_name[0][value]" is enabled
    And I should see the text "Last Name"
    And the element "last_name[0][value]" is enabled
    And I should see the text "Email Address"
    And the element "emailaddress" is disabled
    And I should see the text "Picture"
    And I should see the text "Code Snippet language"
    And I should see the text "Time zone"
    # Site language doesn't appear in travis profile.
    # And I should see the text "Site language"
    And I should not see the text "Current Password"
    And I should not see an "#edit-name" element
    And I should not see an "#edit-consumer-organization-0-value" element
    And I should not see the text "Cancel account"
    And I should see the text "Cancel"
    And I should see the text "Save"
    And I should see the text "Delete account"

@api
  # This is a user with administrator role, not uid==1 admin user.
  Scenario: Editing user details as a user with administrator role
   Given I am logged in as a user with the "Administrator" role
   When I am at "/user/@uid/edit"
   And I enter "Changed Name Admin" for "First Name"
   And I enter "Changed Last Name Admin" for "Last Name"
   When I press the "Save" button
   And there are messages
   And I should see the text "Your account has been updated."
   And the "First Name" field should contain "Changed Name Admin"
   And the "Last Name" field should contain "Changed Last Name Admin"

@api
Scenario: View own edit profile form as admin user (uid==1)
  Given users:
    | name              | mail              | pass                  | status |
    | @data(admin.name) | @data(admin.mail) | @data(admin.password) | 1      |
  Given I am logged in as "@data(admin.name)"
  And I am at "/user/1/edit"
  Then I should see the text "First Name"
  And the element "first_name[0][value]" is enabled
  And the "first_name[0][value]" field should contain "admin"
  And I should see the text "Last Name"
  And I should see the text "Picture"
  And I should see the text "Code Snippet language"
  And I should see the text "Time zone"
  And I should see the text "Email address"
  And the element "mail" is enabled
  And I should not see the text "Username"
  And I should not see the text "Cancel account"
  And I should see the text "Cancel"
  And I should see the text "Save"
  And I should not see the text "Delete account"

@api
Scenario: View another users edit profile form as admin user (uid==1)
  Given users:
    | name              | mail              | pass                  | status |
    | @data(admin.name) | @data(admin.mail) | @data(admin.password) | 1      |
    | @data(andre.name) | @data(andre.mail) | @data(andre.password) | 1      |
  Given consumerorgs:
    | title                     | name                     | id                     | owner             |
    | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
  Given I am logged in as "@data(admin.name)"
  And I am at "/user/@uid(@data(andre.name))/edit"
  Then I should see the text "First Name"
  And the element "first_name[0][value]" is disabled
  And I should see the text "Last Name"
  And the element "last_name[0][value]" is disabled
  And I should see the text "Email Address"
  And the element "emailaddress" is disabled
  And I should see the text "Picture"
  And I should see the text "Code Snippet language"
  And I should see the text "Time zone"
  And I should not see the text "Current Password"
  And I should not see the text "Username"
  And I should not see the text "Cancel account"
  And I should see the text "Cancel"
  And I should see the text "Save"
  And I should not see the text "Delete account"

  @api
  Scenario: View admin user edit profile form as andre with Administrator role
    Given users:
      | name              | mail              | pass                  | status |
      | @data(admin.name) | @data(admin.mail) | @data(admin.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
    Given I am logged in as a user with the "Administrator" role
    And I am at "/user/1/edit"
    Then I should see the text "First Name"
    And the element "first_name[0][value]" is disabled
    And I should see the text "Last Name"
    And the element "last_name[0][value]" is disabled
    And I should see the text "Email Address"
    And the element "emailaddress" is disabled
    And I should see the text "Picture"
    And I should see the text "Code Snippet language"
    And I should see the text "Time zone"
    And I should not see the text "Current Password"
    And I should not see the text "Username"
    And I should not see the text "Cancel account"
    And I should see the text "Cancel"
    And I should see the text "Save"
    And I should not see the text "Delete account"

  @api
  Scenario: View edit profile form as LDAP (!user_managed) user
    Given I am not logged in
    Given userregistries:
      | type | title                             | url                               | user_managed | default |
      | ldap | @data(user_registries[2].title)   | @data(user_registries[2].url)     | no           | yes     |
    Given users:
      | name              | mail              | pass                  | status | registry_url                  |
      | @data(andre.name) |                   | @data(andre.password) | 1      | @data(user_registries[2].url) |
    Given I am logged in as "@data(andre.name)"
    When I am at "/user/@uid/edit"
    Then I should see the text "First Name"
    And the element "first_name[0][value]" is disabled
    And the "first_name[0][value]" field should contain "Andre"
    And I should see the text "Last Name"
    And the element "last_name[0][value]" is disabled
    And the "last_name[0][value]" field should contain "Andresson"
    And I should see the text "Email Address"
    And the element "emailaddress" is disabled
    And the "emailaddress" field should contain ""
    And I should see the text "Picture"
    And I should see the text "Code Snippet language"
    And I should see the text "Time zone"
    And I should not see the text "Current Password"
    And I should not see the text "Username"
    And I should not see the text "Cancel account"
    And I should see the text "Cancel"
    And I should see the text "Save"
    And I should see the text "Delete account"

  @api
  Scenario: Change profile preferred language as LDAP (!user_managed) user
    Given I am not logged in
    Given userregistries:
      | type | title                             | url                               | user_managed | default |
      | ldap | @data(user_registries[2].title)   | @data(user_registries[2].url)     | no           | yes     |
    Given users:
      | name              | mail              | pass                  | status | registry_url                  |
      | @data(andre.name) |                   | @data(andre.password) | 1      | @data(user_registries[2].url) |
    Given I am logged in as "@data(andre.name)"
    When I am at "/user/@uid/edit"
    When I select "es" from "preferred_langcode"
    When I press the "Save" button
    And there are messages
    And I should see the text "Your account has been updated."
    And the "first_name[0][value]" field should contain "Andre"
    And the "last_name[0][value]" field should contain "Andresson"
    And there are no errors
    And there are no warnings


  @api
  Scenario: View edit profile form as a writable LDAP user
    Given the cache has been cleared
    Given I am not logged in
    Given userregistries:
      | type | title                             | url                               | user_managed | default |
      | ldap | @data(user_registries[2].title)   | @data(user_registries[2].url)     | yes           | yes     |
    Given users:
      | name              | mail              | pass                  | status | registry_url                  |
      | @data(andre.name) | @data(andre.mail) | @data(andre.password) | 1      | @data(user_registries[2].url) |
    Given I am logged in as "@data(andre.name)"
    When I am at "/user/@uid/edit"
    Then I should see the text "First Name"
    And the element "first_name[0][value]" is enabled
    And the "first_name[0][value]" field should contain "Andre"
    And I should see the text "Last Name"
    And the element "last_name[0][value]" is enabled
    And the "last_name[0][value]" field should contain "Andresson"
    And I should see the text "Email Address"
    And the element "emailaddress" is disabled
    And the "emailaddress" field should contain "@data(andre.mail)"
    And I should see the text "Code Snippet language"
    And I should see the text "Time zone"
    And I should see the text "Site language"
    And I should see the text "Status"
    And I should see the text "Roles"
    And I should see the text "Avatar Generator"
    And I should see the text "User picture upload"
    And I should not see the text "Current Password"
    And I should not see an "#edit-name" element
    And I should not see an "#edit-consumer-organization-0-value" element
    And I should not see the text "Cancel account"
    And I should see the text "Cancel"
    And I should see the text "Save"
    And I should see the text "Delete account"

  @api
  Scenario: Change profile first name and last name as a writable LDAP user
    Given the cache has been cleared
    Given I am not logged in
    Given userregistries:
      | type | title                             | url                               | user_managed | default |
      | ldap | @data(user_registries[2].title)   | @data(user_registries[2].url)     | yes           | yes     |
    Given users:
      | name              | mail              | pass                  | status | registry_url                  |
      | @data(andre.name) | @data(andre.mail) | @data(andre.password) | 1      | @data(user_registries[2].url) |
    Given I am logged in as "@data(andre.name)"
    When I am at "/user/@uid/edit"
    And I enter "Changed Name" for "First Name"
    And I enter "Changed Last Name" for "Last Name"
    When I press the "Save" button
    And there are messages
    And I should see the text "Your account has been updated."
    And the "First Name" field should contain "Changed Name"
    And the "Last Name" field should contain "Changed Last Name"
    And there are no errors
    And there are no warnings

