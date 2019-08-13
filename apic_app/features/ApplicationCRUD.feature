Feature: ApplicationCRUD
  In order to use the developer portal
  I need to be able to use applications

  @api
  Scenario: Content type exists
    Given I am at "/"
    Then The "application" content type is present

  @mocked
  @api
  Scenario: Create an application
    Given I am not logged in
    Given users:
      | name  | pass     | mail              | status |
      | Andre | Qwert123 | andre@example.com | 1      |
    Given consumerorgs:
      | title       | name        | id     | owner |
      | andreconsumerorg | andreconsumerorg | 123456 | Andre |
    Given I am logged in as "Andre"
    Given I create an application named "myapp_@now" id "myid_@now" consumerorgurl "/consumer-orgs/1234/5678/123456"
    Then I should have an application named "myapp_@now" id "myid_@now"

  @mocked
  @api
  Scenario: Update an application
    Given I am not logged in
    Given users:
      | name  | pass     | mail              | status |
      | Andre | Qwert123 | andre@example.com | 1      |
    Given consumerorgs:
      | title       | name        | id     | owner |
      | andreconsumerorg | andreconsumerorg | 123456 | Andre |
    Given I am logged in as "Andre"
    Given I create an application named "myapp_@now" id "myid_@now" consumerorgurl "/consumer-orgs/1234/5678/123456"
    Then I should have an application named "myapp_@now" id "myid_@now"
    When I update the application named "myapp_@now" to be called "mynewapp_@now"
    Then I should have an application named "mynewapp_@now" id "myid_@now"

  @mocked
  @api
  Scenario: Test createOrUpdate for an application
    Given I am not logged in
    Given users:
      | name  | pass     | mail              | status |
      | Andre | Qwert123 | andre@example.com | 1      |
    Given consumerorgs:
      | title       | name        | id     | owner |
      | andreconsumerorg | andreconsumerorg | 123456 | Andre |
    Given I am logged in as "Andre"
    Given I do not have an application named "myapp_@now"
    When I createOrUpdate an application named "myapp_@now" id "myid_@now" consumerorgurl "/consumer-orgs/1234/5678/123456"
    Then The createOrUpdate output should be "1"
    Then I should have an application named "myapp_@now" id "myid_@now"
    When I createOrUpdate an application named "myapp_@now" id "myid_@now" consumerorgurl "/consumer-orgs/1234/5678/123456"
    Then The createOrUpdate output should be "0"
    Then I should have an application named "mynewapp_@now" id "myid_@now"

  @mocked
  @api
  Scenario: Delete an application
    Given I am not logged in
    Given users:
      | name  | pass     | mail              | status |
      | Andre | Qwert123 | andre@example.com | 1      |
    Given consumerorgs:
      | title       | name        | id     | owner |
      | andreconsumerorg | andreconsumerorg | 123456 | Andre |
    Given I am logged in as "Andre"
    Given I create an application named "myapp_@now" id "myid_@now" consumerorgurl "/consumer-orgs/1234/5678/123456"
    Then I should have an application named "myapp_@now" id "myid_@now"
    When I delete the application named "myapp_@now"
    Then I should not have an application named "mynewapp_@now"

  @api
  Scenario: Admin cannot edit the Application
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
      | @data(admin.name) | @data(admin.mail) | @data(admin.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
    Given applications:
      | title    | id    | org_id      |
      | MyNewApp | 1234567@now | @data(andre.consumerorg.id) |
    Given I am logged in as "@data(admin.name)"
    And I am editing the "application" node "MyNewApp"
    Then The field "#edit-title-0-value" should be disabled
    Then The field "#edit-apic-summary-0-value" should be disabled
    Then The field "#edit-application-image-0-upload" should be disabled
    Then The field "#edit-application-redirect-endpoints-0-value" should be disabled
    And I should not see the text "APIC Catalog ID"
    And I should not see the text "APIC Hostname"
    And I should not see the text "APIC Provider ID"
    And I should not see the text "Client type"
    And I should not see the text "Consumer organization URL"
    And I should not see the text "Credentials"
    And I should not see the text "Application Data"
    And I should not see the text "Application ID"
    And I should not see the text "Pending Lifecycle State"
    And I should not see the text "Lifecycle State"
    And I should not see the text "Subscriptions"
    And there are no errors