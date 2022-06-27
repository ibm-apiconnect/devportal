Feature: ApplicationCreate
  In order to use the developer portal
  I need to be able to register applications

  @api
  Scenario: Create application (green path)
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
    Given I enter "app12345xyz" for "edit-title-0-value"
    And I enter "this is some text" for "edit-apic-summary-0-value"
    When I press the "Save" button
    Then there are no errors
    And I should see the text "API Key and Secret"
    And I should see a ".appID" element
    And I should see a ".appSecret" element
    When I click "OK"
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
    Given I do not have an analytics service
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
    When I press the "Save" button
    Then there are no errors
    And I should see the text "API Key and Secret"
    And I should see a ".appID" element
    And I should see a ".appSecret" element
    When I click "OK"
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
    When I press the "Save" button
    Then there are no errors
    And I should see the text "API Key and Secret"
    And I should see a ".appID" element
    And I should see a ".appSecret" element
    When I click "OK"
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
    Given I enter "app12345xyz" for "edit-title-0-value"
    And I enter "this is some text" for "edit-apic-summary-0-value"
    And I enter "https://abc.redirect.com" for "edit-application-redirect-endpoints-0-value"
    When I press the "Save" button
    Then there are no errors
    And I should see the text "API Key and Secret"
    And I should see a ".appID" element
    And I should see a ".appSecret" element
    When I click "OK"
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
    Given I enter "app12345xyz" for "edit-title-0-value"
    And I enter "this is some text" for "edit-apic-summary-0-value"
    And I enter "https://abc.redirect.com,https://def.acme.redirect.com,https://hij.redirect.com" for "edit-application-redirect-endpoints-0-value"
    When I press the "Save" button
    Then there are no errors
    And I should see the text "API Key and Secret"
    And I should see a ".appID" element
    And I should see a ".appSecret" element
    When I click "OK"
    Then I should see the text "app12345xyz"
    And I should see the text "Subscriptions"
    And I should see the text "Edit"
    And I should see the text "Upload image"
    And I should see the text "Delete"
    And there are no errors
    Then I delete all applications from the site

  @api
  Scenario: Create application with text custom field
    Given I am not logged in
    Given users:
      | name  | pass     | mail              | status |
      | Andre | Qwert123 | andre@example.com | 1      |
    Given consumerorgs:
      | title       | name        | id     | owner |
      | andreconsumerorg | andreconsumerorg | 123456 | Andre |
    Given application entities have text type custom fields
    Given I am logged in as "Andre"
    Given I do not have any applications
    Given I am at "/application/new"
    And I should see a "#edit-field-singletext-0-value" element
    And I should see a "#edit-field-multitext-0-value" element
    And I should see a "#edit-field-multitext-1-value" element
    Given I enter "app12345xyz" for "edit-title-0-value"
    And I enter "singleVal" for "edit-field-singletext-0-value"
    And I enter "first multi val" for "edit-field-multitext-0-value"
    And I enter "theSecond" for "edit-field-multitext-1-value"
    When I press the "Save" button
    Then there are no errors
    When I click "OK"
    # And I click "Dashboard"
    Then I should see the text "singleVal"
    Then I should see the text "first multi val"
    Then I should see the text "theSecond"
    Then I delete all applications from the site
    Then I delete the text type custom fields for application entities

  @api
  Scenario: Create application with timestamp custom field
    Given I am not logged in
    Given users:
      | name  | pass     | mail              | status |
      | Andre | Qwert123 | andre@example.com | 1      |
    Given consumerorgs:
      | title       | name        | id     | owner |
      | andreconsumerorg | andreconsumerorg | 123456 | Andre |
    Given application entities have timestamp type custom fields
    Given I am logged in as "Andre"
    Given I do not have any applications
    Given I am at "/application/new"
    And I should see a "#edit-field-singletimestamp-0-value-date" element
    And I should see a "#edit-field-multitimestamp-0-value-date" element
    And I should see a "#edit-field-multitimestamp-1-value-date" element
    And I should see a "#edit-field-singletimestamp-0-value-time" element
    And I should see a "#edit-field-multitimestamp-0-value-time" element
    And I should see a "#edit-field-multitimestamp-1-value-time" element
    Given I enter "app12345xyz" for "edit-title-0-value"
    And I enter "2003-12-12" for "edit-field-singletimestamp-0-value-date"
    And I enter "2013-01-19" for "edit-field-multitimestamp-0-value-date"
    And I enter "2022-12-24" for "edit-field-multitimestamp-1-value-date"
    And I enter "00:00:00" for "edit-field-singletimestamp-0-value-time"
    And I enter "12:20:20" for "edit-field-multitimestamp-0-value-time"
    And I enter "13:13:13" for "edit-field-multitimestamp-1-value-time"
    When I press the "Save" button
    Then there are no errors
    When I click "OK"
    # And I click "Dashboard"
    Then I should see the text "12/12/2003"
    Then I should see the text "13:13"
    Then I should see the text "12/24/2022"
    Then I should see the text "12:20"
    Then I should see the text "01/19/2013"
    Then I should see the text "00:00"
    Then I delete all applications from the site
    Then I delete the timestamp type custom fields for application entities

  @api
  Scenario: Create application with datetime custom field
    Given I am not logged in
    Given users:
      | name  | pass     | mail              | status |
      | Andre | Qwert123 | andre@example.com | 1      |
    Given consumerorgs:
      | title       | name        | id     | owner |
      | andreconsumerorg | andreconsumerorg | 123456 | Andre |
    Given application entities have datetime type custom fields
    Given I am logged in as "Andre"
    Given I do not have any applications
    Given I am at "/application/new"
    And I should see a "#edit-field-singledatetime-0-value-date" element
    And I should see a "#edit-field-multidatetime-0-value-date" element
    And I should see a "#edit-field-multidatetime-1-value-date" element
    And I should see a "#edit-field-singledatetime-0-value-time" element
    And I should see a "#edit-field-multidatetime-0-value-time" element
    And I should see a "#edit-field-multidatetime-1-value-time" element
    Given I enter "app12345xyz" for "edit-title-0-value"
    And I enter "2003-12-12" for "edit-field-singledatetime-0-value-date"
    And I enter "2013-01-19" for "edit-field-multidatetime-0-value-date"
    And I enter "2022-12-24" for "edit-field-multidatetime-1-value-date"
    And I enter "00:00:00" for "edit-field-singledatetime-0-value-time"
    And I enter "12:20:20" for "edit-field-multidatetime-0-value-time"
    And I enter "13:13:13" for "edit-field-multidatetime-1-value-time"
    When I press the "Save" button
    Then there are no errors
    When I click "OK"
    # And I click "Dashboard"
    Then I should see the text "12/12/2003"
    Then I should see the text "13:13"
    Then I should see the text "12/24/2022"
    Then I should see the text "12:20"
    Then I should see the text "01/19/2013"
    Then I should see the text "00:00"
    Then I delete all applications from the site
    Then I delete the datetime type custom fields for application entities
  
 