@api
Feature: Product with Numeric Name
  As a consumer I should be able to access products whose names start with numbers.
  This tests the fix for products like "1234-my-product:1.0.0" not being confused with node IDs.

  Scenario: I can access a product using name:version when name starts with a number
    Given I am not logged in
    Given apis:
      | title       | id    | document      |
      | Numeric API | 77777 | numeric.json  |
    Given products:
      | name                  | title                | id     | document           |
      | 1234-numeric-product  | 1234 Numeric Product | 888888 | NumericProduct.json |
    Given I am not logged in
    And I am at "/product/1234-numeric-product:1.0.0"
    Then I should see the text "1234 Numeric Product"
    And there are no errors

  Scenario: I can access a product using path alias when name starts with a number
    Given I am not logged in
    Given apis:
      | title       | id    | document      |
      | Numeric API | 77777 | numeric.json  |
    Given products:
      | name                  | title                | id     | document           |
      | 1234-numeric-product  | 1234 Numeric Product | 888888 | NumericProduct.json |
    Given I am not logged in
    And I am at "/product/numericproduct"
    Then I should see the text "1234 Numeric Product"
    And there are no errors

  Scenario: Product list shows products with numeric names correctly
    Given I am not logged in
    Given apis:
      | title       | id    | document      |
      | Numeric API | 77777 | numeric.json  |
    Given products:
      | name                  | title                | id     | document           |
      | 1234-numeric-product  | 1234 Numeric Product | 888888 | NumericProduct.json |
    And I am at "/product"
    Then I should see the text "1234 Numeric Product"
    And there are no errors