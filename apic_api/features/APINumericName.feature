@api
Feature: API with Numeric Name
  As a consumer I should be able to access APIs whose names start with numbers.
  This tests the fix for APIs like "1234-my-api:1.0.0" not being confused with node IDs.

  Scenario: I can access an API using name:version when name starts with a number
    Given I am not logged in
    Given apis:
      | title           | id     | document      |
      | 1234 Numeric API | 999999 | numeric.json  |
    Given products:
      | name                  | title                | id     | document           |
      | 1234-numeric-product  | 1234 Numeric Product | 888888 | NumericProduct.json |
    Given I am not logged in
    And I am at "/productselect/1234-numeric-api:1.0.0"
    Then I should see the text "1234 Numeric API"
    And there are no errors

  Scenario: I can access an API using path alias when name starts with a number
    Given I am not logged in
    Given apis:
      | title           | id     | document      |
      | 1234 Numeric API | 999999 | numeric.json  |
    Given products:
      | name                  | title                | id     | document           |
      | 1234-numeric-product  | 1234 Numeric Product | 888888 | NumericProduct.json |
    Given I am not logged in
    And I am at "/productselect/numericapi"
    Then I should see the text "1234 Numeric API"
    And there are no errors

  Scenario: API list shows APIs with numeric names correctly
    Given I am not logged in
    Given apis:
      | title           | id     | document      |
      | 1234 Numeric API | 999999 | numeric.json  |
    Given products:
      | name                  | title                | id     | document           |
      | 1234-numeric-product  | 1234 Numeric Product | 888888 | NumericProduct.json |
    And I am at "/api"
    Then I should see the text "1234 Numeric API"
    And there are no errors