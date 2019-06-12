Feature: API Rendering
  As Andre I am able to view an API.

  @api
  Scenario: I can see the API
    Given I am not logged in
    Given apis:
      | title    | id    | document      |
      | PetStore | 12345 | petstore.json |
    Given products:
      | name      | title     | id     | document      |
      | pet-store | Pet Store | 123456 | PetStoreProduct.json |
    And I am at "/api"
    Then I should see the text "PetStore"
    When I click "PetStore"
    Then I should see the text "PetStore"
    And there are no errors