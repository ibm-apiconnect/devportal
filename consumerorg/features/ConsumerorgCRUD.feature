@api
Feature: ConsumerorgCRUD
  In order to use the developer portal
  I need to be able to use consumerorgs

  Scenario: Content type exists
    Given I am at "/"
    Then The "consumerorg" content type is present

  Scenario: Create consumerorg with metadata serialization
    Given I create a consumerorg with metadata
