Feature: CredentialsCRUD
  In order to use the developer portal
  I need to be able to add, update and delete credentials to existing applications

  # needs to run in zombie driver cos goutte seems to cache the webpage meaning the updated node is not shown
  @api
  Scenario: Add, update and delete application credentials
    Given users:
      | name  | pass     | mail              | status |
      | Andre | Qwert123 | andre@example.com | 1      |
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
    Given I enter "app12345" for "edit-title-0-value"
    And I enter "this is some text" for "edit-apic-summary-0-value"
    When I press the "Submit" button
    When I press the "Continue" button
  # Broken - see https://github.ibm.com/apimesh/devportal/issues/3016
#    When I click on element "li a.subscriptionsApplication"
#    Then there are no errors
#    When I click on element ".credentialsActionsAdd a.addCredential"
#    Then I should see a "#edit-description" element
#    Given I enter "newcred" for "edit-description"
#    When I press the "Submit" button
#    Then I should see the text "Your client ID is:"
#    And I should see the text "Your client secret is:"
#    Given I am not logged in
#    Given I am logged in as "Andre"
#    Given I am at "/application"
#    And I should see "app12345"
#    When I click "app12345"
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
#    When I press the "Submit" button
#    Then I should see the text "Application credentials updated."
#    And I should see "newcred2"
