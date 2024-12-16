@api
Feature: My Organization Billing
  In order to use the developer portal
  I need to be able to view my organization billing page

  Scenario: Sign in as an organization owner and view my organization billing page with no errors
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
    Given I have an analytics service
    And I have a billing service
    And I am logged in as "@data(andre.mail)"
    When I am at "/myorg/billing"
    Then there are no errors
    And there are no warnings
    And there are no messages

  Scenario: I can add a payment method
    Given I have an analytics service
    Given I have a billing service
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
    Given I am logged in as "@data(andre.mail)"
    And I am at "/myorg/addpayment"
    Then I should see the text "Add Payment Method"
    And I should see the text "Payment Method Title"
    And I should see the text "owner_email"
    When I enter "MyPaymentMethod" for "title"
    And I enter "@data(andre.mail)" for "owner_email"
    When I press the "Save" button
    Then I should be on "/myorg/billing"
    And I should see the text "Successfully added your payment method"
    And there are no errors
    And there are no warnings

  Scenario: I can see my payment methods
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
    Given I have an analytics service
    And I have a billing service
    And I am logged in as "@data(andre.mail)"
    And I have a payment method
    When I am at "/myorg/billing"
    Then I should see the text "paymentMethod"
    And I should see the text "owner_email: andre@example.com"
    And I should see the text "owner_name: Andre"
    And there are no errors
    And there are no warnings
    And there are no messages

  # @api
  # Scenario: I can delete a payment method
  #   Given users:
  #     | name              | mail              | pass                  | status |
  #     | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
  #   Given consumerorgs:
  #     | title                     | name                     | id                     | owner             |
  #     | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
  #   Given I have an analytics service
  #   And I have a billing service
  #   And I am logged in as "@data(andre.mail)"
  #   And I have a payment method
  #   When I am at "/myorg/billing"
  #   Then I should see the text "paymentMethod"
  #   When I click on element "a.trigger"
  #   Then I click "Delete payment method"
  #   Then I press the "Delete" Button
  #   Then I should be on "/myorg/billing"
  #   And I should not see the text "paymentMethod"
  #   And I should see the text "Payment method deleted"
  #   And there are no errors
  #   And there are no warnings