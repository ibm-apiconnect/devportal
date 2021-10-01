Feature: Sign in/ Sign up via an OIDC provider
  In order to use the developer portal
  I need to be able to Sign in via an oidc provider.
  This tests the controller that receives the redirect from
  the authorization provider via apim.

  Scenario: Valid sign in using oidc provider.
    Given I am not logged in
    And I am at "/ibm_apim/oauth2/redirect?code=validauthcode&state=czozOiJrZXkiOw=="
    Then I should be on "/"
    And there are no errors
    And there are no warnings
    And there are no messages

  # Scenario: Invalid - no authcode in params
  #   Given I am not logged in
  #   And I am at "/ibm_apim/oauth2/redirect?state=czozOiJrZXkiOw=="
  #   Then I should be on "/"
  #   And there are errors
  #   And I should see the text "Error: Missing authorization code parameter. Contact your system administrator."
  #   And there are no warnings
  #   And there are no messages

  # Scenario: Invalid - no state in params
  #   Given I am not logged in
  #   And I am at "/ibm_apim/oauth2/redirect?code=YToxOntzOjEyOiJyZWdpc3RyeV91cmwiO3M6NToidmFsaWQiO30="
  #   Then I should be on "/"
  #   And there are errors
  #   And I should see the text "Error: Missing state parameter. Contact your system administrator."
  #   And there are no warnings
  #   And there are no messages

  # Scenario: Invalid - Incorrect state in params
  #   Given I am not logged in
  #   And I am at "/ibm_apim/oauth2/redirect?code=validauthcode&state=czo4OiJ3cm9uZ0tleSI7"
  #   Then I should be on "/"
  #   And there are errors
  #   And I should see the text "Error while authenticating user. Please contact your system administrator."
  #   And there are no warnings
  #   And there are no messages

  # Scenario: Invalid - Error in params
  #   Given I am not logged in
  #   And I am at "/ibm_apim/oauth2/redirect?error=We%20got%20an%20error"
  #   Then I should be on "/"
  #   And there are errors
  #   And I should see the text "We got an error"
  #   And there are no warnings
  #   And there are no messages


  # Scenario: Valid sign in using oidc provider and portal as proxy.
  #   Given I am not logged in
  #   And I am at "/ibm_apim/oauth2/authorize?client_id=clientId&state=czozOiJrZXkiOw==&redirect_uri=/ibm_apim/oauth2/redirect&realm=consumer:orgId:envId/trueRealm&response_type=code&action=signin"
  #   Then I should be on "/"
  #   And there are no errors
  #   And there are no warnings
  #   And there are no messages

  # Scenario: Portal as redirect - Missing Client ID
  #   Given I am not logged in
  #   And I am at "/ibm_apim/oauth2/authorize?state=czozOiJrZXkiOw==&redirect_uri=/ibm_apim/oauth2/redirect&realm=consumer:orgId:envId/trueRealm&response_type=code"
  #   Then I should be on "/"
  #   And there are errors
  #   And I should see the text "Missing Client ID parameter. Contact your system administrator."
  #   And there are no warnings
  #   And there are no messages

  # Scenario: Portal as redirect - Missing State
  #   Given I am not logged in
  #   And I am at "/ibm_apim/oauth2/authorize?client_id=clientId&redirect_uri=/ibm_apim/oauth2/redirect&realm=consumer:orgId:envId/trueRealm&response_type=code"
  #   Then I should be on "/"
  #   And there are errors
  #   And I should see the text "Missing state parameter. Contact your system administrator."
  #   And there are no warnings
  #   And there are no messages

  # Scenario: Portal as redirect - Missing Redirect
  #   Given I am not logged in
  #   And I am at "/ibm_apim/oauth2/authorize?client_id=clientId&state=czozOiJrZXkiOw==&realm=consumer:orgId:envId/trueRealm&response_type=code"
  #   Then I should be on "/"
  #   And there are errors
  #   And I should see the text "Missing redirect uri parameter. Contact your system administrator."
  #   And there are no warnings
  #   And there are no messages

  # Scenario: Portal as redirect - Missing Realm
  #   Given I am not logged in
  #   And I am at "/ibm_apim/oauth2/authorize?client_id=clientId&state=czozOiJrZXkiOw==&redirect_uri=/ibm_apim/oauth2/redirect&response_type=code"
  #   Then I should be on "/"
  #   And there are errors
  #   And I should see the text "Missing realm parameter. Contact your system administrator."
  #   And there are no warnings
  #   And there are no messages

  # Scenario: Portal as redirect - Missing Response code
  #   Given I am not logged in
  #   And I am at "/ibm_apim/oauth2/authorize?client_id=clientId&state=czozOiJrZXkiOw==&redirect_uri=/ibm_apim/oauth2/redirect&realm=consumer:orgId:envId/trueRealm"
  #   Then I should be on "/"
  #   And there are errors
  #   And I should see the text "Error: Missing or incorrect response type parameter. Contact your system administrator."
  #   And there are no warnings
  #   And there are no messages

  # Scenario: Portal as redirect - Wrong Client ID
  #   Given I am not logged in
  #   And I am at "/ibm_apim/oauth2/authorize?client_id=wrongclientId&state=czozOiJrZXkiOw==&redirect_uri=/ibm_apim/oauth2/redirect&realm=consumer:orgId:envId/trueRealm&response_type=code"
  #   Then I should be on "/"
  #   And there are errors
  #   And I should see the text "Invalid client_id parameter. Contact your system administrator."
  #   And there are no warnings
  #   And there are no messages

  # Scenario: Portal as redirect - Invalid redirect uri
  #   Given I am not logged in
  #   And I am at "/ibm_apim/oauth2/authorize?client_id=clientId&state=czozOiJrZXkiOw==&redirect_uri=www.fail.com/ibm_apim/oauth2/redirect&realm=consumer:orgId:envId/trueRealm&response_type=code"
  #   Then I should be on "/"
  #   And there are errors
  #   And I should see the text "Error while authenticating user. Please contact your system administrator."
  #   And there are no warnings
  #   And there are no messages

  # Scenario: Portal as redirect -  Incorrect realm
  #   Given I am not logged in
  #   And I am at "/ibm_apim/oauth2/authorize?client_id=clientId&state=czozOiJrZXkiOw==&redirect_uri=/ibm_apim/oauth2/redirect&realm=wrongconsumer:orgId:envId/trueRealm&response_type=code"
  #   Then I should be on "/"
  #   And there are errors
  #   And I should see the text "Error while authenticating user. Please contact your system administrator."
  #   And there are no warnings
  #   And there are no messages

  @api
  Scenario: Sign in successful but not in a consumer org - onboarding enabled
    Given I am not logged in
    Given users:
      | name      | mail                  | pass     | status |
      | oidcandre | oidcandre@example.com | oidcoidc | 1      |
    And I am at "/ibm_apim/oauth2/redirect?code=noorgenabledonboarding&state=czozOiJrZXkiOw=="
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
    And I am at "/ibm_apim/oauth2/redirect?code=noorgdisabledonboarding&state=czozOiJrZXkiOw=="
    Then I should be on "/ibm_apim/nopermission"
    And there are no errors
    And there are no warnings
    And there are no messages

  # Scenario: Sign in - generic error
  #   Given I am not logged in
  #   And I am at "/ibm_apim/oauth2/redirect?code=routetoerror&state=czozOiJrZXkiOw=="
  #   Then I should be on "/"
  #   And there are errors
  #   And I should see the text "Error while authenticating user. Please contact your system administrator."
  #   And there are no warnings
  #   And there are no messages

  # Scenario: Invalid - Error from apim login call default message
  #   Given I am not logged in
  #   And I am at "/ibm_apim/oauth2/redirect?code=fail&state=czozOiJrZXkiOw=="
  #   Then there are errors
  #   And I should see the text "Error while authenticating user. Please contact your system administrator."
  #   And there are no messages
  #   Then I should be on "/"

  @api
  Scenario: First time sign in
    Given I am not logged in
    Given users:
      | name      | mail                  | pass     | status | first_time_login |
      | oidcandre | oidcandre@example.com | oidcoidc | 1      | 1                |
    Given consumerorgs:
      | title                          | name                          | id                          | owner     |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | oidcandre |
    And I am at "/ibm_apim/oauth2/redirect?code=firsttimelogin&state=czozOiJrZXkiOw=="
    Then I should be on "/start"
    And I should see the text "let's get started"
    And I should see the text "Explore API products"
    And I should see the text "Create a new app"
    And I should see the link "Take me to the homepage"
    Then there are no errors
    And there are no messages
    And there are no warnings

