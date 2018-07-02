Feature: ApplicationACL
  In order to use the developer portal
  I need to be able to access Applications I should be allowed to access

  @acl
  @api
  Scenario: Create Applications as a specific user and test that they can be accessed
# Hard coding these for now.  For full stack testing, substitute with real users/consumerorgs
# in the And clause below
    Given users:
      | name      | mail                  | pass     | status |
      | andre_one | andre_one@example.com | Qwert123 | 1      |
      | andre_two | andre_two@example.com | Qwert123 | 1      |
    Given consumerorgs:
      | title     | name      | id                       | owner     | tags    |
      | a1_consumerorg | a1-consumerorg | a18843f3e4b07631568a159d | andre_one | testers |
      | a2_consumerorg | a2-consumerorg | a28843f3e4b07631568a159d | andre_two | others  |
    Given I am logged in as "andre_one"
    Then I should not see the text "Unrecognized username or password"
    And I should not see the text "Log in"
    Given I create an application named "app1_@now" id "app1Id_@now" consumerorgurl "/consumer-orgs/1234/5678/a18843f3e4b07631568a159d"
    Then I should have an application named "app1_@now" id "app1Id_@now"
    And The application with the name "app1_@now" and id "app1Id_@now" should not be visible to "andre_two"
