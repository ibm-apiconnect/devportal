Feature: ApplicationCreate
  In order to use the developer portal
  I need to be able to register applications

  @api
  Scenario: Create application (green path)
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
    And I should see a ".appID" element
    And I should see a ".appSecret" element
    When I press the "Continue" button
    Then I should see the text "app12345xyz"
    And I should see the text "Subscriptions"
    And I should see the text "Edit"
    And I should see the text "Upload image"
    And I should see the text "Delete"
    And there are no errors
    Then I delete all applications from the site


  @api
  Scenario: Create application as an anonymous user (fail)
    Given I am not logged in
    Given I do not have any applications
    When I go to "/application/new"
    Then I should get a "403" HTTP response
    #Note that actual response is to redirect users to login page, but setupbehat is hardcoded to a r4032login response as follows
    And I should see the text "Access denied"
    And I should see the text "You are not authorized to access this page."
    And there are no errors


  @api
  Scenario: Create application as the admin user uid==1 (fail)
    Given users:
      | name              | mail              | pass                  | status |
      | @data(admin.name) | @data(admin.mail) | @data(admin.password) | 1      |
    Given I am logged in as "@data(admin.name)"
    When I go to "/application/new"
    Then I should get a "403" HTTP response
    And I should see the text "Access denied"
    And I should see the text "You are not authorized to access this page."
    And there are no errors


  @api
  Scenario: Create application as a user with the role viewer (fail)
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
    Given I am logged in as "@data(andre[3].mail)"
    And I do not have any applications
    When I go to "/application/new"
    Then I should get a "403" HTTP response
    #Note that actual response is to redirect users to login page, but setupbehat is hardcoded to a r4032login response as follows
    And I should see the text "Access denied"
    And I should see the text "You are not authorized to access this page."
    And there are no errors


  @api
  Scenario: Create application as a user with the role developer
    Given users:
      | name                 | mail                 | pass                     | status |
      | @data(andre[0].mail) | @data(andre[0].mail) | @data(andre[0].password) | 1      |
      | @data(andre[4].mail) | @data(andre[4].mail) | @data(andre[4].password) | 1      |
    Given consumerorgs:
      | title                             | name                             | id     | owner                |
      | @data(andre[0].consumerorg.title) | @data(andre[0].consumerorg.name) | 123456 | @data(andre[0].mail) |
    Given members:
      | consumerorgid                  | username             | roles                 |
      | 123456                         | @data(andre[4].mail) | developer             |
    Given I am logged in as "@data(andre[4].mail)"
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
    And I should see a ".appID" element
    And I should see a ".appSecret" element
    When I press the "Continue" button
    Then I should see the text "app12345xyz"
    And I should see the text "Subscriptions"
    And I should see the text "Edit"
    And I should see the text "Upload image"
    And I should see the text "Delete"
    And there are no errors
    Then I delete all applications from the site


  @api
  Scenario: Create application as a user with the role administrator
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
    Given I am logged in as "@data(andre[1].mail)"
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
    And I should see a ".appID" element
    And I should see a ".appSecret" element
    When I press the "Continue" button
    Then I should see the text "app12345xyz"
    And I should see the text "Subscriptions"
    And I should see the text "Edit"
    And I should see the text "Upload image"
    And I should see the text "Delete"
    And there are no errors
    Then I delete all applications from the site


  @api
  Scenario: Create an application with one OAuth redirect endpoint
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
    And I enter "https://abc.redirect.com" for "edit-application-redirect-endpoints-0-value"
    When I press the "Submit" button
    Then there are no errors
    And I should see the text "API Key and Secret"
    And I should see a ".appID" element
    And I should see a ".appSecret" element
    When I press the "Continue" button
    Then I should see the text "app12345xyz"
    And I should see the text "Subscriptions"
    And I should see the text "Edit"
    And I should see the text "Upload image"
    And I should see the text "Delete"
    And there are no errors
    Then I delete all applications from the site


  @api
  Scenario: Create an application with multiple OAuth redirect endpoints
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
    And I enter "https://abc.redirect.com,https://def.acme.redirect.com,https://hij.redirect.com" for "edit-application-redirect-endpoints-0-value"
    When I press the "Submit" button
    Then there are no errors
    And I should see the text "API Key and Secret"
    And I should see a ".appID" element
    And I should see a ".appSecret" element
    When I press the "Continue" button
    Then I should see the text "app12345xyz"
    And I should see the text "Subscriptions"
    And I should see the text "Edit"
    And I should see the text "Upload image"
    And I should see the text "Delete"
    And there are no errors
    Then I delete all applications from the site
