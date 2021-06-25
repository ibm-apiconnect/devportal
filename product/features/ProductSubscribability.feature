Feature: ProductSubscribability
  I should not be able subscribe to products if the respective option is disabled

  @api
  Scenario: Accessing a product which has its subscribe disabled while not logged in
    Given users:
      | name      | mail                  | pass     | status |
      | andre_one | andre_one@example.com | Qwert123 | 1      |
    Given consumerorgs:
      | title          | name           | id                       | owner     | tags    |
      | a1_consumerorg | a1-consumerorg | a18843f3e4b07631568a159d | andre_one | testers |

    # Create a public product, check that it's visible
    Given I publish an api with the name "api2_@now"
    And I publish a product with the name "product:2_@now", id "productId2_@now", apis "api2_@now" and subscribility "auth" true false
    And I am on "/product/product:2_@now"
    Then the response status code should be 200
    And I should see the "Select" button
    And the field ".btn.subscribe" should be disabled

    Given I am logged in as "andre_one"
    And I am on "/product/product:2_@now"
    Then the response status code should be 200
    And I should see the "Select" button
    And the field ".btn.subscribe" should be disabled

  @api
  Scenario: Accessing a product which has its subscribability set to tags
    Given users:
      | name      | mail                  | pass     | status |
      | andre_one | andre_one@example.com | Qwert123 | 1      |
      | andre_two | andre_two@example.com | Qwert123 | 1      |
    Given consumerorgs:
      | title          | name           | id                       | owner     | tags    |
      | a1_consumerorg | a1-consumerorg | a18843f3e4b07631568a159d | andre_one | testers |
      | a2_consumerorg | a2-consumerorg | a28843f3e4b07631568a159d | andre_two | others  |

    # Create a public product, check that it's visible
    Given I publish an api with the name "api3_@now"
    And I publish a product with the name "product:3_@now", id "productId3_@now", apis "api3_@now" and subscribility "tags" "testers" true
    And I am logged in as "andre_one"
    And I am on "/product/product:3_@now"
    Then the response status code should be 200
    And I should see the link "Select"

    Given I am logged in as "andre_two"
    And I am on "/product/product:3_@now"
    Then I should see the "Select" button
    And the field ".btn.subscribe" should be disabled

  @api
  Scenario: Accessing a product which has its subscribability set to org_url
    Given users:
      | name      | mail                  | pass     | status |
      | andre_one | andre_one@example.com | Qwert123 | 1      |
      | andre_two | andre_two@example.com | Qwert123 | 1      |
    Given consumerorgs:
      | title          | name           | id                       | owner     | tags    |
      | a1_consumerorg | a1-consumerorg | a18843f3e4b07631568a159d | andre_one | testers |
      | a2_consumerorg | a2-consumerorg | a28843f3e4b07631568a159d | andre_two | others  |

    # Create a public product, check that it's visible
    Given I publish an api with the name "api4_@now"
    And I publish a product with the name "product:4_@now", id "productId4_@now", apis "api4_@now" and subscribility "org_urls" "/consumer-orgs/1234/5678/a18843f3e4b07631568a159d" true
    And I am logged in as "andre_one"
    And I am on "/product/product:4_@now"
    Then the response status code should be 200
    And I should see the link "Select"

    Given I am logged in as "andre_two"
    And I am on "/product/product:4_@now"
    Then I should see the "Select" button
    And the field ".btn.subscribe" should be disabled
