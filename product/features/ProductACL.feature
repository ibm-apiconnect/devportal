Feature: ProductACL
  In order to use the developer portal
  I need to be able to access products I should be allowed to access

  @acl
  @api
  Scenario: Create product with public visibility and test its access control
    # Hard coding these for now.  For full stack testing, substitute with real users/consumerorgs
    # in the Then clauses below
    Given users:
      | name      | mail                  | pass     | status |
      | andre_one | andre_one@example.com | Qwert123IsBadPassword! | 1      |
      | @data(admin.name) | @data(admin.mail) | @data(admin.password) | 1      |
    Given consumerorgs:
      | title          | name           | id                       | owner     | tags    |
      | a1_consumerorg | a1-consumerorg | a18843f3e4b07631568a159d | andre_one | testers |
    # Create a public product, check that it's visible
    Given I have no products or apis
    Given I publish an api with the name "api1_@now" and id "api1_@now"
    And I publish a product with the name "product:1_@now", id "productId1_@now", apis "api1_@now" and visibility "pub" true true

    Given I am not logged in
    # products view /product
    Given I am on "/product"
    And I should see the link "product:1_@now"
    # product view
    # fixed URL /product/{url}
    Given I am on "/product/product:1_@now"
    Then the response status code should be 200


    Given I am logged in as "andre_one"
    # products view /product
    Given I am on "/product"
    And I should see the link "product:1_@now"
    # product view
    # fixed URL /product/{url}
    Given I am on "/product/product:1_@now"
    Then the response status code should be 200


    Given I am logged in as "@data(admin.name)"
    # products view /product
    Given I am on "/product"
    And I should see the link "product:1_@now"
    # product view
    # fixed URL /product/{url}
    Given I am on "/product/product:1_@now"
    Then the response status code should be 200


  @acl
  @api
  Scenario: Create product with auth visibility and test its access control
    Given users:
      | name      | mail                  | pass     | status |
      | andre_one | andre_one@example.com | Qwert123IsBadPassword! | 1      |
      | @data(admin.name) | @data(admin.mail) | @data(admin.password) | 1      |
    Given consumerorgs:
      | title          | name           | id                       | owner     | tags    |
      | a1_consumerorg | a1-consumerorg | a18843f3e4b07631568a159d | andre_one | testers |
    # Create an authenticated product, check that it's visible
    Given I publish an api with the name "api2_@now" and id "api2_@now"
    And I publish a product with the name "product:2_@now", id "productId2_@now", apis "api2_@now" and visibility "auth" true true

    Given I am not logged in
    # products view /product
    Given I am on "/product"
    And I should not see the link "product:2_@now"
    # product view
    # fixed URL /product/{url}
    Given I am on "/product/product:2_@now"
    Then the response status code should be 404

    Given I am logged in as "andre_one"
    # products view /product
    Given I am on "/product"
    And I should see the link "product:2_@now"
    # product view
    # fixed URL /product/{url}
    Given I am on "/product/product:2_@now"
    Then the response status code should be 200

    Given I am logged in as "@data(admin.name)"
    # products view /product
    Given I am on "/product"
    And I should see the link "product:2_@now"
    # product view
    # fixed URL /product/{url}
    Given I am on "/product/product:2_@now"
    Then the response status code should be 200


  @acl
  @api
  Scenario: Create product with org url visibility and test its access control
    Given users:
      | name      | mail                  | pass     | status |
      | andre_one | andre_one@example.com | Qwert123IsBadPassword! | 1      |
      | andre_two | andre_two@example.com | Qwert123IsBadPassword! | 1      |
      | @data(admin.name) | @data(admin.mail) | @data(admin.password) | 1      |
    Given consumerorgs:
      | title          | name           | id                       | owner     | tags    |
      | a1_consumerorg | a1-consumerorg | a18843f3e4b07631568a159d | andre_one | testers |
      | a2_consumerorg | a2-consumerorg | a28843f3e4b07631568a159d | andre_two | others  |
    # Create an organisation product, check that it's visible
    Given I publish an api with the name "api3_@now" and id "api3_@now"
    And I publish a product with the name "product:3_@now", id "productId3_@now", apis "api3_@now" and visibility "org_urls" "/consumer-orgs/1234/5678/a18843f3e4b07631568a159d" true

    Given I am not logged in
    # products view /product
    Given I am on "/product"
    And I should not see the link "product:3_@now"
    # product view
    # fixed URL /product/{url}
    Given I am on "/product/product:3_@now"
    Then the response status code should be 404

    Given I am logged in as "andre_one"
    # products view /product
    Given I am on "/product"
    And I should see the link "product:3_@now"
    # product view
    # fixed URL /product/{url}
    Given I am on "/product/product:3_@now"
    Then the response status code should be 200

    Given I am logged in as "andre_two"
    # products view /product
    Given I am on "/product"
    And I should not see the link "product:3_@now"
    # product view
    # fixed URL /product/{url}
    Given I am on "/product/product:3_@now"
    Then the response status code should be 404

    Given I am logged in as "@data(admin.name)"
    # products view /product
    Given I am on "/product"
    And I should see the link "product:3_@now"
    # product view
    # fixed URL /product/{url}
    Given I am on "/product/product:3_@now"
    Then the response status code should be 200


  @acl
  @api
  Scenario: Create product with tag visibility and test its access control
    Given users:
      | name      | mail                  | pass     | status |
      | andre_one | andre_one@example.com | Qwert123IsBadPassword! | 1      |
      | andre_two | andre_two@example.com | Qwert123IsBadPassword! | 1      |
      | @data(admin.name) | @data(admin.mail) | @data(admin.password) | 1      |
    Given consumerorgs:
      | title          | name           | id                       | owner     | tags    |
      | a1_consumerorg | a1-consumerorg | a18843f3e4b07631568a159d | andre_one | testers |
      | a2_consumerorg | a2-consumerorg | a28843f3e4b07631568a159d | andre_two | others  |
    # Create a tagged product, check that it's visible
    Given I publish an api with the name "api4_@now" and id "api4_@now"
    And I publish a product with the name "product:4_@now", id "productId4_@now", apis "api4_@now" and visibility "tags" "testers" true

    Given I am not logged in
    # products view /product
    Given I am on "/product"
    And I should not see the link "product:4_@now"
    # product view
    # fixed URL /product/{url}
    Given I am on "/product/product:4_@now"
    Then the response status code should be 404

    Given I am logged in as "andre_one"
    # products view /product
    Given I am on "/product"
    And I should see the link "product:4_@now"
    # product view
    # fixed URL /product/{url}
    Given I am on "/product/product:4_@now"
    Then the response status code should be 200

    Given I am logged in as "andre_two"
    # products view /product
    Given I am on "/product"
    And I should not see the link "product:4_@now"
    # product view
    # fixed URL /product/{url}
    Given I am on "/product/product:4_@now"
    Then the response status code should be 404

    Given I am logged in as "@data(admin.name)"
    # products view /product
    Given I am on "/product"
    And I should see the link "product:4_@now"
    # product view
    # fixed URL /product/{url}
    Given I am on "/product/product:4_@now"
    Then the response status code should be 200


  @acl
  @api
  Scenario: Create product with it's visibility disabled and test its access control
    Given users:
      | name      | mail                  | pass     | status |
      | andre_one | andre_one@example.com | Qwert123IsBadPassword! | 1      |
      | @data(admin.name) | @data(admin.mail) | @data(admin.password) | 1      |
    Given consumerorgs:
      | title          | name           | id                       | owner     | tags    |
      | a1_consumerorg | a1-consumerorg | a18843f3e4b07631568a159d | andre_one | testers |
    # Create a tagged product, check that it's visible
    Given I publish an api with the name "api5_@now" and id "api5_@now"
    And I publish a product with the name "product:5_@now", id "productId5_@now", apis "api5_@now" and visibility "auth" true false

    Given I am not logged in
    # products view /product
    Given I am on "/product"
    And I should not see the link "product:5_@now"
    # product view
    # fixed URL /product/{url}
    Given I am on "/product/product:5_@now"
    Then the response status code should be 404

    Given I am logged in as "andre_one"
    # products view /product
    Given I am on "/product"
    And I should not see the link "product:5_@now"
    # product view
    # fixed URL /product/{url}
    Given I am on "/product/product:5_@now"
    Then the response status code should be 404

    Given I am logged in as "@data(admin.name)"
    # products view /product
    Given I am on "/product"
    And I should see the link "product:5_@now"
    # product view
    # fixed URL /product/{url}
    Given I am on "/product/product:5_@now"
    Then the response status code should be 200
#    Given I publish a product with the name "product:5_@now", id "productId5_@now" and visibility "subs" true
# todo: test subscription.  Need to create an app and subscribe to the product:5_@now
