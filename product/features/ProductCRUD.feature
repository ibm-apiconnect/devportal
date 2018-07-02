Feature: ProductCRUD
  In order to use the developer portal
  I need to be able to use products

  @api
  Scenario: Content type exists
    Given I am at "/"
    Then The "product" content type is present

  @api
  Scenario:  Create product and create category taxonomies
    Given I publish a product with the name "product_@now", id "productId_@now" and categories "Sport / Ball / Rugby"
    Then I should have a product with the name "product_@now", id "productId_@now" and categories "Sport / Ball / Rugby"

  @ppi
  Scenario: Create product, do not create category taxonomies
    Given I publish a product with the name "product_@now" and categories "Animals / Fluffy / Cat" and create_taxonomies_from_categories is false
    Then I should have a product with name "product_@now" and no taxonomies for the categories "Animals / Fluffy / Cat"