@api
Feature: CredentialsCRUD
  In order to use the developer portal
  I need to be able to add, update and delete credentials to existing applications

  # needs to run in zombie driver cos goutte seems to cache the webpage meaning the updated node is not shown
  Scenario: As Andre, I can add, update, and delete application credentials
    Given I am not logged in
    Given users:
      | name  | pass     | mail              | status |
      | Andre | Qwert123IsBadPassword! | andre@example.com | 1      |
    Given consumerorgs:
      | title       | name        | id     | owner |
      | andreconsumerorg | andreconsumerorg | 123456 | Andre |
    Given I am logged in as "Andre"
    Given I do not have any applications
    Given I am at "/application/new"
    And I should see a "#edit-title-0-value" element
    And I should see a "#edit-apic-summary-0-value" element
    And I should see a "#edit-application-redirect-endpoints-0-value" element
    And I should see a "#edit-submit" element
    Given I enter "app45678" for "edit-title-0-value"
    And I enter "this is some text" for "edit-apic-summary-0-value"
    When I press the "Save" button
    Then there are no errors
    And I should see the text "API Key and Secret"
    When I click "OK"
    Then I should see the text "app45678"
    When I click "Subscriptions"
    Then I should see the text "Credentials"
    And there are no errors
#    Then I delete all applications from the site

#   Add new credentials
    When I click "Add"
    Then I should see the text "Name"
    And I should see a "#edit-title" element
    And I should see the text "Summary"
    And I should see a "#edit-summary" element
    Given I enter "My New Credentials" for "edit-title"
    Then I press the "Save" button
    And I click "OK"
    Then the custom hook with key "apic_app_creds_pre_create" should have been invoked
    Then the custom hook with key "apic_app_creds_create" should have been invoked

#   Edit new credentials
    Given I am not logged in
    Given I am logged in as "Andre"
    Given I am at "/application"
    Then I should see the text "app45678"
    And I click "app45678"
    When I click "Subscriptions"
    Then I should see the text "Default credentials"
    And I click "Default credentials"
    When I click the element with title "Manage credentials"
    Then I click the element with title "Edit Credentials"
    Then I should see the text "Title"
    And I should see a "#edit-title-label" element
    And I should see the text "Summary"
    And I should see a "#edit-summary" element
    And I enter "new summary 123" for "edit-summary"
    Then I press the "Save" button
    Then the custom hook with key "apic_app_creds_pre_update" should have been invoked
    Then the custom hook with key "apic_app_creds_update" should have been invoked

#   Reset Credentials
    Then I should see the text "app45678"
    When I click "Subscriptions"
    Then I should see the text "Credentials"
    When I click the element with title "Manage credentials"
    When I click "Reset Credentials"
    And I should see the text "Are you sure you want to reset the client ID and client secret? This action cannot be undone."
    When I click the button "Reset"
    And I click the button "OK"
    Then the custom hook with key "apic_app_pre_clientid_reset" should have been invoked
    Then the custom hook with key "apic_app_clientid_reset" should have been invoked

#   Reset Client Secret
    Then I should see the text "app45678"
    When I click "Subscriptions"
    Then I should see the text "Credentials"
    When I click the element with title "Manage credentials"
    When I click "Reset API Secret"
    And I should see the text "Are you sure you want to reset the client secret? This action cannot be undone."
    When I click the button "Reset"
    And I click the button "OK"
    Then the custom hook with key "apic_app_pre_clientsecret_reset" should have been invoked
    Then the custom hook with key "apic_app_clientsecret_reset" should have been invoked
    Then I delete all applications from the site


  Scenario: As Andre, I can add, update, and delete application credentials, needs to fail due to hook
    Given I am not logged in
    Given users:
      | name  | pass     | mail              | status |
      | Andre | Qwert123IsBadPassword! | andre@example.com | 1      |
    Given consumerorgs:
      | title       | name        | id     | owner |
      | andreconsumerorg | andreconsumerorg | 123456 | Andre |
    Given I am logged in as "Andre"
    Given I do not have any applications
    Given I am at "/application/new"
    And I should see a "#edit-title-0-value" element
    And I should see a "#edit-apic-summary-0-value" element
    And I should see a "#edit-application-redirect-endpoints-0-value" element
    And I should see a "#edit-submit" element
    Given I enter "app45678" for "edit-title-0-value"
    And I enter "this is some text" for "edit-apic-summary-0-value"
    When I press the "Save" button
    Then there are no errors
    And I should see the text "API Key and Secret"
    When I click "OK"
    Then I should see the text "app45678"
    When I click "Subscriptions"
    Then I should see the text "Credentials"
    And there are no errors

