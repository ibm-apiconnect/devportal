Feature: ApplicationUpdate
  In order to use the developer portal
  I need to be able to edit existing applications

  # needs to run in zombie driver cos goutte seems to cache the webpage meaning the updated node is not shown

  @api
  Scenario: Edit application name
    Given I am not logged in
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
    Given I enter "app23456" for "edit-title-0-value"
    And I enter "this is some text" for "edit-apic-summary-0-value"
    When I press the "Submit" button
    When I press the "Continue" button
    When I click on element ".applicationMenu .editApplication a"
    Then I should see a "#edit-title-0-value" element
    And I should see a "#edit-apic-summary-0-value" element
    And I should see a "#edit-application-redirect-endpoints-0-value" element
    And I should see a "#edit-submit" element
    Given I enter "newapp23456" for "edit-title-0-value"
    And I enter "this is some new text" for "edit-apic-summary-0-value"
    When I press the "Submit" button
    #Due to caching issues with the mocks, need to log out and log back in to see the updates
    Given I am not logged in
    Given I am logged in as "Andre"
    Given I am at "/application"
    And I should see "newapp23456"
    And I should see "this is some new text"
    And I do not have any applications


  @api
  Scenario: Create and update application as Andre
    Given I am not logged in
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
    Given I enter "app12345xyz" for "edit-title-0-value"
    And I enter "this is some text" for "edit-apic-summary-0-value"
    When I press the "Submit" button
    Then there are no errors
    And I should see the text "API Key and Secret"
    When I press the "Continue" button
    Then I should see the text "app12345xyz"
    And I should see the text "Edit"
    And I should see the text "Upload image"
    And I should see the text "Delete"
    And there are no errors
    When I click "Edit"
    Then I should see the text "Update an application"
    And I should see the text "Title"
    And I should see the text "Description"
    And I should see the text "Application OAuth Redirect URL(s)"
    And I should see a "#edit-title-0-value" element
    And I should see a "#edit-apic-summary-0-value" element
    And I should see a "#edit-application-redirect-endpoints-0-value" element
    And I should see a "#edit-submit" element
    And I should see a "#edit-cancel" element
    And there are no errors
    Given I enter "newapp23456" for "edit-title-0-value"
    And I enter "this is some new text" for "edit-apic-summary-0-value"
    When I press the "Submit" button
    #Due to caching issues with the mocks, need to log out and log back in to see the updates
    Given I am not logged in
    Given I am logged in as "Andre"
    Given I am at "/application"
    Then I should see the text "Apps"
    And I should see the text "newapp23456"
    And I should see the text "this is some new text"
    And there are no errors
    Then I delete all applications from the site


  @api
  Scenario: Update application as an anonymous user (fail)
    Given I am not logged in
    When I go to "/application"
    Then I should get a "403" HTTP response
    #Note that actual response is to redirect users to login page, but setupbehat is hardcoded to a r4032login response as follows
    And I should see the text "Access denied"
    And I should see the text "You are not authorized to access this page."
    And there are no errors


  #This test is written for current admin edit app workflow - issues 5268 & 5269 raised to change the workflow, when that work
  #is done the test will need re-writing
