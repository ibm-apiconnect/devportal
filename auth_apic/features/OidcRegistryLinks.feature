@api
Feature: OIDC Registry Links
  In order to ensure proper user experience
  As a developer
  I need to verify that OIDC registry links don't open in new tabs

  Scenario: OIDC registry links should not have target="_blank"
    Given I am not logged in
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | oidc | @data(user_registries[3].title) | @data(user_registries[3].url) | no           | yes     |
    When I am at "/user/login"
    Then I should see the link "@data(user_registries[3].title)"
    And I should see a link with href including "/consumer-api/oauth2/authorize"
    And the link "@data(user_registries[3].title)" should not have target="_blank"