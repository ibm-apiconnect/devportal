@api
Feature: Product Plan API Count Display
  As a user viewing a product with multiple APIs
  I want to see the correct number of APIs in a plan
  So that I know how many APIs are included

  Scenario: Plan with more than 4 APIs shows correct count
    Given I am not logged in
    Given I publish an api with the name "api-one_@now"
    And I publish an api with the name "api-two_@now"
    And I publish an api with the name "api-three_@now"
    And I publish an api with the name "api-four_@now"
    And I publish an api with the name "api-five_@now"
    And I publish a product with the name "multi-api-prod_@now", id "200001_@now", multiple apis "api-one_@now,api-two_@now,api-three_@now,api-four_@now,api-five_@now" and subscribility "auth" true true
    And I am on "/product/multi-api-prod_@now"
    Then I should see the text "5 APIs including"
    And I should not see the text "@number APIs including"
    And there are no errors

  Scenario: Plan with exactly 4 APIs does not show count message
    Given I am not logged in
    Given I publish an api with the name "api-one_@now"
    And I publish an api with the name "api-two_@now"
    And I publish an api with the name "api-three_@now"
    And I publish an api with the name "api-four_@now"
    And I publish a product with the name "four-api-prod_@now", id "200002_@now", multiple apis "api-one_@now,api-two_@now,api-three_@now,api-four_@now" and subscribility "auth" true true
    And I am on "/product/four-api-prod_@now"
    Then I should not see the text "APIs including"
    And there are no errors

  Scenario: Plan with 10 APIs shows correct count 
    Given I am not logged in
    Given I publish an api with the name "api-one_@now"
    And I publish an api with the name "api-two_@now"
    And I publish an api with the name "api-three_@now"
    And I publish an api with the name "api-four_@now"
    And I publish an api with the name "api-five_@now"
    And I publish an api with the name "api-six_@now"
    And I publish an api with the name "api-seven_@now"
    And I publish an api with the name "api-eight_@now"
    And I publish an api with the name "api-nine_@now"
    And I publish an api with the name "api-ten_@now"
    And I publish a product with the name "ten-api-prod_@now", id "200003_@now", multiple apis "api-one_@now,api-two_@now,api-three_@now,api-four_@now,api-five_@now,api-six_@now,api-seven_@now,api-eight_@now,api-nine_@now,api-ten_@now" and subscribility "auth" true true
    And I am on "/product/ten-api-prod_@now"
    Then I should see the text "10 APIs including"
    And I should not see the text "@number APIs including"
    And there are no errors