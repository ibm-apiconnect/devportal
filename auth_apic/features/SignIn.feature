Feature: Sign in
  In order to use the developer portal
  I need to be able to Sign in

  Scenario: View the sign in page - lur with admin not hidden (default case)
    Given I am not logged in
    And ibm_apim settings config boolean property "hide_admin_registry" value is "false"
    Given userregistries:
      | type | title                             | url                               | user_managed | default |
      | lur  | @data(user_registries[0].title)   | @data(user_registries[0].url)     | yes          | yes     |
    When I am at "/user/login"
    Then I should see the text "Sign in"
    And I should see the text "Create new account"
    And I should see the text "Reset your password"
    And I should see the text "Username"
    And I should see the text "Password"
    And I should see the text "Continue with"
    And I should see the link "admin"
    And I should see the link "Forgot password?"
    And I should see the text "Don't have an account?"
    And I should see the link "Sign up"
    And there are no messages
    And there are no warnings
    And there are no errors

  Scenario: View the sign in page - lur with admin hidden (single registry)
    Given I am not logged in
    And ibm_apim settings config boolean property "hide_admin_registry" value is "true"
    Given userregistries:
      | type | title                             | url                               | user_managed | default |
      | lur  | @data(user_registries[0].title)   | @data(user_registries[0].url)     | yes          | yes     |
    And I am at "/user/login"
    Then I should see the text "Sign in"
    And I should see the text "Create new account"
    And I should see the text "Reset your password"
    And I should see the text "Username"
    And I should see the text "Password"
    And I should not see the text "Continue with"
    And I should not see the link "admin"
    And I should see the link "Forgot password?"
    And I should see the text "Don't have an account?"
    And I should see the link "Sign up"
    And there are no messages
    And there are no warnings
    And there are no errors

  #LDAP tests - no link to sign up or forgot password
  Scenario: View the sign in page - ldap with admin not hidden
    Given I am not logged in
    And ibm_apim settings config boolean property "hide_admin_registry" value is "false"
    Given userregistries:
      | type | title                             | url                               | user_managed | default |
      | ldap  | @data(user_registries[2].title)   | @data(user_registries[2].url)     | no          | yes     |
    And I am at "/user/login"
    Then I should see the text "Sign in"
    And I should see the text "Create new account"
    And I should see the text "Reset your password"
    And I should see the text "Username"
    And I should see the text "Password"
    And I should see the text "Continue with"
    And I should see the link "admin"
    And I should not see the link "Forgot password?"
    And I should see the link "Forgot your 'admin' password?"
    And I should not see the text "Don't have an account?"
    And I should not see the link "Sign up"
    And there are no messages
    And there are no warnings
    And there are no errors

  Scenario: View the sign in page - ldap with admin hidden
    Given I am not logged in
    And ibm_apim settings config boolean property "hide_admin_registry" value is "true"
    Given userregistries:
      | type | title                             | url                               | user_managed | default |
      | ldap | @data(user_registries[2].title)   | @data(user_registries[2].url)     | no          | yes     |
    And I am at "/user/login"
    Then I should see the text "Sign in"
    And I should see the text "Create new account"
    And I should see the text "Reset your password"
    And I should see the text "Username"
    And I should see the text "Password"
    And I should not see the text "Continue with"
    And I should not see the link "admin"
    And I should not see the link "Forgot password?"
    And I should not see the link "Forgot your 'admin' password?"
    And I should not see the text "Don't have an account?"
    And I should not see the link "Sign up"
    And there are no messages
    And there are no warnings
    And there are no errors


  #OIDC - just link - no fields
  Scenario: View the sign in page - oidc with admin not hidden
    Given I am not logged in
    And ibm_apim settings config boolean property "hide_admin_registry" value is "false"
    Given userregistries:
      | type | title                             | url                               | user_managed | default |
      | oidc | @data(user_registries[3].title)   | @data(user_registries[3].url)     | no           | yes     |
    And I am at "/user/login"
    Then I should see the text "Sign in"
    And I should see the link "@data(user_registries[3].title)"
    And I should see a link with href including "/consumer-api/oauth2/authorize"
    And I should not see the text "Username"
    #And I should not see the text "Password"
    And I should see the text "Continue with"
    And I should see the link "admin"
    And I should not see the link "Forgot password?"
    And I should see the link "Forgot your 'admin' password?"
    And I should not see the text "Don't have an account?"
    And I should not see the link "Sign up"
    And there are no messages
    And there are no warnings
    And there are no errors

  Scenario: View the sign in page - oidc with admin hidden (common case on public internet facing sites)
    Given I am not logged in
    And ibm_apim settings config boolean property "hide_admin_registry" value is "true"
    Given userregistries:
      | type | title                             | url                               | user_managed | default |
      | oidc | @data(user_registries[3].title)   | @data(user_registries[3].url)     | no           | yes     |
    And I am at "/user/login"
    Then I should see the text "Sign in"
    And I should see the link "@data(user_registries[3].title)"
    And I should see a link with href including "/consumer-api/oauth2/authorize"
    And I should not see the text "Username"
    #And I should not see the text "Password"
    And I should not see the text "Continue with"
    And I should not see the link "admin"
    And I should not see the link "Forgot password?"
    And I should not see the link "Forgot your 'admin' password?"
    And I should not see the text "Don't have an account?"
    And I should not see the link "Sign up"
    And there are no messages
    And there are no warnings
    And there are no errors

  @api
  Scenario: Sign in as a non-admin user
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status | registry_url                  |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      | @data(user_registries[0].url) |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
    Given userregistries:
      | type | title                             | url                               | user_managed | default |
      | lur  | @data(user_registries[0].title)   | @data(user_registries[0].url)     | yes          | yes     |
    Given I am logged in as "@data(andre.mail)"
    Then I should not see the text "Unrecognized username or password"
    And I should not see the text "sign in"
    Given the apim user "@data(andre.mail)" is logged in
    Given I am at "/user/logout"
    Then I should see the text "Sign in"

