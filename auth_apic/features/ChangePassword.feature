@api
Feature: Change Password
  In order to use the developer portal securely
  I need to be able to change my password

  Scenario: View the change password form as admin
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
    Given I am logged in as a registry user with the "Administrator" role
    And I am at "/user/@uid/change-password"
    Then I should see the text "Current password"
    Then I should see the text "Password"
    Then I should see the text "Confirm password"
    Then I should see the "Save" button

  Scenario: View the change password form as non-admin
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.name) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                          | name                          | id                          | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
    Given I am logged in as "@data(andre.name)"
    And I am at "/user/@uid/change-password"
#    And I click "Change Password"
    Then I should see the text "Current password"
    Then I should see the text "Password"
    Then I should see the text "Confirm password"
    Then I should see the "Save" button

  Scenario: Submit change password form as non-admin user
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.name) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                          | name                          | id                          | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
    Given I am logged in as "@data(andre.name)"
    And I am at "/user/@uid/change-password"
    When I enter "@data(andre.password)" for "Current password"
    And I enter "newPassw0rdIsGreat!" for "Password"
    And I enter "newPassw0rdIsGreat!" for "Confirm password"
    And I press the "Save" button
    Given I am at "/user/@uid/change-password"
    When I enter "newPassw0rdIsGreat!" for "Current password"
    And I enter "@data(andre.password)" for "Password"
    And I enter "@data(andre.password)" for "Confirm password"
    And I press the "Save" button
  #TODO: verify things are good!!!!!!!

#  Admin users - we need to check 2 cases, uid===1 special case and a user which has the administrator role.
#                uid===1 is a local drupal user and will use the standard form behaviour.
  Scenario: Submit change password form as user with administrator role
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
    Given I am logged in as a registry user with the "Administrator" role
    And I am at "/user/@uid/change-password"
    When I enter "blah" for "current_pass"
    And I enter "newPassw0rdIsGreat!" for "pass[pass1]"
    And I enter "newPassw0rdIsGreat!" for "pass[pass2]"
    And I press the "Save" button
    Then I should see the text "Password changed successfully"

  Scenario: Submit change password form as admin user
    Given users:
      | name              | mail              | pass                  | status |
      | @data(admin.name) | @data(admin.mail) | @data(admin.password) | 1      |
    Given I am logged in as "@data(admin.name)"
    And I am at "/user/1/change-password"
    When I enter "@data(admin.password)" for "current_pass"
    And I enter "newPassw0rdIsGreat!" for "pass[pass1]"
    And I enter "newPassw0rdIsGreat!" for "pass[pass2]"
    And I press the "Save" button
    Then I should see the text "Your password has been changed."
  # unfortunately this has actually changed the admin password so we need to revert it.
  # admin user does not logout, so we are still on the changepassword screen.
    Given I am at "/user/1/change-password"
    When I enter "newPassw0rdIsGreat!" for "current_pass"
    And I enter "@data(admin.password)" for "pass[pass1]"
    And I enter "@data(admin.password)" for "pass[pass2]"
    And I press the "Save" button
    Then I should see the text "Your password has been changed."

  Scenario: Wrong current password as admin user
    Given users:
      | name              | mail              | pass                  | status |
      | @data(admin.name) | @data(admin.mail) | @data(admin.password) | 1      |
    Given I am logged in as "@data(admin.name)"
    And I am at "/user/1/change-password"
    When I enter "thisiswrong" for "current_pass"
    And I enter "newPassw0rdIsGreat!" for "pass[pass1]"
    And I enter "newPassw0rdIsGreat!" for "pass[pass2]"
    And I press the "Save" button
    Then I should see the text "The current password you provided is incorrect."

  Scenario: Wrong current password as non-admin
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.name) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                          | name                          | id                          | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
    Given I am logged in as "@data(andre.name)"
    And I am at "/user/@uid/change-password"
    When I enter "thisiswrong" for "Current password"
    And I enter "newPassw0rdIsGreat!" for "Password"
    And I enter "newPassw0rdIsGreat!" for "Confirm password"
    And I press the "Save" button
    Then I should see the text "The old password is incorrect"

  Scenario: Invalid new password as non-admin
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.name) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                          | name                          | id                          | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
    Given I am logged in as "@data(andre.name)"
    And I am at "/user/@uid/change-password"
    When I enter "@data(andre.password)" for "Current password"
    And I enter "thisisinvalid" for "Password"
    And I enter "thisisinvalid" for "Confirm password"
    And I press the "Save" button
    Then I should see the text "Password must contain at least 3 types of characters from the following character types:"

  Scenario: Form not available if not logged in
    Given I am not logged in
    When I go to "/user/1/change-password"
    Then the response status code should be 403
    And I should see the text "You are not authorized to access the requested page."

  Scenario: Change password for users with the same username
    Given I am not logged in
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | lur  | @data(user_registries[0].title) | @data(user_registries[0].url) | yes          | yes     |
      | lur  | @data(user_registries[1].title) | @data(user_registries[1].url) | yes          | no      |
    Given users:
      | uid    | name         | mail                  | pass     | registry_url                  |
      | 234567 | changepwuser | changepw1@example.com | Qwert123IsBadPassword! | @data(user_registries[0].url) |
      | 765432 | changepwuser | changepw2@example.com | Qwert246 | @data(user_registries[1].url) |
    Given consumerorgs:
      | title | name | id   | owner_uid |
      | org1  | org1 | org1 | 234567    |
      | org2  | org2 | org2 | 765432    |
    When I am logged in as "changepwuser" from "@data(user_registries[0].url)" with "Qwert123IsBadPassword!"
    And I am at "/user/@uid/change-password"
    And I enter "Qwert123IsBadPassword!" for "Current password"
    And I enter "newPassw0rdIsGreat!" for "Password"
    And I enter "newPassw0rdIsGreat!" for "Confirm password"
    And I press the "Save" button
  #And I am on "/myorg"
    Then there are no errors
    And there are no warnings
    And there are messages
    And I should see the text "Password changed successfully"
    And I am logged in as "changepwuser" from "@data(user_registries[1].url)" with "Qwert246"
    When I am at "/user/@uid/change-password"
    When I enter "Qwert246" for "Current password"
    And I enter "newPassw0rdIsGreat!" for "Password"
    And I enter "newPassw0rdIsGreat!" for "Confirm password"
    And I press the "Save" button
    Then there are no errors
    And there are no warnings
    And there are messages
    And I should see the text "Password changed successfully"


  Scenario: Change password for a user on writable ldap
    Given I am not logged in
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | ldap | @data(user_registries[2].title) | @data(user_registries[2].url) | yes          | yes     |
    Given users:
      | uid    | name         | mail                  | pass     | registry_url                  |
      | 123456 | changepwuser | changepw1@example.com | Qwert123IsBadPassword! | @data(user_registries[2].url) |
    Given consumerorgs:
      | title | name | id   | owner_uid |
      | org1  | org1 | org1 | 123456    |
    When I am logged in as "changepwuser" from "@data(user_registries[2].url)" with "Qwert123IsBadPassword!"
    And I am at "/user/@uid/change-password"
    And I enter "Qwert123IsBadPassword!" for "Current password"
    And I enter "newPassw0rdIsGreat!" for "Password"
    And I enter "newPassw0rdIsGreat!" for "Confirm password"
    And I press the "Save" button
    Then there are no errors
    And there are no warnings
    And there are messages
    And I should see the text "Password changed successfully"
