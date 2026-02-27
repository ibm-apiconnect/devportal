@api
Feature: Subscription
  In order to use the developer portal
  I need to be able to subscribe to applications

  Scenario: Subscription Wizard when logged in and selecting a product plan
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | 123456 | @data(andre.mail) |
    Given I am logged in as "@data(andre.mail)"
    And I create an application named "myapp_@now" id "myid_@now" consumerorgurl "/consumer-orgs/1234/5678/123456"
    And I publish an api with the name "api1_@now" and id "api1_@now"
    And I publish a product with the name "product1_@now", id "product1_@now", apis "api1_@now" and visibility "pub" true true
    And I am on "/product/product1_@now"
    When I click "Select"
    Then I should not see the text 'Select Plan'
    And I should see the text 'Select Application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Select an existing application or create a new application'
    And I should see the link 'Create Application'
    And I should see the text 'Subscribe to product1_@now'

    When I press the "myapp_@now" button
    Then I should not see the text 'Select Plan'
    And I should see the text 'Select Application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Confirm Subscription'
    And I should see the text 'myapp_@now'
    And I should see the text 'Default Plan'

    When I press the "Next" button
    Then I should not see the text 'Select Plan'
    And I should see the text 'Select Application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Subscription Complete'
    And I should see the link 'product1_@now'
    And I should see the link 'myapp_@now'
    And I should see the text 'Default Plan'

  Scenario: Subscription Wizard when logged in and selecting an API via Get Access button
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | 123456 | @data(andre.mail) |
    Given I am logged in as "@data(andre.mail)"
    And I create an application named "myapp_@now" id "myid_@now" consumerorgurl "/consumer-orgs/1234/5678/123456"
    And I publish an api with the name "api1_@now" and id "api1_@now"
    And I publish a product with the name "product1_@now", id "product1_@now", apis "api1_@now" and visibility "pub" true true
    And I am on "/product/product1_@now/api/api1_@now"
    When I click "Get access"
    And I click "Select"
    Then I should see the text 'Select Plan'
    And I should see the text 'Select Application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Select an existing application or create a new application'
    And I should see the link 'Create Application'
    And I should see the text 'Subscribe to product1_@now'

    When I press the "myapp_@now" button
    Then I should see the text 'Select Plan'
    And I should see the text 'Select Application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Confirm Subscription'
    And I should see the text 'myapp_@now'
    And I should see the text 'Default Plan'

    When I press the "Next" button
    Then I should see the text 'Select Plan'
    And I should see the text 'Select Application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Subscription Complete'
    And I should see the link 'product1_@now'
    And I should see the link 'myapp_@now'
    And I should see the text 'Default Plan'

  Scenario: Subscription Wizard when not logged in and selecting a product plan
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | 123456 | @data(andre.mail) |
    Given I am not logged in
    And I create an application named "myapp_@now" id "myid_@now" consumerorgurl "/consumer-orgs/1234/5678/123456"
    And I publish an api with the name "api1_@now" and id "api1_@now"
    And I publish a product with the name "product1_@now", id "product1_@now", apis "api1_@now" and visibility "pub" true true
    And I am on "/product/product1_@now"
    When I click "Select"

    Then I should be on "/user/login"
    And I should see the text 'Sign in to an existing account or create a new account to subscribe to this Product.'
    When I enter "@data(andre.mail)" for "Username"
    And I enter "@data(andre.password)" for "Password"
    And I press the "Sign in" button
    Then I should see the text 'Select Plan'
    And I should see the text 'Select Application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'

    When I click "Select"
    Then I should see the text 'Select Plan'
    And I should see the text 'Select Application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Select an existing application or create a new application'
    And I should see the link 'Create Application'
    And I should see the text 'Subscribe to product1_@now'

    When I press the "myapp_@now" button
    Then I should see the text 'Select Plan'
    And I should see the text 'Select Application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Confirm Subscription'
    And I should see the text 'myapp_@now'
    And I should see the text 'Default Plan'

    When I press the "Next" button
    Then I should see the text 'Select Plan'
    And I should see the text 'Select Application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Subscription Complete'
    And I should see the link 'product1_@now'
    And I should see the link 'myapp_@now'
    And I should see the text 'Default Plan'

  Scenario: Subscription Wizard when not logged in and selecting an API via Get Access button
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | 123456 | @data(andre.mail) |
    Given I am not logged in
    And I create an application named "myapp_@now" id "myid_@now" consumerorgurl "/consumer-orgs/1234/5678/123456"
    And I publish an api with the name "api1_@now" and id "api1_@now"
    And I publish a product with the name "product1_@now", id "product1_@now", apis "api1_@now" and visibility "pub" true true
    And I am on "/product/product1_@now/api/api1_@now"

    When I click "Get access"
    Then I should be on "/user/login"
    And I should see the text 'Sign in to an existing account or create a new account to subscribe to this Product.'
    When I enter "@data(andre.mail)" for "Username"
    And I enter "@data(andre.password)" for "Password"
    And I press the "Sign in" button
    Then I should see the text 'Select Plan'
    And I should see the text 'Select Application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'

    When I click "Select"
    Then I should see the text 'Select Plan'
    And I should see the text 'Select Application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Select an existing application or create a new application'
    And I should see the link 'Create Application'
    And I should see the text 'Subscribe to product1_@now'

    When I press the "myapp_@now" button
    Then I should see the text 'Select Plan'
    And I should see the text 'Select Application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Confirm Subscription'
    And I should see the text 'myapp_@now'
    And I should see the text 'Default Plan'

    When I press the "Next" button
    Then I should see the text 'Select Plan'
    And I should see the text 'Select Application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Subscription Complete'
    And I should see the link 'product1_@now'
    And I should see the link 'myapp_@now'
    And I should see the text 'Default Plan'

  Scenario: Subscription Wizard with a paid plan and no payment method
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | 123456 | @data(andre.mail) |
    Given I am logged in as "@data(andre.mail)"
    And I have an analytics service
    And I have a billing service
    And I create an application named "myapp_@now" id "myid_@now" consumerorgurl "/consumer-orgs/1234/5678/123456"
    And I publish an api with the name "api1_@now" and id "api1_@now"
    And I publish a product with the name "product1_@now", id "product1_@now", apis "api1_@now" and a paid plan
    And I am on "/product/product1_@now"

    When I click "Select"
    And I press the "myapp_@now" button
    Then I should see the text 'This plan requires payment. '
    And I should see the text 'Confirm Subscription'
    And I should see the link 'Click here to add a payment method and try again.'

  Scenario: Subscription Wizard with a paid plan
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
    Given I am logged in as "@data(andre.mail)"
    And I have an analytics service
    And I have a billing service
    And I have a payment method
    And I create an application named "myapp_@now" id "myid_@now" consumerorgurl "/consumer-orgs/1234/5678/982318734"
    And I publish an api with the name "api1_@now" and id "api1_@now"
    And I publish a product with the name "product1_@now", id "product1_@now", apis "api1_@now" and a paid plan
    And I am on "/product/product1_@now"
    Then I should see the text '$10.00 per month'
    And I should see the text '(No trial period)'

    When I click "Select"
    Then I should not see the text 'Select Plan'
    And I should see the text 'Select Application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Select an existing application or create a new application'
    And I should see the link 'Create Application'
    And I should see the text 'Subscribe to product1_@now'

    When I press the "myapp_@now" button
    Then I should not see the text 'Select Plan'
    And I should see the text 'Select Application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Confirm Subscription'
    And I should see the text 'myapp_@now'
    And I should see the link 'Click here to change your default payment method'
    And I should see the text 'Your default payment method is paymentMethod'
    And I should see the text 'Price'
    And I should see the text '$10.00 per month (No trial period)'
    And I should not see the text 'Free'
    And I should see the text 'Default Plan'

    When I press the "Next" button
    Then I should not see the text 'Select Plan'
    And I should see the text 'Select Application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Subscription Complete'
    And I should see the text 'Price'
    And I should see the text '$10.00 per month (No trial period)'
    And I should not see the text 'Free'
    And I should see the link 'myapp_@now'
    And I should see the link 'product1_@now'
    And I should see the text 'Default Plan'

  Scenario: Application subscriptions display correctly when product is retired
    Given I have no products or apis
    And I do not have any applications
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
      | title  | id          | org_id                      |
      | MyApp4 | 1234567@now | @data(andre.consumerorg.id) |
    Given subscriptions:
      | org_id                      | app_id      | sub_id    | product | plan    |
      | @data(andre.consumerorg.id) | 1234567@now | abcde@now | 123456  | default |
    And I am at "/application"
    Then I should see the text "MyApp4"
    And there are no errors
    
    # View subscriptions before product retirement
    When I click "MyApp4"
    Then I should see the text "Subscriptions"
    When I click "Subscriptions"
    Then I should see the text "Product subscriptions"
    And I should see the text "Pet Store"
    And I should see the text "View documentation"
    And I should see the text "Unsubscribe"
    And there are no errors
    
    # Retire the product (simulates product being retired in API Manager)
    Given I retire the product with id "123456"
    
    # View subscriptions after product retirement - should handle gracefully
    When I am at "/application"
    And I click "MyApp4"
    Then I should see the text "Subscriptions"
    When I click "Subscriptions"
    Then I should see the text "Product subscriptions"
    And I should not see the text "Pet Store"
    And I should see the text "No subscriptions found"
    And there are no errors

  Scenario: Application subscriptions display correctly after product replacement without manual cache clear
    Given I have no products or apis
    And I do not have any applications
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
      | name       | title      | id     | document             |
      | pet-store  | Pet Store  | 123456 | PetStoreProduct.json |
      | pet-store2 | Pet Store2 | 123457 | PetStoreProduct.json |
    Given applications:
      | title  | id          | org_id                      |
      | MyApp5 | 1234568@now | @data(andre.consumerorg.id) |
    Given subscriptions:
      | org_id                      | app_id      | sub_id    | product | plan    |
      | @data(andre.consumerorg.id) | 1234568@now | abcdf@now | 123456  | default |
    And I am at "/application"
    Then I should see the text "MyApp5"
    And there are no errors
    
    # View subscriptions before product replacement
    When I click "MyApp5"
    Then I should see the text "Subscriptions"
    When I click "Subscriptions"
    Then I should see the text "Product subscriptions"
    And I should see the text "Pet Store"
    And I should see the text "View documentation"
    And I should see the text "Unsubscribe"
    And there are no errors
    
    # Replace the product (simulates product replacement in API Manager)
    # This should update subscriptions to point to the new product and clear caches
    Given I replace product "123456" with product "123457" and plan mapping
    
    # View subscriptions after product replacement - should show new product WITHOUT manual cache clear
    When I am at "/application"
    And I click "MyApp5"
    Then I should see the text "Subscriptions"
    When I click "Subscriptions"
    Then I should see the text "Product subscriptions"
    And I should see the text "Pet Store2"
    And I should see the text "View documentation"
    And I should see the text "Unsubscribe"
    And there are no errors
    And there are no errors
