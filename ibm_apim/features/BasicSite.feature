Feature: Basic site verification
  In order to use the developer portal I need to verify that the site has been created and is ready to use.

  @fullstack
  Scenario: Verify site is loaded with no errors
    Given I am not logged in
    And I am at "/"
    Then there are no errors
    And there are no messages
