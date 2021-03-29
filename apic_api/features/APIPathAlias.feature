Feature: API PathAlias
  As a consumer I should see APIs honouring their path alias.

  @api
  Scenario: I can see APIs using their path alias
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
    And I am at "/api"
    Then I should see the text "Climbing Weather API"
    Then I should see a link with href including "/productselect/climbing"
    And there are no errors