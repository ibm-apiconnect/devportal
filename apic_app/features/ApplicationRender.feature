Feature: Application Rendering
  As Andre I am able to view an application.

  @api
  Scenario: I can see an application when there is an analytics service
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
    And I should see the text "Dashboard"
    And I should see the text "API Stats"
    And I should see the text "Subscriptions"
    And I should see the text "Credentials"
    And there are no errors


  @api
  Scenario: I can see an application when there is no analytics service
    Given I do not have an analytics service
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
    And I should not see the text "Dashboard"
    And I should not see the text "API Stats"
    And I should see the text "Credentials"
    And I should see the text "Product subscriptions"
    And I should see the text "PRODUCT"
    And I should see the text "PLAN"
    And there are no errors


  @api
  Scenario: I can see the application with a subscription and an analytics service
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
      | @data(andre.consumerorg.id) | 1234567@now | abcde@now  | 123456 | default |
    And I am at "/application"
    Then I should see the text "MyApp2"
    And there are no errors
    When I click "MyApp2"
    Then I should see the text "MyApp2"
    And I should see the text "Dashboard"
    And I should see the text "API Stats"
    And I should see the text "Subscriptions"
    And I should see the text "Credentials"
    And there are no errors
    When I click "Subscriptions"
    Then I should see the text "Pet Store"
    And I should see the text "View documentation"
    And I should see the text "Unsubscribe"
    And there are no errors


  @api
  Scenario: I can see the application with a subscription when there is no analytics service
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
      | @data(andre.consumerorg.id) | 1234567@now | abcde@now  | 123456 | default |
    And I am at "/application"
    Then I should see the text "MyApp2"
    And there are no errors
    When I click "MyApp2"
    Then I should see the text "MyApp2"
    And I should not see the text "Dashboard"
    And I should not see the text "API Stats"
    And I should see the text "Product subscriptions"
    And I should see the text "Credentials"
    And there are no errors
    When I should see the text "Product subscriptions"
    Then I should see the text "Pet Store"
    And I should see the text "View documentation"
    And I should see the text "Unsubscribe"
    And there are no errors


  @api
  Scenario: I can see an application when there are no subscriptions
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
    And I should see the text "Subscriptions"
    And there are no errors
    When I click "Subscriptions"
    Then I should see the text "No subscriptions found."
    And I should see the link "Why not browse the available APIs?"
    And there are no errors