@api
  Scenario: Attempt Sign in with incorrect password
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
    Given userregistries:
      | type | title                             | url                               | user_managed | default |
      | lur  | @data(user_registries[0].title)   | @data(user_registries[0].url)     | yes          | yes     |
    When I am at "/user/login"
    Given I enter "@data(andre.mail)" for "Username"
    And I enter "invalidPassword" for "Password"
    When I press the "Sign in" button
    Then there are errors
    And I should see the text "Forgot your password? Click here to reset it."
    And the apim user "@data(andre.mail)" is not logged in

@api
  Scenario: Sign in form loads with multiple user registries including admin
  Given the cache has been cleared
  Given I am not logged in
  And ibm_apim settings config boolean property "hide_admin_registry" value is "false"
  Given userregistries:
    | type | title                             | url                               | user_managed | default |
    | lur  | @data(user_registries[0].title)   | @data(user_registries[0].url)     | yes          | yes     |
    | ldap | @data(user_registries[2].title)   | @data(user_registries[2].url)     | no           | no      |
  When I am at "/user/login"
  Then I should see the text "Sign in with @data(user_registries[0].title)"
  And I should see "or" in the ".apic-user-form-or" element
  And I should see the text "or"
  And I should see the text "Continue with"
  And I should see the link "@data(user_registries[2].title)"
  And I should see the link "admin"
  And I should see the link "Forgot password?"
  And I should see the text "Don't have an account?"
  And I should see the link "Sign up"

  @api
  Scenario: Sign in form loads with multiple user registries without admin
    Given the cache has been cleared
    Given I am not logged in
    And ibm_apim settings config boolean property "hide_admin_registry" value is "true"
    Given userregistries:
      | type | title                             | url                               | user_managed | default |
      | lur  | @data(user_registries[0].title)   | @data(user_registries[0].url)     | yes          | yes     |
      | ldap | @data(user_registries[2].title)   | @data(user_registries[2].url)     | no           | no      |
    When I am at "/user/login"
    Then I should see the text "Sign in with @data(user_registries[0].title)"
    And I should see "or" in the ".apic-user-form-or" element
    And I should see the text "or"
    And I should see the text "Continue with"
    And I should see the link "@data(user_registries[2].title)"
    And I should not see the link "admin"
    And I should see the link "Forgot password?"
    And I should see the text "Don't have an account?"
    And I should see the link "Sign up"