#   Add new credentials
    Given the Drupal state "apic_app_creds_pre_create_throw_exception" is set to "TRUE"
    When I click "Add"
    Then I should see the text "Name"
    And I should see a "#edit-title" element
    And I should see the text "Summary"
    And I should see a "#edit-summary" element
    Given I enter "My New Credentials" for "edit-title"
    Then I press the "Save" button
    Then I should see the unexpected error message

#   Edit new credentials
    Given I am not logged in
    Given I am logged in as "Andre"
    Given I am at "/application"
    Then I should see the text "app45678"
    And I click "app45678"
    When I click "Subscriptions"
    Then I should see the text "Default credentials"
    And I click "Default credentials"
    Given the Drupal state "apic_app_creds_pre_update_throw_exception" is set to "TRUE"
    When I click the element with title "Manage credentials"
    Then I click the element with title "Edit Credentials"
    Then I should see the text "Title"
    And I should see a "#edit-title-label" element
    And I should see the text "Summary"
    And I should see a "#edit-summary" element
    And I enter "new summary 123" for "edit-summary"
    Then I press the "Save" button
    Then I should see the unexpected error message

#   Reset Credentials
    Given I am not logged in
    Given I am logged in as "Andre"
    Given I am at "/application"
    Then I should see the text "app45678"
    And I click "app45678"
    Given the Drupal state "apic_app_pre_clientid_reset_throw_exception" is set to "TRUE"
    When I click "Subscriptions"
    Then I should see the text "Credentials"
    When I click the element with title "Manage credentials"
    When I click "Reset Credentials"
    And I should see the text "Are you sure you want to reset the client ID and client secret? This action cannot be undone."
    When I click the button "Reset"
    Then I should see the unexpected error message

#   Reset Client Secret
    Given I am not logged in
    Given I am logged in as "Andre"
    Given I am at "/application"
    Then I should see the text "app45678"
    And I click "app45678"
    Given the Drupal state "apic_app_pre_clientsecret_reset_throw_exception" is set to "TRUE"
    When I click "Subscriptions"
    Then I should see the text "Credentials"
    When I click the element with title "Manage credentials"
    When I click "Reset API Secret"
    And I should see the text "Are you sure you want to reset the client secret? This action cannot be undone."
    When I click the button "Reset"
    Then I should see the unexpected error message
    Then I delete all applications from the site


#    Given I am not logged in
#    Given I am logged in as "Andre"
#    Given I am at "/application"
#    Then I should see the text "app45678"
#    When I click "app45678"
#    Then I should see the text "app45678"
#    When I click "Subscriptions"
#    Then dump the current html

  # Broken - see https://github.ibm.com/velox/devportal/issues/3016 - now fixed
#    When I click on element "li a.subscriptionsApplication"
#    Then there are no errors
#    When I click on element ".credentialsActionsAdd a.addCredential"
#    Then I should see a "#edit-description" element
#    Given I enter "newcred" for "edit-description"
#    When I press the "Save" button
#    Then I should see the text "Your client ID is:"
#    And I should see the text "Your client secret is:"
#    Given I am not logged in
#    Given I am logged in as "Andre"
#    Given I am at "/application"
#    And I should see "app45678"
#    When I click "app45678"
#    When I click on element "li a.subscriptionsApplication"
#    Then there are no errors
#    Then I should see "newcred"
#    When I click on element ".credentialsActionsManage .credentialsMenu .deleteCredentials a"
#    Then I should see "Delete credentials for"
#    When I press the "Delete" button
#    Then I should see the text "Credentials deleted successfully."
#    When I click on element ".credentialsActionsManage .credentialsMenu .editCredentials a"
#    Then I should see "Update application credentials"
#    Given I enter "newcred2" for "edit-description"
#    When I press the "Save" button
#    Then I should see the text "Application credentials updated."
#    And I should see "newcred2"
