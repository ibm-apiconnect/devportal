Feature: Edit Profile
  As a user of the developer portal using an LDAP user registry
  I want to be able to edit my account details

@api
Scenario: Viewing the edit profile form as a non admin user
  Given users:
    | name              | mail              | pass                  | status |
    | @data(andre.name) | @data(andre.mail) | @data(andre.password) | 1      |
  Given consumerorgs:
    | title                     | name                     | id                     | owner             |
    | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
  When I am logged in as "@data(andre.name)"
  # to help narrow down: https://github.ibm.com/apimesh/devportal-apictest/issues/57
  Then dump the current html
  And I am at "/user/@uid/edit"
  Then I should see the text "First Name"
  And the element "first_name[0][value]" is disabled
  And I should see the text "Last Name"
  And the element "last_name[0][value]" is disabled
  And I should see the text "Email Address"
  And the element "emailaddress" is disabled
  And I should see the text "Picture"
  And I should see the text "Code Snippet language"
  And I should see the text "Time zone"
    # Site language doesn't appear in travis profile.
    # And I should see the text "Site language"
  And I should not see the text "Current Password"
  And I should not see the text "Consumer organization"
  And I should not see an "#edit-name" element
  And I should not see an "#edit-consumer-organization-0-value" element

# admin shouldn't vary based on LUR used because they are always stored in
# the drupal db, but valid to check this due to the complexity in building
# the forms.
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
  And the element "last_name[0][value]" is enabled
  And the "last_name[0][value]" field should contain "admin"
  And I should see the text "Picture"
  And I should see the text "Code Snippet language"
  And I should see the text "Time zone"
  And I should see the text "Current Password"
  And the element "edit-current-pass" is enabled
  And I should see the text "Email address"
  And the element "edit-mail" is enabled
  And I should see the text "Username"
  And the element "name" is enabled
  And the "name" field should contain "admin"

@api
Scenario: Save edit profile form as andre
  Given users:
    | name              | mail              | pass                  | status |
    | @data(andre.name) | @data(andre.mail) | @data(andre.password) | 1      |
  Given consumerorgs:
    | title                     | name                     | id                     | owner             |
    | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
  And I am logged in as "@data(andre.name)"
  And I am at "/user/@uid/edit"
  And the "timezone" field should contain "UTC"
  When I enter "Europe/London" for "timezone"
  And I press the "Save" button
  Then there are no errors
  And there are no warnings
  And there are messages
  And I should see the text "Your account has been updated."
  And the "timezone" field should contain "Europe/London"