@api
  Scenario: Login form changes user registry via link
  Given the cache has been cleared
  Given I am not logged in
  Given userregistries:
    | type | title                             | url                               | user_managed | default |
    | lur  | @data(user_registries[0].title)   | @data(user_registries[0].url)     | yes          | yes     |
    | ldap | @data(user_registries[2].title)   | @data(user_registries[2].url)     | no           | no      |
  When I am at "/user/login"
  Then I should see the text "Sign in with @data(user_registries[0].title)"
  And I should see the link "@data(user_registries[2].title)"
  When I click "@data(user_registries[2].title)"
  And I should see the text "Sign in with @data(user_registries[2].title)"
  And I should see the link "@data(user_registries[0].title)"

@api
Scenario: Login form loads non default registry directly
  Given I am not logged in
  Given userregistries:
    | type | title                             | url                               | user_managed | default |
    | lur  | @data(user_registries[0].title)   | @data(user_registries[0].url)     | yes          | yes     |
    | lur  | @data(user_registries[1].title)   | @data(user_registries[1].url)     | yes           | no      |
  When I am at "/user/login?registry_url=@data(user_registries[1].url)"
  Then I should see the text "Sign in with @data(user_registries[1].title)"

@api
  Scenario: Login form handles invalid user registry url query parameter by using default registry
  Given I am not logged in
  Given userregistries:
    | type | title                             | url                               | user_managed | default |
    | lur  | @data(user_registries[0].title)   | @data(user_registries[0].url)     | yes          | yes     |
    | ldap | @data(user_registries[2].title)   | @data(user_registries[2].url)     | no           | no      |
  When I am at "/user/login?registry_url=thisisnotvalid"
  Then I should see the text "Sign in with @data(user_registries[0].title)"

@api
Scenario: Login form handles valid but incorrect user registry url query parameter by using default registry
  Given I am not logged in
  Given userregistries:
    | type | title                             | url                               | user_managed | default |
    | lur  | @data(user_registries[0].title)   | @data(user_registries[0].url)     | yes          | yes     |
    | ldap | @data(user_registries[2].title)   | @data(user_registries[2].url)     | no           | no      |
  When I am at "/user/login?registry_url=/consumer-api/user-registries/aaaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee"
  Then I should see the text "Sign in with @data(user_registries[0].title)"

@api
Scenario: Login form loads with oidc registry link (non-default)
  Given the cache has been cleared
  Given I am not logged in
  Given userregistries:
    | type | title                             | url                               | user_managed | default |
    | lur  | @data(user_registries[0].title)   | @data(user_registries[0].url)     | yes          | yes     |
    | oidc | @data(user_registries[3].title)   | @data(user_registries[3].url)     | no           | no      |
  When I am at "/user/login"
  Then I should see the text "Sign in with @data(user_registries[0].title)"
  And I should see "or" in the ".apic-user-form-or" element
  And I should see the text "or"
  And I should see the text "Continue with"
  And I should see the link "@data(user_registries[3].title)"
  And I should see a link with href including "/consumer-api/oauth2/authorize"
  And I should see the link "Forgot password?"
  And I should see the text "Don't have an account?"
  And I should see the link "Sign up"

