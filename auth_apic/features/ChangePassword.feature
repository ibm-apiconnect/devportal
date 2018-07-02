Feature: Change Password
  In order to use the developer portal securely
  I need to be able to change my password

@api
  Scenario: View the change password form as admin
    Given I am logged in as a user with the "Administrator" role
    And I am at "/user/@uid/change-password"
    Then I should see the text "Current password"
    Then I should see the text "Password"
    Then I should see the text "Confirm password"
    Then I should see the "Submit" button

@api
@fullstack
  Scenario: View the change password form as non-admin
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.name) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
    Given I am logged in as "@data(andre.name)"
    And I am at "/user/@uid/change-password"
#    And I click "Change Password"
    Then I should see the text "Current password"
    Then I should see the text "Password"
    Then I should see the text "Confirm password"
    Then I should see the "Submit" button

@api
@fullstack
Scenario: Submit change password form as non-admin user
  Given users:
    | name              | mail              | pass                  | status |
    | @data(andre.name) | @data(andre.mail) | @data(andre.password) | 1      |
  Given consumerorgs:
    | title                     | name                     | id                     | owner             |
    | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
  Given I am logged in as "@data(andre.name)"
  And I am at "/user/@uid/change-password"
  When I enter "@data(andre.password)" for "Current password"
  And I enter "newPassw0rd" for "Password"
  And I enter "newPassw0rd" for "Confirm password"
  And I press the "Submit" button
  Given I am at "/user/@uid/change-password"
  When I enter "newPassw0rd" for "Current password"
  And I enter "@data(andre.password)" for "Password"
  And I enter "@data(andre.password)" for "Confirm password"
  And I press the "Submit" button

#  Admin users - we need to check 2 cases, uid===1 special case and a user which has the administrator role.
#                uid===1 is a local drupal user and will use the standard form behaviour.
  @api
  Scenario: Submit change password form as user with administrator role
    Given I am logged in as a user with the "Administrator" role
    And I am at "/user/@uid/change-password"
    When I enter "blah" for "current_pass"
    And I enter "newPassw0rd" for "pass[pass1]"
    And I enter "newPassw0rd" for "pass[pass2]"
    And I press the "Submit" button
    Then I should see the text "Password changed successfully"

@api
Scenario: Submit change password form as admin user
  Given users:
    | name              | mail              | pass                  | status |
    | @data(admin.name) | @data(admin.mail) | @data(admin.password) | 1      |
  Given I am logged in as "@data(admin.name)"
  And I am at "/user/1/change-password"
  When I enter "@data(admin.password)" for "current_pass"
  And I enter "newPassw0rd" for "pass[pass1]"
  And I enter "newPassw0rd" for "pass[pass2]"
  And I press the "Submit" button
  Then I should see the text "Your password has been changed."
  # unfortunately this has actually changed the admin password so we need to revert it.
  # admin user does not logout, so we are still on the changepassword screen.
  Given I am at "/user/1/change-password"
  When I enter "newPassw0rd" for "current_pass"
  And I enter "@data(admin.password)" for "pass[pass1]"
  And I enter "@data(admin.password)" for "pass[pass2]"
  And I press the "Submit" button
  Then I should see the text "Your password has been changed."

@api
  Scenario: Wrong current password as admin user
    Given users:
      | name              | mail              | pass                  | status |
      | @data(admin.name) | @data(admin.mail) | @data(admin.password) | 1      |
    Given I am logged in as "@data(admin.name)"
    And I am at "/user/1/change-password"
    When I enter "thisiswrong" for "current_pass"
    And I enter "newPassw0rd" for "pass[pass1]"
    And I enter "newPassw0rd" for "pass[pass2]"
    And I press the "Submit" button
    Then I should see the text "The current password you provided is incorrect."

@api
@fullstack
Scenario: Wrong current password as non-admin
  Given users:
    | name              | mail              | pass                  | status |
    | @data(andre.name) | @data(andre.mail) | @data(andre.password) | 1      |
  Given consumerorgs:
    | title                     | name                     | id                     | owner             |
    | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
  Given I am logged in as "@data(andre.name)"
  And I am at "/user/@uid/change-password"
  When I enter "thisiswrong" for "Current password"
  And I enter "newPassw0rd" for "Password"
  And I enter "newPassw0rd" for "Confirm password"
  And I press the "Submit" button
  Then I should see the text "The old password is incorrect"

@api
@fullstack
Scenario: Invalid new password as non-admin
  Given users:
    | name              | mail              | pass                  | status |
    | @data(andre.name) | @data(andre.mail) | @data(andre.password) | 1      |
  Given consumerorgs:
    | title                     | name                     | id                     | owner             |
    | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
  Given I am logged in as "@data(andre.name)"
  And I am at "/user/@uid/change-password"
  When I enter "@data(andre.password)" for "Current password"
  And I enter "thisisinvalid" for "Password"
  And I enter "thisisinvalid" for "Confirm password"
  And I press the "Submit" button
  Then I should see the text "Password must contain characters from 3 of the 4 following categories:"

Scenario: Form not available if not logged in
  Given I am not logged in
  When I go to "/user/1/change-password"
  Then the response status code should be 403
  And I should see the text "You are not authorized to access this page."



