Feature: Application Rendering
  As Andre I am able to view an application.

  @api
  Scenario: I can see the Application
    Given I have an analytics service
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
    Given I am logged in as "@data(andre.mail)"
    Given applications:
      | title    | id    | org_id      |
      | MyApp | 1234567@now | @data(andre.consumerorg.id) |
    And I am at "/application"
    Then I should see the text "MyApp"
    And there are no errors
    When I click "MyApp"
    Then I should see the text "MyApp"
    And there are no errors

  @api
  Scenario: I can see the Application with a subscription
    Given I have an analytics service
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                          | name                          | id                          | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
    Given I am logged in as "@data(andre.mail)"
    Given apis:
      | title    | id    | document      |
      | PetStore | 12345 | petstore.json |
    Given products:
      | name      | title     | id     | document             |
      | pet-store | Pet Store | 123456 | PetStoreProduct.json |
    Given applications:
      | title | id      | org_id                      |
      | MyApp2 | 1234567@now | @data(andre.consumerorg.id) |
    Given subscriptions:
      | org_id                      | app_id  | sub_id | product   | plan    |
      | @data(andre.consumerorg.id) | 1234567@now | abcde  | 123456 | default |
    And I am at "/application"
    Then I should see the text "MyApp2"
    And there are no errors
    When I click "MyApp2"
    Then I should see the text "MyApp2"
    And there are no errors
    When I click "Subscriptions"
    Then I should see the text "Pet Store"
    And there are no errors

  @api
  Scenario: I can see the Application with a subscription when analytics is disabled
    Given I do not have an analytics service
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                          | name                          | id                          | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
    Given I am logged in as "@data(andre.mail)"
    Given apis:
      | title    | id    | document      |
      | PetStore | 12345 | petstore.json |
    Given products:
      | name      | title     | id     | document             |
      | pet-store | Pet Store | 123456 | PetStoreProduct.json |
    Given applications:
      | title | id      | org_id                      |
      | MyApp2 | 1234567@now | @data(andre.consumerorg.id) |
    Given subscriptions:
      | org_id                      | app_id  | sub_id | product   | plan    |
      | @data(andre.consumerorg.id) | 1234567@now | abcde  | 123456 | default |
    And I am at "/application"
    Then I should see the text "MyApp2"
    And there are no errors
    When I click "MyApp2"
    Then I should see the text "MyApp2"
    And there are no errors
    When I should see the text "Subscriptions"
    Then I should see the text "Pet Store"
    And there are no errors
