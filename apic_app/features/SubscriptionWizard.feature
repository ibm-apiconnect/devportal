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
    Then I should not see the text 'Select plan'
    And I should see the text 'Select application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Select an existing application or create a new application'
    And I should see the link 'Create application'
    And I should see the text 'Subscribe to product1_@now'

    Then the element with class "application-state-enabled" should be clickable
    When I press the "myapp_@now" button
    Then I should not see the text 'Select plan'
    And I should see the text 'Select application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Confirm Subscription'
    And I should see the text 'myapp_@now'
    And I should see the text 'Default Plan'

    When I press the "Next" button
    Then I should not see the text 'Select plan'
    And I should see the text 'Select application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Subscription Complete'
    And I should see the link 'product1_@now'
    And I should see the link 'myapp_@now'
    And I should see the text 'Default Plan'
    And I click "Done"
    #Due to caching issues with the mocks, need to log out and log back in to see the updates
    Given I am not logged in
    Given I am logged in as "@data(andre.mail)"
    Then the custom hook with key "apic_app_pre_subscribe" should have been invoked
    Then the custom hook with key "apic_app_subscribe" should have been invoked
    # Visit the unsubscribe URL directly (workaround for Mink click issues)
    When I am on "/application/myid_@now/unsubscribe/x"
    And I should see the text 'Are you sure you want to unsubscribe from this plan?'
    And I click the button "Unsubscribe"
    Then the custom hook with key "apic_app_pre_unsubscribe" should have been invoked
    Then the custom hook with key "apic_app_unsubscribe" should have been invoked

  Scenario: Application subscriptions can be sorted by plan name and product title
    Given I have an analytics service
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                          | name                          | id                          | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
    Given I am logged in as "@data(andre.mail)"
    And I create an application named "testapp_@now" id "testid_@now" consumerorgurl "/consumer-orgs/1234/5678/@data(andre.consumerorg.id)"
    And I publish an api with the name "api_test_@now" and id "api_test_@now"
    And I publish a product with the name "product_test_@now", id "product_test_@now", apis "api_test_@now" and plan name "Test Plan"
    And I subscribe application "testid_@now" to product "product_test_@now" with plan "Test Plan"
    And I am at "/application"
    When I click "testapp_@now"
    And I click "Subscriptions"
    # Test sorting by Plan Name descending (default)
    When I select "Plan" from "sort_by"
    And I press "Apply"
    And I wait 4 seconds
    Then the current URL should contain "sort_by=plan_title"
    And the current URL should contain "sort_order=DESC"
    # Test sorting by Plan Name ascending
    When I select "ASC" from "sort_order"
    And I press "Apply"
    And I wait 4 seconds
    Then the current URL should contain "sort_by=plan_title"
    And the current URL should contain "sort_order=ASC"
    # Test sorting by Product ascending (order persists from previous selection)
    When I select "Product" from "sort_by"
    And I press "Apply"
    And I wait 4 seconds
    Then the current URL should contain "sort_by=product_title"
    And the current URL should contain "sort_order=ASC"
    # Test sorting by Product descending
    When I select "DESC" from "sort_order"
    And I press "Apply"
    And I wait 4 seconds
    Then the current URL should contain "sort_by=product_title"
    And the current URL should contain "sort_order=DESC"

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
    Then I should see the text 'Select plan'
    And I should see the text 'Select application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Select an existing application or create a new application'
    And I should see the link 'Create application'
    And I should see the text 'Subscribe to product1_@now'

    When I press the "myapp_@now" button
    Then I should see the text 'Select plan'
    And I should see the text 'Select application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Confirm Subscription'
    And I should see the text 'myapp_@now'
    And I should see the text 'Default Plan'

    When I press the "Next" button
    Then I should see the text 'Select plan'
    And I should see the text 'Select application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Subscription Complete'
    And I should see the link 'product1_@now'
    And I should see the link 'myapp_@now'
    And I should see the text 'Default Plan'
    
    And I click "Done"
    #Due to caching issues with the mocks, need to log out and log back in to see the updates
    Given I am not logged in
    Given I am logged in as "@data(andre.mail)"
    Given I am at "/application"
    And I click "myapp_@now"
    When I am on "/application/myid_@now/migrate/x/product1_@now:default-plan"
    And I should see the text 'Are you sure you want to migrate this subscription? This action cannot be undone.'
    And I click the button "Migrate subscription"
    Then the custom hook with key "apic_app_pre_migrate" should have been invoked
    Then the custom hook with key "apic_app_migrate" should have been invoked

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
    Then I should see the text 'Select plan'
    And I should see the text 'Select application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'

    When I click "Select"
    Then I should see the text 'Select plan'
    And I should see the text 'Select application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Select an existing application or create a new application'
    And I should see the link 'Create application'
    And I should see the text 'Subscribe to product1_@now'

    When I press the "myapp_@now" button
    Then I should see the text 'Select plan'
    And I should see the text 'Select application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Confirm Subscription'
    And I should see the text 'myapp_@now'
    And I should see the text 'Default Plan'

    When I press the "Next" button
    Then I should see the text 'Select plan'
    And I should see the text 'Select application'
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
    Then I should see the text 'Select plan'
    And I should see the text 'Select application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'

    When I click "Select"
    Then I should see the text 'Select plan'
    And I should see the text 'Select application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Select an existing application or create a new application'
    And I should see the link 'Create application'
    And I should see the text 'Subscribe to product1_@now'

    When I press the "myapp_@now" button
    Then I should see the text 'Select plan'
    And I should see the text 'Select application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Confirm Subscription'
    And I should see the text 'myapp_@now'
    And I should see the text 'Default Plan'

    When I press the "Next" button
    Then I should see the text 'Select plan'
    And I should see the text 'Select application'
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
    Then I should not see the text 'Select plan'
    And I should see the text 'Select application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Select an existing application or create a new application'
    And I should see the link 'Create application'
    And I should see the text 'Subscribe to product1_@now'

    When I press the "myapp_@now" button
    Then I should not see the text 'Select plan'
    And I should see the text 'Select application'
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
    Then I should not see the text 'Select plan'
    And I should see the text 'Select application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Subscription Complete'
    And I should see the text 'Price'
    And I should see the text '$10.00 per month (No trial period)'
    And I should not see the text 'Free'
    And I should see the link 'myapp_@now'
    And I should see the link 'product1_@now'
    And I should see the text 'Default Plan'

  Scenario: Subscription Wizard when logged in and selecting a product plan, subscripton needs to fail due to pre_hook
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
    Then I should not see the text 'Select plan'
    And I should see the text 'Select application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Select an existing application or create a new application'
    And I should see the link 'Create application'
    Given the Drupal state "apic_app_pre_subscribe_throw_exception" is set to "TRUE"
    And I should see the text 'Subscribe to product1_@now'

    When I press the "myapp_@now" button
    Then I should not see the text 'Select plan'
    And I should see the text 'Select application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Confirm Subscription'
    And I should see the text 'myapp_@now'
    And I should see the text 'Default Plan'

    When I press the "Next" button
    Then I should not see the text 'Select plan'
    Then I should see the unexpected error message

  Scenario: Subscription Wizard when logged in and selecting a product plan, unsubscripton needs to fail due to pre_hook
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
    Then I should not see the text 'Select plan'
    And I should see the text 'Select application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Select an existing application or create a new application'
    And I should see the link 'Create application'
    And I should see the text 'Subscribe to product1_@now'

    When I press the "myapp_@now" button
    Then I should not see the text 'Select plan'
    And I should see the text 'Select application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Confirm Subscription'
    And I should see the text 'myapp_@now'
    And I should see the text 'Default Plan'

    When I press the "Next" button
    Then I should not see the text 'Select plan'
    And I should see the text 'Select application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Subscription Complete'
    And I should see the link 'product1_@now'
    And I should see the link 'myapp_@now'
    And I should see the text 'Default Plan'
    And I click "Done"
    #Due to caching issues with the mocks, need to log out and log back in to see the updates
    Given I am not logged in
    Given I am logged in as "@data(andre.mail)"
    Given the Drupal state "apic_app_pre_unsubscribe_throw_exception" is set to "TRUE"
    # Visit the unsubscribe URL directly (workaround for Mink click issues)
    When I am on "/application/myid_@now/unsubscribe/x"
    And I should see the text 'Are you sure you want to unsubscribe from this plan?'
    And I click the button "Unsubscribe"
    Then I should see the unexpected error message

  Scenario: Subscription Wizard when logged in and selecting an API via Get Access button, migration needs to fail due to pre_hook
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
    Then I should see the text 'Select plan'
    And I should see the text 'Select application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Select an existing application or create a new application'
    And I should see the link 'Create application'
    And I should see the text 'Subscribe to product1_@now'

    When I press the "myapp_@now" button
    Then I should see the text 'Select plan'
    And I should see the text 'Select application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Confirm Subscription'
    And I should see the text 'myapp_@now'
    And I should see the text 'Default Plan'

    When I press the "Next" button
    Then I should see the text 'Select plan'
    And I should see the text 'Select application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Subscription Complete'
    And I should see the link 'product1_@now'
    And I should see the link 'myapp_@now'
    And I should see the text 'Default Plan'
    
    And I click "Done"
    #Due to caching issues with the mocks, need to log out and log back in to see the updates
    Given I am not logged in
    Given I am logged in as "@data(andre.mail)"
    Given I am at "/application"
    And I click "myapp_@now"
    Given the Drupal state "apic_app_pre_migrate_throw_exception" is set to "TRUE"
    When I am on "/application/myid_@now/migrate/x/product1_@now:default-plan"
    And I should see the text 'Are you sure you want to migrate this subscription? This action cannot be undone.'
    And I click the button "Migrate subscription"
    Then I should see the unexpected error message

  Scenario: Subscription Wizard when logged in should be blocked if application is on pending state
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | 123456 | @data(andre.mail) |
    Given I am logged in as "@data(andre.mail)"
    And I create an application named "myapp_@now" id "myid_@now" consumerorgurl "/consumer-orgs/1234/5678/123456" state "pending"
    And I publish an api with the name "api1_@now" and id "api1_@now"
    And I publish a product with the name "product1_@now", id "product1_@now", apis "api1_@now" and visibility "pub" true true
    And I am on "/product/product1_@now"
    When I click "Select"
    Then I should not see the text 'Select plan'
    And I should see the text 'Select application'
    And I should see the text 'Subscribe'
    And I should see the text 'Summary'
    And I should see the text 'Select an existing application or create a new application'
    And I should see the link 'Create application'
    And I should see the text 'Subscribe to product1_@now'
    Then dump the current html
    Then the "application-state-pending" class should exist
    Then the element with class "application-state-pending" should not be clickable
    And I should see the text 'This application is pending'

    Scenario: Subscription Wizard shows Development lifecycle state when enabled in manager portal
    Given I have application lifecycle enabled
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                          | name                          | id     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | 123456 | @data(andre.mail) |
    Given I am logged in as "@data(andre.mail)"
    Given applications:
      | title       | id          | org_id | state     | applicationType |
      | DevApp_@now | dev123_@now | 123456 | published | DEVELOPMENT     |
    And I publish an api with the name "api1_@now" and id "api1_@now"
    And I publish a product with the name "product1_@now", id "product1_@now", apis "api1_@now" and visibility "pub" true true
    And I am on "/product/product1_@now"
    When I click "Select"
    Then I should see the text "Select application"
    And I should see the lifecycle state "development" for "DevApp_@now"
    And there are no errors

  Scenario: Subscription Wizard shows Production lifecycle state when enabled in manager portal
    Given I have application lifecycle enabled
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                          | name                          | id     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | 123456 | @data(andre.mail) |
    Given I am logged in as "@data(andre.mail)"
    Given applications:
      | title        | id           | org_id | state     | applicationType |
      | ProdApp_@now | prod123_@now | 123456 | published | PRODUCTION      |
    And I publish an api with the name "api1_@now" and id "api1_@now"
    And I publish a product with the name "product1_@now", id "product1_@now", apis "api1_@now" and visibility "pub" true true
    And I am on "/product/product1_@now"
    When I click "Select"
    Then I should see the text "Select application"
    And I should see the lifecycle state "production" for "ProdApp_@now"
    And there are no errors

  Scenario: Subscription Wizard does not show lifecycle state when disabled in manager portal
    Given I do not have application lifecycle enabled
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                          | name                          | id     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | 123456 | @data(andre.mail) |
    Given I am logged in as "@data(andre.mail)"
    Given applications:
      | title       | id          | org_id | state     | applicationType |
      | DevApp_@now | dev123_@now | 123456 | published | DEVELOPMENT     |
    And I publish an api with the name "api1_@now" and id "api1_@now"
    And I publish a product with the name "product1_@now", id "product1_@now", apis "api1_@now" and visibility "pub" true true
    And I am on "/product/product1_@now"
    When I click "Select"
    Then I should see the text "Select application"
    And I should not see any lifecycle state for "DevApp_@now"
    And there are no errors

  Scenario: Terms of Service is displayed in subscription flow when enabled
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                          | name                          | id     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | 123456 | @data(andre.mail) |
    Given I am logged in as "@data(andre.mail)"
    And I create an application named "myapp_@now" id "myid_@now" consumerorgurl "/consumer-orgs/1234/5678/123456"
    And I publish an api with the name "api1_@now" and id "api1_@now"
    And I publish a product with the name "product1_@now", id "product1_@now", apis "api1_@now" and visibility "pub" true true with terms of service
    And I am on "/product/product1_@now"
    When I click "Select"
    And I press the "myapp_@now" button
    Then I should see the text "Confirm Subscription"
    And I should see the text "Terms of Service"
    And there are no errors

  Scenario: Terms of Service checkbox validation blocks subscription
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                          | name                          | id     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | 123456 | @data(andre.mail) |
    Given I am logged in as "@data(andre.mail)"
    And I create an application named "myapp_@now" id "myid_@now" consumerorgurl "/consumer-orgs/1234/5678/123456"
    And I publish an api with the name "api1_@now" and id "api1_@now"
    And I publish a product with the name "product1_@now", id "product1_@now", apis "api1_@now" and visibility "pub" true true with terms of service
    And I am on "/product/product1_@now"
    When I click "Select"
    And I press the "myapp_@now" button
    Then I should see the text "Terms of Service"
    When I press the "Next" button
    And I should not see the text "Subscription Complete"

  Scenario: Terms of Service subscription completes when checkbox is checked
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                          | name                          | id     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | 123456 | @data(andre.mail) |
    Given I am logged in as "@data(andre.mail)"
    And I create an application named "myapp_@now" id "myid_@now" consumerorgurl "/consumer-orgs/1234/5678/123456"
    And I publish an api with the name "api1_@now" and id "api1_@now"
    And I publish a product with the name "product1_@now", id "product1_@now", apis "api1_@now" and visibility "pub" true true with terms of service
    And I am on "/product/product1_@now"
    When I click "Select"
    And I press the "myapp_@now" button
    Then I should see the text "Terms of Service"
    When I check "tos_accepted"
    And I press the "Next" button
    Then I should see the text "Subscription Complete"
    And I should see the link "product1_@now"
    And there are no errors

  Scenario: Terms of Service is not displayed when disabled in configuration
    Given the ibm_apim configuration "show_subscription_tos" is set to FALSE
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                          | name                          | id     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | 123456 | @data(andre.mail) |
    Given I am logged in as "@data(andre.mail)"
    And I create an application named "myapp_@now" id "myid_@now" consumerorgurl "/consumer-orgs/1234/5678/123456"
    And I publish an api with the name "api1_@now" and id "api1_@now"
    And I publish a product with the name "product1_@now", id "product1_@now", apis "api1_@now" and visibility "pub" true true with terms of service
    And I am on "/product/product1_@now"
    When I click "Select"
    And I press the "myapp_@now" button
    Then I should see the text "Confirm Subscription"
    And I should not see the text "Terms of Service"
    When I press the "Next" button
    Then I should see the text "Subscription Complete"
    And there are no errors

  Scenario: Terms of Service is not displayed when product has no ToS
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                          | name                          | id     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | 123456 | @data(andre.mail) |
    Given I am logged in as "@data(andre.mail)"
    And I create an application named "myapp_@now" id "myid_@now" consumerorgurl "/consumer-orgs/1234/5678/123456"
    And I publish an api with the name "api1_@now" and id "api1_@now"
    And I publish a product with the name "product1_@now", id "product1_@now", apis "api1_@now" and visibility "pub" true true
    And I am on "/product/product1_@now"
    When I click "Select"
    And I press the "myapp_@now" button
    Then I should see the text "Confirm Subscription"
    And I should not see the text "Terms of Service"
    When I press the "Next" button
    Then I should see the text "Subscription Complete"
    And there are no errors