#  @api
#  Scenario: Update application as the admin user uid==1 (fail)
#    Given users:
#      | name              | mail              | pass                  | status |
#      | @data(admin.name) | @data(admin.mail) | @data(admin.password) | 1      |
#      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
#    Given consumerorgs:
#      | title                          | name                          | id                          | owner             |
#      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
#    Given applications:
#      | title | id          | org_id                      |
#      | MyApp | 1234567@now | @data(andre.consumerorg.id) |
#    Given I am logged in as "@data(admin.name)"
#    When I go to "/application"
#    Then I should see the text "Apps"
#    And I should see the text "MyApp"
#    When I click "MyApp"
#    Then I should see the text "MyApp"
#    And I should see the text "View"
#    And I should see the text "Edit"
#    And I should see the text "Outline"
#    And I should see the text "Delete"
#    When I click "Edit"
#    Then The field "#edit-title-0-value" should be disabled
#    And The field "#edit-apic-summary-0-value" should be disabled
#    And The field "#edit-application-image-0-upload" should be disabled
#    And The field "#edit-application-redirect-endpoints-0-value" should be disabled
#    And I should not see the text "APIC Catalog ID"
#    And I should not see the text "APIC Hostname"
#    And I should not see the text "APIC Provider ID"
#    And I should not see the text "Client type"
#    And I should not see the text "Consumer organization URL"
#    And I should not see the text "Credentials"
#    And I should not see the text "Application Data"
#    And I should not see the text "Application ID"
#    And I should not see the text "Pending Lifecycle State"
#    And I should not see the text "Lifecycle State"
#    And I should not see the text "Subscriptions"
#    And there are no errors
#    Then I delete all applications from the site


  @api
  Scenario: Update application as a user with the role viewer (fail)
    Given users:
      | name                 | mail                 | pass                     | status |
      | @data(andre[0].mail) | @data(andre[0].mail) | @data(andre[0].password) | 1      |
      | @data(andre[3].mail) | @data(andre[3].mail) | @data(andre[3].password) | 1      |
    Given consumerorgs:
      | title                             | name                             | id     | owner                |
      | @data(andre[0].consumerorg.title) | @data(andre[0].consumerorg.name) | 123456 | @data(andre[0].mail) |
    Given members:
      | consumerorgid                  | username             | roles                   |
      | 123456                         | @data(andre[3].mail) | viewer                  |
    Given applications:
      | title | id          | org_id                      |
      | MyApp | 1234567@now | 123456                      |
    Given I am logged in as "@data(andre[3].mail)"
    When I go to "/application"
    Then I should see the text "Apps"
    And I should see the text "MyApp"
    When I click "MyApp"
    Then I should see the text "Applications"
    And I should see the text "MyApp"
    And I should not see the text "Edit"
    When I go to "/application/1234567@now/edit"
    Then I should get a "403" HTTP response
    And I should see the text "Access denied"
    And I should see the text "You are not authorized to access this page."
    And there are no errors
    Then I delete all applications from the site


  @api
  Scenario: Update application as a user with the role administrator
    Given users:
      | name                 | mail                 | pass                     | status |
      | @data(andre[0].mail) | @data(andre[0].mail) | @data(andre[0].password) | 1      |
      | @data(andre[1].mail) | @data(andre[1].mail) | @data(andre[1].password) | 1      |
    Given consumerorgs:
      | title                             | name                             | id         | owner                |
      | @data(andre[0].consumerorg.title) | @data(andre[0].consumerorg.name) | 123456     | @data(andre[0].mail) |
    Given members:
      | consumerorgid                  | username             | roles                   |
      | 123456                         | @data(andre[1].mail) | administrator           |
    Given applications:
      | title | id          | org_id                      |
      | MyApp | 1234567@now | 123456                      |
    Given I am logged in as "@data(andre[1].mail)"
    When I go to "/application"
    Then I should see the text "Apps"
    And I should see the text "MyApp"
    When I click "MyApp"
    Then I should see the text "Applications"
    And I should see the text "MyApp"
    And I should see the text "Edit"
    When I click "Edit"
    Then I should see the text "Update an application"
    And I should see the text "Title"
    And I should see the text "Description"
    And I should see the text "Application OAuth Redirect URL(s)"
    And I should see a "#edit-title-0-value" element
    And I should see a "#edit-apic-summary-0-value" element
    And I should see a "#edit-application-redirect-endpoints-0-value" element
    And I should see a "#edit-submit" element
    And I should see a "#edit-cancel" element
    And there are no errors
    Given I enter "newapp23456" for "edit-title-0-value"
    And I enter "this is some new text" for "edit-apic-summary-0-value"
    When I press the "Submit" button
    #Due to caching issues with the mocks, need to log out and log back in to see the updates
    Given I am not logged in
    Given I am logged in as "@data(andre[1].mail)"
    Given I am at "/application"
    Then I should see the text "Apps"
    And I should see the text "newapp23456"
    And I should see the text "this is some new text"
    And there are no errors
    Then I delete all applications from the site



  @api
  Scenario: As Andre I can update the certificate setting of my application
    Given users:
      | name                 | mail                 | pass                     | status |
      | @data(andre[0].mail) | @data(andre[0].mail) | @data(andre[0].password) | 1      |
    Given consumerorgs:
      | title                             | name                             | id         | owner                |
      | @data(andre[0].consumerorg.title) | @data(andre[0].consumerorg.name) | 123456     | @data(andre[0].mail) |
    Given applications:
      | title | id          | org_id                      |
      | MyApp | 1234567@now | 123456                      |
    Given application certificates are enabled
    Given I am logged in as "@data(andre[0].mail)"
    When I go to "/application"
    Then I should see the text "Apps"
    And I should see the text "MyApp"
    When I click "MyApp"
    Then I should see the text "Applications"
    And I should see the text "MyApp"
    And I should see the text "Edit"
    When I click "Edit"
    Then I should see the text "Update an application"
    And I should see the text "Title"
    And I should see the text "Certificate"
    And I should see the tooltip text "Paste the content of your application's x509 certificate."
    And I should see a "#edit-certificate" element
    Given I enter "newapp23456" for "edit-title-0-value"
    And I enter "cert23456" for "edit-certificate"
    When I press the "Submit" button
    #Due to caching issues with the mocks, need to log out and log back in to see the updates
    Given I am not logged in
    Given I am logged in as "@data(andre[0].mail)"
    Given I am at "/application"
    Then I should see the text "Apps"
    And I should see the text "newapp23456"
    And there are no errors
    Then I delete all applications from the site
    Given application certificates are disabled


  @api
  Scenario: As Andre I can update the OAuth redirect endpoint of my application
    Given users:
      | name                 | mail                 | pass                     | status |
      | @data(andre[0].mail) | @data(andre[0].mail) | @data(andre[0].password) | 1      |
    Given consumerorgs:
      | title                             | name                             | id         | owner                |
      | @data(andre[0].consumerorg.title) | @data(andre[0].consumerorg.name) | 123456     | @data(andre[0].mail) |
    Given applications:
      | title | id          | org_id                      |
      | MyApp | 1234567@now | 123456                      |
    Given I am logged in as "@data(andre[0].mail)"
    When I go to "/application"
    Then I should see the text "Apps"
    And I should see the text "MyApp"
    When I click "MyApp"
    Then I should see the text "Applications"
    And I should see the text "MyApp"
    And I should see the text "Edit"
    When I click "Edit"
    Then I should see the text "Update an application"
    And I should see the text "Title"
    And I should see the text "Description"
    And I should see the text "Application OAuth Redirect URL(s)"
    And I should see a "#edit-title-0-value" element
    And I should see a "#edit-apic-summary-0-value" element
    And I should see a "#edit-application-redirect-endpoints-0-value" element
    And I should see a "#edit-submit" element
    And I should see a "#edit-cancel" element
    And there are no errors
    Given I enter "https://abc.redirect.com" for "edit-application-redirect-endpoints-0-value"
    When I press the "Submit" button
    #Due to caching issues with the mocks, need to log out and log back in to see the updates
    Given I am not logged in
    Given I am logged in as "@data(andre[0].mail)"
    Given I am at "/application"
    When I click "MyApp"
    Then I should see the text "Application OAuth Redirect URL(s)"
    And I should see the text "https://abc.redirect.com"
    And there are no errors
    Then I delete all applications from the site


  @api
  Scenario: As Andre I can update multiple OAuth redirect endpoints of my application
    Given users:
      | name                 | mail                 | pass                     | status |
      | @data(andre[0].mail) | @data(andre[0].mail) | @data(andre[0].password) | 1      |
    Given consumerorgs:
      | title                             | name                             | id         | owner                |
      | @data(andre[0].consumerorg.title) | @data(andre[0].consumerorg.name) | 123456     | @data(andre[0].mail) |
    Given applications:
      | title | id          | org_id                      |
      | MyApp | 1234567@now | 123456                      |
    Given I am logged in as "@data(andre[0].mail)"
    When I go to "/application"
    Then I should see the text "Apps"
    And I should see the text "MyApp"
    When I click "MyApp"
    Then I should see the text "Applications"
    And I should see the text "MyApp"
    And I should see the text "Edit"
    When I click "Edit"
    Then I should see the text "Update an application"
    And I should see the text "Title"
    And I should see the text "Description"
    And I should see the text "Application OAuth Redirect URL(s)"
    And I should see a "#edit-title-0-value" element
    And I should see a "#edit-apic-summary-0-value" element
    And I should see a "#edit-application-redirect-endpoints-0-value" element
    And I should see a "#edit-submit" element
    And I should see a "#edit-cancel" element
    And there are no errors
    Given I enter "https://abc.redirect.com" for "edit-application-redirect-endpoints-0-value"
    And I press the "Add another item" button
    Then I should see a "#edit-application-redirect-endpoints-1-value" element
    Given I enter "https://def.redirect.com" for "edit-application-redirect-endpoints-1-value"
    And I press the "Add another item" button
    Then I should see a "#edit-application-redirect-endpoints-2-value" element
    Given I enter "https://hij.redirect.com" for "edit-application-redirect-endpoints-2-value"
    When I press the "Submit" button
    #Due to caching issues with the mocks, need to log out and log back in to see the updates
    Given I am not logged in
    Given I am logged in as "@data(andre[0].mail)"
    Given I am at "/application"
    When I click "MyApp"
    Then I should see the text "Application OAuth Redirect URL(s)"
    And I should see the text "https://abc.redirect.com"
    And I should see the text "https://def.redirect.com"
    And I should see the text "https://hij.redirect.com"
    And there are no errors
    Then I delete all applications from the site