@api
Scenario: Login form loads with oidc registry link (default)
  Given the cache has been cleared
  Given I am not logged in
  Given userregistries:
    | type | title                             | url                               | user_managed | default |
    | lur  | @data(user_registries[0].title)   | @data(user_registries[0].url)     | yes          | no      |
    | oidc | @data(user_registries[3].title)   | @data(user_registries[3].url)     | no           | yes     |
  When I am at "/user/login"
  Then I should see the text "Sign in with @data(user_registries[3].title)"
  And I should see a link with href including "/consumer-api/oauth2/authorize"
  And I should not see the text "Sign in with @data(user_registries[0].title)"
  And I should not see the "Sign up" button
  And I should see "or" in the ".apic-user-form-or" element
  And I should see the text "or"
  And I should see the text "Continue with"
  And I should see the link "@data(user_registries[0].title)"
  And I should see the link "Forgot password?"
  And I should see the text "Don't have an account?"

@api
Scenario: Login form with admin - single user manager registry
  Given I am not logged in
  Given userregistries:
    | type | title                             | url                               | user_managed | default |
    | lur  | @data(user_registries[0].title)   | @data(user_registries[0].url)     | yes          | yes     |
  Given ibm_apim settings config boolean property "hide_admin_registry" value is "false"
  When I am at "/user/login"
  Then I should see the text "Sign in with @data(user_registries[0].title)"
  And I should see the text "Continue with"
  And I should see the link "admin"
  And I should see the text "Forgot password?"
  And I should see the text "Don't have an account?"
  And I should see the link "Sign up"

@api
Scenario: Login form with hidden admin - single user manager registry
  Given I am not logged in
  Given userregistries:
    | type | title                             | url                               | user_managed | default |
    | lur  | @data(user_registries[0].title)   | @data(user_registries[0].url)     | yes          | yes     |
  Given ibm_apim settings config boolean property "hide_admin_registry" value is "true"
  When I am at "/user/login"
  Then I should see the text "Sign in with @data(user_registries[0].title)"
  And I should not see the text "Continue with"
  And I should not see the link "admin"
  And I should see the text "Forgot password?"
  And I should see the text "Don't have an account?"
  And I should see the link "Sign up"

@api
Scenario: Login form with hidden admin - single non user manager registry
   Given I am not logged in
   Given userregistries:
     | type | title                             | url                               | user_managed | default |
     | ldap | @data(user_registries[2].title)   | @data(user_registries[2].url)     | no           | yes     |
   Given ibm_apim settings config boolean property "hide_admin_registry" value is "true"
   When I am at "/user/login"
   Then I should see the text "Sign in with @data(user_registries[2].title)"
   And I should not see the text "Continue with"
   And I should not see the link "admin"
   And I should not see the text "Forgot password?"
   And I should not see the text "Don't have an account?"
   And I should not see the link "Sign up"

@api
Scenario: Login form with hidden admin - multiple registries - user managed default
  Given I am not logged in
  Given userregistries:
    | type | title                             | url                               | user_managed | default |
    | lur  | @data(user_registries[0].title)   | @data(user_registries[0].url)     | yes          | yes     |
    | ldap | @data(user_registries[2].title)   | @data(user_registries[2].url)     | no           | no      |
  Given ibm_apim settings config boolean property "hide_admin_registry" value is "true"
  When I am at "/user/login"
  Then I should see the text "Sign in with @data(user_registries[0].title)"
  And I should see the text "Continue with"
  And I should see the link "@data(user_registries[2].title)"
  And I should not see the link "admin"
  And I should see the text "Forgot password?"
  And I should see the text "Don't have an account?"
  And I should see the link "Sign up"

@api
Scenario: Login form with hidden admin - multiple registries - non user managed default
  Given I am not logged in
  Given userregistries:
    | type | title                             | url                               | user_managed | default |
    | ldap | @data(user_registries[2].title)   | @data(user_registries[2].url)     | no           | yes     |
    | lur  | @data(user_registries[0].title)   | @data(user_registries[0].url)     | yes          | no      |
  Given ibm_apim settings config boolean property "hide_admin_registry" value is "true"
  When I am at "/user/login"
  Then I should see the text "Sign in with @data(user_registries[2].title)"
  And I should see the text "Continue with"
  And I should see the link "@data(user_registries[0].title)"
  And I should not see the link "admin"
  And I should see the text "Forgot password?"
  And I should see the text "Don't have an account?"
  And I should see the link "Sign up"

