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
      | title     | name      | id                       | owner     | tags    |
      | a1_consumerorg | a1-consumerorg | a18843f3e4b07631568a159d | andre_one | testers |
      | a2_consumerorg | a2-consumerorg | a28843f3e4b07631568a159d | andre_two | others  |
# For this to work, we need to create an API and then include it in a product
    Given I am logged in as "andre_one"
    Then I should not see the text "Unrecognized username or password"
    And I should not see the text "Log in"
    Given I publish an api with the name "api1_@now"
# publish a product visible to andre_one's organisation containing the API
    Given I publish a product name "product:1_@now", id "productId1_@now", apis "api1_@now" and visible to org "a18843f3e4b07631568a159d"
    Then I should have an api name "api1_@now" visible by "andre_one"