#Commenting out this scenario as the mocks for creating an OAuth redirect url don't work
#  @api
#  Scenario: As Andre I can create and edit multiple OAuth redirect endpoints of my application
#    Given I am not logged in
#    Given users:
#      | name                 | mail                 | pass                     | status |
#      | @data(andre[0].mail) | @data(andre[0].mail) | @data(andre[0].password) | 1      |
#    Given consumerorgs:
#      | title                             | name                             | id         | owner                |
#      | @data(andre[0].consumerorg.title) | @data(andre[0].consumerorg.name) | 123456     | @data(andre[0].mail) |
#    Given I am logged in as "@data(andre[0].mail)"
#    Given I do not have any applications
#    Given I am at "/application/new"
#    And I should see a "#edit-title-0-value" element
#    And I should see a "#edit-apic-summary-0-value" element
#    And I should see a "#edit-application-redirect-endpoints-0-value" element
#    And I should see a "#edit-submit" element
#    Given I enter "app12345xyz" for "edit-title-0-value"
#    And I enter "this is some text" for "edit-apic-summary-0-value"
#    And I enter "https://abc.redirect.com" for "edit-application-redirect-endpoints-0-value"
#    When I press the "Submit" button
#    Then there are no errors
#    And I should see the text "API Key and Secret"
#    When I press the "Continue" button
#    Then I should see the text "app12345xyz"
#    #Due to caching issues with the mocks, need to log out and log back in to see the updates
#    Given I am not logged in
#    Given I am logged in as "@data(andre[0].mail)"
#    Given I am at "/application"
#    When I click "app12345xyz"
#    Then I should see the text "Application OAuth Redirect URL(s)"
#    And I should see the text "https://abc.redirect.com"
#    Then dump the current html

