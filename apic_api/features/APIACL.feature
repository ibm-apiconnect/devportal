Feature: APIACL
  In order to use the developer portal
  I need to be able to access APIs that I should be allowed to access

  @acl
  @api
  Scenario: Create APIs with various ACLs and test that they can be accessed
# Hard coding these for now.  For full stack testing, substitute with real users/consumerorgs
# in the Then clauses below
    Given users:
      | name      | mail                  | pass     | status |
      | andre_one | andre_one@example.com | Qwert123 | 1      |
      | andre_two | andre_two@example.com | Qwert123 | 1      |
    Given consumerorgs:
      | title          | name           | id                       | owner     | tags    |
      | a1_consumerorg | a1-consumerorg | a18843f3e4b07631568a159d | andre_one | testers |
      | a2_consumerorg | a2-consumerorg | a28843f3e4b07631568a159d | andre_two | others  |
# For this to work, we need to create an API and then include it in a product
    Given I am logged in as "andre_one"
    Then I should not see the text "Unrecognized username or password"
    And I should not see the text "Log in"
    Given I publish a product with the name "product1_@now", id "product1_@now", apis "api1_@now" and visibility "pub" true
    Given I publish a product with the name "product2_@now", id "product2_@now", apis "api2_@now" and visibility "auth" true
    Given I publish a product with the name "product3_@now", id "product3_@now", apis "api3_@now" and visibility "org_urls" "/consumer-orgs/1234/5678/a18843f3e4b07631568a159d"
    Given I publish a product with the name "product4_@now", id "product4_@now", apis "api4_@now" and visibility "tags" "testers"
    Given I publish an api with the name "api1_@now"
    Given I publish an api with the name "api2_@now"
    Given I publish an api with the name "api3_@now"
    Given I publish an api with the name "api4_@now"
    # note we rely on x-pathalias being set in the api to hit it.
    Given I am on "/api/api1_@now"
    Then the response status code should be 200
    Given I am on "/api/api2_@now"
    Then the response status code should be 200
    Given I am on "/api/api3_@now"
    Then the response status code should be 200
    Given I am on "/api/api4_@now"
    Then the response status code should be 200

    Given I am not logged in
    Given I am logged in as "andre_two"
    Then I should not see the text "Unrecognized username or password"
    And I should not see the text "Log in"
    Given I am on "/api/api1_@now"
    Then the response status code should be 200
    Given I am on "/api/api2_@now"
    Then the response status code should be 200
    Given I am on "/api/api3_@now"
    Then the response status code should be 404
    Given I am on "/api/api4_@now"
    Then the response status code should be 404
