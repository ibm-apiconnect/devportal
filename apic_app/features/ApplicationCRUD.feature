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
