Feature: Change Password
  In order to use a developer portal using an ldap user registry securely
  I need to not be presented with the change password form when I can't use it.

@api
Scenario: Change password is not available as andre user.
  Given users:
    | name              | mail              | pass                  | status |
    | @data(andre.name) | @data(andre.mail) | @data(andre.password) | 1      |
  Given consumerorgs:
    | title                     | name                     | id                     | owner             |
    | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
  Given I am logged in as "@data(andre.name)"
  And I go to "/user/@uid/change-password"
  Then the response status code should be 403
  And I should see the text "You are not authorized to access this page."

@api
  Scenario: Change password is not available as a user with an administrator role.
  Given I am logged in as an "administrator"
  And I go to "/user/@uid/change-password"
  Then the response status code should be 403
  And I should see the text "You are not authorized to access this page."

  Scenario: Change password form is not available if not logged in
  Given I am not logged in
  When I go to "/user/1/change-password"
  Then the response status code should be 403
  And I should see the text "You are not authorized to access this page."

@api
Scenario: Change password form is available as admin (uid=1) user
  Given users:
    | name              | mail              | pass                  | status |
    | @data(admin.name) | @data(admin.mail) | @data(admin.password) | 1      |
  Given I am logged in as "@data(admin.name)"
  And I go to "/user/1/change-password"
  Then the response status code should be 200
  Then there are no errors
  And there are no messages

@api
Scenario: Change password form for andre is not available to admin (uid=1) user
  Given users:
    | name              | mail              | pass                  | status |
    | @data(admin.name) | @data(admin.mail) | @data(admin.password) | 1      |
    | @data(andre.name) | @data(andre.mail) | @data(andre.password) | 1      |
  Given consumerorgs:
    | title                     | name                     | id                     | owner             |
    | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
  Given I am logged in as "@data(admin.name)"
  And I go to "/user/@uid(@data(andre.name))/change-password"
  Then the response status code should be 403
  And I should see the text "You are not authorized to access this page."

# TODO....
#@api
#Scenario: Change password form for andre is not available to another andre user
#  Given users:
#    | name              | mail              | pass                  | status |
#    | @data(admin.name) | @data(admin.mail) | @data(admin.password) | 1      |
#    | @data(andre.name) | @data(andre.mail) | @data(andre.password) | 1      |
#  Given consumerorgs:
#    | title                     | name                     | id                     | owner             |
#    | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
#  Given I am logged in as "@data(admin.name)"
#  And I go to "/user/@uid(@data(andre.name))/change-password"
#  Then the response status code should be 403
#  And I should see the text "You are not authorized to access this page."
#




#  When I enter "@data(admin.password)" for "current_pass"
#  And I enter "newPassw0rd" for "pass[pass1]"
#  And I enter "newPassw0rd" for "pass[pass2]"
#  And I press the "Submit" button
#  Then I should see the text "Your password has been changed."
#  # unfortunately this has actually changed the admin password so we need to revert it.
#  # admin user does not logout, so we are still on the changepassword screen.
#  When I enter "newPassw0rd" for "current_pass"
#  And I enter "@data(admin.password)" for "pass[pass1]"
#  And I enter "@data(admin.password)" for "pass[pass2]"
#  And I press the "Submit" button
#  Then I should see the text "Your password has been changed."
#
#@api
#  Scenario: Wrong current password as admin user
#    Given users:
#      | name              | mail              | pass                  | status |
#      | @data(admin.name) | @data(admin.mail) | @data(admin.password) | 1      |
#    Given I am logged in as "@data(admin.name)"
#    And I am at "/user/1/change-password"
#    When I enter "thisiswrong" for "current_pass"
#    And I enter "newPassw0rd" for "pass[pass1]"
#    And I enter "newPassw0rd" for "pass[pass2]"
#    And I press the "Submit" button
#    Then I should see the text "The current password you provided is incorrect."
#
#@api
#@fullstack
#Scenario: Wrong current password as non-admin
#  Given users:
#    | name              | mail              | pass                  | status |
#    | @data(andre.name) | @data(andre.mail) | @data(andre.password) | 1      |
#  Given consumerorgs:
#    | title                     | name                     | id                     | owner             |
#    | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
#  Given I am logged in as "@data(andre.name)"
#  And I am at "/user/@uid/change-password"
#  When I enter "thisiswrong" for "Current password"
#  And I enter "newPassw0rd" for "Password"
#  And I enter "newPassw0rd" for "Confirm password"
#  And I press the "Submit" button
#  Then I should see the text "The old password is incorrect"
#
#@api
#@fullstack
#Scenario: Invalid new password as non-admin
#  Given users:
#    | name              | mail              | pass                  | status |
#    | @data(andre.name) | @data(andre.mail) | @data(andre.password) | 1      |
#  Given consumerorgs:
#    | title                     | name                     | id                     | owner             |
#    | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
#  Given I am logged in as "@data(andre.name)"
#  And I am at "/user/@uid/change-password"
#  When I enter "@data(andre.password)" for "Current password"
#  And I enter "thisisinvalid" for "Password"
#  And I enter "thisisinvalid" for "Confirm password"
#  And I press the "Submit" button
#  Then I should see the text "Password must contain characters from 3 of the 4 following categories:"
#
#Scenario: Form not available if not logged in
#  Given I am not logged in
#  When I go to "/user/1/change-password"
#  Then the response status code should be 403
#  And I should see the text "You are not authorized to access this page."
#
#
#