@api
Scenario: Login form with hidden admin - single oidc registry
  Given I am not logged in
  Given userregistries:
    | type | title                             | url                               | user_managed | default |
    | oidc | @data(user_registries[3].title)   | @data(user_registries[3].url)     | no           | yes     |
  Given ibm_apim settings config boolean property "hide_admin_registry" value is "true"
  When I am at "/user/login"
  Then I should see the text "Sign in with @data(user_registries[3].title)"
  And I should not see the text "Continue with"
  And I should not see the link "admin"
  And I should not see the text "Forgot password?"
  And I should not see the text "Don't have an account?"
  And I should not see the link "Sign up"

@api
Scenario: Login form with hidden admin - load admin form directly
  Given I am not logged in
  Given userregistries:
    | type | title                             | url                               | user_managed | default |
    | oidc | @data(user_registries[3].title)   | @data(user_registries[3].url)     | no           | yes     |
  Given ibm_apim settings config boolean property "hide_admin_registry" value is "true"
  When I am at "/user/login?registry_url=/admin"
  Then I should see the text "Sign in with admin"
  And I should see the text "Continue with"
  And I should see the link "@data(user_registries[3].title)"
  And I should see a link with href including "/consumer-api/oauth2/authorize"
  And I should not see the text "Forgot password?"
  And I should not see the text "Don't have an account?"
  And I should not see the link "Sign up"

@api
Scenario: Sign in with a user not in the database
  Given I am not logged in
  Given userregistries:
    | type | title                             | url                               | user_managed | default |
    | lur  | @data(user_registries[0].title)   | @data(user_registries[0].url)     | yes          | yes     |
  When I am at "/user/login"
  Given I enter "notindatabase" for "Username"
  And I enter "Qwert123" for "Password"
  When I press the "Sign in" button
  Then there are no errors

@api
Scenario: Sign in without a consumer org
  Given I am not logged in
  Given users:
    | name              | mail              | pass                  | status |
    | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      |
  When I am at "/user/login"
  Given I enter "@data(andre.mail)" for "Username"
  And I enter "@data(andre.password)" for "Password"
  When I press the "Sign in" button
  Then I should be on "/myorg/create"
  Then there are no errors
  And there are no messages
  And there are no warnings

@api
Scenario: Sign in for the first time
  Given I am not logged in
  Given users:
    | name              | mail              | pass                  | status | first_time_login |
    | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      | 1                |
  Given consumerorgs:
    | title                          | name                          | id                          | owner             |
    | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
  When I am at "/user/login"
  Given I enter "@data(andre.mail)" for "Username"
  And I enter "@data(andre.password)" for "Password"
  When I press the "Sign in" button
  Then I should be on "/start"
  And I should see the text "Get Started"
  And I should see the text "Explore API Products"
  And I should see the link "Start Exploring"
  And I should see the text "Create a new App"
  And I should see the link "Create an App"
  And I should see the link "Take me to the homepage"
  Then there are no errors
  And there are no messages
  And there are no warnings
  And the apim user "@data(andre.mail)" is logged in

