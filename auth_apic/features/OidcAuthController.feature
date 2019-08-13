Feature: Sign in/ Sign up via an OIDC provider
  In order to use the developer portal
  I need to be able to Sign in via an oidc provider.
  This tests the controller that receives the redirect from
  the authorization provider via apim.

  Scenario: Valid sign in using oidc provider.
    Given I am not logged in
    And I am at "/ibm_apim/oauth2/redirect?code=validauthcode&state=YToxOntzOjEyOiJyZWdpc3RyeV91cmwiO3M6NToidmFsaWQiO30="
    Then I should be on "/"
    And there are no errors
    And there are no warnings
    And there are no messages

  Scenario: Invalid - no authcode in params
    Given I am not logged in
    And I am at "/ibm_apim/oauth2/redirect?state=YToxOntzOjEyOiJyZWdpc3RyeV91cmwiO3M6NToidmFsaWQiO30="
    Then I should be on "/"
    And there are errors
    And I should see the text "Error: Missing authorization code parameter. Contact your system administrator."
    And there are no warnings
    And there are no messages

  Scenario: Invalid - no state in params
    Given I am not logged in
    And I am at "/ibm_apim/oauth2/redirect?code=YToxOntzOjEyOiJyZWdpc3RyeV91cmwiO3M6NToidmFsaWQiO30="
    Then I should be on "/"
    And there are errors
    And I should see the text "Error: Missing state parameter. Contact your system administrator."
    And there are no warnings
    And there are no messages

  @api
  Scenario: Sign in successful but not in a consumer org - onboarding enabled
    Given I am not logged in
    Given users:
      | name      | mail                  | pass     | status |
      | oidcandre | oidcandre@example.com | oidcoidc | 1      |
    And I am at "/ibm_apim/oauth2/redirect?code=noorgenabledonboarding&state=YToxOntzOjEyOiJyZWdpc3RyeV91cmwiO3M6NToidmFsaWQiO30="
    Then I should be on "/myorg/create"
    And there are no errors
    And there are no warnings
    And there are no messages

  @api
  Scenario: Sign in successful but not in a consumer org - onboarding disabled
    Given I am not logged in
    Given users:
      | name      | mail                  | pass     | status |
      | oidcandre | oidcandre@example.com | oidcoidc | 1      |
    And I am at "/ibm_apim/oauth2/redirect?code=noorgdisabledonboarding&state=YToxOntzOjEyOiJyZWdpc3RyeV91cmwiO3M6NToidmFsaWQiO30="
    Then I should be on "/ibm_apim/nopermission"
    And there are no errors
    And there are no warnings
    And there are no messages

  Scenario: Sign in - generic error
    Given I am not logged in
    And I am at "/ibm_apim/oauth2/redirect?code=routetoerror&state=YToxOntzOjEyOiJyZWdpc3RyeV91cmwiO3M6NToidmFsaWQiO30="
    Then I should be on "/"
    And there are errors
    And I should see the text "Error while authenticating user. Please contact your system administrator."
    And there are no warnings
    And there are no messages

  Scenario: Invalid - Error from apim login call default message
    Given I am not logged in
    And I am at "/ibm_apim/oauth2/redirect?code=fail&state=YToxOntzOjEyOiJyZWdpc3RyeV91cmwiO3M6NToidmFsaWQiO30="
    Then there are errors
    And I should see the text "Error while authenticating user. Please contact your system administrator."
    And there are no messages
    Then I should be on "/"
