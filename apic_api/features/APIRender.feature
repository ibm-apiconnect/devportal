@api
Feature: API Rendering
  As Andre I am able to view an API.

  Scenario: I can see the API that uses extended name regex
    Given I am not logged in
    Given apis:
      | title    | id    | document      |
      | AZaz API | 987655 | AzazApi.json |
    Given products:
      | name      | title     | id     | document      |
      | AZaz_0.9 | AZaz Product | 098765 | AZazProduct.json |
    And I am at "/api"
    Then I should see the text "AZaz API"
    When I click "AZaz API"
    Then I should see the text "AZaz API"
    And there are no errors

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
