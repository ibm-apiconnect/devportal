@api
Feature: Product PathAlias
  As a consumer I should see products honouring their path alias.

  Scenario: I can see products using their path alias
    Given I am not logged in
    Given users:
      | name                 | mail                 | pass                     | status |
      | @data(admin.name)    | @data(admin.mail)    | @data(admin.password)    | 1      |
    Given apis:
      | title    | id    | document      |
      | Climbing Weather API | 65432 | climbing.json |
    Given products:
      | name      | title     | id     | document      |
      | climbing-weather | Climbing Weather | 654321 | ClimbingProduct.json |
    Given I am not logged in
    And I am at "/product"
    Then I should see the text "Climbing Weather"
    Then I should see a link with href including "/product/climbingweather"
    And there are no errors

  Scenario: I can see products using name:version
    Given I am not logged in
    Given users:
      | name                 | mail                 | pass                     | status |
      | @data(admin.name)    | @data(admin.mail)    | @data(admin.password)    | 1      |
    Given apis:
      | title    | id    | document      |
      | Climbing Weather API | 65432 | climbing.json |
    Given products:
      | name      | title     | id     | document      |
      | climbing-weather | Climbing Weather | 654321 | ClimbingProduct.json |
    Given I am not logged in
    And I am at "/product/climbing-weather:1.0.0"
    Then I should see the text "Climbing Weather"
    And there are no errors