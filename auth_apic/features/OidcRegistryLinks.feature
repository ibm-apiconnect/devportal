@api
Feature: OIDC Registry Links
  In order to ensure proper user experience
  As a developer
  I need to verify that OIDC registry links are well-formed

  Scenario: OIDC registry links should not have target="_blank" and should have registry-button class
    Given I am not logged in
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | oidc | @data(user_registries[3].title) | @data(user_registries[3].url) | no           | yes     |
    When I am at "/user/login"
    Then I should see the link "@data(user_registries[3].title)"
    And I should see a link with href including "/consumer-api/oauth2/authorize"
    And the link "@data(user_registries[3].title)" should not have target="_blank"
    And the link "@data(user_registries[3].title)" should have the "registry-button" class
    And the link "@data(user_registries[3].title)" should not have the "ext" class

  Scenario: OIDC authorize URL is well-formed when language URL query parameter negotiation is enabled on the login page
    Given I am not logged in
    And language URL query parameter negotiation is enabled
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | oidc | @data(user_registries[3].title) | @data(user_registries[3].url) | no           | yes     |
    When I am at "/user/login"
    Then I should see a link with href including "/consumer-api/oauth2/authorize"
    And I should not see a link with href matching "authorize\?language=[^?]+\?"
    And there are no errors
    And there are no warnings
    Given language URL query parameter negotiation is disabled

  Scenario: OIDC authorize URL contains correct query parameters when language negotiation is active
    Given I am not logged in
    And language URL query parameter negotiation is enabled
    Given userregistries:
      | type | title                           | url                           | user_managed | default |
      | oidc | @data(user_registries[3].title) | @data(user_registries[3].url) | no           | yes     |
    When I am at "/user/login"
    Then I should see a link with href matching "oauth2/authorize\?client_id="
    And I should see a link with href matching "redirect_uri="
    And I should see a link with href matching "response_type=code"
    And there are no errors
    And there are no warnings
    Given language URL query parameter negotiation is disabled
