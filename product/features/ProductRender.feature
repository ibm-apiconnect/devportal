@api
Feature: Product Rendering
  As Andre I am able to view a Product.

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

  Scenario: I can see the Product
    Given I am not logged in
    Given I have no products or apis
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

  Scenario: Postman button shows when enabled with collection and workspace IDs
    Given I am not logged in
    Given I have no products or apis
    Given the postman setting is enabled
    Given apis:
      | title    | id    | document      |
      | PetStore | 12345 | petstore.json |
    Given the api "PetStore" has collection_id "7dc6e184-dd5c-4efe-a27e-15e9899a6ac3" and workspace_id "c7a4b6c4-e841-4d43-8fc9-2dc830d1aa7d"
    Given products:
      | name      | title     | id     | document      |
      | pet-store | Pet Store | 123456 | PetStoreProduct.json |
    And I am at "/product"
    When I click "Pet Store"
    And I click "PetStore"
    Then I should see the link "Run in Postman"
    And there are no errors

  Scenario: Postman button does not show when disabled
    Given I am not logged in
    Given I have no products or apis
    Given the postman setting is disabled
    Given apis:
      | title    | id    | document      |
      | PetStore | 12345 | petstore.json |
    Given the api "PetStore" has collection_id "7dc6e184-dd5c-4efe-a27e-15e9899a6ac3" and workspace_id "c7a4b6c4-e841-4d43-8fc9-2dc830d1aa7d"
    Given products:
      | name      | title     | id     | document      |
      | pet-store | Pet Store | 123456 | PetStoreProduct.json |
    And I am at "/product"
    When I click "Pet Store"
    And I click "PetStore"
    Then I should not see the link "Run in Postman"
    And there are no errors
