Feature: Product Rendering
  As Andre I am able to view a Product.

  @api
  Scenario: I can see the Product
    Given I am not logged in
    Given apis:
      | title    | id    | document      |
      | PetStore | 12345 | petstore.json |
    Given products:
      | name      | title     | id     | document      |
      | pet-store | Pet Store | 123456 | PetStoreProduct.json |
    And I am at "/product"
    Then I should see the text "Pet Store"
    When I click "Pet Store"
    Then I should see the text "Pet Store"
    And there are no errors