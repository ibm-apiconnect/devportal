@api
Feature: System Settings
  In order to use the developer portal
  I need to be configure system settings

  Scenario: System Settings - Proxy settings
    Given I am logged in as a user with the "Administrator" role
    And restore ibm_apim settings default values
    And I am at "/admin/config/system/ibm_apim"
    Then I should see the text "Configure the IBM API Developer Portal"
    And I should see the text "Proxy Configuration"
    And I should see the text "Enable Proxy Support"
    And I should see the text "If enabled, use the Proxy for Consumer, Platform or Analytics APIs"
    And I should see the text "Proxy type"
    And I should see the text "Proxy URL"
    And I should see the text "Proxy Authentication"
    When I check the box "Enable Proxy Support"
    And I enter "http://proxyserver.domain.com" for "Proxy URL"
    And I enter "username:password" for "Proxy Authentication"
    When I press the "Save configuration" button
    Then the "use_proxy" checkbox should be checked
    And  ibm_apim settings config property "proxy_for_api" value should be "CONSUMER,PLATFORM,ANALYTICS"
    And  ibm_apim settings config property "proxy_type" value should be "CURLPROXY_HTTP"
    And  ibm_apim settings config property "proxy_url" value should be "http://proxyserver.domain.com"
    And  ibm_apim settings config property "proxy_auth" value should be "username:password"
    And there are no warnings
    And there are no errors
    Then restore ibm_apim settings default values
