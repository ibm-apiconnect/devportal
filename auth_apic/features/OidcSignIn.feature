Feature: Sign in via an OIDC provider
  In order to use the developer portal
  I need to be able to Sign in via an oidc provider.
  This tests the controller that receives the redirect from
  the authorization provider via apim.

  Scenario: Valid sign in using oidc provider.
    Given I am not logged in
    And I am at "/ibm_apim/oauth2/redirect?code=validauthcode&state=validstate"
    Then there are no errors
    And there are no messages
    Then I should be on "/"

  Scenario: Invalid - no authcode in params
    Given I am not logged in
    And I am at "/ibm_apim/oauth2/redirect?state=validstate"
    Then there are errors
    And I should see the text "Error: Missing authorization code parameter. Contact your system administrator."
    And I should be on "/"

  Scenario: Invalid - no authcode in params
    Given I am not logged in
    And I am at "/ibm_apim/oauth2/redirect?code=validauthcode"
    Then there are errors
    And I should see the text "Error: Missing state parameter. Contact your system administrator."
    And I should be on "/"

#  Scenario: Sign in successful but not in a consumer org - onboarding enabled
#    Given I am not logged in
#    And I am not in a consumer org # TODO
#    And onboarding is enabled
#    And I am at "/ibm_apim/oauth2/redirect?code=validauthcode&state=validstate"
#    Then I should be on "/myorg/create"

#  Scenario: Sign in successful but not in a consumer org - onboarding disabled
#    Given I am not logged in
#    And I am not in a consumer org # TODO
#    And onboarding is disabled
#    And I am at "/ibm_apim/oauth2/redirect?code=validauthcode&state=validstate"
#    Then I should be on "/ibm_apim/nopermission"

 Scenario: Invalid - Error from apim login call with message
   Given I am not logged in
   And I am at "/ibm_apim/oauth2/redirect?code=failwithmessage&state=validstate"
   Then there are errors
   And I should see the text "Mocked error message from apim login() call"
   And there are no messages
   Then I should be on "/"

  Scenario: Invalid - Error from apim login call default message
    Given I am not logged in
    And I am at "/ibm_apim/oauth2/redirect?code=fail&state=validstate"
    Then there are errors
    And I should see the text "Error while authenticating user. Please contact your system administrator."
    And there are no messages
    Then I should be on "/"
