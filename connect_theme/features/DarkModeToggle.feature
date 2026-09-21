Feature: Dark Mode Toggle
  As a site administrator
  I want to control the dark mode toggle visibility
  So that users can switch between light and dark themes when enabled

  @api
  Scenario: Dark mode toggle is visible when enabled in admin config
    Given I am logged in as a user with the "administrator" role
    When I am at "/admin/config/system/ibm_apim"
    And I check the box "Enable dark mode toggle"
    And I press "Save configuration"
    When I am on the homepage
    Then I should see the dark mode toggle button in the navbar

  @api
  Scenario: Dark mode toggle is hidden when disabled in admin config
    Given I am logged in as a user with the "administrator" role
    When I am at "/admin/config/system/ibm_apim"
    And I uncheck the box "Enable dark mode toggle"
    And I press "Save configuration"
    When I am on the homepage
    Then I should not see the dark mode toggle button in the navbar

  @api
  Scenario: Dark mode toggle is enabled by default for new installations
    Given the "enable_dark_mode" config does not exist
    When I am on the homepage
    Then I should see the dark mode toggle button in the navbar