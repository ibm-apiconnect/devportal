@api
@entity_autocomplete_access
Feature: Entity Autocomplete Access Restrictions
  In order to maintain security of the developer portal
  As a site administrator
  I need to ensure that the entity autocomplete endpoint is only accessible to administrators

  Scenario: Admin gets 200; regular and anonymous get 403 on the same autocomplete path
    Given users:
        | name         | mail               | roles         | status |
        | Admin User   | joe@example.com    | administrator | 1      |
        | Regular User | sam@example.com    |               | 1      |
    And I am logged in as "Admin User"
    And I have the "view authmap" permission
    And I have the "administer site configuration" permission

    When I go to "/admin/people/authmap"
    Then I should see "External authentication links"

    And I capture the autocomplete path from the "uid" field
    Then the autocomplete path should be captured

    # Admin request with query → expect 200
    When I request the captured autocomplete path with query "q=usr" without following redirects
    Then the response status code should be 200
    And the response should be in JSON format

    # Anonymous user -> expect 403
    And I am logged out
    Given I am on "/"
    When I request the captured autocomplete path with query "q=usr" without following redirects
    Then the response status code should be 403

    # Regular user -> expect 302
    And I am logged in as "Regular User"
    Given I am on "/"
    When I request the captured autocomplete path with query "q=usr" without following redirects
    Then the response status code should be 302
