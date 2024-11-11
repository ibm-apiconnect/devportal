@api
Feature: APIACL
  In order to use the developer portal
  I need to be able to access APIs that I should be allowed to access

  @acl
  Scenario: Create APIs with various ACLs and test that they can be accessed
# Hard coding these for now.  For full stack testing, substitute with real users/consumerorgs
# in the Then clauses below
    Given users:
      | name      | mail                  | pass     | status |
      | andre_one | andre_one@example.com | Qwert123IsBadPassword! | 1      |
      | andre_two | andre_two@example.com | Qwert123IsBadPassword! | 1      |
      | @data(admin.name) | @data(admin.mail) | @data(admin.password) | 1      |
    Given consumerorgs:
      | title          | name           | id                       | owner     | tags                           |
      | a1_consumerorg | a1-consumerorg | a18843f3e4b07631568a159d | andre_one | /consumer-api/groups/567804321 |
      | a2_consumerorg | a2-consumerorg | a28843f3e4b07631568a159d | andre_two | /consumer-api/groups/455223456 |
# For this to work, we need to create an API and then include it in a product
    Given I have no products or apis
    Given I am logged in as "andre_one"
    Then I should not see the text "Unrecognized username or password"
    And I should not see the text "Log in"
    Given I publish an api with the name "api1_@now"
    Given I publish an api with the name "api2_@now"
    Given I publish an api with the name "api3_@now"
    Given I publish an api with the name "api4_@now"
    Given I publish an api with the name "api5_@now"
    Given I publish a product with the name "product1_@now", id "product1_@now", apis "api1_@now" and visibility "pub" true true
    Given I publish a product with the name "product2_@now", id "product2_@now", apis "api2_@now" and visibility "auth" true true
    Given I publish a product with the name "product3_@now", id "product3_@now", apis "api3_@now" and visibility "org_urls" "/consumer-orgs/1234/5678/a18843f3e4b07631568a159d" true
    Given I publish a product with the name "product4_@now", id "product4_@now", apis "api4_@now" and visibility "tags" "/consumer-api/groups/567804321" true
    Given I publish a product with the name "product5_@now", id "product5_@now", apis "api5_@now" and visibility "pub" true false
    # note we rely on x-pathalias being set in the api to hit it.

   # ---------------- andre_one Tests -------------------------------------
    Given I am on "/api"
    Then I should see the link "api1_@now"
    Then I should see the link "api2_@now"
    Then I should see the link "api3_@now"
    Then I should see the link "api4_@now"
    Then I should not see the link "api5_@now"

    Given I am on "/api/api1_@now"
    Then the response status code should be 200
    # Then I should not be on "/search/content"
    And there are no errors
    And I should not see the text "The page you requested does not exist."
    Given I am on "/api/api2_@now"
    Then the response status code should be 200
    # Then I should not be on "/search/content"
    And there are no errors
    And I should not see the text "The page you requested does not exist."
    Given I am on "/api/api3_@now"
    Then the response status code should be 200
    # Then I should not be on "/search/content"
    And there are no errors
    And I should not see the text "The page you requested does not exist."
    Given I am on "/api/api4_@now"
    Then the response status code should be 200
    # Then I should not be on "/search/content"
    And there are no errors
    And I should not see the text "The page you requested does not exist."
    Given I am on "/api/api5_@now"
    # Redirect to page not found
    Then I should be on "/search/content"

    Given I am on "/productselect/api1_@now"
    Then the response status code should be 200
    # Then I should not be on "/search/content"
    And there are no errors
    And I should not see the text "The page you requested does not exist."
    Given I am on "/productselect/api2_@now"
    Then the response status code should be 200
    # Then I should not be on "/search/content"
    And there are no errors
    And I should not see the text "The page you requested does not exist."
    Given I am on "/productselect/api3_@now"
    Then the response status code should be 200
    # Then I should not be on "/search/content"
    And there are no errors
    And I should not see the text "The page you requested does not exist."
    Given I am on "/productselect/api4_@now"
    Then the response status code should be 200
    # Then I should not be on "/search/content"
    And there are no errors
    And I should not see the text "The page you requested does not exist."
    Given I am on "/productselect/api5_@now"
    # Redirect to page not found
    Then I should be on "/search/content"


    Given I am on "/product/product1_@now/api/api1_@now"
    Then the response status code should be 200
    # Then I should not be on "/search/content"
    And there are no errors
    And I should not see the text "The page you requested does not exist."
    Given I am on "/product/product2_@now/api/api2_@now"
    Then the response status code should be 200
    # Then I should not be on "/search/content"
    And there are no errors
    And I should not see the text "The page you requested does not exist."
    Given I am on "/product/product3_@now/api/api3_@now"
    Then the response status code should be 200
    # Then I should not be on "/search/content"
    And there are no errors
    And I should not see the text "The page you requested does not exist."
    Given I am on "/product/product4_@now/api/api4_@now"
    Then the response status code should be 200
    # Then I should not be on "/search/content"
    And there are no errors
    And I should not see the text "The page you requested does not exist."
    Given I am on "/product/product4_@now/api/api5_@now"
    # Redirect to page not found
    Then I should be on "/search/content"

   # ---------------- andre_two Tests -------------------------------------
    Given I am not logged in
    Given I am logged in as "andre_two"
    Then I should not see the text "Unrecognized username or password"
    And I should not see the text "Log in"

    Given I am on "/api"
    Then I should see the link "api1_@now"
    Then I should see the link "api2_@now"
    Then I should not see the link "api3_@now"
    Then I should not see the link "api4_@now"
    Then I should not see the link "api5_@now"

    Given I am on "/api/api1_@now"
    Then the response status code should be 200
    # Then I should not be on "/search/content"
    And there are no errors
    And I should not see the text "The page you requested does not exist."
    Given I am on "/api/api2_@now"
    Then the response status code should be 200
    # Then I should not be on "/search/content"
    And there are no errors
    And I should not see the text "The page you requested does not exist."
    Given I am on "/api/api3_@now"
    # Redirect to page not found
    Then I should be on "/search/content"
    Given I am on "/api/api4_@now"
    # Redirect to page not found
    Then I should be on "/search/content"
    Given I am on "/api/api5_@now"
    # Redirect to page not found
    Then I should be on "/search/content"

    Given I am on "/productselect/api1_@now"
    Then the response status code should be 200
    # Then I should not be on "/search/content"
    And there are no errors
    And I should not see the text "The page you requested does not exist."
    Given I am on "/productselect/api2_@now"
    Then the response status code should be 200
    # Then I should not be on "/search/content"
    And there are no errors
    And I should not see the text "The page you requested does not exist."
    Given I am on "/productselect/api3_@now"
    # Redirect to page not found
    Then I should be on "/search/content"
    Given I am on "/productselect/api4_@now"
    # Redirect to page not found
    Then I should be on "/search/content"
    Given I am on "/productselect/api5_@now"
    # Redirect to page not found
    Then I should be on "/search/content"


    Given I am on "/product/product1_@now/api/api1_@now"
    Then the response status code should be 200
    # Then I should not be on "/search/content"
    And there are no errors
    And I should not see the text "The page you requested does not exist."
    Given I am on "/product/product2_@now/api/api2_@now"
    Then the response status code should be 200
    # Then I should not be on "/search/content"
    And there are no errors
    And I should not see the text "The page you requested does not exist."
    Given I am on "/product/product3_@now/api/api3_@now"
    # Redirect to page not found
    Then I should be on "/search/content"
    Given I am on "/product/product4_@now/api/api4_@now"
    # Redirect to page not found
    Then I should be on "/search/content"
    Given I am on "/product/product4_@now/api/api5_@now"
    # Redirect to page not found
    Then I should be on "/search/content"

   # ---------------- Admin Tests -------------------------------------

    Given I am not logged in
    Given I am logged in as "@data(admin.name)"
    Then I should not see the text "Unrecognized username or password"
    And I should not see the text "Log in"

    Given I am on "/api"
    Then I should see the link "api1_@now"
    Then I should see the link "api2_@now"
    Then I should see the link "api3_@now"
    Then I should see the link "api4_@now"
    Then I should see the link "api5_@now"

    Given I am on "/api/api1_@now"
    Then the response status code should be 200
    # Then I should not be on "/search/content"
    And there are no errors
    And I should not see the text "The page you requested does not exist."
    Given I am on "/api/api2_@now"
    Then the response status code should be 200
    # Then I should not be on "/search/content"
    And there are no errors
    And I should not see the text "The page you requested does not exist."
    Given I am on "/api/api3_@now"
    Then the response status code should be 200
    # Then I should not be on "/search/content"
    And there are no errors
    And I should not see the text "The page you requested does not exist."
    Given I am on "/api/api4_@now"
    Then the response status code should be 200
    # Then I should not be on "/search/content"
    And there are no errors
    And I should not see the text "The page you requested does not exist."
    Given I am on "/api/api5_@now"
    Then the response status code should be 200
    # Then I should not be on "/search/content"
    And there are no errors
    And I should not see the text "The page you requested does not exist."

    Given I am on "/productselect/api1_@now"
    Then the response status code should be 200
    # Then I should not be on "/search/content"
    And there are no errors
    And I should not see the text "The page you requested does not exist."
    Given I am on "/productselect/api2_@now"
    Then the response status code should be 200
    # Then I should not be on "/search/content"
    And there are no errors
    And I should not see the text "The page you requested does not exist."
    Given I am on "/productselect/api3_@now"
    Then the response status code should be 200
    # Then I should not be on "/search/content"
    And there are no errors
    And I should not see the text "The page you requested does not exist."
    Given I am on "/productselect/api4_@now"
    Then the response status code should be 200
    # Then I should not be on "/search/content"
    And there are no errors
    And I should not see the text "The page you requested does not exist."
    Given I am on "/productselect/api5_@now"
    Then the response status code should be 200
    # Then I should not be on "/search/content"
    And there are no errors
    And I should not see the text "The page you requested does not exist."

    Given I am on "/product/product1_@now/api/api1_@now"
    Then the response status code should be 200
    # Then I should not be on "/search/content"
    And there are no errors
    And I should not see the text "The page you requested does not exist."
    Given I am on "/product/product2_@now/api/api2_@now"
    Then the response status code should be 200
    # Then I should not be on "/search/content"
    And there are no errors
    And I should not see the text "The page you requested does not exist."
    Given I am on "/product/product3_@now/api/api3_@now"
    Then the response status code should be 200
    # Then I should not be on "/search/content"
    And there are no errors
    And I should not see the text "The page you requested does not exist."
    Given I am on "/product/product4_@now/api/api4_@now"
    Then the response status code should be 200
    # Then I should not be on "/search/content"
    And there are no errors
    And I should not see the text "The page you requested does not exist."
    Given I am on "/product/product4_@now/api/api5_@now"
    Then the response status code should be 200
    # Then I should not be on "/search/content"
    And there are no errors
    And I should not see the text "The page you requested does not exist."