@api
Scenario: Sign in users from different user registries with the same username
  Given I am not logged in
  Given userregistries:
    | type | title                             | url                               | user_managed | default |
    | lur  | @data(user_registries[0].title)   | @data(user_registries[0].url)     | yes          | yes     |
    | lur  | @data(user_registries[1].title)   | @data(user_registries[1].url)     | yes          | no      |
  Given users:
    | uid    | name       | mail                    | pass     | registry_url                  |
    | 123456 | portaluser | portaluser1@example.com | Qwert123 | @data(user_registries[0].url) |
    | 654321 | portaluser | portaluser2@example.com | Qwert246 | @data(user_registries[1].url) |
  Given consumerorgs:
    | title | name | id   | owner_uid |
    | org1  | org1 | org1 | 123456    |
    | org2  | org2 | org2 | 654321    |
  When I am at "/user/login"
  Given I enter "portaluser" for "Username"
  And I enter "Qwert123" for "Password"
  When I press the "Sign in" button
  # go to edit own profile page to check we are who we think we are
  When I am at "/user/123456/edit"
  Then there are no errors
  And there are no messages
  And there are no warnings
  # logout first user
  Then I am at "/user/logout"
  # login second user
  When I am at "/user/login?registry_url=@data(user_registries[1].url)"
  Given I enter "portaluser" for "Username"
  And I enter "Qwert246" for "Password"
  When I press the "Sign in" button
  # go to edit own profile page to check we are who we think we are
  When I am at "/user/654321/edit"
  Then there are no errors
  And there are no messages
  And there are no warnings


  #writableLDAP tests - should see link to sign up and forgot password
  Scenario: View the sign in form - writable ldap with admin not hidden (default case)
    Given I am not logged in
    And ibm_apim settings config boolean property "hide_admin_registry" value is "false"
    Given userregistries:
      | type | title                             | url                               | user_managed | default |
      | ldap | @data(user_registries[2].title)   | @data(user_registries[2].url)     | yes          | yes     |
    And I am at "/user/login"
    Then I should see the text "Sign in with @data(user_registries[2].title)"
    And I should see the text "Sign in"
    And I should see the text "Username"
    And I should see the text "Password"
    And I should see the text "Continue with"
    And I should see the link "admin"
    And I should see the link "Forgot password?"
    And I should see the text "Don't have an account?"
    And I should see the link "Sign up"
    And there are no messages
    And there are no warnings
    And there are no errors

  Scenario: View the sign in form - writable ldap with admin hidden (single registry)
    Given I am not logged in
    And ibm_apim settings config boolean property "hide_admin_registry" value is "true"
    Given userregistries:
      | type | title                             | url                               | user_managed | default |
      | ldap | @data(user_registries[2].title)   | @data(user_registries[2].url)     | yes          | yes     |
    And I am at "/user/login"
    Then I should see the text "Sign in with @data(user_registries[2].title)"
    Then I should see the text "Sign in"
    And I should see the text "Username"
    And I should see the text "Password"
    And I should not see the text "Continue with"
    And I should not see the link "admin"
    And I should see the link "Forgot password?"
    And I should see the text "Don't have an account?"
    And I should see the link "Sign up"
    And there are no messages
    And there are no warnings
    And there are no errors


  @api
  Scenario: Sign in form loads with writable ldap registry link (non-default)
    Given the cache has been cleared
    Given I am not logged in
    Given userregistries:
      | type | title                             | url                               | user_managed | default |
      | lur  | @data(user_registries[0].title)   | @data(user_registries[0].url)     | yes          | yes     |
      | ldap | @data(user_registries[2].title)   | @data(user_registries[2].url)     | yes          | no     |
    When I am at "/user/login"
    Then I should see the text "Sign in with @data(user_registries[0].title)"
    Then I should see the text "Sign in"
    And I should see the text "Username"
    And I should see the text "Password"
    And I should see "or" in the ".apic-user-form-or" element
    And I should see the text "or"
    And I should see the text "Continue with"
    And I should see the link "@data(user_registries[2].title)"
    Then I should see the button "Sign in"
    And I should see the link "Forgot password?"
    And I should see the text "Don't have an account?"
    And I should see the link "Sign up"
    And there are no messages
    And there are no warnings
    And there are no errors


  @api
  Scenario: Sign in form loads with writable ldap registry link (default)
    Given the cache has been cleared
    Given I am not logged in
    Given userregistries:
      | type | title                             | url                               | user_managed | default |
      | lur  | @data(user_registries[0].title)   | @data(user_registries[0].url)     | yes          | no     |
      | ldap | @data(user_registries[2].title)   | @data(user_registries[2].url)     | yes          | yes     |
    When I am at "/user/login"
    Then I should see the text "Sign in with @data(user_registries[2].title)"
    Then I should see the text "Sign in"
    And I should see the text "Username"
    And I should see the text "Password"
    And I should see "or" in the ".apic-user-form-or" element
    And I should see the text "or"
    And I should see the text "Continue with"
    And I should see the link "@data(user_registries[0].title)"
    Then I should see the button "Sign in"
    And I should see the link "Forgot password?"
    And I should see the text "Don't have an account?"
    And I should see the link "Sign up"
    And there are no messages
    And there are no warnings
    And there are no errors

