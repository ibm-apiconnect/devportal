@api @subscription_migration
Feature: Subscription Migration Service
  In order to keep subscriptions up to date
  As a system
  I need to have a service that can detect available subscription migrations

  Scenario: Service returns empty array when user has no subscriptions
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                          | name                          | id                          | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
    Given I am logged in as "@data(andre.mail)"
    When I call the subscription migration service
    Then the migration service should return an empty array

  Scenario: Service returns empty array when subscriptions have no newer versions
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                          | name                          | id                          | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
    Given I am logged in as "@data(andre.mail)"
    And I create an application named "TestApp" id "app-test-@now" consumerorgurl "/consumer-orgs/1234/5678/@data(andre.consumerorg.id)"
    And I publish a product with the name "product_@now", id "prod_@now", apis "api_@now" and visibility "pub" true true
    And subscriptions:
      | org_id                      | app_id         | sub_id      | product    | plan    |
      | @data(andre.consumerorg.id) | app-test-@now  | sub-@now    | prod_@now  | default |
    When I call the subscription migration service
    Then the migration service should return an empty array

  Scenario: Service executes without errors for authenticated user
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                          | name                          | id                          | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
    Given I am logged in as "@data(andre.mail)"
    When I call the subscription migration service
    Then the service should execute without throwing exceptions

  Scenario: User sees migration notification message on login with single application
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                          | name                          | id                          | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
    And I publish a product with the name "product_v1_@now", id "prod_v1_@now", apis "api_@now" and visibility "pub" true true
    And I publish a product with the name "product_v2_@now", id "prod_v2_@now", apis "api_@now" and visibility "pub" true true
    And I create an application named "TestApp" id "app-test-@now" consumerorgurl "/consumer-orgs/1234/5678/@data(andre.consumerorg.id)"
    And I create a subscription for app "app-test-@now" to product "prod_v1_@now" plan "default-plan" in org "@data(andre.consumerorg.id)"
    And product "prod_v1_@now" is superseded by "prod_v2_@now"
    When I am logged in as "@data(andre.mail)"
    Then I should see the migration notification message

  Scenario: User sees migration notification message on login with multiple applications
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                          | name                          | id                          | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
    And I publish a product with the name "product_v1_@now", id "prod_v1_@now", apis "api_@now" and visibility "pub" true true
    And I publish a product with the name "product_v2_@now", id "prod_v2_@now", apis "api_@now" and visibility "pub" true true
    And I create an application named "TestApp1" id "app-test-1-@now" consumerorgurl "/consumer-orgs/1234/5678/@data(andre.consumerorg.id)"
    And I create an application named "TestApp2" id "app-test-2-@now" consumerorgurl "/consumer-orgs/1234/5678/@data(andre.consumerorg.id)"
    And I create a subscription for app "app-test-1-@now" to product "prod_v1_@now" plan "default-plan" in org "@data(andre.consumerorg.id)"
    And I create a subscription for app "app-test-2-@now" to product "prod_v1_@now" plan "default-plan" in org "@data(andre.consumerorg.id)"
    And product "prod_v1_@now" is superseded by "prod_v2_@now"
    When I am logged in as "@data(andre.mail)"
    Then I should see the migration notification message

  Scenario: User does not see migration notification when no migrations available
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                          | name                          | id                          | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
    And I create an application named "TestApp" id "app-test-@now" consumerorgurl "/consumer-orgs/1234/5678/@data(andre.consumerorg.id)"
    And I publish a product with the name "product_@now", id "prod_@now", apis "api_@now" and visibility "pub" true true
    And subscriptions:
      | org_id                      | app_id         | sub_id      | product    | plan    |
      | @data(andre.consumerorg.id) | app-test-@now  | sub-@now    | prod_@now  | default |
    When I am logged in as "@data(andre.mail)"
    Then I should not see the text "You are currently subscribed to a deprecated product"
    And I should not see the text "You are currently subscribed to deprecated products"
