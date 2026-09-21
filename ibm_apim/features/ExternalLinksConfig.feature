@api
@external_links_config
Feature: External Links Configuration
  In order to properly configure external links
  As a site administrator
  I need to ensure that external links are configured correctly

  Scenario: Verify External Links Configuration
    Given users:
        | name         | mail               | roles         | status |
        | Admin User   | joe@example.com    | administrator | 1      |
    And I am logged in as "Admin User"
    And I have the "administer site configuration" permission
    And I have the "administer extlink" permission

    When I go to "/admin/config/user-interface/extlink"
    Then I should see the text "External Links"

    And the "extlink_css_exclude" field should include ".registry-button"