#wLDAP sign in tests - note new user sign in authenticates with ldap db, creates a consumer
#org on the fly, & takes user to /start page. However, current test framework doesn't support this behaviour,
#rather it takes the user to /myorg/create form to create an organization. Test will need updating if framework changes.
  @api
  Scenario: Sign in for the first time without completing sign up form but already exists in a wLDAP registry
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status | first_time_login |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      | 1                |
    Given userregistries:
      | type | title                             | url                               | user_managed | default |
      | ldap | @data(user_registries[2].title)   | @data(user_registries[2].url)     | yes          | yes     |
    When I am at "/user/login"
    Then I should see the text "Sign in with @data(user_registries[2].title)"
    Given I enter "@data(andre.mail)" for "Username"
    And I enter "@data(andre.password)" for "Password"
    When I press the "Sign in" button
    Then I should be on "/myorg/create"
    Then there are no errors
    And there are no messages
    And there are no warnings


  @api
  Scenario: Sign in for the first time after completing a sign up form with a wLDAP registry
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status | first_time_login |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      | 1                |
    Given userregistries:
      | type | title                             | url                               | user_managed | default |
      | ldap | @data(user_registries[2].title)   | @data(user_registries[2].url)     | yes          | yes     |
    Given consumerorgs:
      | title                          | name                          | id                          | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.name) |
    When I am at "/user/login"
    Given I enter "@data(andre.mail)" for "Username"
    And I enter "@data(andre.password)" for "Password"
    When I press the "Sign in" button
    Then I should be on "/start"
    And I should see the text "Get Started"
    And I should see the text "Explore API Products"
    And I should see the link "Start Exploring"
    And I should see the text "Create a new App"
    And I should see the link "Create an App"
    And I should see the link "Take me to the homepage"
    Then there are no errors
    And there are no messages
    And there are no warnings
    And the apim user "@data(andre.mail)" is logged in


  @api
  Scenario: Sign in as a wLDAP user (not the first time)
    Given I am not logged in
    Given users:
      | name              | mail              | pass                  | status | first_time_login |
      | @data(andre.mail) | @data(andre.mail) | @data(andre.password) | 1      | 0                |
    Given consumerorgs:
      | title                     | name                     | id                     | owner             |
      | @data(andre.consumerorg.title) | @data(andre.consumerorg.name) | @data(andre.consumerorg.id) | @data(andre.mail) |
    Given userregistries:
      | type | title                             | url                               | user_managed | default |
      | ldap | @data(user_registries[2].title)   | @data(user_registries[2].url)     | yes          | yes     |
    When I am at "/user/login"
    Given I enter "@data(andre.mail)" for "Username"
    And I enter "@data(andre.password)" for "Password"
    When I press the "Sign in" button
#    Then I should be on the homepage - this is where the user should be, but current test framework doesn't support this
    Then I should be on "/start"
    And I should see the text "Get Started"
    And I should see the text "Explore API Products"
    And I should see the link "Start Exploring"
    And I should see the text "Create a new App"
    And I should see the link "Create an App"
    And I should see the link "Take me to the homepage"
    Then there are no errors
    And there are no messages
    And there are no warnings
    And the apim user "@data(andre.mail)" is logged in

