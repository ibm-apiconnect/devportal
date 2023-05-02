Feature: Product Rendering
  As Andre I am able to view a Product.

    @api
  Scenario: I can see the Product that uses extended name regex
    Given I am not logged in
    Given apis:
      | title    | id    | document      |
      | AZaz API | 987655 | AzazApi.json |
    Given products:
      | name      | title     | id     | document      |
      | AZaz_0.9 | AZaz Product | 098765 | AZazProduct.json |
    And I am at "/product"
    Then I should see the text "AZaz Product"
    When I click "AZaz Product"
    Then I should see the text "AZaz API"
    And there are no errors

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
