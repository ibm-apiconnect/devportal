Feature: ProductACL
  In order to use the developer portal
  I need to be able to access products I should be allowed to access

  @acl
  @api
  Scenario: Create Products with various ACLs and test that they can be accessed
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

    # First log in as andre one
    Given I am logged in as "andre_one"
    Then I should not see the text "Unrecognized username or password"
    And I should not see the text "Log in"

    # Create a public product, check that it's visible
    Given I publish a product with the name "product:1_@now", id "productId1_@now" and visibility "pub" true
    Then The product with the name "product:1_@now" and id "productId1_@now" should be visible

    # Create an authenticated product, check that it's visible
    Given I publish a product with the name "product:2_@now", id "productId2_@now" and visibility "auth" true
    Then The product with the name "product:2_@now" and id "productId2_@now" should be visible

    # Create an organisation product, check that it's visible
    Given I publish a product with the name "product:3_@now", id "productId3_@now" and visibility "org_urls" "/consumer-orgs/1234/5678/a18843f3e4b07631568a159d"
    Then The product with the name "product:3_@now" and id "productId3_@now" should be visible

    # Create a tagged product, check that it's visible
    Given I publish a product with the name "product:4_@now", id "productId4_@now" and visibility "tags" "testers"
    Then The product with the name "product:4_@now" and id "productId4_@now" should be visible

    # Now log in as andre two
    Given I am logged in as "andre_two"
    Then I should not see the text "Unrecognized username or password"
    And I should not see the text "Log in"

    # The public product should still be visible
    Then The product with the name "product:1_@now" and id "productId1_@now" should be visible
    # The authenticated product should still be visible
    Then The product with the name "product:2_@now" and id "productId2_@now" should be visible
    # The organisation product should not be visible, because andre two is in a different org
    And The product with the name "product:3_@now" and id "productId3_@now" should not be visible
    # The tagged product should not be visible, because andre two's organisation has a different tag
    And The product with the name "product:4_@now" and id "productId4_@now" should not be visible

#    Given I publish a product with the name "product:5_@now", id "productId5_@now" and visibility "subs" true
# todo: test subscription.  Need to create an app and subscribe to the product:5_@now